<?php

namespace HuntRecipes;

use HuntRecipes\Base\Common_Object;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\SqlException;

class RecipeType extends Common_Object {
    private SqlController $conn;
    public int $id;
    public string $name;
    public string $icon;

    public function __construct(int $recipe_type_id, SqlController $conn) {
        $this->id = $recipe_type_id;
        $this->conn = $conn;

        if ($this->id > 0) {
            $this->update_from_db();
        }
    }

    protected function update_from_db(): void {
        $sel_query = "select * from RecipeType where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if (!!$result) {
            $row = $result->fetch_object();
            $this->name = $row->name;
            $this->icon = $row->icon;
        }
    }

    public static function list(SqlController $conn, array $props): array {
        $sel_query = "
        select r.*
        from RecipeType r
        order by r.name
        ";
        $data = [];

        $result = $conn->query($sel_query);

        while ($row = $result->fetch_object()) {
            $data[] = $row;
        }

        return $data;
    }

    protected function exists_in_db(): bool {
        $sel_query = "select * from RecipeType where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            return false;
        }
        return $result->num_rows > 0;
    }

    public function save_to_db(): bool {
        $save_query = "
        INSERT INTO RecipeType(
                           name,
                           icon
        ) VALUES (
                  '" . $this->conn->escape_string($this->name) . "'
                  '" . $this->conn->escape_string($this->icon) . "'
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE RecipeType
            SET name = '" . $this->conn->escape_string($this->name) . "',
                icon = '" . $this->conn->escape_string($this->icon) . "'
            WHERE id = {$this->id};
            
            SELECT {$this->id} as id;
            ";
        }

        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving RecipeType: ' . $this->conn->last_message());
        }

        $row = $updResult->fetch_object();
        $this->id = $row->id;

        return true;
    }

    public function delete_from_db(): bool {
        $delete_query = "
        DELETE FROM RecipeType
        WHERE id = {$this->id};
        ";
        $result = $this->conn->query($delete_query);
        if ($result === false) {
            throw new SqlException('Error deleting RecipeType: ' . $this->conn->last_message());
        }
        return true;
    }
}
