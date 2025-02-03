<?php

require_once '../src/modelo/Cuenta.php';
require_once '../src/modelo/CuentaAhorros.php';
require_once '../src/modelo/CuentaCorriente.php';
require_once '../src/modelo/TipoCuenta.php';
require_once '../src/dao/OperacionDAO.php';

/**
 * Clase CuentaDAO
 */
class CuentaDAO {

    /**
     * Conexión a la base de datos
     * @var PDO
     */
    private PDO $pdo;

    /**
     * DAO para gestionar operaciones
     * @var OperacionDAO
     */
    private OperacionDAO $operacionDAO;

    public function __construct(PDO $pdo, OperacionDAO $operacionDAO) {
        $this->pdo = $pdo;
        $this->operacionDAO = $operacionDAO;
    }

    /**
     * Obtener una cuenta dado su identificador
     * @param int $id
     * @return CuentaCorriente|CuentaAhorros|null
     */
    public function recuperaPorId(int $id): CuentaCorriente|CuentaAhorros|null {
        $sql = "SELECT id, cliente_id as idCliente, tipo, saldo, UNIX_TIMESTAMP(fecha_creacion) as fechaCreacion, libreta FROM cuentas WHERE id = :id;";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_OBJ);
        $datosCuenta = $stmt->fetch();
        return $datosCuenta ? $this->crearCuenta($datosCuenta) : null;
    }

    /**
     * Obtener los identificadores de las cuentas de un cliente dado su identificador
     * @param int $idCliente
     * @return array
     */
    public function recuperaIdCuentasPorClienteId(int $idCliente): array {
        $sql = "SELECT id FROM cuentas WHERE cliente_id = :idCliente;";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idCliente' => $idCliente]);
        $stmt->setFetchMode(PDO::FETCH_NUM);
        $idCuentas = $stmt->fetchAll() ?? [];
        return array_merge(...$idCuentas);
    }

    /**
     * Obtiene todas las cuentas de la base de datos
     * 
     * @return array
     */
    public function recuperaTodos(): array {
        $sql = "SELECT id as id, cliente_id as idCliente, tipo, saldo, UNIX_TIMESTAMP(fecha_creacion) as fechaCreacion, libreta FROM cuentas;";
        $stmt = $this->pdo->query($sql);
        $cuentasDatos = $stmt->fetchAll(PDO::FETCH_OBJ);
        return array_map(fn($datos) => $this->crearCuenta($datos), $cuentasDatos);
    }

    /**
     * Crea una cuenta a partir de los datos obtenidos del registro
     * 
     * @param object $datosCuenta
     * @return CuentaCorriente|CuentaAhorros
     */
    private function crearCuenta(object $datosCuenta): CuentaCorriente|CuentaAhorros {
        $cuenta = match ($datosCuenta?->tipo) {
            TipoCuenta::AHORROS->value => (new CuentaAhorros($this->operacionDAO, $datosCuenta->idCliente, $datosCuenta->saldo, $datosCuenta->libreta)),
            TipoCuenta::CORRIENTE->value => (new CuentaCorriente($this->operacionDAO, $datosCuenta->idCliente, $datosCuenta->saldo)),
            default => null
        };
        $cuenta->setId($datosCuenta->id);
        $operaciones = $this->operacionDAO->recuperaPorIdCuenta($datosCuenta->id);
        $cuenta->setOperaciones($operaciones);
        return $cuenta;
    }

    /**
     * Crea un registro de una instancia de cuenta
     * @param Cuenta $cuenta
     */
    public function crear(Cuenta $cuenta): bool {
        $sqlCuentaAhorros = "INSERT INTO cuentas (cliente_id, tipo, saldo, fecha_creacion, libreta) VALUES (:cliente_id, :tipo, :saldo, FROM_UNIXTIME(:fechaCreacion), :libreta);";
        $sqlCuentaCorriente = "INSERT INTO cuentas (cliente_id, tipo, saldo, fecha_creacion) VALUES (:cliente_id, :tipo, :saldo, FROM_UNIXTIME(:fechaCreacion));";
        $params = [
            'cliente_id' => $cuenta->getIdCliente(),
            'tipo' => $cuenta->getTipo()->value,
            'saldo' => $cuenta->getSaldo(),
            'fechaCreacion' => ($cuenta->getFechaCreacion())->format('Y-m-d')
        ];
        if ($cuenta instanceof CuentaAhorros) {
            $sql = $sqlCuentaAhorros;
            $params['libreta'] = (int) $cuenta->getLibreta();
        }
        else {
            $sql = $sqlCuentaCorriente;
        }
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);
        if ($result) {
            $cuenta->setId($this->pdo->lastInsertId());
        }
        return $result;
    }

    /**
     * Modifica un registro de una instancia de cuenta
     * @param Cuenta $cuenta
     */
    public function modificar(Cuenta $cuenta): bool {
        $sql = "UPDATE cuentas SET cliente_id = :cliente_id, tipo = :tipo, saldo = :saldo, fecha_creacion = FROM_UNIXTIME (:fecha_creacion) WHERE id = :id;";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            'id' => $cuenta->getId(),
            'cliente_id' => $cuenta->getIdCliente(),
            'tipo' => ($cuenta->getTipo())->value,
            'saldo' => $cuenta->getSaldo(),
            'fecha_creacion' => ($cuenta->getFechaCreacion())->format('Y-m-d')
        ]);
        return $result;
    }

    /**
     * Elimina un registro de una instancia de cuenta
     * @param int $id
     */
    public function eliminar(int $id): bool {
        $sql = "DELETE FROM cuentas WHERE id = :id";
        $operaciones = $this->operacionDAO->recuperaPorIdCuenta($id);
        foreach ($operaciones as $operacion) {
            $this->operacionDAO->eliminar($operacion->getId());
        }
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute(['id' => $id]);
        return $result;
    }

    // Estos métodos permiten usar el modo transaccional para operaciones de persistencia de cuentas.

    public function beginTransaction() {
        $this->pdo->beginTransaction();
    }

    public function endTransaction() {
        $this->pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
    }

    public function commit() {
        $this->pdo->commit();
    }

    public function rollback() {
        $this->pdo->rollback();
    }
}
