<?php
// Conexion.php


class DB_Conexion {
    private $host = "localhost"; 
    private $usuario = "root";   
    private $password = "";      
    private $nombre_bd = "db_parcial_formdatos"; 
    
    private $conexion;

    public function __construct() {
        // Establecer la conexión
        $this->conexion = new mysqli($this->host, $this->usuario, $this->password, $this->nombre_bd);

        // Verificar errores de conexión
        if ($this->conexion->connect_error) {
            die("Error de Conexión: " . $this->conexion->connect_error);
        }
        
        // Asegurar la codificación UTF-8 para manejar tildes y ñ correctamente
        $this->conexion->set_charset("utf8mb4");
    }


    public function get_conexion(): mysqli {
        return $this->conexion;
    }


    public function ejecutar_consulta(string $sql): bool {
        return $this->conexion->query($sql);
    }

    public function obtener_datos(string $sql) {
        return $this->conexion->query($sql);
    }

    public function ejecutar_consulta_con_param(string $sql, string $tipos, array $parametros) {
        $stmt = $this->conexion->prepare($sql);
        
        if (!$stmt) {
            error_log("Error al preparar la consulta: " . $this->conexion->error);
            return false;
        }

        // Usamos call_user_func_array para enlazar los parámetros de forma dinámica
        $stmt->bind_param($tipos, ...$parametros);

        if ($stmt->execute()) {
            $resultado = $stmt->get_result();
            $stmt->close();
            return $resultado;
        } else {
            error_log("Error al ejecutar la consulta con parámetros: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    /**
     * Cierra la conexión a la base de datos.
     */
    public function cerrar_conexion() {
        $this->conexion->close();
    }
}