<?php

namespace App\Models;

use App\Enums\InsigniaTypes;
use App\Exceptions\AuthException;
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

    protected array $casts = [
        'id' => 'int'
    ];
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

        $individual['id'] = $this->insert($individual);
        if ($db->transStatus() === false) {
            $db->transRollback();
            log_message('critical', 'Failed to complete ceremony due individual creation');

            throw AuthException::forCeremonyFailToInsertIndividual();
        }

        $individual['insignia'] = InsigniaTypes::random_key();
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

        $metadata['id'] = $individualMetadataModel->insert($metadata);
        if ($db->transStatus() === false) {
            $db->transRollback();
            log_message('crititcal', 'Failed to complete ceremony due metadata creation');

            throw AuthException::forCeremonyFailToInsertMetadata();
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
            throw AuthException::forLoginWrongCredentials();
        }

        if (!password_verify($credentials['code'], $individual['code'])) {
            throw AuthException::forLoginWrongCredentials();
        }

        return $individual['id'];
    }

    public function profile($individualId)
    {
        $individual = $this->find($individualId);
        if (is_null($individual)) {
            throw new Exception(
                'That individual don\'t exists. Be sure if the ID is correct.',
                ResponseInterface::HTTP_NOT_FOUND
            );
        }

        $metadata = $this->getMetadataFromId($individual['id']);

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

            throw new Exception(
                'Something went wrong during the prayer. Do the prayer from deep in your heart.',
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR,
                $e
            );
        }
    }

    public function learnSpell($individual, $spellId)
    {
        $spellModel = new SpellModel();
        $spell = $spellModel->find($spellId);
        if (is_null($spell)) {
            throw new Exception(
                'You are trying to learn a spell that even exists. Take it seriously.',
                ResponseInterface::HTTP_NOT_FOUND
            );
        }

        if ($this->hasSpell($individual['id'], $spellId)) {
            throw new Exception(
                'You already learned this spell. Why are you trying to learn again?',
                ResponseInterface::HTTP_BAD_REQUEST
            );
        }

        if (!$spellModel->isSpellAvailableToIndividualLearn($spell, $individual)) {
            throw new Exception(
                'You cannot learn this spell. Try to learn another that matches your insignia.',
                ResponseInterface::HTTP_BAD_REQUEST
            );
        }

        $metadata = $this->getMetadataFromId($individual['id']);

        if ($metadata['sp'] < $spell['price']) {
            throw new Exception(
                'You lack points. Get some job or pray to the gods.',
                ResponseInterface::HTTP_BAD_REQUEST
            );
        }

        $metadata['sp'] -= $spell['price'];

        $db = db_connect();
        $db->transStart();
        $db->transException(true);

        $individualMetadataModel = new IndividualMetadataModel();
        $individualHasSpellsModel = new IndividualHasSpellsModel();

        try {
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

            throw new Exception(
                'Something went wrong in the learning process. You could try again.',
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR,
                $e
            );
        }

        $db->transComplete();
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

        if (!$this->hasSpell($individualId, $spellId)) {
            throw new Exception(
                'You don\'t have this spell to release. Learn it or consider releasing another one.',
                ResponseInterface::HTTP_NOT_FOUND
            );
        }

        $individualModel = new IndividualModel();
        $metadata = $individualModel->getMetadataFromId($individualId);

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
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR,
                $e
            );
        }

        return $spell;
    }

    public function meditate($individual)
    {
        $metadata = $this->getMetadataFromId($individual['id']);

        $recover = max(5, $metadata['mp'] * 0.05) + rand(0, 5);
        $metadata['mp'] += $recover;

        try {
            $individualMetadataModel = new IndividualMetadataModel();
            $individualMetadataModel->update($metadata['id'], $metadata);
        } catch (Exception $e) {
            throw new Exception(
                'You\'ve lost your focus.',
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR,
                $e
            );
        }
    }

    public function hasSpell($individualId, $spellId)
    {
        $individualHasSpellsModel = new IndividualHasSpellsModel();
        $individualSpell = $individualHasSpellsModel
            ->where('individual_id', $individualId)
            ->where('spell_id', $spellId)
            ->first();

        return !is_null($individualSpell);
    }

    public function getMetadataFromId($individualId)
    {
        $individualMetadataModel = new IndividualMetadataModel();
        $metadata = $individualMetadataModel
            ->where('individual_id', $individualId)
            ->first();

        if (is_null($metadata)) {
            log_message('critical', 'Individual "{id}"\'s metadata not found.', [
                'id' => $individualId
            ]);

            throw new Exception(
                'The system wasn\'t able to grab your metadata.',
                ResponseInterface::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $metadata;
    }
}
