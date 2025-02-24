<?php

namespace App\Models;

use App\Enums\InsigniaTypes;
use CodeIgniter\Model;

class IndividualModel extends Model
{
    protected $table            = 'individuals';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'soul', 'code', 'insignia'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['beforeInsert'];
    protected $afterInsert    = [];
    protected $beforeUpdate   = ['beforeUpdate'];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = ['afterFind'];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    protected function beforeInsert($data)
    {
        return $this->encryptUpdatedCode($data);
    }

    protected function beforeUpdate($data)
    {
        return $this->encryptUpdatedCode($data);
    }

    protected function afterFind(array $data)
    {
        if (isset($data['data']['insignia'])) {
            $data['data']['insignia'] = InsigniaTypes::from_key($data['data']['insignia'])->label();
        }

        return $data;
    }

    private function encryptUpdatedCode(array $data): array
    {
        if (isset($data['data']['code'])) {
            $data['data']['code'] = password_hash(
                $data['data']['code'],
                PASSWORD_BCRYPT
            );
        }

        return $data;
    }

    public function getMetadataFromId($individualId)
    {
        $individualMetadataModel = new IndividualMetadataModel();
        $metadata = $individualMetadataModel
            ->where('individual_id', $individualId)
            ->first();

        return $metadata;
    }
}
