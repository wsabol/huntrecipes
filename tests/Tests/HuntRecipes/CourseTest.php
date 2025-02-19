<?php

namespace Tests\HuntRecipes;

require __DIR__ . '/../../../includes/common.php';

use HuntRecipes\Course;
use HuntRecipes\Database\SqlController;
use PHPUnit\Framework\TestCase;

class CourseTest extends TestCase {
    private SqlController $conn;
    private Course $course;

    protected function setUp(): void {
        $this->conn = $this->createMock(SqlController::class);
        $this->course = new Course(1, $this->conn);
    }

    public function testList(): void {
        $mockResult = $this->createMock(\mysqli_result::class);
        $mockResult->method('fetch_object')
            ->willReturnOnConsecutiveCalls(
                (object)['id' => 1, 'name' => 'Appetizer', 'icon' => 'appetizer.png'],
                (object)['id' => 2, 'name' => 'Main Course', 'icon' => 'main.png'],
                null
            );

        $this->conn->expects($this->once())
            ->method('query')
            ->willReturn($mockResult);

        $courses = Course::list($this->conn, []);

        $this->assertCount(2, $courses);
        $this->assertEquals('Appetizer', $courses[0]->name);
    }
}
