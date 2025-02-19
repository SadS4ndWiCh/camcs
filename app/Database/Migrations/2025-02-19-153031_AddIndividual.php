<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddIndividual extends Migration
{
    public function up()
    {
        $fields = [
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '64',
            ],
            'soul' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'unique'     => true
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => '255'
            ],
            'insignia' => [
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
            ->addPrimaryKey('id')
            ->createTable('individuals');
    }

    public function down()
    {
        $this->forge->dropTable('individuals');
    }
}
