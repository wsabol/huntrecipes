<?php

namespace HuntRecipes\User;

use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\SqlException;

class Authenticator {
    public const DAYS_LOGIN_PERSISTS = 30;
    public SqlController $conn;

    public function __construct() {
        $this->conn = new SqlController();
    }

    public function checkCookieLogin(string $uname_token): int {
        $tokenData = explode(":", $uname_token, 2);
        // wla($tokenData);
        // exit;
        if (count($tokenData) == 2) {
            $qValidator = "
            SELECT ls.login_id, hashed_validator
            FROM LoginSession ls
            JOIN User l
            ON l.id = ls.login_id
            AND l.account_status_id <> 2
            AND ls.disabled_flag = 0
            WHERE selector = '" . $this->conn->escape_string($tokenData[0]) . "'
            AND expires > NOW();
            ";
            $result = $this->conn->query($qValidator);

            $login_id = 0;
            while ($row = $result->fetch_assoc()) {
                $row['hashed_validator'] = trim(@$row['hashed_validator']);

                if (password_verify($tokenData[1], $row['hashed_validator'])) {
                    $login_id = (int)$row['login_id'];
                    break;
                }
            }
            return $login_id;
        }
        return 0;
    }

    public function deletePersistentLogin(string $uname_token) {
        $tokenData = explode(":", $uname_token, 2);
        // bad uname_auth cookie
        setcookie("uname_auth", '', time() - 3600, '/', "", true, true);
        if (!empty(@$tokenData[0])) {
            $sql = "
                  UPDATE LoginSession
                  SET disabled_flag = 1
                  WHERE selector = '" . $this->conn->escape_string($tokenData[0]) . "';
                ";
            $this->conn->query($sql);
        }
    }

    public static function validateLoginCookie(?string $uname_token): bool {
        if (empty($uname_token)) {
            return false;
        }
        $tokenData = explode(":", $uname_token);
        return count($tokenData) === 2;
    }

    public function setPersistentLogin(int $login_id) {
        $validator = security_token();
        $expires = time() + 3600 * 24 * self::DAYS_LOGIN_PERSISTS;

        $uname_token = @$_COOKIE['uname_auth'];
        $selector = security_token(16);
        if (!empty($uname_token)) {
            $selector = explode(":", $uname_token, 2)[0];
        }

        $new_uname_token = $selector . ":" . $validator;

        setcookie("uname_auth", $new_uname_token, $expires, '/', "", true, true);

        $this->update_login_token($login_id, $selector, $validator, $expires);
    }

    /**
     * @param int $login_id
     * @param string $selector
     * @param string $validator
     * @param string $expires
     * @return void
     * @throws SqlException
     */
    private function update_login_token(int $login_id, string $selector, string $validator, string $expires): void {
        $hashed = password_hash($validator, PASSWORD_BCRYPT);
        $sql_expires = date('Y-m-d H:i:s', $expires);

        $save_query = "
        INSERT INTO LoginSession(
                                 login_id,
                                 selector,
                                 hashed_validator,
                                 expires
        )
        VALUES(
               $login_id,
               '$selector',
               '$hashed',
               '$sql_expires'
        )
        ";

        if ($this->login_token_exists($login_id, $selector, $validator)) {
            $save_query = "
            UPDATE LoginSession ls
            SET ls.expires = '$sql_expires'
            WHERE ls.login_id = $login_id
            AND ls.selector = '$selector'
            AND ls.hashed_validator = '$hashed';
            ";
        }

        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving LoginSession: ' . $this->conn->last_message());
        }
    }

    private function login_token_exists(int $login_id, string $selector, string $validator): bool {
        $sel_query = "
        select *
        from LoginSession
        where login_id = {$login_id}
        AND selector = '" . $this->conn->escape_string($selector) . "'
        AND hashed_validator = '" . password_hash($validator, PASSWORD_BCRYPT) . "'
        ";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            return false;
        }
        return $result->num_rows > 0;
    }
}
