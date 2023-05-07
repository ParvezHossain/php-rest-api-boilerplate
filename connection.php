<?php

class Database {

    private $host;
    private $user;
    private $password;
    private $db;
    private $dsn;
    private $pdo;
    public static $instance = null;

    private function __construct($host, $user, $password, $db){
        
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->db = $db;
        $this->dsn = "mysql:host={$this->host};dbname={$this->db}";

        try {
          $this->pdo = new PDO($this->dsn, $this->user, $this->password);
          $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            error_log("Connection failed: " . $e->getMessage(), 0);
            exit("Database connection failed. Please try again later.");
        }
    }

    public static function getInstance(){
        if (!self::$instance) {
            self::$instance = new self(HOST, USER, PASSWORD, DB);
        }
        return self::$instance;
    }
    public function getConnection(){
        return $this->pdo;
    }
}

require_once('config.php');
$db = Database::getInstance();
$pdo = $db->getConnection();