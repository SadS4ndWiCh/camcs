<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\IndividualModel;
use App\Models\SpellModel;
use CodeIgniter\HTTP\ResponseInterface;

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
        $individualModel->learnSpell($individual, $spellId);

        return $this->response
            ->setJSON(['message' => 'Spells learned successfuly.']);
    }
}
