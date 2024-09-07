<?php

namespace HuntRecipes;

use HuntRecipes\Base\Common_Object;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\SqlException;

class Recipe extends Common_Object {
    private SqlController $conn;
    public int $id;
    public int $course_id = 0;
    public int $cuisine_id = 0;
    public int $type_id;
    public int $chef_id = 0;
    public string $title;
    public string $instructions;
    public string $image_filename = 'assets/images/recipes/generic_recipe.jpg';
    public float $serving_count;
    public int $serving_measure_id = 0;
    public int $parent_recipe_id = 0;
    public bool $published_flag = false;

    public function __construct(int $recipe_id, SqlController $conn) {
        $this->id = $recipe_id;
        $this->conn = $conn;

        if ($this->id > 0) {
            $this->update_from_db();
        }
    }

    protected function update_from_db(): void {
        $sel_query = "select * from Recipe where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if (!!$result) {
            $row = $result->fetch_object();
            $this->course_id = $row->course_id;
            $this->cuisine_id = $row->cuisine_id;
            $this->type_id = $row->type_id;
            $this->chef_id = $row->chef_id;
            $this->title = $row->title;
            $this->instructions = $row->instructions;
            $this->image_filename = $row->image_filename;
            $this->serving_count = $row->serving_count;
            $this->serving_measure_id = $row->serving_measure_id;
            $this->parent_recipe_id = $row->parent_recipe_id;
            $this->published_flag = (bool)$row->published_flag;
        }
    }

    public static function list(SqlController $conn, array $props): array {
        $props = (object)$props;

        $keyword = $conn->escape_string(@$props->keyword ?? '');
        $recipe_type_id = @$props->recipe_type_id ?? 0;
        $course_id = @$props->course_id ?? 0;
        $cuisine_id = @$props->cuisine_id ?? 0;
        $chef_id = @$props->chef_id ?? 0;

        $sel_query = "
        select r.*
            ,co.name as course
            ,cu.name as cuisine
            ,rt.name as type
            ,ch.name as chef
            ,0 as likes_count
        from Recipe r
        LEFT JOIN Course co
            ON co.id = r.course_id
        LEFT JOIN Cuisine cu
            ON cu.id = r.cuisine_id
        LEFT JOIN RecipeType rt
            ON rt.id = r.type_id
        LEFT JOIN Chef ch
            ON ch.id = r.chef_id
        WHERE r.published_flag = 1
        AND CASE WHEN $recipe_type_id = 0 THEN 1 WHEN $recipe_type_id = r.type_id THEN 1 ELSE 0 END = 1
        AND CASE WHEN $course_id = 0 THEN 1 WHEN $course_id = r.course_id THEN 1 ELSE 0 END = 1
        AND CASE WHEN $cuisine_id = 0 THEN 1 WHEN $cuisine_id = r.cuisine_id THEN 1 ELSE 0 END = 1
        AND CASE WHEN $chef_id = 0 THEN 1 WHEN $chef_id = r.chef_id THEN 1 ELSE 0 END = 1
        AND CASE WHEN '$keyword' = '' THEN 1 WHEN r.title like '%$keyword%' THEN 1 ELSE 0 END = 1
        AND CASE WHEN '$keyword' = '' THEN 1 WHEN r.instructions like '%$keyword%' THEN 1 ELSE 0 END = 1
        order by r.title
        ";
        // echo $sel_query;
        $data = [];

        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting recipes: " . $conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            if (!str_starts_with($row->image_filename, "/")) {
                $row->image_filename = "/" . $row->image_filename;
            }

            $row->link = "/recipes/recipe/?id=" . $row->id;

            $data[] = $row;
        }

        return $data;
    }

    public static function list_top_recipes(SqlController $conn): array {
        $sel_query = "
        select r.*
            ,co.name as course
            ,cu.name as cuisine
            ,rt.name as type
            ,ch.name as chef
        from Recipe r
        LEFT JOIN Course co
            ON co.id = r.course_id
        LEFT JOIN Cuisine cu
            ON cu.id = r.cuisine_id
        LEFT JOIN RecipeType rt
            ON rt.id = r.type_id
        LEFT JOIN Chef ch
            ON ch.id = r.chef_id
        order by r.name
        ";
        $data = [];

        $result = $conn->query($sel_query);

        while ($row = $result->fetch_object()) {
            $row->published_flag = (bool)$row->published_flag;
            $data[] = $row;
        }

        return $data;
    }

    protected function exists_in_db(): bool {
        $sel_query = "select * from Recipe where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            return false;
        }
        return $result->num_rows > 0;
    }

    public function save_to_db(): bool {
        $save_query = "
        INSERT INTO Recipe(
                           course_id,
                           cuisine_id, 
                           type_id,
                           chef_id,
                           title,
                           instructions,
                           image_filename,
                           serving_count,
                           serving_measure_id,
                           parent_recipe_id,
                           published_flag
        ) VALUES (
                  $this->course_id,
                  $this->cuisine_id,
                  $this->type_id,
                  $this->chef_id,
                  '" . $this->conn->escape_string($this->title) . "'
                  '" . $this->conn->escape_string($this->instructions) . "',
                  '" . $this->conn->escape_string($this->image_filename) . "',
                  $this->serving_count,
                  $this->serving_measure_id,
                  $this->parent_recipe_id,
                  " . ($this->published_flag ? 1 : 0) . "
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE  v
            SET course_id = $this->course_id,
                cuisine_id = $this->cuisine_id,
                type_id = $this->type_id,
                chef_id = $this->chef_id,
                title = '" . $this->conn->escape_string($this->title) . "',
                instructions = '" . $this->conn->escape_string($this->instructions) . "',
                image_filename = '" . $this->conn->escape_string($this->image_filename) . "',
                serving_count = $this->serving_count,
                serving_measure_id = $this->serving_measure_id,
                parent_recipe_id = $this->parent_recipe_id,
                published_flag = " . ($this->published_flag ? 1 : 0) . "
            WHERE id = {$this->id};
            
            SELECT {$this->id} as id;
            ";
        }

        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving Recipe: ' . $this->conn->last_message());
        }

        $row = $updResult->fetch_object();
        $this->id = $row->id;

        return true;
    }

    public function delete_from_db(): bool {
        $delete_query = "
        DELETE FROM Recipe
        WHERE id = {$this->id};
        ";
        $result = $this->conn->query($delete_query);
        if ($result === false) {
            throw new SqlException('Error deleting Recipe: ' . $this->conn->last_message());
        }
        return true;
    }
}
