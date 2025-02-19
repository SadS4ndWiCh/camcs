<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\IndividualMetadataModel;
use App\Models\IndividualModel;
use App\Models\InsigniaTypes;
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
            return $this->getResponse(
                ['error' => $this->validator->getErrors()],
                ResponseInterface::HTTP_BAD_REQUEST
            );
        }

        log_message('info', 'The "{name}" started a ceremony.', $data);

        $individualModel = new IndividualModel();

        // The logic to choose the insignia should be inside class `beforeInsert`.
        $choosedIdx = random_int(0, count($individualModel::$INSIGNIAS) - 1);
        $data['insignia'] = $choosedIdx + 1;

        log_message('info', 'The "{name}" receives the insignia "{insignia}".', $data);

        // Starting a transaction to rollback if some error occour, because 
        // ceremony needs to create both `individual` and `individual metadata` 
        // entries.
        $db = db_connect();
        $db->transStart();

        $individualId = $individualModel->insert($data);
        log_message('info', 'The "{name}" was included in database.', $data);

        $metadata = [
            'individual_id' => $individualId,
            'sp'            => 10,
            'mp'            => 100,
            'max_mp'        => 100,
            'xp'            => 0,
            'level'         => 0
        ];

        // The insignia `DARKNESS` and `LIGHT` are specials
        $insignia = $individualModel::$INSIGNIAS[$choosedIdx];
        if ($insignia == InsigniaTypes::DARKNESS || $insignia == InsigniaTypes::LIGHT) {
            $metadata['sp'] = 20;
            $metadata['mp'] = 200;
            $metadata['max_mp'] = 200;
        }

        $metadataModel = new IndividualMetadataModel();
        if (!$metadataModel->insert($metadata)) {
            log_message('critital', 'Failed to create metadata for "{name}".', $data);
            $db->transRollback();

            return $this->getResponse(
                ['error' => $db->error()],
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $db->transComplete();
        log_message('info', 'The "{name}" ceremory completes successfuly.', $data);

        return $this->getResponse(
            ['individual' => $individualId],
            ResponseInterface::HTTP_CREATED
        );
    }
}
