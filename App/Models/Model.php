<?php

namespace App\Models;

class Model {

    public function __construct() {
        $this->conn = new \mysqli("localhost", "root", "", "portali");
        if ($this->conn->connect_errno > 0) {
            echo "Gabim gjate konektimit me MySQL: " . $this->conn->connect_error;
            exit();
        }
        $this->conn->set_charset("utf8");
    }
}