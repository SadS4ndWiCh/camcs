<?php

namespace Tests\App\Models\IndividualModel;

use App\Exceptions\AuthException;
use App\Models\IndividualModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Fabricator;
use Tests\Support\Fabricators\IndividualFabricator;

class CeremonyTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = null;

    private IndividualModel $individualModel;
    private Fabricator $individualFabricator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->individualModel = new IndividualModel();
        $this->individualFabricator = new Fabricator(IndividualFabricator::class);
    }

    public function test_it_should_be_able_to_complete_ceremony()
    {
        $data = $this->individualFabricator->make();

        $id = $this->individualModel->ceremony($data);

        $this->assertIsInt($id);
        $this->seeInDatabase('individuals', [
            'soul' => $data['soul']
        ]);
    }

    public function test_it_shouldnt_be_able_to_complete_ceremony_with_missing_data()
    {
        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('The ceremony wasn\'t able to even start. Maybe you already have an insignia?');

        $data = $this->individualFabricator->make();
        unset($data['soul']);

        $this->individualModel->ceremony($data);
    }

    public function test_it_shouldnt_be_able_to_complete_ceremony_with_already_existing_soul()
    {
        $individual = $this->individualFabricator->create();

        $this->expectException(AuthException::class);
        $this->expectExceptionCode(ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);

        $data = $this->individualFabricator->make();
        $data['soul'] = $individual['soul'];

        $this->individualModel->ceremony($data);
    }
}
