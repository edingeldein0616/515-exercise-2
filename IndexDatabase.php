<?php
ini_set('display_errors', 1);
error_reporting(E_ERROR & ~E_WARNING);

class IndexDatabase {

    private static $insertCount = 0;
    private static $selectCount = 0;
    public static function getCounts() {
        return 
            "inserts: ". self::$insertCount.
            ", selects: ".self::$selectCount;
    }

    private $errors = [];
    public function errors() {
        return $this->errors;
    }

    private $connection;
    public function connection() {
        return $this->connection;
    }

    function __construct($host, $username, $password, $schema)
    {
        $this->connection = new mysqli($host, $username, $password, $schema);

        if($this->connection->connect_error) {
            die("Database connection failed: " . $this->connection->connect_error);
        }
    }

    function __desctruct() {
        $this->connection->close();
    }

    public function insert($into, $values) {
        $sql = "INSERT INTO $into VALUES $values;";

        if($this->connection->query($sql) === true) {
            IndexDatabase::$insertCount++;
        } else {
            array_push($this->errors, $this->connection->error);
        }
    }

    public function select($columns, $from, $where = '') {
        $sql = "SELECT $columns FROM $from";

        if($where !== '') {
            $sql .= " WHERE $where";
        }

        $sql .= ";";

        $result = $this->connection->query($sql);

        if($this->connection->error !== '') {
            array_push($this->errors, $this->connection->error);
        } 

        self::$selectCount++;
        return $result;
    }

    public function delete($from, $where ='') {
        $sql = "DELETE FROM $from";

        if($where !== '') {
            $sql .= " WHERE $where";
        }
        $sql .= ";";

        if($this->connection->query($sql) !== true) {
            array_push($this->errors, $this->connection->error);
        }
    }
}

?> 