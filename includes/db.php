<?php
// Database class - no longer requires config.php
class Database {
    private $host = DB_HOST;
    private $port = DB_PORT;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function __construct() {
        $this->getConnection();
    }

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
                $this->conn = new PDO(
                    $dsn,
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_PERSISTENT => false
                    ]
                );
                
                // Test connection
                $this->conn->query("SELECT 1");
                
            } catch(PDOException $exception) {
                error_log("Database Connection Error: " . $exception->getMessage());
                if (defined('APP_ENV') && APP_ENV === 'development') {
                    die("Database connection failed: " . $exception->getMessage());
                } else {
                    die("Database connection failed. Please try again later.");
                }
            }
        }
        return $this->conn;
    }

    // ... rest of the methods remain the same ...

    // Generic query execution method
    public function executeQuery($sql, $params = []) {
        try {
            // Ensure we have a connection
            if ($this->conn === null) {
                $this->getConnection();
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " - SQL: " . $sql);
            return false;
        }
    }

    // Execute INSERT/UPDATE/DELETE and return affected rows
    public function execute($sql, $params = []) {
        try {
            // Ensure we have a connection
            if ($this->conn === null) {
                $this->getConnection();
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch(PDOException $e) {
            error_log("Execute Error: " . $e->getMessage() . " - SQL: " . $sql);
            return false;
        }
    }

    // Execute INSERT and return last insert ID
    public function insert($sql, $params = []) {
        try {
            // Ensure we have a connection
            if ($this->conn === null) {
                $this->getConnection();
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $this->conn->lastInsertId();
        } catch(PDOException $e) {
            error_log("Insert Error: " . $e->getMessage() . " - SQL: " . $sql);
            return false;
        }
    }

    // Fetch single row
    public function fetchSingle($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }

    // Fetch all rows
    public function fetchAll($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt ? $stmt->fetchAll() : false;
    }

    // Get last insert ID
    public function lastInsertId() {
        return $this->conn ? $this->conn->lastInsertId() : null;
    }

    // Begin transaction
    public function beginTransaction() {
        return $this->conn ? $this->conn->beginTransaction() : false;
    }

    // Commit transaction
    public function commit() {
        return $this->conn ? $this->conn->commit() : false;
    }

    // Rollback transaction
    public function rollback() {
        return $this->conn ? $this->conn->rollback() : false;
    }

    // Check if connected
    public function isConnected() {
        return $this->conn !== null;
    }
}

// Create database instance - only if constants are defined
if (defined('DB_HOST')) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$database->isConnected()) {
            throw new Exception("Failed to establish database connection");
        }
    } catch(Exception $e) {
        die("Database initialization failed: " . $e->getMessage());
    }
}
?>
