<?php

namespace Tests\Support\Fabricators;

use App\Enums\InsigniaTypes;
use App\Models\IndividualModel;
use Faker\Generator;

class IndividualFabricator extends IndividualModel
{
    public function fake(Generator &$faker)
    {
        return [
            'name'     => $faker->firstName() . ' ' . $faker->lastName(),
            'soul'     => uniqid() . '.' . uniqid() . '.' . uniqid(),
            'code'     => $faker->password(),
            'insignia' => rand(0, InsigniaTypes::count())
        ];
    }
}
