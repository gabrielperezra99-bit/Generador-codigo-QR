<?php
class Plano {
    private $conn;
    private $table_name = "planos";

    public $id;
    public $usuario_id;
    public $nombre;
    public $descripcion;
    public $cliente;
    public $archivo_url;
    public $formato;
    public $qr_code;
    public $metadata;
    public $fecha_subida;
    public $estado;
    public $empresa;
    public $etiquetas;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (usuario_id, nombre, descripcion, cliente, archivo_url, formato, qr_code, metadata)
                  VALUES (:usuario_id, :nombre, :descripcion, :cliente, :archivo_url, :formato, :qr_code, :metadata)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->cliente = htmlspecialchars(strip_tags($this->cliente));
        $this->archivo_url = htmlspecialchars(strip_tags($this->archivo_url));
        $this->formato = htmlspecialchars(strip_tags($this->formato));

        // Bind de parámetros
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":cliente", $this->cliente);
        $stmt->bindParam(":archivo_url", $this->archivo_url);
        $stmt->bindParam(":formato", $this->formato);
        $stmt->bindParam(":qr_code", $this->qr_code);
        $stmt->bindParam(":metadata", $this->metadata);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    public function read() {
        $query = "SELECT id, usuario_id, nombre, descripcion, cliente, archivo_url, formato, qr_code, metadata, fecha_subida, 
                         COALESCE(favorito, 0) as favorito, fecha_favorito, estado, empresa, etiquetas
              FROM " . $this->table_name . " 
              WHERE usuario_id = :usuario_id 
              ORDER BY COALESCE(favorito, 0) DESC, fecha_subida DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT id, usuario_id, nombre, descripcion, cliente, archivo_url, formato, qr_code, metadata, fecha_subida,
                     COALESCE(favorito, 0) as favorito, fecha_favorito, estado, empresa, etiquetas
              FROM " . $this->table_name . " 
              WHERE id = :id 
              LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->usuario_id = $row['usuario_id'];
            $this->nombre = $row['nombre'];
            $this->descripcion = $row['descripcion'];
            $this->cliente = $row['cliente'];
            $this->archivo_url = $row['archivo_url'];
            $this->formato = $row['formato'];
            $this->qr_code = $row['qr_code'];
            $this->metadata = $row['metadata'];
            $this->fecha_subida = $row['fecha_subida'];
            $this->estado = $row['estado'];
            $this->empresa = $row['empresa'];
            $this->etiquetas = $row['etiquetas'];
            return true;
        }

        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre = :nombre, descripcion = :descripcion, cliente = :cliente, estado = :estado, empresa = :empresa, etiquetas = :etiquetas
                  WHERE id = :id AND usuario_id = :usuario_id";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->cliente = htmlspecialchars(strip_tags($this->cliente));
        $this->estado = isset($this->estado) ? htmlspecialchars(strip_tags($this->estado)) : 'planificacion';
        $this->empresa = isset($this->empresa) ? htmlspecialchars(strip_tags($this->empresa)) : 'FREMAQ';
        $this->etiquetas = isset($this->etiquetas) ? $this->etiquetas : null;

        // Bind de parámetros
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":cliente", $this->cliente);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":empresa", $this->empresa);
        $stmt->bindParam(":etiquetas", $this->etiquetas);
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        return $stmt->execute();
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