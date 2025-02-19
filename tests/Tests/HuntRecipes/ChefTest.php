<?php

namespace Tests\HuntRecipes;

require __DIR__ . '/../../../includes/common.php';

use DateTimeImmutable;
use HuntRecipes\Chef;
use HuntRecipes\Database\SqlController;
use PHPUnit\Framework\TestCase;

class ChefTest extends TestCase {
    private SqlController $conn;
    private Chef $chef;

    protected function setUp(): void {
        $this->conn = $this->createMock(SqlController::class);
        $this->chef = new Chef(1, $this->conn);
    }

    public function testConstructorWithValidId(): void {
        $mockResult = $this->createMock(\mysqli_result::class);
        $mockResult->method('fetch_object')->willReturn((object)[
            'name' => 'Test Chef',
            'is_male' => true,
            'wisdom' => 'Test wisdom',
            'story' => 'Test story',
            'favorite_foods' => 'Test foods'
        ]);

        $this->conn->expects($this->once())
            ->method('query')
            ->with($this->stringContains('select * from Chef where id = 1'))
            ->willReturn($mockResult);

        $chef = new Chef(1, $this->conn);

        $this->assertEquals('Test Chef', $chef->name);
        $this->assertTrue($chef->is_male);
    }

    public function testSetNewChefOfTheDay(): void {
        $date = new DateTimeImmutable();

        $mockResult = $this->createMock(\mysqli_result::class);
        $mockResult->method('fetch_object')->willReturn((object)['id' => 1]);

        $this->conn->expects($this->exactly(3))
            ->method('query')
            ->willReturn($mockResult);

        Chef::set_new_chef_of_the_day($date, $this->conn);
    }
}
