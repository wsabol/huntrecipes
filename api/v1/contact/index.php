<?php

use HuntRecipes\Endpoint\Common_Endpoint;

require '../../../includes/common.php';

class Contact_Endpoint extends Common_Endpoint {

    public function __construct() {
        // $this->restrict_access();

        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'POST':
                $this->send_message();
                break;

            default:
                $this->method_not_allowed();
        }
    }

    public function send_message() {
        $data = array();
        $code = 400;
        $message = '';

        try {

            $request = json_decode(file_get_contents('php://input'));

            if (!isset($request->name)) {
                throw new Exception("name is not set");
            }

            if (!isset($request->email)) {
                throw new Exception("email is not set");
            }

            if (!isset($request->message)) {
                throw new Exception("message is not set");
            }

            $mailer = new \HuntRecipes\Base\Email_Controller();

            $mailer->add_address($_ENV['EMAIL_CONTACT']);
            $mailer->add_reply_to($request->email);
            $mailer->set_subject("HuntRecipes Support - $request->name - " . date('n/j/Y'));

            // mail body setup
            $mailer->set_view('emails/support-contact.twig');
            $mailer->set_message_context([
                'subject' => $mailer->get_subject(),
                'pre_text' => 'Support message from HuntRecipes',
                'name' => $request->name,
                'email' => $request->email,
                'message' => $request->message
            ]);

            // send
            $mailer->send();

            $message = 'Message sent';
            $code = 200;

        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        echo $this->response($data, $code, $message);
        return true;
    }
}

new Contact_Endpoint();
