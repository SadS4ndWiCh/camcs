<?php

namespace App\Models;

use App\Enums\InsigniaTypes;
use CodeIgniter\Model;

class SpellModel extends Model
{
    protected $table            = 'spells';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'type', 'code', 'price', 'mana'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'    => 'int',
        'price' => 'int',
        'mana' => 'int'
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
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function listSpells($individual = null)
    {
        $spells = $this->findAll();

        if (is_null($individual)) {
            return $spells;
        }

        $individualHasSpellsModel = new IndividualHasSpellsModel();
        for ($i = 0; $i < count($spells); $i++) {
            $available = $this->isSpellAvailableToIndividualLearn($spells[$i], $individual);
            $spells[$i]['available'] = $available;

            if ($available) {
                $spell = $individualHasSpellsModel
                    ->where('individual_id', $individual['id'])
                    ->where('spell_id', $spells[$i]['id'])
                    ->first();

                $spells[$i]['learned'] = !is_null($spell);
            }

            $spells[$i]['type'] = InsigniaTypes::from_key($spells[$i]['type'])->label();
        }

        return $spells;
    }

    public function isSpellAvailableToIndividualLearn($spell, $individual)
    {
        return $spell['type'] == $individual['insignia'];
    }
}
