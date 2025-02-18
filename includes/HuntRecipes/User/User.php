<?php

namespace HuntRecipes\User;

use DateTimeImmutable;
use HuntRecipes\Base\Common_Object;
use HuntRecipes\Base\Email_Controller;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Exception\SqlException;
use HuntRecipes\Recipe;

class User extends Common_Object {
    public const IMAGES_DIR = 'assets/images/users';

    private SqlController $conn;
    public int $id;
    public string $name;
    public string $email;
    public int $account_status_id;
    public string $profile_picture;
    public int $chef_application_id = 0;
    public int $chef_id = 0;
    public bool $is_chef = false;
    public bool $is_developer = false;
    public bool $is_email_verified = false;
    public DateTimeImmutable $date_created;

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
            $this->password = $row->password;
            $this->name = $row->name;
            $this->email = $row->email;
            $this->account_status_id = $row->account_status_id;
            $this->profile_picture = $row->profile_picture;
            $this->chef_application_id = $row->chef_application_id;
            $this->chef_id = $row->chef_id;
            $this->is_chef = (bool)$row->is_chef;
            $this->is_developer = (bool)$row->is_developer;
            $this->is_email_verified = (bool)$row->is_email_verified;
            $this->date_created = new DateTimeImmutable($row->date_created);

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
                        name,
                        email,
                         password,
                         account_status_id,
                         profile_picture,
                         chef_application_id,
                         chef_id,
                         is_chef,
                        is_developer,
                        is_email_verified
        ) VALUES (
                  '" . $this->conn->escape_string($this->name) . "',
                  '" . $this->conn->escape_string($this->email) . "',
                  '" . $this->conn->escape_string($this->password) . "',
                  {$this->account_status_id},
                  '{$this->profile_picture}',
                  {$this->chef_application_id},
                  {$this->chef_id},
                  " . ($this->is_chef ? 1 : 0) .",
                  " . ($this->is_developer ? 1 : 0) .",
                  " . ($this->is_email_verified ? 1 : 0) ."
        );
        
        SELECT LAST_INSERT_ID() as id;
        ";

        if ($this->exists_in_db()) {
            $save_query = "
            UPDATE User
            SET name = '" . $this->conn->escape_string($this->name) . "',
                email = '" . $this->conn->escape_string($this->email) . "',
                account_status_id = {$this->account_status_id},
                profile_picture = '{$this->profile_picture}',
                chef_application_id = {$this->chef_application_id},
                chef_id = {$this->chef_id},
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
     * @param SqlController $conn
     * @param string $email
     * @return false|User
     * @throws SqlException
     */
    public static function create_from_email(SqlController $conn, string $email): self|false {
        $sel_query = "
        select id
        from User
        where email = '" . $conn->escape_string($email) . "'
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

    public function get_favorites(): array {
        $favorites = [];

        $sel_query = "
        SELECT
            r.id,
            IFNULL((
                SELECT count(1)
                FROM UserRecipeFavorite u
                WHERE u.recipe_id = r.id
            ), 0) as likes_count
        FROM Recipe r
        JOIN UserRecipeFavorite urf
        ON urf.recipe_id = r.id
        AND urf.user_id = $this->id
        WHERE r.published_flag = 1
        ";

        $result = $this->conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error getting user favorites: " . $this->conn->last_message());
        }

        while ($row = $result->fetch_object()) {
            $recipe = new Recipe($row->id, $this->conn);

            $data = $recipe->toObject();
            $data->is_liked = true;
            $data->likes_count = $row->likes_count;
            $data->link = $recipe->get_link();

            $favorites[] = $data;
        }

        return $favorites;
    }

    public function is_safe_to_change_email_to(string $email): bool {
        $sel_query = "
        SELECT *
        FROM User
        WHERE email = '" . $this->conn->escape_string($email) . "'
        AND id <> $this->id
        ";

        $result = $this->conn->query($sel_query);
        if ($result === false) {
            throw new SqlException("Error running email check: " . $this->conn->last_message());
        }

        return $result->num_rows === 0;
    }

    public function set_password(string $password) {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    public function send_email_verification(): void {
        $ev = new EmailVerification(0, $this->conn);
        $ev->user_id = $this->id;
        $ev->email = $this->email;
        $ev->save_to_db();

        $mailer = new Email_Controller();
        $mailer->add_address($this->email);
        $mailer->set_subject("New HuntRecipes Email Verification");

        // mail body setup
        $mailer->set_view('emails/email-verification.twig');
        $mailer->set_message_context([
            'subject' => $mailer->get_subject(),
            'pre_text' => 'Please Verify Your Account',
            'hashed_token' => $ev->get_hashed_token()
        ]);

        // send
        $mailer->send();
    }

    public function send_reset_password(): void {
        $rp = new ResetPasswordAuth(0, $this->conn);
        $rp->user_id = $this->id;
        $rp->save_to_db();

        $mailer = new Email_Controller();
        $mailer->add_address($this->email);
        $mailer->set_subject("HuntRecipes Reset Password Request");

        // mail body setup
        $mailer->set_view('emails/reset-password.twig');
        $mailer->set_message_context([
            'subject' => $mailer->get_subject(),
            'pre_text' => 'We sent you are link to reset your password',
            'hashed_token' => $rp->get_hashed_token()
        ]);

        // send
        $mailer->send();
    }

    public function send_reset_password_confirmation(): void {
        $mailer = new Email_Controller();
        $mailer->add_address($this->email);
        $mailer->set_subject("Your Password Has Been Changed");

        // mail body setup
        $mailer->set_view('emails/reset-password-confirmation.twig');
        $mailer->set_message_context([
            'subject' => $mailer->get_subject(),
            'pre_text' => 'Your HuntRecipes Password Has Been Changed',
            'date_changed' => date('Y-m-d H:i:s')
        ]);

        // send
        $mailer->send();
    }

    public function has_open_email_verification(): bool {
        return !empty(EmailVerification::list_active_tokens_for_user($this->id, $this->email, $this->conn));
    }

    public function send_chef_application_notification(bool $approved): void {
        $mailer = new Email_Controller();
        $mailer->add_address($this->email);
        $mailer->set_subject("An update on your HuntRecipes Chef Application");

        // mail body setup
        if ($approved) {
            $mailer->set_view('emails/chef-app-approval.twig');
        } else {
            $mailer->set_view('emails/chef-app-denial.twig');
        }

        $mailer->set_message_context([
            'subject' => $mailer->get_subject(),
            'pre_text' => 'Your HuntRecipes Password Has Been Changed'
        ]);

        // send
        $mailer->send();
    }
}
