<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\IndividualModel;
use CodeIgniter\HTTP\ResponseInterface;

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

        $data = $this->request->getJSON(true);
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

        $individualModel = new IndividualModel();
        $releasedSpell = $individualModel->releaseSpell($individual['id'], $spellId);

        return $this->response
            ->setJSON(['code' => $releasedSpell['code']]);
    }
}
