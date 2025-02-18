<?php

namespace HuntRecipes;

use HuntRecipes\Base\Common_Object;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\SqlException;
use HuntRecipes\User\SessionController;

class RecipeIngredient extends Common_Object {
    private SqlController $conn;
    public int $id;
    public int $recipe_id;
    public int $ingredient_id;
    public string $ingredient_prep;
    public int $measure_id;
    public float $amount;
    public bool $optional_flag;

    public function __construct(int $recipe_ingredient_id, SqlController $conn) {
        $this->id = $recipe_ingredient_id;
        $this->conn = $conn;

        if ($this->id > 0) {
            $this->update_from_db();
        }
    }

    protected function exists_in_db(): bool {
        $sel_query = "select * from RecipeIngredient where recipe_id = {$this->recipe_id} AND ingredient_id = {$this->ingredient_id}";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            return false;
        }
        if ($result->num_rows === 0) {
            return false;
        }
        $row = $result->fetch_object();
        $this->id = $row->id;
        return true;
    }

    protected function update_from_db(): void {
        $sel_query = "select * from RecipeIngredient where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if (!!$result) {
            $row = $result->fetch_object();
            $this->recipe_id = $row->recipe_id;
            $this->ingredient_id = $row->ingredient_id;
            $this->ingredient_prep = $row->ingredient_prep;
            $this->measure_id = $row->measure_id;
            $this->amount = $row->amount;
            $this->optional_flag = (bool)$row->optional_flag;
        }
    }

    public function save_to_db(): bool {
        $save_query = "
        INSERT INTO RecipeIngredient(
                                     recipe_id,
                                     ingredient_id,
                                     ingredient_prep,
                                     measure_id,
                                     amount,
                                     optional_flag
        )
        VALUES (
                {$this->recipe_id},
                {$this->ingredient_id},
                '" . $this->conn->escape_string(strtolower($this->ingredient_prep)) . "',
                {$this->measure_id},
                {$this->amount},
                " . ($this->optional_flag ? 1 : 0) . "
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE RecipeIngredient
            SET recipe_id = {$this->recipe_id},
                ingredient_id = {$this->ingredient_id},
                ingredient_prep = '" . $this->conn->escape_string(strtolower($this->ingredient_prep)) . "',
                measure_id = {$this->measure_id},
                amount = {$this->amount},
                optional_flag = " . ($this->optional_flag ? 1 : 0) . "
            WHERE id = {$this->id};
            
            SELECT {$this->id} as id;
            ";
        }

        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving RecipeIngredient: ' . $this->conn->last_message() . $save_query);
        }

        $row = $updResult->fetch_object();
        $this->id = $row->id;

        return true;
    }

    public function delete_from_db(): bool {
        $delete_query = "
        DELETE FROM RecipeIngredient
        WHERE id = {$this->id};
        ";
        $result = $this->conn->query($delete_query);
        if ($result === false) {
            throw new SqlException('Error deleting RecipeIngredient: ' . $this->conn->last_message());
        }
        return true;
    }

    public static function list(SqlController $conn, array $props): array {
        $props = (object)$props;

        $recipe_id = @$props->recipe_id ?? 0;

        $sel_query = "
        select ri.*, r.title as recipe_title
        from RecipeIngredient ri
        LEFT JOIN Recipe r
            ON r.id = ri.recipe_id
        WHERE r.published_flag = 1
        AND CASE WHEN $recipe_id = 0 THEN 1 WHEN $recipe_id = r.id THEN 1 ELSE 0 END = 1
        order by r.title
        ";
        // echo $sel_query;
        $data = [];

        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting recipe ingredients: " . $conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            $data[] = $row;
        }

        return $data;
    }

    public static function create(SqlController $conn,
        Recipe $recipe,
        Ingredient $ingredient,
        string $ingredient_prep,
        int $measure_id,
        float $amount,
        bool $optional_flag
    ): self {
        $ri = new self(0, $conn);
        $ri->recipe_id = $recipe->id;
        $ri->ingredient_id = $ingredient->id;
        $ri->ingredient_prep = $ingredient_prep;
        $ri->measure_id = $measure_id;
        $ri->amount = $amount;
        $ri->optional_flag = $optional_flag;
        $ri->exists_in_db();
        return $ri;
    }
}
