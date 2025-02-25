<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Enums\InsigniaTypes;
use App\Models\IndividualModel;
use CodeIgniter\HTTP\ResponseInterface;

class Auth extends BaseController
{
    public function ceremony()
    {
        $rules = [
            'name' => 'required|min_length[6]|max_length[64]',
            'soul' => 'required|min_length[6]|max_length[255]|is_unique[individuals.soul]',
            'code' => 'required|min_length[6]|max_length[255]'
        ];

        $data = $this->getRequestData();
        if (!$this->validateData($data, $rules)) {
            return $this->response
                ->setJSON(['error' => $this->validator->getErrors()])
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
        }

        $data['insignia'] = InsigniaTypes::random_key();

        $individualModel = new IndividualModel();
        $individualId = $individualModel->ceremony($data);
        if (is_null($individualId)) {
            return $this->response
                ->setJSON(['error' => 'Failed to complete ceremony. Maybe you didn\'t put in enough effort?'])
                ->setStatusCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        helper('jwt');
        $token = JWT_signTokenFor($individualId);

        return $this->response
            ->setJSON([
                'message'      => 'Ceremony successfuly completed.',
                'access_token' => $token
            ])
            ->setStatusCode(ResponseInterface::HTTP_CREATED);
    }

    public function login()
    {
        $rules = [
            'soul' => 'required|min_length[6]|max_length[255]',
            'code' => 'required|min_length[6]|max_length[255]'
        ];

        $data = $this->getRequestData();
        if (!$this->validateData($data, $rules)) {
            return $this->response
                ->setJSON(['error' => $this->validator->getErrors()])
                ->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST);
        }


        $individualModel = new IndividualModel();
        $individualId = $individualModel->login($data);
        if (is_null($individualId)) {
            return $this->response
                ->setJSON(['error' => 'Individual\'s soul or code is invalid.'])
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        }

        helper('jwt');
        $token = JWT_signTokenFor($individualId);

        return $this->response->setJSON([
            'message'      => 'Successfuly logged.',
            'access_token' => $token
        ]);
    }
}
