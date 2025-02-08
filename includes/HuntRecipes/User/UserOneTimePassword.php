<?php

namespace HuntRecipes\User;

use DateTimeImmutable;
use HuntRecipes\Base\Common_Object;
use HuntRecipes\Base\Email_Controller;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\SqlException;
use Throwable;

class UserOneTimePassword extends Common_Object {
    private SqlController $conn;
    public int $id;
    public int $user_id;
    private int $code;
    public bool $is_enabled;
    public ?DateTimeImmutable $date_used = null;
    public DateTimeImmutable $expires;

    public function __construct(int $id, SqlController $conn) {
        $this->id = $id;
        $this->conn = $conn;

        if ($this->id > 0) {
            $this->update_from_db();
        }
    }

    protected function exists_in_db(): bool {
        $sel_query = "select * from UserOneTimePassword where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if ($result === false) {
            return false;
        }
        return $result->num_rows > 0;
    }

    protected function update_from_db(): void {
        $sel_query = "select * from UserOneTimePassword where id = {$this->id}";
        $result = $this->conn->query($sel_query);
        if (!!$result) {
            $row = $result->fetch_object();
            $this->user_id = $row->user_id;
            $this->code = $row->code;
            $this->is_enabled = (bool)$row->is_enabled;
            $this->date_used = empty($row->date_used) ? null : new DateTimeImmutable($row->expires);
            $this->expires = new DateTimeImmutable($row->expires);
        }
    }

    public function save_to_db(): bool {
        $save_query = "
        INSERT INTO UserOneTimePassword(
                                        user_id,
                                        code,
                                        is_enabled,
                                        date_used,
                                        expires
        )
        VALUES(
               {$this->user_id},
               {$this->code},
               " . ($this->is_enabled ? 1 : 0) .",
               " . (empty($this->date_used) ? "NULL" : ("'" . $this->date_used->format("Y-m-d H:i:s") . "'")) . ",
               '" . $this->expires->format("Y-m-d H:i:s") . "'
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE UserOneTimePassword
            SET user_id = {$this->user_id},
                code = {$this->code},
                is_enabled = " . ($this->is_enabled ? 1 : 0) .",
                date_used = " . (empty($this->date_used) ? "NULL" : ("'" . $this->date_used->format("Y-m-d H:i:s") . "'")) . ",
                expires = '" . $this->expires->format("Y-m-d H:i:s") . "'
            WHERE id = {$this->id};
            
            SELECT {$this->id} as id;
            ";
        }

        $updResult = $this->conn->query($save_query);
        if ($updResult === false) {
            throw new SqlException('Error saving UserOneTimePassword: ' . $this->conn->last_message());
        }

        $row = $updResult->fetch_object();
        $this->id = $row->id;

        return true;
    }

    public function delete_from_db(): bool {
        trigger_error("do not delete these");
        return false;
    }

    public function is_used(): bool {
        return !empty($this->date_used);
    }

    public function is_expired(): bool {
        return $this->expires < new DateTimeImmutable();
    }

    public function send_code_to_user(string $email): void {
        $this->save_to_db();

        $mailer = new Email_Controller();
        $mailer->add_address($email);
        $mailer->set_subject("Your one-time passcode for HuntRecipes");

        // mail body setup
        $mailer->set_view('emails/one-time-password.twig');
        $mailer->set_message_context([
            'subject' => $mailer->get_subject(),
            'pre_text' => 'Here’s your one-time passcode for Disney+',
            'code' => $this->code
        ]);

        // send
        $mailer->send();
    }

    public static function list(SqlController $conn, array $props): array {
        trigger_error("do not list these");
        return [];
    }

    public static function code(): int {
        try {
            return random_int(100000, 999999);
        } catch (Throwable) {
            return mt_rand(100000, 999999);
        }
    }

    public static function create_new_for_user(User $user, SqlController $conn): self {
        $code = self::code();

        $otp = new self(0, $conn);
        $otp->code = $code;
        $otp->user_id = $user->id;
        $otp->is_enabled = true;
        $otp->expires = new DateTimeImmutable("now +15 minutes");
        $otp->save_to_db();
        return $otp;
    }

    /**
     * @param int $user_id
     * @param int $code
     * @param SqlController $conn
     * @return self|false
     * @throws SqlException
     */
    public static function from_code(int $user_id, int $code, SqlController $conn): self|false {
        $sel_query = "
        SELECT id 
        FROM UserOneTimePassword
        WHERE user_id = {$user_id}
        AND code = {$code};
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
