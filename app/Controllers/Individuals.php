<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Exceptions\ValidationException;
use App\Models\IndividualModel;
use CodeIgniter\HTTP\ResponseInterface;

class Individuals extends BaseController
{
    public function profile()
    {
        $individual = $this->getAuthenticated();

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

        $data = $this->request->getJSON(true);
        if (!$this->validateData($data, $rules)) {
            throw ValidationException::forRequestValidationError($this->validator);
        }

        $prayer = $data['prayer'];

        $individualModel = new IndividualModel();
        $individualModel->pray($individual, $prayer);

        return $this->response->setJSON(['message' => 'Pray successfuly completed.']);
    }

    public function releaseSpell($spellId)
    {
        $individual = $this->getAuthenticated();

        $individualModel = new IndividualModel();
        $releasedSpell = $individualModel->releaseSpell($individual['id'], $spellId);

        return $this->response
            ->setJSON(['code' => $releasedSpell['code']]);
    }

    public function meditate()
    {
        $individual = $this->getAuthenticated();

        $throttler = service('throttler');
        $throttlerId = sprintf('individual-meditate-%d', $individual['id']);

        if (!$throttler->check($throttlerId, 2, MINUTE)) {
            return $this->response
                ->setHeader('RateLimit-Reset', $throttler->getTokenTime())
                ->setJSON(['error' => 'Individual can only meditate twice every minute.'])
                ->setStatusCode(ResponseInterface::HTTP_TOO_MANY_REQUESTS);
        }

        $individualModel = new IndividualModel();
        $individualModel->meditate($individual);

        return $this->response
            ->setJSON(['message' => 'You\'ve successfuly meditate.']);
    }
}
