<?php
require_once __DIR__ . '/config.php';

class Database {
    private $connection;

    public function __construct() {
        $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->connection->connect_error) {
            error_log("Erreur DB: " . $this->connection->connect_error);
            die("Une erreur est survenue. Veuillez réessayer plus tard.");
        }
    }

    /**
     * Exécute une requête SELECT sécurisée
     */
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            error_log("Erreur de préparation SQL: " . $this->connection->error);
            return false;
        }
        if (!empty($params)) {
            $types = $this->getTypes($params);
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    /**
     * Insère des données de manière sécurisée
     */
    public function insert($table, $data) {
        $columns = [];
        $placeholders = [];
        $values = [];
        $types = '';

        foreach ($data as $key => $value) {
            $columns[] = "`$key`";
            $placeholders[] = '?';
            $values[] = $value;
            $types .= $this->getType($value);
        }

        $sql = "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            error_log("Erreur INSERT SQL: " . $this->connection->error);
            return false;
        }
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        return $stmt->insert_id;
    }

    /**
     * Met à jour des données de manière sécurisée
     */
    public function update($table, $data, $where) {
        $set = [];
        $values = [];
        $types = '';

        foreach ($data as $key => $value) {
            $set[] = "`$key` = ?";
            $values[] = $value;
            $types .= $this->getType($value);
        }

        $whereClause = [];
        $whereValues = [];
        foreach ($where as $key => $value) {
            $whereClause[] = "`$key` = ?";
            $whereValues[] = $value;
            $types .= $this->getType($value);
        }

        $values = array_merge($values, $whereValues);
        $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $whereClause);

        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            error_log("Erreur UPDATE SQL: " . $this->connection->error);
            return false;
        }
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }

    /**
     * Récupère le type pour bind_param
     */
    private function getTypes($params) {
        $types = '';
        foreach ($params as $param) {
            $types .= $this->getType($param);
        }
        return $types;
    }

    private function getType($value) {
        if (is_int($value)) return 'i';
        if (is_float($value)) return 'd';
        if (is_bool($value)) return 'i'; // MySQLi traite les booléens comme des int
        return 's'; // string par défaut
    }

    public function getLastInsertId() {
        return $this->connection->insert_id;
    }

    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }
}

$db = new Database();
?>