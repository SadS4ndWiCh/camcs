<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Enums\InsigniaTypes;
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
        $spells = $spellModel->listSpells($individual);

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

        $individualModel = new IndividualModel();
        if (!$individualModel->learnSpell($individual, $spellId)) {
            return $this->response
                ->setJSON(['error' => 'Failed to learn spell.'])
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
        }

        return $this->response
            ->setJSON(['message' => 'Spells learned successfuly.']);
    }
}
