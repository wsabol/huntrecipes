<?php

namespace HuntRecipes\Database;

use HuntRecipes\Exception\SqlException;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;

/**
 * HuntRecipes\Database\Mysql
 *
 * @package     HuntRecipes\Database
 */
class SqlController {

    /**
     * MySQL Database Resource object
     *
     * @var mysqli
     */
    public mysqli $db;

    public function __construct() {
        $database = "saboldru_recipes";
        $port = ini_get("mysqli.default_port");
        
        $this->db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $database, $port);

        if ($this->db->connect_errno) {
            throw new SqlException(printf("Connect failed: %s\n", $this->db->connect_error));
        }
    }

    /**
     * Returns last error and warning message about the last MySQL operation performed by this object
     *
     * @return string
     */
    public function last_message(): string {
        return $this->db->error;
    }

    /**
     * Prepares and executes a query or parameterized query
     *
     * triggers error if query string is empty. Logs query and failure errors when enabled
     *
     * @param string $sql_query_string The string that defines the query to be prepared and executed.
     * @return bool|mysqli_result
     */
    public function query(string $sql_query_string) {
        @$this->clean();

        try {
            $success = $this->db->multi_query($sql_query_string);
            if (!$success) {
                return false;
            }
        } catch (mysqli_sql_exception) {
            return false;
        }

        $results = [];
        $results[] = $this->db->store_result();

        while ($this->db->next_result()) {
            $results[] = $this->db->store_result();
        }

        $results = array_values(array_filter($results, fn($result) => $result !== false));

        if (empty($results)) {
            return true;
        }
        return $results[0];
    }

    public function __destruct() {
        if (isset($this->db)) {
            $this->db->close();
        }
    }

    public function clean(): void {
        do {
            $this->db->use_result();
        } while ($this->db->next_result());
    }

    function escape_string($str): string {
        return $this->db->real_escape_string($str);
    }
}
