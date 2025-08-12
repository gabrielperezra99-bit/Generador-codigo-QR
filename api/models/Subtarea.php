<?php

class Subtarea {
    private $conn;
    private $table_name = "subtareas";

    public $id;
    public $plano_id;
    public $usuario_id;
    public $titulo;
    public $descripcion;
    public $estado;
    public $prioridad;
    public $fecha_vencimiento;
    public $asignado_a;
    public $tiempo_estimado;
    public $notas_internas;
    public $fecha_creacion;
    public $fecha_actualizacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (plano_id, usuario_id, titulo, descripcion, estado, prioridad, fecha_vencimiento, asignado_a, tiempo_estimado, notas_internas)
                  VALUES (:plano_id, :usuario_id, :titulo, :descripcion, :estado, :prioridad, :fecha_vencimiento, :asignado_a, :tiempo_estimado, :notas_internas)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->asignado_a = htmlspecialchars(strip_tags($this->asignado_a));
        $this->notas_internas = htmlspecialchars(strip_tags($this->notas_internas));

        $stmt->bindParam(":plano_id", $this->plano_id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":prioridad", $this->prioridad);
        $stmt->bindParam(":fecha_vencimiento", $this->fecha_vencimiento);
        $stmt->bindParam(":asignado_a", $this->asignado_a);
        $stmt->bindParam(":tiempo_estimado", $this->tiempo_estimado);
        $stmt->bindParam(":notas_internas", $this->notas_internas);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    public function readByPlano() {
        $query = "SELECT id, plano_id, usuario_id, titulo, descripcion, estado, prioridad, 
                         fecha_vencimiento, asignado_a, tiempo_estimado, notas_internas,
                         fecha_creacion, fecha_actualizacion
                  FROM " . $this->table_name . " 
                  WHERE plano_id = :plano_id AND usuario_id = :usuario_id
                  ORDER BY prioridad DESC, fecha_creacion ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":plano_id", $this->plano_id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->execute();

        return $stmt;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET titulo = :titulo, descripcion = :descripcion, estado = :estado, 
                      prioridad = :prioridad, fecha_vencimiento = :fecha_vencimiento, 
                      asignado_a = :asignado_a, tiempo_estimado = :tiempo_estimado, 
                      notas_internas = :notas_internas,
                      fecha_actualizacion = CURRENT_TIMESTAMP
                  WHERE id = :id AND usuario_id = :usuario_id";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->asignado_a = htmlspecialchars(strip_tags($this->asignado_a));
        $this->notas_internas = htmlspecialchars(strip_tags($this->notas_internas));

        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":prioridad", $this->prioridad);
        $stmt->bindParam(":fecha_vencimiento", $this->fecha_vencimiento);
        $stmt->bindParam(":asignado_a", $this->asignado_a);
        $stmt->bindParam(":tiempo_estimado", $this->tiempo_estimado);
        $stmt->bindParam(":notas_internas", $this->notas_internas);

        return $stmt->execute();
    }

    public function readOne() {
        $query = "SELECT id, plano_id, usuario_id, titulo, descripcion, estado, prioridad, 
                         fecha_vencimiento, asignado_a, tiempo_estimado, notas_internas,
                         fecha_creacion, fecha_actualizacion
                  FROM " . $this->table_name . " 
                  WHERE id = :id AND usuario_id = :usuario_id
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->plano_id = $row['plano_id'];
            $this->titulo = $row['titulo'];
            $this->descripcion = $row['descripcion'];
            $this->estado = $row['estado'];
            $this->prioridad = $row['prioridad'];
            $this->fecha_vencimiento = $row['fecha_vencimiento'];
            $this->asignado_a = $row['asignado_a'];
            $this->tiempo_estimado = $row['tiempo_estimado'];
            $this->notas_internas = $row['notas_internas'];
            $this->fecha_creacion = $row['fecha_creacion'];
            $this->fecha_actualizacion = $row['fecha_actualizacion'];
            return true;
        }

        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND usuario_id = :usuario_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        return $stmt->execute();
    }
}
?>