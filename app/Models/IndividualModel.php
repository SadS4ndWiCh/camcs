<?php

namespace App\Models;

use App\Enums\InsigniaTypes;
use App\Exceptions\IndividualDoesNotHaveSpellException;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Model;
use Config\Services;
use Exception;

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
    protected $afterFind      = [];
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

    public function ceremony(array $individual)
    {
        // Starting a transaction to rollback if some error occour, because 
        // ceremony needs to create both `individual` and `individual metadata` 
        // entries.
        $db = db_connect();
        $db->transStart();

        try {
            $individual['id'] = $this->insert($individual);
        } catch (Exception $e) {
            $db->transRollback();
            log_message('critical', 'Failed to complete ceremony due individual creation: {message}', [
                'message' => $e->getMessage()
            ]);

            return null;
        }

        $insignia = InsigniaTypes::from_key($individual['insignia']);
        $metadata = match ($insignia) {
            InsigniaTypes::DARKNESS, InsigniaTypes::LIGHT => [
                'individual_id' => $individual['id'],
                'sp'            => 20,
                'mp'            => 200,
                'max_mp'        => 200,
            ],
            default => [
                'individual_id' => $individual['id'],
                'sp'            => 10,
                'mp'            => 100,
                'max_mp'        => 100,
            ]
        };

        $individualMetadataModel = new IndividualMetadataModel();

        try {
            $metadata['id'] = $individualMetadataModel->insert($metadata);
        } catch (Exception $e) {
            $db->transRollback();
            log_message('crititcal', 'Failed to complete ceremony due metadata creation: {message}', [
                'message' => $e->getMessage()
            ]);

            return null;
        }

        $db->transComplete();
        return $individual['id'];
    }

    public function login($credentials)
    {
        $individual = $this
            ->where('soul', $credentials['soul'])
            ->first();

        if (is_null($individual)) {
            return null;
        }

        if (!password_verify($credentials['code'], $individual['code'])) {
            return null;
        }

        return $individual['id'];
    }

    public function profile($individualId)
    {
        $individual = $this->find($individualId);
        if (is_null($individual)) {
            return null;
        }

        $individualMetadataModel = new IndividualMetadataModel();
        $metadata = $individualMetadataModel
            ->where('individual_id', $individualId)
            ->first();

        if (is_null($metadata)) {
            return null;
        }

        // Omit sensive data
        unset($individual['code']);
        $individual['insignia'] = InsigniaTypes::from_key($individual['insignia'])
            ->label();

        return [
            'individual' => $individual,
            'metadata'   => $metadata
        ];
    }

    public function pray($individual, string $prayer)
    {
        $metadata = $this->getMetadataFromId($individual['id']);
        if (is_null($metadata)) {
            return false;
        }

        $insignia = InsigniaTypes::from_key($individual['insignia']);

        $prayService = Services::pray();
        $prayerWorth = $prayService->analyze($prayer, $insignia);

        $metadata['sp'] += $prayerWorth;

        try {
            $individualMetadataModel = new IndividualMetadataModel();
            $individualMetadataModel->update($metadata['id'], $metadata);
        } catch (Exception $e) {
            log_message('critical', 'Failed to add SP from prayer worth: {message}', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    public function learnSpell($individual, $spellId)
    {
        $spellModel = new SpellModel();
        $spell = $spellModel->find($spellId);
        if (is_null($spell)) {
            return false;
        }

        $individualHasSpellsModel = new IndividualHasSpellsModel();
        if (!$individualHasSpellsModel->isIndividualHasSpell($individual['id'], $spellId)) {
            return false;
        }

        if (!$spellModel->isSpellAvailableToIndividualLearn($spell, $individual)) {
            return false;
        }

        $metadata = $this->getMetadataFromId($individual['id']);
        if (is_null($metadata)) {
            return false;
        }

        if ($metadata['sp'] < $spell['price']) {
            return false;
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

            return false;
        }

        $db->transComplete();
        return true;
    }

    public function releaseSpell($individualId, $spellId)
    {
        $spellModel = new SpellModel();
        $spell = $spellModel->find($spellId);

        if (is_null($spell)) {
            throw new Exception(
                'You are trying to release a spell that even exists. Take it seriously.',
                ResponseInterface::HTTP_BAD_REQUEST
            );
        }

        $individualHasSpellsModel = new IndividualHasSpellsModel();
        $individualSpell = $individualHasSpellsModel
            ->where('individual_id', $individualId)
            ->where('spell_id', $spellId)
            ->first();

        if (is_null($individualSpell)) {
            throw new Exception(
                'You don\'t have this spell to release. Learn it or consider releasing another one.',
                ResponseInterface::HTTP_NOT_FOUND
            );
        }

        $individualModel = new IndividualModel();
        $metadata = $individualModel->getMetadataFromId($individualId);

        if (is_null($metadata)) {
            log_message('critical', 'Individual "{id}"\'s metadata not found.', [
                'id' => $individualId
            ]);

            throw new Exception(
                'The system wasn\'t able to grab your metadata.',
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if ($metadata['mp'] < $spell['mana']) {
            throw new Exception(
                'You don\'t have mana enough to release this spell.',
                ResponseInterface::HTTP_BAD_REQUEST
            );
        }

        $metadata['mp'] -= $spell['mana'];

        try {
            $individualMetadataModel = new IndividualMetadataModel();
            $individualMetadataModel->update($metadata['id'], $metadata);
        } catch (Exception $e) {
            throw new Exception(
                "The spell has almost been cast. Try again.",
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $spell;
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
