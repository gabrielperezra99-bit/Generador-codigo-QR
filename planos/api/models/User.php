<?php
class User {
    private $conn;
    private $table_name = "usuarios"; // Cambiado de "users" a "usuarios"

    public $id;
    public $nombre;
    public $email;
    public $password_hash;
    public $fecha_registro;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nombre=:nombre, email=:email, password_hash=:password_hash";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->email = htmlspecialchars(strip_tags($this->email));

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    public function emailExists() {
        $query = "SELECT id, nombre, email, password_hash FROM " . $this->table_name . " WHERE email = :email LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        $num = $stmt->rowCount();

        if($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nombre = $row['nombre'];
            $this->email = $row['email'];
            $this->password_hash = $row['password_hash'];
            return true;
        }

        return false;
    }

    // Método faltante para verificar contraseña
    public function verifyPassword($password) {
        return password_verify($password, $this->password_hash);
    }

    // Método para hashear contraseña (útil para registro)
    public function hashPassword($password) {
        $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>