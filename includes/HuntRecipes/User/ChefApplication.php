<?php

namespace HuntRecipes\User;

use DateTimeImmutable;
use HuntRecipes\Base\Common_Object;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\SqlException;

class ChefApplication extends Common_Object {
    private SqlController $conn;
    public int $id;
    public int $user_id;
    public int $chef_application_status_id = 0;
    public DateTimeImmutable $date_created;
    public bool $already_exists = false;
    public string $relationship = '';
    public string $story = '';
    public bool $is_deleted = false;

    public function __construct(int $chef_application_id, SqlController $conn) {
        $this->id = $chef_application_id;
        $this->conn = $conn;
        $this->date_created = new DateTimeImmutable('now');

        if ($this->id > 0) {
            $this->update_from_db();
        }
    }

    protected function update_from_db(): void {
        $sel_query = "select * from ChefApplication where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if (!!$result) {
            $row = $result->fetch_object();
            $this->user_id = $row->user_id;
            $this->chef_application_status_id = $row->chef_application_status_id;
            $this->date_created = new DateTimeImmutable($row->date_created);
            $this->already_exists = (bool)$row->already_exists;
            $this->relationship = $row->relationship;
            $this->story = $row->story;
            $this->is_deleted = (bool)$row->is_deleted;
        }
    }

    public static function list(SqlController $conn, array $props): array {
        $status_id = @$props['chef_application_status_id'] ?? 0;
        $user_id = @$props['user_id'] ?? 0;
        $date_from = @$props['date_from'] ?? '';
        $date_to = @$props['date_to'] ?? '';

        $sel_query = "
        select r.*, u.name, u.email, u.is_email_verified
        from ChefApplication r
        JOIN User u
        ON u.id = r.user_id
        WHERE r.is_deleted = 0
        " . ($status_id > 0 ? "AND r.chef_application_status_id = {$status_id}" : '') . "
        " . ($user_id > 0 ? "AND r.user_id = {$user_id}" : '') . "
        " . ($date_from != '' ? "AND CAST(r.date_created AS DATE) >= '{$date_from}'" : '') . "
        " . ($date_to != '' ? "AND CAST(r.date_created AS DATE) <= '{$date_to}'" : '') . "
        order by r.id
        ";
        $data = [];

        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error retrieving Chef Application list: " . $conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            $row->chef_application_status = ChefApplicationStatus::get_name($row->chef_application_status_id);
            $row->relation_name = ChefRelation::get_name($row->relationship);

            $data[] = $row;
        }

        return $data;
    }

    protected function exists_in_db(): bool {
        $sel_query = "select * from ChefApplication where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            return false;
        }
        return $result->num_rows > 0;
    }

    public function save_to_db(): bool {
        $save_query = "
        INSERT INTO ChefApplication(
                         user_id,
                         chef_application_status_id,
                         already_exists,
                         relationship,
                         story,
                        is_deleted
        ) VALUES (
                  $this->user_id,
                  $this->chef_application_status_id,
                  " . (int)$this->already_exists . ",
                  '" . $this->conn->escape_string($this->relationship) . "',
                  '" . $this->conn->escape_string($this->story) . "',
                  " . (int)$this->is_deleted . "
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE ChefApplication
            SET user_id = $this->user_id,
                chef_application_status_id = $this->chef_application_status_id,
                already_exists = " . (int)$this->already_exists . ",
                relationship = '" . $this->conn->escape_string($this->relationship) . "',
                story = '" . $this->conn->escape_string($this->story) . "',
                is_deleted = " . (int)$this->is_deleted . "
            WHERE id = {$this->id};
            
            SELECT {$this->id} as id;
            ";
        }

        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving ChefApplication: ' . $this->conn->last_message());
        }

        $row = $updResult->fetch_object();
        $this->id = $row->id;

        return true;
    }

    public function delete_from_db(): bool {
        $delete_query = "
        DELETE FROM ChefApplication
        WHERE id = {$this->id};
        ";
        $result = $this->conn->query($delete_query);
        if ($result === false) {
            throw new SqlException('Error deleting ChefApplication ' . $this->conn->last_message());
        }
        return true;
    }
}
