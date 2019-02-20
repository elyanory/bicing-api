<?php

declare(strict_types=1);

namespace tests\App\Infrastructure\Persistence\Doctrine\Statement;

use App\Domain\Model\StationState\DateTimeImmutableStringable;
use App\Infrastructure\Persistence\Doctrine\Statement\DoctrineUpdateStationLocationGeometryStatement;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use tests\App\Infrastructure\System\MockClock;
use tests\Support\Builder\LocationBuilder;
use tests\Support\Builder\StationBuilder;
use tests\Support\TestCase\DatabaseTestCase;

final class DoctrineUpdateStationLocationGeometryStatementIntegrationTest extends DatabaseTestCase
{
    /** @var DoctrineUpdateStationLocationGeometryStatement */
    private $statement;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var MockClock */
    private $clock;

    /** @test */
    public function it_can_update_station_location_geometry(): void
    {
        $stationId = Uuid::uuid4();
        $updatedAt = new DateTimeImmutableStringable();

        $this->buildPersisted(StationBuilder::create()
            ->withStationId($stationId)
            ->withLocation(LocationBuilder::create()
                ->withLatitude(41.397952)
                ->withLongitude(2.180042)
                ->withGeometry(null)
                ->build())
            ->withUpdatedAt(null));

        $this->clock::willReturnDateTimeImmutableStringable($updatedAt);

        $this->assertEquals(1, $this->statement->execute($stationId));

        $this->clock::reset();

        $this->assertEquals([
            '01010000207308000078E714F012A55541E1DAAFEF4A1B5141',
            $updatedAt->__toString(),
        ], $this->locationGeometryAndUpdatedAtFromStation($stationId));
    }

    /**
     * @param UuidInterface $stationId
     *
     * @return array
     */
    private function locationGeometryAndUpdatedAtFromStation(UuidInterface $stationId): array
    {
        $query = <<<SQLSELECT
SELECT 
  location_geometry, 
  updated_at
FROM station
WHERE
  station_id= '$stationId'
SQLSELECT;

        return $this->entityManager->getConnection()->fetchArray($query);
    }

    protected function setUp()
    {
        parent::setUp();

        $this->statement = $this->getContainer()->get('test.app.statement.station_location_geometry_update');
        $this->entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->clock = $this->getContainer()->get('test.app.system.clock.mock');
    }

    protected function tearDown()
    {
        $this->clock = null;
        $this->entityManager = null;
        $this->statement = null;

        parent::tearDown();
    }
}
