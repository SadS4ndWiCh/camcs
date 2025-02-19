<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddIndividualMetadata extends Migration
{
    public function up()
    {
        $fields = [
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'individual_id' => [
                'type'     => 'INT',
                'unsigned' => true
            ],
            'sp' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 10
            ],
            'mp' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 100
            ],
            'max_mp' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 100
            ],
            'xp' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0
            ],
            'level' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 1
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
            ->addPrimaryKey('id')
            ->addForeignKey('individual_id', 'individuals', 'id', '', 'CASCADE')
            ->createTable('individuals_metadata');
    }

    public function down()
    {
        $this->forge->dropTable('individuals_metadata');
    }
}
