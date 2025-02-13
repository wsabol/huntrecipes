<?php

namespace HuntRecipes\User;

use DateTimeImmutable;
use HuntRecipes\Base\Common_Object;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\HuntRecipesException;
use HuntRecipes\Exception\SqlException;

class EmailVerification extends Common_Object {
    public const DAYS_TO_EXPIRE = 1;
    public int $id;
    public int $user_id;
    public string $email;
    public string $token;
    public DateTimeImmutable $date_created;
    public bool $is_used;

    private SqlController $conn;

    public function __construct(int $id, SqlController $conn) {
        $this->id = $id;
        $this->conn = $conn;
        $this->token = security_token(24);
        $this->date_created = new DateTimeImmutable();
        $this->is_used = false;
    }

    protected function exists_in_db(): bool {
        $sel_query = "select * from EmailVerification where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            return false;
        }
        return $result->num_rows > 0;
    }

    protected function update_from_db(): void {
        $sel_query = "select * from EmailVerification where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if (!!$result) {
            $row = $result->fetch_object();
            $this->user_id = $row->user_id;
            $this->email = $row->email;
            $this->token = $row->token;
            $this->date_created = new DateTimeImmutable($row->date_created);
            $this->is_used = (bool)$row->is_used;
        }
    }

    public function delete_from_db(): bool {
        return false;
    }

    public static function list(SqlController $conn, array $props): array {
        $user_id = @$props["user_id"] ?? 0;
        $email = $conn->escape_string(@$props["email"] ?? '');
        $is_used = (int)@$props["is_used"] ?? -1;

        $sel_query = "
        select r.*
        from EmailVerification r
        WHERE 1 = 1
        " . ($user_id ? "AND r.user_id = {$user_id} \n" : "")
            . ($email ? "AND r.email = '{$email}' \n" : "")
            . ($is_used > -1 ? "AND r.is_used = {$is_used} \n" : "");

        $data = [];

        $result = $conn->query($sel_query);

        while ($row = $result->fetch_object()) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * @param string $hashed_token
     * @param SqlController $conn
     * @return EmailVerification|false
     * @throws SqlException
     */
    public static function from_hashed_token(string $hashed_token, SqlController $conn): false|self {
        $dLogin = explode(";", base64_decode($hashed_token), 2);
        $user_id = (int)@$dLogin[0];
        $token = @$dLogin[1];

        if (empty($user_id) || empty($token)) {
            return false;
        }

        $sel_query = "
        SELECT *
        FROM EmailVerification
        WHERE user_id = $user_id
        AND token = '" . $conn->escape_string($token) . "'
        ";
        $result = $conn->query($sel_query);
        if (!$result) {
            throw new SqlException("Error getting email verification: " . $conn->last_message());
        }
        if ($result->num_rows === 0) {
            return false;
        }

        $row = $result->fetch_object();

        $ev = new self(0, $conn);
        $ev->id = $row->id;
        $ev->user_id = $row->user_id;
        $ev->email = $row->email;
        $ev->token = $row->token;
        $ev->date_created = new DateTimeImmutable($row->date_created);
        $ev->is_used = (bool)$row->is_used;

        return $ev;
    }

    public static function list_active_tokens_for_user(int $user_id, string $email, SqlController $conn): array {
        $created_after = new DateTimeImmutable("-" . self::DAYS_TO_EXPIRE . " days");

        $email = $conn->escape_string($email);

        $sel_query = "
        select *
        from EmailVerification
        where is_used = 0
        AND user_id = $user_id
        AND email = '$email'
        AND date_created >= '" . $created_after->format("Y-m-d H:i:s") . "'
        ";
        $data = [];
        // echo $sel_query;
        $result = $conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting active tokens for user: " . $conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            $data[] = $row;
        }
        return $data;
    }

    public function save_to_db(): bool {
        $save_query = "
        INSERT INTO EmailVerification(
                        user_id,
                                      email,
                                      token,
                                      date_created,
                                      is_used
        ) VALUES (
                  {$this->user_id},
                  '" . $this->conn->escape_string($this->email) . "',
                  '" . $this->conn->escape_string($this->token) . "',
                  '" . $this->date_created->format("Y-m-d H:i:s") . "',
                  " . ($this->is_used ? 1 : 0) ."
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE EmailVerification
            SET user_id = {$this->user_id},
                email = '" . $this->conn->escape_string($this->email) . "',
                token = '" . $this->conn->escape_string($this->token) . "',
                is_used = " . ($this->is_used ? 1 : 0) ."
            WHERE id = {$this->id};
            
            SELECT {$this->id} as id;
            ";
        }

        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving EmailVerification: ' . $this->conn->last_message());
        }

        $row = $updResult->fetch_object();
        $this->id = $row->id;

        return true;
    }

    public function is_expired(): bool {
        $expires = $this->date_created->modify("+" . self::DAYS_TO_EXPIRE . " days");
        return $expires < new DateTimeImmutable();
    }

    public function get_hashed_token(): string {
        return base64_encode($this->user_id . ";" . $this->token);
    }
}
