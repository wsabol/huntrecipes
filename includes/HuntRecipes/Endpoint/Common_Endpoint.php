<?php

namespace HuntRecipes\Endpoint;

use HuntRecipes\User\SessionController;

/**
 * Common endpoint class to be extended
 */
class Common_Endpoint {
    public function restrict_access(): void {
        $sess = new SessionController();
        $sess->start();
        if (!$sess->is_valid()) {
            echo $this->response([], 401, "Access denied.");
            exit();
        }
    }

    /**
     * A common response.
     *
     * @param mixed $data
     * @param int $code
     * @param string $message
     * @return string
     */
    public function response($data = array(), int $code = 400, string $message = ''): string {
        http_response_code($code);

        $response = array(
            'message' => $message,
            'response_code' => $code,
            'data' => $data,
        );

        $json = json_encode($response);
        if ($json === false) {
            return $this->response([], 400, json_last_error_msg());
        }

        return $json;
    }

    public function method_not_allowed(): string {
        return $this->response([], 405, "Method not allowed.");
    }
}
