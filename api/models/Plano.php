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
    public $imagen_preview;
    public $formato;
    public $qr_code;
    public $metadata;
    public $es_favorito;
    public $fecha_favorito;
    public $estado;
    public $progreso_porcentaje;
    public $etiquetas;
    public $tiempo_estimado;
    public $visitas;
    public $fecha_subida;
    public $fecha_creacion;
    public $fecha_actualizacion;
    public $empresa; // Nuevo campo para FREMAQ o SOLMAN

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (usuario_id, nombre, descripcion, cliente, archivo_url, imagen_preview, formato, qr_code, metadata, estado, progreso_porcentaje, etiquetas, tiempo_estimado, empresa)
                  VALUES (:usuario_id, :nombre, :descripcion, :cliente, :archivo_url, :imagen_preview, :formato, :qr_code, :metadata, :estado, :progreso_porcentaje, :etiquetas, :tiempo_estimado, :empresa)";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->cliente = htmlspecialchars(strip_tags($this->cliente));
        $this->archivo_url = htmlspecialchars(strip_tags($this->archivo_url));
        $this->imagen_preview = htmlspecialchars(strip_tags($this->imagen_preview));
        $this->formato = htmlspecialchars(strip_tags($this->formato));
        $this->estado = $this->estado ?: 'planificacion';
        $this->progreso_porcentaje = $this->progreso_porcentaje ?: 0;
        $this->empresa = $this->empresa ?: 'FREMAQ'; // Valor por defecto

        // Bind de parámetros
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":cliente", $this->cliente);
        $stmt->bindParam(":archivo_url", $this->archivo_url);
        $stmt->bindParam(":imagen_preview", $this->imagen_preview);
        $stmt->bindParam(":formato", $this->formato);
        $stmt->bindParam(":qr_code", $this->qr_code);
        $stmt->bindParam(":metadata", $this->metadata);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":progreso_porcentaje", $this->progreso_porcentaje);
        $stmt->bindParam(":etiquetas", $this->etiquetas);
        $stmt->bindParam(":tiempo_estimado", $this->tiempo_estimado);
        $stmt->bindParam(":empresa", $this->empresa);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    public function read() {
        $query = "SELECT id, usuario_id, nombre, descripcion, cliente, archivo_url, imagen_preview, formato, qr_code, metadata, 
                         es_favorito, fecha_favorito, estado, progreso_porcentaje, etiquetas, tiempo_estimado, visitas,
                         fecha_subida, fecha_creacion, fecha_actualizacion, empresa
                  FROM " . $this->table_name . " 
                  WHERE usuario_id = :usuario_id 
                  ORDER BY es_favorito DESC, fecha_favorito DESC, fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT id, usuario_id, nombre, descripcion, cliente, archivo_url, imagen_preview, formato, qr_code, metadata,
                         es_favorito, fecha_favorito, estado, progreso_porcentaje, etiquetas, tiempo_estimado, visitas,
                         fecha_subida, fecha_creacion, fecha_actualizacion, empresa
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
            $this->imagen_preview = $row['imagen_preview'];
            $this->formato = $row['formato'];
            $this->qr_code = $row['qr_code'];
            $this->metadata = $row['metadata'];
            $this->es_favorito = $row['es_favorito'];
            $this->fecha_favorito = $row['fecha_favorito'];
            $this->estado = $row['estado'];
            $this->progreso_porcentaje = $row['progreso_porcentaje'];
            $this->etiquetas = $row['etiquetas'];
            $this->tiempo_estimado = $row['tiempo_estimado'];
            $this->visitas = $row['visitas'];
            $this->fecha_subida = $row['fecha_subida'];
            $this->fecha_creacion = $row['fecha_creacion'];
            $this->fecha_actualizacion = $row['fecha_actualizacion'];
            $this->empresa = $row['empresa'];
            return true;
        }

        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre = :nombre, descripcion = :descripcion, cliente = :cliente, 
                      imagen_preview = :imagen_preview, estado = :estado, progreso_porcentaje = :progreso_porcentaje,
                      etiquetas = :etiquetas, tiempo_estimado = :tiempo_estimado, empresa = :empresa,
                      fecha_actualizacion = CURRENT_TIMESTAMP
                  WHERE id = :id AND usuario_id = :usuario_id";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->cliente = htmlspecialchars(strip_tags($this->cliente));
        $this->imagen_preview = htmlspecialchars(strip_tags($this->imagen_preview));
        $this->empresa = htmlspecialchars(strip_tags($this->empresa));

        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":cliente", $this->cliente);
        $stmt->bindParam(":imagen_preview", $this->imagen_preview);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":progreso_porcentaje", $this->progreso_porcentaje);
        $stmt->bindParam(":etiquetas", $this->etiquetas);
        $stmt->bindParam(":tiempo_estimado", $this->tiempo_estimado);
        $stmt->bindParam(":empresa", $this->empresa);

        return $stmt->execute();
    }

    public function toggleFavorito() {
        $query = "UPDATE " . $this->table_name . " 
                  SET es_favorito = :es_favorito, 
                      fecha_favorito = :fecha_favorito
                  WHERE id = :id AND usuario_id = :usuario_id";

        $stmt = $this->conn->prepare($query);
        
        $fecha_favorito = $this->es_favorito ? date('Y-m-d H:i:s') : null;
        
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":es_favorito", $this->es_favorito);
        $stmt->bindParam(":fecha_favorito", $fecha_favorito);

        return $stmt->execute();
    }

    public function incrementarVisitas() {
        $query = "UPDATE " . $this->table_name . " 
                  SET visitas = visitas + 1 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

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