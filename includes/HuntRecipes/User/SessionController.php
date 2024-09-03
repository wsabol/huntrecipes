<?php

namespace HuntRecipes\User;

/**
 * Session controller. Controls $_SESSION status and contents
 */
class SessionController {

    /**
     * Starts session if not already started
     *
     * @return bool started successfully
     */
    public function start(): bool {
        if (session_status() != PHP_SESSION_ACTIVE) {
            return session_start();
        }
        return true;
    }

    /**
     * Returns status of session
     *
     * @return bool true if started
     */
    public function is_started(): bool {
        return session_status() === PHP_SESSION_ACTIVE || isset($_SESSION);
    }

    /**
     * If session has stared, it locks session data.
     *
     * @return bool if locked successfully. false on failure, or if session not started.
     */
    public function close(): bool {
        if (!$this->is_started()) {
            return false;
        }
        return session_write_close();
    }

    /**
     * If session has stared, it empties and destroys the session.
     *
     * @return void
     */
    public function destroy(): void {
        if (!$this->is_started()) {
            return;
        }
        // clear cookies
        setcookie(session_name(), '', time() - 3600);
        // free session variables
        session_unset();
        // kill php session
        session_destroy();
    }

    /**
     * Returns whether the $_SESSION variable has an existing User object set.
     *
     * @return bool
     */
    public function has_user(): bool {
        if (!$this->is_started()) {
            return false;
        }
        if (empty($_SESSION['User'])) {
            return false;
        }
        if (!is_a($_SESSION['User'], User::class)) {
            return false;
        }
        return @$_SESSION['User']->id > 0;
    }

    /**
     * Set the User object on the $_SESSION variable
     *
     * @param User $user
     * @return void
     */
    public function set_user(User $user) {
        if (!$this->is_started()) {
            trigger_error("session is not started", E_USER_ERROR);
        }
        $_SESSION['User'] = $user;
    }

    /**
     * Get the User object on the $_SESSION variable
     *
     * @return User
     */
    public function user(): User {
        if (!$this->has_user()) {
            trigger_error("User does not exist", E_USER_ERROR);
        }
        return $_SESSION['User'];
    }

    /**
     * Set a Location header to redirects user to the login page.
     * If headers are already sent, the php process will die with redirect link.
     *
     * @return void
     */
    public function redirect_to_login(): void {
        $redirect = "/login.php?ref=" . urlencode(@$_SERVER["REQUEST_URI"]);

        if (headers_sent()) {
            die("Redirect failed. <a href='$redirect'>Please click on this link</a>");
        } else {
            http_response_code(302);
            header("Location: $redirect");
            exit();
        }
    }

    /**
     * Returns true if the Session User is enabled.
     *
     * @return bool
     */
    public function is_valid(): bool {
        if (!$this->is_started()) {
            trigger_error("session is not started", E_USER_ERROR);
        }

        if ($this->has_user()) {
            return $this->user()->is_enabled();
        }

        if ($this->has_persistent_cookie()) {
            $this->recreate_from_persistent_cookie();

            if ($this->has_user()) {
                return $this->user()->is_enabled();
            }
        }

        return false;
    }

    public function has_persistent_cookie(): bool {
        return !empty(@$_COOKIE['uname_auth']);
    }

    private function recreate_from_persistent_cookie(): void {
        if (!$this->has_persistent_cookie()) {
            return;
        }

        $auth = new Authenticator();

        if (!$auth->validateLoginCookie(@$_COOKIE['uname_auth'])) {
            return;
        }

        $user_id = $auth->checkCookieLogin($_COOKIE['uname_auth']);
        if (empty($user_id)) {
            return;
        }

        $auth->setPersistentLogin($user_id);
        $this->set_user(new User($user_id));
    }

    /**
     * Starts session and evaluates its contents. If Session does not contain a valid user, it redirects to the login page.
     * @return void
     */
    public function validate(): void {
        if (!$this->is_started()) {
            $this->start();
        }

        if (!$this->is_valid()) {
            $this->logout();
            $this->redirect_to_login();
            return;
        }
        
        $this->close();
    }

    public function logout() {
        if ($this->has_persistent_cookie()) {
            $auth = new Authenticator();
            $auth->deletePersistentLogin($_COOKIE['uname_auth']);
        }
        $this->destroy();
    }

}
