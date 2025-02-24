<?php

namespace App\Database\Seeds;

use App\Enums\InsigniaTypes;
use App\Models\SpellModel;
use CodeIgniter\Database\Seeder;
use Faker\Factory;

class SpellsSeeder extends Seeder
{
    public function run()
    {
        $faker = Factory::create();

        for ($i = 0; $i < 30; $i++) {
            $spell = [
                'name' => $faker->words(2, true),
                'type' => InsigniaTypes::random_key(),
                'code' => md5(random_bytes(32)),
                'price' => random_int(10, 100),
                'mana' => random_int(30, 200)
            ];

            $spellModel = new SpellModel();
            $spellModel->insert($spell);
        }
    }
}
