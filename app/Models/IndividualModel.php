<?php

namespace App\Models;

use App\Enums\InsigniaTypes;
use App\Exceptions\AuthException;
use App\Exceptions\IndividualException;
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

            throw IndividualException::forPrayFailToUpdateSP($e);
        }
    }

    public function learnSpell($individual, $spellId)
    {
        $spellModel = new SpellModel();
        $spell = $spellModel->find($spellId);
        if (is_null($spell)) {
            throw IndividualException::forLearnAnUnexistingSpell();
        }

        if ($this->hasSpell($individual['id'], $spellId)) {
            throw IndividualException::forLearnAnAlreadyLearnedSpell();
        }

        if (!$spellModel->isSpellAvailableToIndividualLearn($spell, $individual)) {
            throw IndividualException::forLearnAnUnavailableSpell();
        }

        $metadata = $this->getMetadataFromId($individual['id']);

        if ($metadata['sp'] < $spell['price']) {
            throw IndividualException::forLearnWithoutEnoughPoints();
        }

        $metadata['sp'] -= $spell['price'];

        $db = db_connect();
        $db->transStart();

        $individualMetadataModel = new IndividualMetadataModel();
        $individualMetadataModel->update($metadata['id'], $metadata);
        if ($db->transStatus() === false) {
            $db->transRollback();

            log_message('critical', 'Failed to update metadata from "{individualId}" in learn spell "{spellId}" process', [
                'individualId' => $individual['id'],
                'spellId'      => $spellId,
            ]);

            throw IndividualException::forLearnFailsToUpdateMetadata();
        }

        $individualHasSpellsModel = new IndividualHasSpellsModel();
        $individualHasSpellsModel->insert([
            'individual_id' => $individual['id'],
            'spell_id'      => $spellId
        ]);
        if ($db->transStatus() === false) {
            $db->transRollback();

            log_message('critical', 'Failed to insert spell "{spellId}" as learned to "{individualId}"', [
                'individualId' => $individual['id'],
                'spellId'      => $spellId
            ]);

            throw IndividualException::forLearnFailsToInsertSpellAsLearned();
        }

        $db->transComplete();
    }

    public function releaseSpell($individualId, $spellId)
    {
        $spellModel = new SpellModel();
        $spell = $spellModel->find($spellId);

        if (is_null($spell)) {
            throw IndividualException::forReleaseAnUnexistingSpell();
        }

        if (!$this->hasSpell($individualId, $spellId)) {
            throw IndividualException::forReleaseNotLearnedSpell();
        }

        $individualModel = new IndividualModel();
        $metadata = $individualModel->getMetadataFromId($individualId);

        if ($metadata['mp'] < $spell['mana']) {
            throw IndividualException::forReleaseWithoutEnoughMana();
        }

        $metadata['mp'] -= $spell['mana'];

        try {
            $individualMetadataModel = new IndividualMetadataModel();
            $individualMetadataModel->update($metadata['id'], $metadata);
        } catch (Exception $e) {
            throw IndividualException::forReleaseFailToUpdateMetadata($e);
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
            throw IndividualException::forMeditateFailToUpdateMetadata($e);
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

            throw IndividualException::forFailsToGrabIndividualMetadata();
        }

        return $metadata;
    }
}
