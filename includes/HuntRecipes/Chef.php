<?php

namespace HuntRecipes;

use DateTimeImmutable;
use HuntRecipes\Base\Common_Object;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\SqlException;
use HuntRecipes\User\User;

class Chef extends Common_Object {
    private SqlController $conn;
    public int $id;
    public string $name;
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
            $this->is_male = (bool)$row->male_flag; // todo change in db
            $this->wisdom = $row->wisdom;
            $this->story = $row->story;
            $this->favorite_foods = $row->favorite_cuisine; // todo change this in db
        }
    }

    public static function list(SqlController $conn, array $props): array {
        $sel_query = "
        select r.*, CASE WHEN EXISTS(SELECT * FROM User u where u.chef_id = r.id) THEN 1 ELSE 0 END is_linked_to_user
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

    public static function set_new_chef_of_the_day(DateTimeImmutable $date, SqlController $conn) {
        $random_chef = "
        SELECT id
        FROM Chef
        WHERE id NOT IN (
            SELECT x.chef_id
            FROM ChefOfTheDay x
            WHERE x.day >= DATE_ADD('" . $date->format("Y-m-d") . "', INTERVAL -3 DAY)
        )
        ORDER BY RAND()
        LIMIT 1
        ";
        $result = $conn->query($random_chef);
        if ($result === false) {
            throw new SqlException("Error getting random chef: " . $conn->last_message());
        }

        $chef_id = $result->fetch_object()->id;

        $new_query = "
        INSERT INTO ChefOfTheDay (
                                    day,
                                    chef_id
        )
        VALUES (
                '" . $date->format("Y-m-d") . "',
                $chef_id
        );
        ";
        $result = $conn->query($new_query);
        if ($result === false) {
            throw new SqlException("Error setting chef of the day: " . $conn->last_message());
        }

        $archive_query = "
        DELETE FROM ChefOfTheDay
        WHERE day < DATE_ADD(CURDATE(), INTERVAL -30 DAY);
        ";
        $result = $conn->query($archive_query);
        if ($result === false) {
            throw new SqlException("Error archiving chef of the day: " . $conn->last_message());
        }
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
                         male_flag,
                         wisdom,
                         story,
                         favorite_cuisine
        ) VALUES (
                  '" . $this->conn->escape_string($this->name) . "',
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

    /**
     * @return object[]
     * @throws SqlException
     */
    public function get_recipes(int $current_user_id = 0, bool $included_drafts = false): array {
        $recipes = [];

        $sel_query = "
        SELECT
            r.id,
            IFNULL((
                SELECT count(1)
                FROM UserRecipeFavorite u
                WHERE u.recipe_id = r.id
            ), 0) as likes_count
        FROM Recipe r
        WHERE " . ($included_drafts ? "1 = 1" : "r.published_flag = 1") . "
        AND r.chef_id = {$this->id}
        ";

        $result = $this->conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting chef's recipes: " . $this->conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            $recipe = new Recipe($row->id, $this->conn);

            $data = $recipe->toObject();
            $data->is_liked = false;
            if ($current_user_id > 0) {
                $data->is_liked = $recipe->is_liked($current_user_id);
            }

            $data->likes_count = $row->likes_count;
            $data->link = $recipe->get_link();

            $recipes[] = $data;
        }

        return $recipes;
    }

    public function get_user(SqlController $conn): false|User {
        $sel_query = "
        select u.id
        from User u
        WHERE chef_id = {$this->id}
        AND u.is_chef = 1
        order by account_status_id, id
        ";

        $result = $conn->query($sel_query);
        if ($result->num_rows === 0) {
            return false;
        }

        $row = $result->fetch_object();
        return new User($row->id, $this->conn);
    }
}
