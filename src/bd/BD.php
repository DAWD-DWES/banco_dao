<?php

class BD {

    private static ?BD $instance = null; // Singleton de la clase
    private ?PDO $conexion = null; // Conexión PDO

    const DB_HOST = '127.0.0.1';
    const DB_PORT = '3306';
    const DB_DATABASE = 'banco';
    const DB_USUARIO = 'gestor';
    const DB_PASSWORD = 'secreto';

    private function __construct() {
        try {
            // Crear la conexión PDO
            $this->conexion = new PDO("mysql:host=" . BD::DB_HOST . ";dbname=" . BD::DB_DATABASE, BD::DB_USUARIO, BD::DB_PASSWORD,
                    [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]
            );
        } catch (PDOException $e) {
            throw new Exception("Error de conexión: " . $e->getMessage(), (int) $e->getCode());
        }
    }

    public static function getConexion(): PDO {
        if (self::$instance === null) {
            self::$instance = new BD();
        }
        return self::$instance->conexion;
    }

    // Evitar la clonación del objeto
    public function __clone() {
        
    }

    // Evitar la deserialización del objeto
    public function __wakeup() {
        
    }

    public function beginTransaction() {
        self::bd->beginTransaction();
    }

    public function commit() {
        self::bd->commit();
    }

    public function rollback() {
        self::bd->rollBack();
    }
}
