<?php

require_once "../src/modelo/Operacion.php";
require_once "../src/modelo/TipoCuenta.php";
require_once "../src/modelo/IProductoBancario.php";
require_once "../src/dao/OperacionDAO.php";
require_once "../src/excepciones/SaldoInsuficienteException.php";

/**
 * Clase Cuenta 
 */
abstract class Cuenta implements IProductoBancario {

    protected OperacionDAO $operacionDAO;

    /**
     * Id de la cuenta
     * @var int
     */
    private int $id;

    /**
     * Saldo de la cuenta
     * @var float
     */
    private float $saldo;

    /**
     * Timestamp de Fecha y hora de creación de la cuenta
     * @var int
     */
    private int $fechaCreacion;

    /**
     * Tipo de la cuenta
     * @var string
     */
    private string $tipo;

    /**
     * Id del cliente dueño de la cuenta
     * @var int
     */
    private int $idCliente;

    /**
     * Operaciones realizadas en la cuenta
     * @param float $saldo
     */
    private array $operaciones;

    public function __construct(OperacionDAO $operacionDAO, int $idCliente, TipoCuenta $tipo, float $saldo = 0, string $fechaCreacion = 'now') {
        if (func_num_args() > 0) {
            $this->operacionDAO = $operacionDAO;
            $this->setTipo($tipo);
            $this->setSaldo($saldo);
            $this->setOperaciones([]);
            $this->setFechaCreacion(new DateTime($fechaCreacion));
            $this->setIdCliente($idCliente);
        }
    }

    public function getId(): int {
        return $this->id;
    }

    public function getSaldo(): float {
        return $this->saldo;
    }

    public function getFechaCreacion(): DateTime {
        $fecha = new DateTime();
        return $fecha->setTimestamp($this->fechaCreacion);
    }

    public function getTipo(): TipoCuenta {
        return TipoCuenta::from($this->tipo);
    }

    public function getIdCliente(): int {
        return $this->idCliente;
    }

    public function getOperaciones(): array {
        return $this->operaciones;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function setSaldo(float $saldo): void {
        $this->saldo = $saldo;
    }

    public function setFechaCreacion(DateTime $fechaCreacion): void {
        $this->fechaCreacion = $fechaCreacion->getTimestamp();
    }

    public function setIdCliente(int $idCliente): void {
        $this->idCliente = $idCliente;
    }

    public function setTipo(TipoCuenta $tipo): void {
        $this->tipo = $tipo->value;
    }

    public function setOperaciones(array $operaciones): void {
        $this->operaciones = $operaciones;
    }

    /**
     * Ingreso de una cantidad en una cuenta
     * @param type $cantidad Cantidad de dinero
     * @param type $descripcion Descripción del ingreso
     */
    public function ingreso(float $cantidad, string $descripcion): void {
        if ($cantidad > 0) {
            $operacion = new Operacion($this->getId(), TipoOperacion::INGRESO, $cantidad, $descripcion);
            $operacionId = $this->operacionDAO->crear($operacion);
            $operacion->setId($operacionId);
            $this->agregaOperacion($operacion);
            $this->setSaldo($this->getSaldo() + $cantidad);
        }
    }

    /**
     * 
     * @param type $cantidad Cantidad de dinero a retirar
     * @param type $descripcion Descripcion del debito
     *
     */
    abstract public function debito(float $cantidad, string $descripcion): void;

    public function __toString(): string {
        $saldoFormatted = number_format($this->getSaldo(), 2); // Formatear el saldo con dos decimales
        $operacionesStr = implode("</br>", array_map(fn($operacion) => "{$operacion->__toString()}", $this->getOperaciones())); // Convertir las operaciones en una cadena separada por saltos de línea

        return "Cuenta ID: {$this->getId()}</br>" .
                "Tipo Cuenta: " . ($this->getTipo())->value . "</br>" .
                "Cliente ID: {$this->getIdCliente()}</br>" .
                "Saldo: $saldoFormatted</br>" .
                "Fecha Creación: {$this->getFechaCreacion()->format('Y-m-d')}</br>" .
                "$operacionesStr";
    }

    /**
     * Agrega operación a la lista de operaciones de la cuenta
     * @param type $operacion Operación a añadir
     */
    protected function agregaOperacion(Operacion $operacion): void {
        $this->operaciones[] = $operacion;
    }
}
