<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\IndividualHasSpellsModel;
use App\Models\IndividualMetadataModel;
use App\Models\IndividualModel;
use App\Models\SpellModel;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class Spells extends BaseController
{
    public function index()
    {
        $individual = $this->getAuthenticated();

        // If individual isn't logged, is allowed only once request every 10 seconds.
        if (is_null($individual)) {
            $throttler = service('throttler');
            $throttlerId = sprintf('spells-all-%s', $this->request->getIPAddress());

            if (!$throttler->check($throttlerId, 1, SECOND * 10)) {
                return $this->response
                    ->setHeader('RateLimit-Reset', $throttler->getTokenTime())
                    ->setJSON(['error' => 'You must be logged to don\'t be affected by this limit.'])
                    ->setStatusCode(ResponseInterface::HTTP_TOO_MANY_REQUESTS);
            }
        }

        $spellModel = new SpellModel();
        $individualHasSpellsModel = new IndividualHasSpellsModel();

        // TODO: Pagination for more efficient visualization.
        $spells = $spellModel->findAll();

        if (is_null($individual)) {
            return $this->response->setJSON($spells);
        }

        for ($i = 0; $i < count($spells); $i++) {
            $available = $spells[$i]['type'] == $individual['insignia'];
            $spells[$i]['available'] = $available;

            if ($available) {
                $spell = $individualHasSpellsModel
                    ->where('individual_id', $individual['id'])
                    ->where('spell_id', $spells[$i]['id'])
                    ->first();

                $spells[$i]['learned'] = !is_null($spell);
            }
        }

        return $this->response->setJSON($spells);
    }

    public function learn($spellId)
    {
        $individual = $this->getAuthenticated();
        if (is_null($individual)) {
            return $this->response
                ->setJSON(['error' => 'Unauthorized'])
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        $spellModel = new SpellModel();
        $spell = $spellModel->find($spellId);
        if (is_null($spell)) {
            return $this->response
                ->setJSON(['error' => 'Spell was not found'])
                ->setStatusCode(ResponseInterface::HTTP_NOT_FOUND);
        }

        $individualHasSpellsModel = new IndividualHasSpellsModel();
        $individualSpell = $individualHasSpellsModel
            ->where('individual_id', $individual['id'])
            ->where('spell_id', $spellId)
            ->first();

        if (!is_null($individualSpell)) {
            return $this->response
                ->setJSON(['error' => 'You already learned this spell.'])
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
        }

        $individualModel = new IndividualModel();
        $metadata = $individualModel->getMetadataFromId($individual['id']);

        if (is_null($metadata)) {
            log_message('warning', 'The metadata from "{id}" was not found.', $individual);

            return $this->response
                ->setJSON(['error' => 'Something went wrong.'])
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($spell['type'] != $individual['insignia']) {
            return $this->response
                ->setJSON(['error' => 'This spell isn\'t available to you learn.'])
                ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN);
        }

        // Has individual enough skill point?
        if ($spell['price'] > $metadata['sp']) {
            return $this->response
                ->setJSON(['error' => 'You don\'t have enough SP to learn this spell.'])
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
        }

        $metadata['sp'] -= $spell['price'];

        $db = db_connect();
        $db->transStart();

        try {
            $individualMetadataModel = new IndividualMetadataModel();
            $individualMetadataModel->update($metadata['id'], $metadata);
            $individualHasSpellsModel->insert([
                'individual_id' => $individual['id'],
                'spell_id'      => $spellId
            ]);
        } catch (Exception $e) {
            $db->transRollback();

            log_message('critical', 'Failed to "{individualId}" learn spell "{spellId}": {message}', [
                'individualId' => $individual['id'],
                'spellId'      => $spellId,
                'message'      => $e->getMessage()
            ]);

            return $this->response
                ->setJSON(['error' => 'Something went wrong.'])
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        $db->transComplete();

        return $this->response
            ->setJSON(['message' => 'Spells learned successfuly.']);
    }
}
