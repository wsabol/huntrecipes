<?php

namespace HuntRecipes;

use HuntRecipes\Base\Common_Object;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\SqlException;

class Chef extends Common_Object {
    private SqlController $conn;
    public int $id;
    public string $name;
    public int $login_id = 0;
    public bool $is_male = false;
    public string $wisdom = '';
    public string $story = '';
    public string $favorite_foods = '';

    public function __construct(int $chef_id, SqlController $conn) {
        $this->id = $chef_id;
        $this->conn = $conn;

        if ($this->id > 0) {
            $this->update_from_db();
        }
    }

    protected function update_from_db(): void {
        $sel_query = "select * from Chef where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if (!!$result) {
            $row = $result->fetch_object();
            $this->name = $row->name;
            $this->login_id = $row->login_id;
            $this->is_male = (bool)$row->male_flag; // todo change in db
            $this->wisdom = $row->wisdom;
            $this->story = $row->story;
            $this->favorite_foods = $row->favorite_cuisine; // todo change this in db
        }
    }

    public static function list(SqlController $conn, array $props): array {
        $sel_query = "
        select r.*
        from Chef r
        order by r.name
        ";
        $data = [];

        $result = $conn->query($sel_query);

        while ($row = $result->fetch_object()) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * @param int $user_id
     * @param SqlController $conn
     * @return Chef|false
     */
    public static function from_user(int $user_id, SqlController $conn) {
        $sel_query = "
        select r.*
        from Chef r
        WHERE login_id = $user_id
        ";
        $result = $conn->query($sel_query);
        if ($result->num_rows === 0) {
            return false;
        }

        $row = $result->fetch_object();
        $chef = new self(0, $conn);
        $chef->id = $row->id;
        $chef->name = $row->name;
        $chef->login_id = $row->id;
        $chef->is_male = (bool)$row->male_flag; // todo change in db
        $chef->wisdom = $row->wisdom;
        $chef->story = $row->story;
        $chef->favorite_foods = $row->favorite_cuisine; // todo change this in db
        return $chef;
    }

    protected function exists_in_db(): bool {
        $sel_query = "select * from Chef where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            return false;
        }
        return $result->num_rows > 0;
    }

    public function save_to_db(): bool {
        $save_query = "
        INSERT INTO Chef(
                         name,
                         login_id,
                         male_flag,
                         wisdom,
                         story,
                         favorite_cuisine
        ) VALUES (
                  '" . $this->conn->escape_string($this->name) . "'
                  $this->login_id,
                  " . (int)$this->is_male . ",
                  '" . $this->conn->escape_string($this->wisdom) . "',
                  '" . $this->conn->escape_string($this->story) . "',
                  '" . $this->conn->escape_string($this->favorite_foods) . "'
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE Chef
            SET name = '" . $this->conn->escape_string($this->name) . "',
                login_id = $this->login_id,
                male_flag = " . (int)$this->is_male . ",
                wisdom = '" . $this->conn->escape_string($this->wisdom) . "',
                story = '" . $this->conn->escape_string($this->story) . "',
                favorite_cuisine = '" . $this->conn->escape_string($this->favorite_foods) . "'
            WHERE id = {$this->id};
            
            SELECT {$this->id} as id;
            ";
        }

        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving Chef: ' . $this->conn->last_message());
        }

        $row = $updResult->fetch_object();
        $this->id = $row->id;

        return true;
    }

    public function delete_from_db(): bool {
        $delete_query = "
        DELETE FROM Chef
        WHERE id = {$this->id};
        ";
        $result = $this->conn->query($delete_query);
        if ($result === false) {
            throw new SqlException('Error deleting Chef: ' . $this->conn->last_message());
        }
        return true;
    }

    public static function chef_of_the_day(SqlController $conn): self {
        $result = $conn->query("SELECT chef_id FROM ChefOfTheDay ORDER BY day DESC LIMIT 1");
        if ($result === false) {
            throw new SqlException("Error getting chef of the day: " . $conn->last_message());
        }
        if ($result->num_rows === 0) {
            throw new SqlException("chef of the day does not exist");
        }

        $row = $result->fetch_object();
        return new self($row->chef_id, $conn);
    }
}
