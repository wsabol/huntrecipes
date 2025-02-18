<?php

namespace HuntRecipes;

use HuntRecipes\Base\Common_Object;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\SqlException;

class Ingredient extends Common_Object {
    private SqlController $conn;
    public int $id;
    public string $name;
    public string $name_plural;

    public function __construct(int $ingredient_id, SqlController $conn) {
        $this->id = $ingredient_id;
        $this->conn = $conn;

        if ($this->id > 0) {
            $this->update_from_db();
        }
    }

    protected function update_from_db(): void {
        $sel_query = "select * from Ingredient where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if (!!$result) {
            $row = $result->fetch_object();
            $this->name = $row->name;
            $this->name_plural = $row->name_plural;
        }
    }

    protected function exists_in_db(): bool {
        $sel_query = "select * from Ingredient where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            return false;
        }
        return $result->num_rows > 0;
    }

    public function save_to_db(): bool {
        if ($this->id === 0) {
            $sql_name = $this->conn->escape_string($this->name);
            $sql_name_plural = $this->conn->escape_string($this->name_plural);

            $dup_check = "select * from Ingredient where name = '$sql_name' OR name_plural = '$sql_name_plural'";
            $result = $this->conn->query($dup_check);
            if ($result !== false) {
                if ($result->num_rows > 0) {
                    $dup = $result->fetch_object();
                    $this->id = $dup->id;
                }
            }
        }

        $save_query = "
        INSERT INTO Ingredient(
                           name,
                           name_plural
        ) VALUES (
                  '" . $this->conn->escape_string(strtolower($this->name)) . "',
                  '" . $this->conn->escape_string(strtolower($this->name_plural)) . "'
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE Ingredient
            SET name = '" . $this->conn->escape_string($this->name) . "',
                name_plural = '" . $this->conn->escape_string($this->name_plural) . "'
            WHERE id = {$this->id};
            
            SELECT {$this->id} as id;
            ";
        }

        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving Ingredient: ' . $this->conn->last_message());
        }

        $row = $updResult->fetch_object();
        $this->id = $row->id;

        return true;
    }

    public function delete_from_db(): bool {
        $delete_query = "
        DELETE FROM Ingredient
        WHERE id = {$this->id};
        ";
        $result = $this->conn->query($delete_query);
        if ($result === false) {
            throw new SqlException('Error deleting Ingredient: ' . $this->conn->last_message());
        }
        return true;
    }

    public static function list(SqlController $conn, array $props): array {
        $props = (object)$props;

        $sel_query = "
        select i.id, i.name, i.name_plural
        from Ingredient i
        order by i.name
        ";
        // echo $sel_query;
        $data = [];

        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting ingredients: " . $conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            $data[] = $row;
        }

        return $data;
    }

    public static function prep_list(SqlController $conn, array $props): array {
        $props = (object)$props;

        $sel_query = "
        select distinct ingredient_prep as name
        from RecipeIngredient i
        WHERE ingredient_prep <> ''
        order by i.ingredient_prep
        ";
        // echo $sel_query;
        $data = [];

        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting ingredient prep: " . $conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            $data[] = $row;
        }

        return $data;
    }

    public static function top(SqlController $conn, array $props): array {
        $props = (object)$props;

        $sel_query = "
        select i.name, ROUND(count(1) / 10) as order_by
        from Ingredient i
        JOIN RecipeIngredient ri
        ON ri.ingredient_id = i.id
        WHERE i.name not in ('salt')
        group by i.name
        HAVING count(1) > 1
        ORDER BY round(count(1) / 10) DESC, i.name
        ";
        // echo $sel_query;
        $data = [];

        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting ingredients: " . $conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            $data[] = (object)[
                'name' => $row->name,
            ];
        }

        return $data;
    }

    public static function create_from_name(string $name, SqlController $conn): self {
        $name = strtolower($name);

        $sel_query = "
        select * from Ingredient
        where name = '" . $conn->escape_string($name) . "'
            OR name_plural = '" . $conn->escape_string($name) . "'
        ";

        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException('Error creating Ingredient from name: ' . $conn->last_message());
        }

        if ($result->num_rows === 0) {
            $i = new Ingredient(0, $conn);
            $i->name = $name;
            $i->name_plural = $name;
            $i->save_to_db();
            return $i;
        }

        $row = $result->fetch_object();
        $ingredient = new Ingredient(0, $conn);
        $ingredient->id = $row->id;
        $ingredient->name = $row->name;
        $ingredient->name_plural = $row->name_plural;

        return $ingredient;
    }
}
