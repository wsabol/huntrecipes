<?php

namespace HuntRecipes\User;

use DateTimeImmutable;
use HuntRecipes\Base\Common_Object;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\SqlException;

class User extends Common_Object {
    private SqlController $conn;
    public int $id;
    public string $username;
    public string $name;
    public string $email;
    public int $account_status_id;
    public string $profile_picture;
    public int $chef_app_pending;
    public bool $is_chef;
    public bool $is_developer;
    public bool $is_email_verified;

    private string $password;

    public function __construct(int $user_id, ?SqlController $conn = null) {
        $this->id = $user_id;
        $this->conn = $conn;

        if ($this->id > 0) {
            $this->update_from_db();
        }
    }

    protected function update_from_db(): void {
        $sel_query = "select * from User where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if (!!$result) {
            $row = $result->fetch_object();
            $this->username = $row->username;
            $this->password = $row->password;
            $this->name = $row->name;
            $this->email = $row->email;
            $this->account_status_id = $row->account_status_id;
            $this->profile_picture = $row->profile_picture;
            $this->chef_app_pending = $row->chef_app_pending;
            $this->is_chef = (bool)$row->is_chef;
            $this->is_developer = (bool)$row->is_developer;
            $this->is_email_verified = (bool)$row->is_email_verified;

            if (!str_starts_with($this->profile_picture, "/")) {
                $this->profile_picture = "/$this->profile_picture";
            }
        }
    }

    public static function list(SqlController $conn, array $props): array {
        $sel_query = "
        select u.*, s.name as account_status
        from User u
        JOIN UserAccountStatus s
        ON s.id = u.account_status_id
        where account_status_id = 1
        order by u.name
        ";
        $data = [];

        $result = $conn->query($sel_query);

        while ($row = $result->fetch_object()) {
            unset($row->password);
            $data[] = $row;
        }
        return $data;
    }

    /**
     * @return bool
     */
    public function is_enabled(): bool {
        return $this->account_status_id === 1;
    }

    protected function exists_in_db(): bool {
        $sel_query = "select * from User where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            return false;
        }
        return $result->num_rows > 0;
    }

    public function save_to_db(): bool {
        $save_query = "
        INSERT INTO User(
                        username,
                        name,
                        email,
                         account_status_id,
                         profile_picture,
                         chef_app_pending,
                         is_chef,
                        is_developer,
                        is_email_verified
        ) VALUES (
                  '{$this->username}',
                  '" . $this->conn->escape_string($this->name) . "',
                  '{$this->email}',
                  {$this->account_status_id},
                  '{$this->profile_picture}',
                  {$this->chef_app_pending},
                  " . ($this->is_chef ? 1 : 0) .",
                  " . ($this->is_developer ? 1 : 0) .",
                  " . ($this->is_email_verified ? 1 : 0) ."
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE User
            SET username = '{$this->username}',
                name = '" . $this->conn->escape_string($this->name) . "',
                email = '{$this->email}',
                account_status_id = {$this->account_status_id},
                profile_picture = '{$this->profile_picture}',
                chef_app_pending = {$this->chef_app_pending},
                is_chef = " . ($this->is_chef ? 1 : 0) .",
                is_developer = " . ($this->is_developer ? 1 : 0) .",
                is_email_verified = " . ($this->is_email_verified ? 1 : 0) ."
            WHERE id = {$this->id};
            
            SELECT {$this->id} as id;
            ";
        }

        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving User: ' . $this->conn->last_message());
        }

        $row = $updResult->fetch_object();
        $this->id = $row->id;

        return true;
    }

    public function delete_from_db(): bool {
        $delete_query = "
        DELETE FROM User
        WHERE id = {$this->id};
        ";
        $result = $this->conn->query($delete_query);
        if ($result === false) {
            throw new SqlException('Error deleting User: ' . $this->conn->last_message());
        }
        return true;
    }

    /**
     * @return string
     */
    public function get_password(): string {
        return $this->password;
    }

    /**
     * @return false|User
     * @throws SqlException
     */
    public static function create_from_username(SqlController $conn, string $username) {
        $sel_query = "
        select id
        from User
        where username = '" . $conn->escape_string($username) . "'
        ";
        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException('Error looking up User: ' . $conn->last_message());
        }

        if ($result->num_rows === 0) {
            return false;
        }

        $row = $result->fetch_object();
        return new self($row->id, $conn);
    }
}
