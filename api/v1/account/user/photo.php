<?php

use HuntRecipes\Endpoint\FileUploadController;
use HuntRecipes\Database\SqlController;
use HuntRecipes\Endpoint\Common_Endpoint;
use HuntRecipes\User\SessionController;
use HuntRecipes\User\User;

require '../../../../includes/common.php';

class Account_User_Photo_Endpoint extends Common_Endpoint {

    public function __construct() {
        $this->restrict_access();

        ini_set('upload_max_filesize', '40M');
        ini_set('post_max_size', '42M');

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->set_profile_picture();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    private function set_profile_picture(): bool {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = (object)$_POST;

            if (!isset($request->current_user_id)) {
                throw new Exception("current_user_id is not set");
            }

            if (!isset($_FILES['profile_photo'])) {
                throw new Exception("profile_photo is not set");
            }

            $user_id = (int)$request->current_user_id;
            $conn = new SqlController();
            $user = new User($user_id, $conn);

            if (!$user->is_enabled()) {
                throw new Exception("Account is not enabled");
            }

            $uploader = new FileUploadController('profile_photo');

            // Check if it's an image or audio file
            if (!$uploader->is_valid()) {
                throw new Exception("Error handling profile image: " . $uploader->get_error());
            }
            // Check if it's an image or audio file
            if (!$uploader->is_image()) {
                throw new Exception("Error handling profile image: Only image files are allowed");
            }

            // Move file to permanent location
            $profile_photo = $uploader->move(User::IMAGES_DIR);

            $user->profile_picture = "/" . $profile_photo;
            $user->save_to_db();

            // update session
            $sess = new SessionController();
            $sess->start();
            if ($sess->has_user()) {
                if ($sess->user()->id === $user->id) {
                    $sess->set_user($user);
                }
            }
            $sess->close();

            $data = $user->toObject();
            $message = "Successfully set new profile image";
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Account_User_Photo_Endpoint();
