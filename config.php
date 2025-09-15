<?php

class Database
{
    private $conn;

    public function __construct()
    {
        $dsn = "pgsql:host=localhost;dbname=testdb";
        $username = "postgres";
        $password = "securepassword";

        try {
            $this->conn = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function __destruct()
    {
        $this->conn = null;
    }

    function validate($value)
    {
        $value = trim($value);
        $value = stripslashes($value);
        $value = str_replace(
            [
                '‘',
                '’',
                '“',
                '”',
                '"',
                '„',
                '‟',
                '‹',
                '›',
                '«',
                '»',
                '`',
                '´',
                '❛',
                '❜',
                '❝',
                '❞',
                '〝',
                '〞'
            ],
            "'",
            $value
        );
        return $value;
    }
    function eQuery($sql, $params = [])
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            if (stripos($sql, 'SELECT') === 0) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            return true;
        } catch (PDOException $e) {
            error_log("ERROR: " . $e->getMessage());
            return false;
        }
    }


    public function executeQuery($sql)
    {
        try {
            return $this->conn->query($sql);
        } catch (PDOException $e) {
            die("ERROR: " . $e->getMessage());
        }
    }

    public function select($table, $columns = "*", $condition = "")
    {
        $sql = "SELECT $columns FROM $table $condition";
        return $this->executeQuery($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($table, $id)
    {
        $id = intval($id);
        $condition = "WHERE id = $id";
        $result = $this->select($table, "*", $condition);
        return $result ? $result[0] : null;
    }

    function insert($table, $data)
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $this->eQuery($sql, array_values($data));
        return $this->lastInsertId();
    }

    public function update($table, $data, $condition = "")
    {
        $set = '';
        $params = [];
        foreach ($data as $key => $value) {
            $set .= "$key = ?, ";
            $params[] = $value;
        }
        $set = rtrim($set, ', ');
        $sql = "UPDATE $table SET $set $condition";
        return $this->eQuery($sql, $params);
    }

    public function delete($table, $condition = "")
    {
        $sql = "DELETE FROM $table $condition";
        return $this->executeQuery($sql);
    }

    function hashPassword($password)
    {
        return hash_hmac('sha256', $password, "iqbolshoh");
    }

    public function login($username, $password, $table)
    {
        $username = $this->validate($username);
        $sql = "SELECT * FROM $table WHERE username = ? AND password = ?";
        return $this->eQuery($sql, [$username, $this->hashPassword($password)]);
    }

    public function count($table)
    {
        $userId = $_SESSION['id'];
        $sql = "SELECT COUNT(*) AS total_elements FROM $table WHERE user_id = ?";
        $result = $this->eQuery($sql, [$userId]);
        return $result[0]['total_elements'];
    }

    function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }
}
