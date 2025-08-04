<?php
class Plano {
    private $conn;
    private $table_name = "planos";

    public $id;
    public $user_id;
    public $nombre;
    public $descripcion;
    public $cliente;
    public $archivo_nombre;
    public $archivo_ruta;
    public $archivo_tamaño;
    public $fecha_creacion;
    public $visitas;
    public $qr_data;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        // Verificar qué campos existen en la tabla
        try {
            $check_query = "DESCRIBE " . $this->table_name;
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->execute();
            
            $existing_fields = [];
            while ($row = $check_stmt->fetch(PDO::FETCH_ASSOC)) {
                $existing_fields[] = $row['Field'];
            }
            
            // Construir query dinámicamente basado en campos existentes
            $fields = [];
            $placeholders = [];
            
            if (in_array('user_id', $existing_fields)) {
                $fields[] = 'user_id';
                $placeholders[] = ':user_id';
            } elseif (in_array('usuario_id', $existing_fields)) {
                $fields[] = 'usuario_id';
                $placeholders[] = ':user_id';
            }
            
            if (in_array('nombre', $existing_fields)) {
                $fields[] = 'nombre';
                $placeholders[] = ':nombre';
            }
            
            if (in_array('descripcion', $existing_fields)) {
                $fields[] = 'descripcion';
                $placeholders[] = ':descripcion';
            }
            
            if (in_array('cliente', $existing_fields)) {
                $fields[] = 'cliente';
                $placeholders[] = ':cliente';
            }
            
            if (in_array('archivo_nombre', $existing_fields)) {
                $fields[] = 'archivo_nombre';
                $placeholders[] = ':archivo_nombre';
            }
            
            if (in_array('archivo_ruta', $existing_fields)) {
                $fields[] = 'archivo_ruta';
                $placeholders[] = ':archivo_ruta';
            }
            
            if (in_array('archivo_tamaño', $existing_fields)) {
                $fields[] = 'archivo_tamaño';
                $placeholders[] = ':archivo_tamaño';
            }
            
            if (in_array('qr_data', $existing_fields)) {
                $fields[] = 'qr_data';
                $placeholders[] = ':qr_data';
            }
            
            $query = "INSERT INTO " . $this->table_name . " (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->conn->prepare($query);

            // Sanitizar datos
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
            $this->cliente = htmlspecialchars(strip_tags($this->cliente));
            $this->archivo_nombre = htmlspecialchars(strip_tags($this->archivo_nombre));

            // Bind valores dinámicamente
            if (in_array('user_id', $existing_fields) || in_array('usuario_id', $existing_fields)) {
                $stmt->bindParam(":user_id", $this->user_id);
            }
            if (in_array('nombre', $existing_fields)) {
                $stmt->bindParam(":nombre", $this->nombre);
            }
            if (in_array('descripcion', $existing_fields)) {
                $stmt->bindParam(":descripcion", $this->descripcion);
            }
            if (in_array('cliente', $existing_fields)) {
                $stmt->bindParam(":cliente", $this->cliente);
            }
            if (in_array('archivo_nombre', $existing_fields)) {
                $stmt->bindParam(":archivo_nombre", $this->archivo_nombre);
            }
            if (in_array('archivo_ruta', $existing_fields)) {
                $stmt->bindParam(":archivo_ruta", $this->archivo_ruta);
            }
            if (in_array('archivo_tamaño', $existing_fields)) {
                $stmt->bindParam(":archivo_tamaño", $this->archivo_tamaño);
            }
            if (in_array('qr_data', $existing_fields)) {
                $stmt->bindParam(":qr_data", $this->qr_data);
            }

            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }

            return false;
            
        } catch (Exception $e) {
            error_log("Error en Plano::create(): " . $e->getMessage());
            return false;
        }
    }

    public function readByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id
                  ORDER BY fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->user_id = $row['user_id'];
            $this->nombre = $row['nombre'];
            $this->descripcion = $row['descripcion'];
            $this->cliente = $row['cliente'];
            $this->archivo_nombre = $row['archivo_nombre'];
            $this->archivo_ruta = $row['archivo_ruta'];
            $this->archivo_tamaño = $row['archivo_tamaño'];
            $this->fecha_creacion = $row['fecha_creacion'];
            $this->visitas = $row['visitas'];
            $this->qr_data = $row['qr_data'];
            return true;
        }

        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre=:nombre, descripcion=:descripcion, cliente=:cliente
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->cliente = htmlspecialchars(strip_tags($this->cliente));

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":cliente", $this->cliente);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }

    public function incrementVisitas() {
        $query = "UPDATE " . $this->table_name . " SET visitas = visitas + 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }

    public function updateQRData() {
        $query = "UPDATE " . $this->table_name . " SET qr_data = :qr_data WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":qr_data", $this->qr_data);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }
}
?>