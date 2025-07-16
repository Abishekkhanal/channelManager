<?php
class Database {
    private $connection;
    private static $instance = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_FOUND_ROWS => true
            ]);
        } catch (PDOException $e) {
            Logger::error("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Logger::error("Database query failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Database query failed");
        }
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function escape($value) {
        return $this->connection->quote($value);
    }
    
    public function buildWhere($conditions) {
        if (empty($conditions)) {
            return ['', []];
        }
        
        $where = [];
        $params = [];
        
        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $where[] = "$key IN ($placeholders)";
                $params = array_merge($params, $value);
            } else {
                $where[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        return ['WHERE ' . implode(' AND ', $where), $params];
    }
    
    public function buildInsert($table, $data) {
        $columns = array_keys($data);
        $placeholders = str_repeat('?,', count($columns) - 1) . '?';
        $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES ($placeholders)";
        return [$sql, array_values($data)];
    }
    
    public function buildUpdate($table, $data, $conditions) {
        $set = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $set[] = "$key = ?";
            $params[] = $value;
        }
        
        list($whereClause, $whereParams) = $this->buildWhere($conditions);
        $params = array_merge($params, $whereParams);
        
        $sql = "UPDATE $table SET " . implode(',', $set) . " $whereClause";
        return [$sql, $params];
    }
    
    public function insert($table, $data) {
        list($sql, $params) = $this->buildInsert($table, $data);
        $this->query($sql, $params);
        return $this->lastInsertId();
    }
    
    public function update($table, $data, $conditions) {
        list($sql, $params) = $this->buildUpdate($table, $data, $conditions);
        return $this->execute($sql, $params);
    }
    
    public function delete($table, $conditions) {
        list($whereClause, $params) = $this->buildWhere($conditions);
        $sql = "DELETE FROM $table $whereClause";
        return $this->execute($sql, $params);
    }
    
    public function select($table, $columns = '*', $conditions = [], $orderBy = '', $limit = '') {
        $sql = "SELECT $columns FROM $table";
        
        list($whereClause, $params) = $this->buildWhere($conditions);
        $sql .= " $whereClause";
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if (!empty($limit)) {
            $sql .= " LIMIT $limit";
        }
        
        return $this->fetchAll($sql, $params);
    }
    
    public function selectOne($table, $columns = '*', $conditions = []) {
        $sql = "SELECT $columns FROM $table";
        
        list($whereClause, $params) = $this->buildWhere($conditions);
        $sql .= " $whereClause LIMIT 1";
        
        return $this->fetch($sql, $params);
    }
    
    public function count($table, $conditions = []) {
        list($whereClause, $params) = $this->buildWhere($conditions);
        $sql = "SELECT COUNT(*) as count FROM $table $whereClause";
        $result = $this->fetch($sql, $params);
        return $result['count'];
    }
    
    public function exists($table, $conditions) {
        return $this->count($table, $conditions) > 0;
    }
    
    public function paginate($table, $columns = '*', $conditions = [], $orderBy = '', $page = 1, $perPage = ITEMS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($table, $conditions);
        
        $data = $this->select($table, $columns, $conditions, $orderBy, "$offset, $perPage");
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }
}
?>