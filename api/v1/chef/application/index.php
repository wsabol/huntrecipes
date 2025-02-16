<?php

use HuntRecipes\Chef;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\ChefApplication;
use HuntRecipes\User\ChefApplicationStatus;
use HuntRecipes\User\User;

require '../../../../includes/common.php';

class Account_Chef_Application_Endpoint extends Common_Endpoint {

    public function __construct() {
        $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->save_application();
                break;

            case 'PATCH':
                $this->handle_patch();
                break;

            case 'DELETE':
                $this->withdraw_application();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function save_application(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->user_id)) {
                throw new Exception("chef_id is not set");
            }
            if (!isset($request->already_exists)) {
                throw new Exception("already_exists is not set");
            }
            if (!isset($request->relationship)) {
                throw new Exception("relationship is not set");
            }
            if (!isset($request->story)) {
                throw new Exception("story is not set");
            }

            $conn = new SqlController();

            $user = new User($request->user_id, $conn);
            if (!$user->is_enabled()) {
                throw new Exception("This user is not an eligible to become a chef");
            }
            if ($user->is_chef) {
                throw new Exception("This user is already a chef");
            }
            if (!$user->is_email_verified) {
                throw new Exception("This user has not verified their email");
            }

            // check existing app
            if ($user->chef_application_id > 0) {
                $existing = new ChefApplication($user->chef_application_id, $conn);

                if ($existing->chef_application_status_id === ChefApplicationStatus::DENIED) {
                    throw new Exception("This user is not an eligible to become a chef");
                }
                if ($existing->chef_application_status_id > 0) {
                    throw new Exception("This user already has an existing application");
                }
            }

            $app = new ChefApplication(0, $conn);
            $app->user_id = $request->user_id;
            $app->chef_application_status_id = ChefApplicationStatus::PENDING;
            $app->already_exists = filter_var($request->already_exists, FILTER_VALIDATE_BOOL);
            $app->relationship = $request->relationship;
            $app->story = $request->story;
            $app->save_to_db();

            // save application to user
            $user->is_chef = false;
            $user->chef_application_id = $app->id;
            $user->chef_id = 0;
            $user->save_to_db();

            $message = "Successfully created chef application";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }

    private function withdraw_application(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));
            if (empty($request)) {
                parse_str(file_get_contents('php://input'), $request);
            }
            if (empty($request)) {
                $request = (object)$_REQUEST;
            }

            if (!isset($request->user_id)) {
                throw new Exception("user_id is not set");
            }
            if (!isset($request->chef_application_id)) {
                throw new Exception("chef_application_id is not set");
            }
            $chef_application_id = (int)$request->chef_application_id;

            $conn = new SqlController();

            $user = new User($request->user_id, $conn);
            if (!$user->is_enabled()) {
                throw new Exception("This user is not valid");
            }
            if ($user->is_chef) {
                throw new Exception("This user is already a chef");
            }
            if ($user->chef_application_id === 0) {
                throw new Exception("This user is does not have an pending application");
            }
            if ($user->chef_application_id !== $chef_application_id) {
                throw new Exception("This chef application id in the request does not match the application tied to the user");
            }

            // check existing app
            $app = new ChefApplication($request->chef_application_id, $conn);
            $app->is_deleted = true;
            $app->save_to_db();

            // save application to user
            $user->is_chef = false;
            $user->chef_application_id = 0;
            $user->chef_id = 0;
            $user->save_to_db();

            $message = "Successfully deleted chef application";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }

    private function handle_patch(): void {
        $request = json_decode(file_get_contents('php://input'));

        if (!isset($request->action)) {
            echo $this->response([], 400, "action is not set");
            return;
        }

        match ($request->action) {
            'approve' => $this->set_approval(),
            default => (function($a) {
                echo $this->response([], 400, "action is not recognized: $a");
            })($request->action)
        };
    }

    public function set_approval(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->chef_application_id)) {
                throw new Exception("chef_application_id is not set");
            }
            if (!isset($request->status)) {
                throw new Exception("status is not set");
            }
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);

            $conn = new SqlController();
            $app = new ChefApplication($request->chef_application_id, $conn);

            if (!in_array($app->chef_application_status_id, [ChefApplicationStatus::PENDING, ChefApplicationStatus::NONE])) {
                throw new Exception("Application status is already set");
            }

            $user = new User($app->user_id, $conn);
            if (!$user->is_enabled()) {
                throw new Exception("This user is not valid");
            }
            if ($user->is_chef) {
                throw new Exception("This user is already a chef");
            }

            if (!$status) {
                // deny
                $app->chef_application_status_id = ChefApplicationStatus::DENIED;
                $app->save_to_db();

                $user->send_chef_application_notification(false);

                $code = 200;
                throw new Exception("Denied chef application and notified user");
            }

            // approve

            // create chef
            $chef_id = (int)@$request->chef_id;
            $chef = new Chef($chef_id, $conn);
            if (empty($chef->name)) {
                $chef->name = $user->name;
            }
            $chef->story = $app->story;
            $chef->save_to_db();

            // link to user
            $user->is_chef = true;
            $user->chef_id = $chef->id;
            $user->save_to_db();
            $user->send_chef_application_notification(true);

            $message = "Approved chef application and updated user";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_Chef_Application_Endpoint();
