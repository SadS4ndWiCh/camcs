<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddIndividualsHasSpells extends Migration
{
    public function up()
    {
        $fields = [
            'individual_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'spell_id' => [
                'type'     => 'INT',
                'unsigned' => true
            ],
            'updated_at' => [
                'type' => 'timestamp'
            ],
            'created_at' => [
                'type'    => 'timestamp',
                'default' => new RawSql('CURRENT_TIMESTAMP')
            ]
        ];

        $this->forge
            ->addField($fields)
            ->addPrimaryKey(['individual_id', 'spell_id'], 'id')
            ->addForeignKey('individual_id', 'individuals', 'id')
            ->addForeignKey('spell_id', 'spells', 'id')
            ->createTable('individuals_has_spells');
    }

    public function down()
    {
        $this->forge->dropTable('individuals_has_spells');
    }
}
