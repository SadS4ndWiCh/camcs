<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\IndividualHasSpellsModel;
use App\Models\IndividualMetadataModel;
use App\Models\IndividualModel;
use App\Models\SpellModel;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class Individuals extends BaseController
{
    public function profile()
    {
        $individual = $this->getAuthenticated();
        if (is_null($individual)) {
            return $this->response
                ->setJSON(['error' => 'Unauthorized'])
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $individualModel = new IndividualModel();
        $profile = $individualModel->profile($individual['id']);

        if (is_null($profile)) {
            log_message('critical', 'Failed to fetch profile from "{id}"', $individual);
            return $this->response
                ->setJSON(['error' => 'Something went wrong. You is really you?'])
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->response->setJSON($profile);
    }

    public function pray()
    {
        $individual = $this->getAuthenticated();
        if (is_null($individual)) {
            return $this->response
                ->setJSON(['error' => 'Unauthorized'])
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        /*
            Individual can't abuse from pray system to farm points. So, is available 
            only once pray every 30 minutes.
        */
        $throttler = service('throttler');
        $throttlerId = sprintf('individual-pray-%d', $individual['id']);

        if (!$throttler->check($throttlerId, 1, MINUTE * 30)) {
            return $this->response
                ->setHeader('RateLimit-Reset', $throttler->getTokenTime())
                ->setJSON(['error' => 'Individual can only pray once every 30 minutes.'])
                ->setStatusCode(ResponseInterface::HTTP_TOO_MANY_REQUESTS);
        }

        $rules = [
            'prayer' => 'required|min_length[1]|max_length[1024]'
        ];

        $data = $this->getRequestData();
        if (!$this->validateData($data, $rules)) {
            return $this->response
                ->setJSON(['error' => $this->validator->getErrors()])
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
        }

        $prayer = $data['prayer'];

        $individualModel = new IndividualModel();
        if (!$individualModel->pray($individual, $prayer)) {
            return $this->response
                ->setJSON(['error' => 'Failed to complete pray. Are you really trying?'])
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->response->setJSON(['message' => 'Pray successfuly completed.']);
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

        $individualModel = new IndividualModel();
        $metadata = $individualModel->getMetadataFromId($individual['id']);

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
            $individualMetadataModel = new IndividualMetadataModel();
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
