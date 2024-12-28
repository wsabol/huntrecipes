<?php

namespace HuntRecipes\User;

use DateTimeImmutable;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\HuntRecipesException;
use HuntRecipes\Exception\SqlException;

class EmailVerification {
    public const DAYS_TO_EXPIRE = 1;
    private SqlController $conn;
    public int $id;
    public int $user_id;
    public string $token;
    public DateTimeImmutable $date_created;
    public bool $is_used;

    public function __construct(SqlController $conn) {
        $this->conn = $conn;
    }

    /**
     * @param string $stoken
     * @param SqlController $conn
     * @return EmailVerification|false
     * @throws SqlException
     */
    public static function from_secure_token(string $stoken, SqlController $conn) {
        $dLogin = explode(";", base64_decode($stoken), 2);
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

        $ev = new self($conn);
        $ev->id = $row->id;
        $ev->user_id = $row->user_id;
        $ev->token = $row->token;
        $ev->date_created = new DateTimeImmutable($row->date_created);
        $ev->is_used = (bool)$row->is_used;

        return $ev;
    }

    /**
     * @param int $user_id
     * @param SqlController $conn
     * @return EmailVerification
     * @throws HuntRecipesException
     * @throws SqlException
     */
    public static function new_token(int $user_id, SqlController $conn): EmailVerification {

        if ($user_id <= 0) {
            throw new HuntRecipesException("Invalid user_id");
        }

        $token = security_token(24);

        $save_query = "
        INSERT INTO EmailVerification(
                        user_id,
                        token
        ) VALUES (
                  {$user_id},
                  '" . $conn->escape_string($token) . "'
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        $updResult = $conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error creating EmailVerification: ' . $conn->last_message());
        }

        $ev = new self($conn);
        $ev->user_id = $user_id;
        $ev->token = $token;
        $ev->is_used = false;
        $ev->date_created = new DateTimeImmutable();

        $row = $updResult->fetch_object();
        $ev->id = $row->id;

        return $ev;
    }

    public static function list_active_tokens_for_user(int $user_id, SqlController $conn): array {
        $created_after = new DateTimeImmutable("-" . self::DAYS_TO_EXPIRE . " days");

        $sel_query = "
        select *
        from EmailVerification
        where is_used = 0
        AND user_id = $user_id
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
        UPDATE EmailVerification
        SET user_id = {$this->user_id},
            token = '" . $this->conn->escape_string($this->token) . "',
            is_used = " . ($this->is_used ? 1 : 0) ."
        WHERE id = {$this->id};
        ";
        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving EmailVerification: ' . $this->conn->last_message());
        }
        return true;
    }

    public function is_expired(): bool {
        $expires = $this->date_created->modify("+" . self::DAYS_TO_EXPIRE . " days");
        return $expires < new DateTimeImmutable();
    }

    public function get_secure_token(): string {
        return base64_encode($this->user_id . ";" . $this->token);
    }
}
