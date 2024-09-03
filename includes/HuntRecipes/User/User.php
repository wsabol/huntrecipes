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
    public string $title;
    public string $email;
    public bool $is_developer;
    public DateTimeImmutable $birthdate;
    public int $account_status_id;
    public bool $has_gigs_enabled;
    public int $household_id;

    private string $password;

    public function __construct(int $user_id, ?SqlController $conn = null) {
        $this->id = $user_id;

        // if (empty($conn)) {
        //     $this->conn = new SqlController();
        // } else {
            $this->conn = $conn;
        // }

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
            $this->title = $row->title;
            $this->email = $row->email_address;
            $this->is_developer = (bool)$row->is_developer;
            $this->birthdate = new DateTimeImmutable($row->birthdate);
            $this->account_status_id = (int)$row->account_status_id;
            $this->has_gigs_enabled = (bool)$row->has_gigs_enabled;
            $this->household_id = $row->household_id;
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
        INSERT INTO Login(
                        username,
                        name,
                        title, 
                        email_address,
                        is_developer,
                        birthdate,
                        account_status_id,
                        has_gigs_enabled,
                        household_id
        ) VALUES (
                  '{$this->username}',
                  '" . $this->conn->escape_string($this->name) . "',
                  '" . $this->conn->escape_string($this->title) . "',
                  '{$this->email}',
                  " . ($this->is_developer ? 1 : 0) .",
                  '{$this->birthdate->format("Y-m-d")}',
                  {$this->account_status_id},
                  " . ($this->has_gigs_enabled ? 1 : 0) .",
                  {$this->household_id}
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE Login
            SET username = '{$this->username}',
                name = '" . $this->conn->escape_string($this->name) . "',
                title = '" . $this->conn->escape_string($this->title) . "',
                email_address = '{$this->email}',
                is_developer = " . ($this->is_developer ? 1 : 0) .",
                birthdate = '{$this->birthdate->format("Y-m-d")}',
                account_status_id = {$this->account_status_id},
                has_gigs_enabled = " . ($this->has_gigs_enabled ? 1 : 0) .",
                household_id = {$this->household_id}
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
        DELETE FROM Login
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
        select u.id
        from User u
        where username = '{$username}'
        order by u.name
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
