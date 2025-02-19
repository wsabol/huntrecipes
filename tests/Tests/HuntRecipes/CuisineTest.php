<?php

namespace Tests\HuntRecipes;

require __DIR__ . '/../../../includes/common.php';

use HuntRecipes\Cuisine;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Test\SQLResultStub;
use PHPUnit\Framework\TestCase;

class CuisineTest extends TestCase {
    private SqlController $conn;
    private Cuisine $cuisine;

    protected function setUp(): void {
        $this->conn = $this->createMock(SqlController::class);
        $this->cuisine = new Cuisine(1, $this->conn);
    }

    public function testList(): void {
        $mockResult = $this->createMock(\mysqli_result::class);
        $mockResult->method('fetch_object')
            ->willReturnOnConsecutiveCalls(
                (object)['id' => 1, 'name' => 'Italian', 'icon' => 'italian.png'],
                (object)['id' => 2, 'name' => 'Mexican', 'icon' => 'mexican.png'],
                null
            );

        $this->conn->expects($this->once())
            ->method('query')
            ->willReturn($mockResult);

        $cuisines = Cuisine::list($this->conn, []);

        $this->assertCount(2, $cuisines);
        $this->assertEquals('Italian', $cuisines[0]->name);
    }
}
