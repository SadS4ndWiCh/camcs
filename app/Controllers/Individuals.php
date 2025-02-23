<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\IndividualHasSpellsModel;
use App\Models\IndividualMetadataModel;
use App\Models\SpellModel;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use PHPUnit\Framework\ExpectationFailedException;

class Individuals extends BaseController
{
    public function profile()
    {
        $individual = $this->getAuthenticated();
        if (is_null($individual)) {
            return $this->getResponse(
                ['error' => 'Unauthorized'],
                ResponseInterface::HTTP_UNAUTHORIZED
            );
        }

        $metadataModel = new IndividualMetadataModel();
        $metadata = $metadataModel
            ->where('individual_id', $individual['id'])
            ->first();

        if (is_null($metadata)) {
            log_message('warning', 'The metadata from "{id}" was not found.', $individual);
        }

        $profile = [
            'individual' => $individual,
            'metadata' => $metadata
        ];

        return $this->getResponse($profile);
    }

    public function pray()
    {
        $individual = $this->getAuthenticated();
        if (is_null($individual)) {
            return $this->getResponse(
                ['error' => 'Unauthorized'],
                ResponseInterface::HTTP_UNAUTHORIZED
            );
        }

        /*
            Individual can't abuse from pray system to farm points. So, is available 
            only once pray every 30 minutes.
        */
        $throttler = service('throttler');
        $throttlerId = sprintf('individual-pray-%d', $individual['id']);

        if (!$throttler->check($throttlerId, 1, MINUTE * 30)) {
            return $this->response
                ->setHeader('X-RateLimit-Reset', $throttler->getTokenTime() * 1000)
                ->setJSON(['error' => 'Individual can only pray once every 30 minutes.'])
                ->setStatusCode(ResponseInterface::HTTP_TOO_MANY_REQUESTS);
        }

        $rules = [
            'pray' => 'required|min_length[1]|max_length[1024]'
        ];

        $data = $this->getRequestData();
        if (!$this->validateData($data, $rules)) {
            return $this->getResponse(
                ['error' => $this->validator->getErrors()],
                ResponseInterface::HTTP_BAD_REQUEST
            );
        }

        $individualMetadataModel = new IndividualMetadataModel();
        $metadata = $individualMetadataModel
            ->where('individual_id', $individual['id'])
            ->first();

        if (is_null($metadata)) {
            return $this->getResponse(
                ['error', 'Individual\'s metadata was not found.'],
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $pray = $data['pray'];
        /*
            The logic how the pray is analyzed:
            1. How long the pray is.
            2. How many words contains.
            3. How many time 'god' was mentioned.
        */

        $prayLength = strlen($pray);

        $words = explode(' ', str_replace(',', ' ', strtolower($pray)));
        $totalWords = count($words);

        $totalGods = 0;
        foreach ($words as $word) {
            if ($word == 'god') {
                $totalGods++;
            }
        }

        // Some comparations
        $presenceOfGodInPray = $totalGods / $totalWords;
        $prayLengthFromTheMaximum = $prayLength / 1024;

        if ($totalGods == 0) {
            // How can a person don't mention god in their prey?
            return $this->getResponse(['message' => 'Pray successfuly completed.']);
        }

        $totalSPToReceive = 0;

        /*
            To give more attraction from gods, is valuable to say their name 
            during pray. That way, they can feel more gratitude from you.

            But, saying very frequently in the pray, like around 40% to 70% of 
            the pray don't fit very well. So, to not to encourage that, only 1 SP
            is gain with that.
        */
        if ($presenceOfGodInPray < 0.6) {
            $totalSPToReceive += max(1, $totalGods * 0.15);
        } else if ($presenceOfGodInPray >= 0.4 && $presenceOfGodInPray <= 0.7) {
            $totalSPToReceive += 1;
        }

        /*
            A person that gives more attention to their prey, giving a more long 
            pray, can receive more acknowledgement from gods.
        */
        $totalSPToReceive += 10 * $prayLengthFromTheMaximum;

        // People with DARKNESS and LIGHT insignias receives a special point.
        $insignia = $individual['insignia'];
        if ($insignia == 4 || $individual == 5) {
            $totalSPToReceive += 1;
        }

        $metadata['sp'] += $totalSPToReceive;
        $individualMetadataModel->update($metadata['id'], $metadata);

        return $this->getResponse(['message' => 'Pray successfuly completed.']);
    }

    public function releaseSpell($spellId)
    {
        $individual = $this->getAuthenticated();
        if (is_null($individual)) {
            return $this->response
                ->setJSON(['error' => 'Unauthorized'])
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $individualHasSpellsModel = new IndividualHasSpellsModel();
        $individualSpell = $individualHasSpellsModel
            ->where('individual_id', $individual['id'])
            ->where('spell_id', $spellId)
            ->first();

        if (is_null($individualSpell)) {
            return $this->response
                ->setJSON(['error' => 'You don\'t have this spell.'])
                ->setStatusCode(ResponseInterface::HTTP_NOT_FOUND);
        }

        $spellModel = new SpellModel();
        $spell = $spellModel->find($spellId);

        if (is_null($spell)) {
            log_message('warning', 'Spell "{spellId}" was found in individual_has_spell but not in spells', [
                'spellId' => $spellId
            ]);

            return $this->response
                ->setJSON(['error' => 'Spell not found.'])
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
        }

        $individualMetadataModel = new IndividualMetadataModel();
        $metadata = $individualMetadataModel
            ->where('individual_id', $individual['id'])
            ->first();

        if (is_null($metadata)) {
            log_message('critical', 'Individual "{id}"\'s metadata not found.', $individual);

            return $this->response
                ->setJSON(['error' => 'Something went wrong.'])
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($metadata['mp'] < $spell['mana']) {
            return $this->response
                ->setJSON(['error' => 'Insufficient mana to release spell.'])
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
        }

        $metadata['mp'] -= $spell['mana'];

        try {
            $individualMetadataModel->update($metadata['id'], $metadata);
        } catch (Exception $e) {
            return $this->response
                ->setJSON(['error' => $e->getMessage()])
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->response
            ->setJSON(['code' => $spell['code']]);
    }
}
