<?php

require_once "../src/modelo/Cuenta.php";
require_once "../src/modelo/TipoCuenta.php";
require_once "../src/dao/OperacionDAO.php";

/**
 * Clase CuentaAhorros 
 */
class CuentaAhorros extends Cuenta {
    
    private float $bonificacion;
    private bool $libreta;

    public function __construct(OperacionDao $operacionDAO, string $idCliente, bool $libreta = false, float $bonificacion = 0, float $saldo = 0, string $fechaCreacion = "now") {
        $this->libreta = $libreta;
        $this->bonificacion = $bonificacion;
        parent::__construct($operacionDAO, $idCliente, TipoCuenta::AHORROS, $saldo, $fechaCreacion);
    }

    public function getLibreta(): bool {
        return $this->libreta;
    }

    public function setLibreta(bool $libreta): void {
        $this->libreta = $libreta;
    }
    
    public function getBonificacion(): float {
        return $this->bonificacion;
    }

    public function setBonificacion(float $bonificacion): void {
        $this->bonificacion = $bonificacion;
    }
    
    public function ingreso(float $cantidad, string $descripcion): void {
          $cantidadBonificada = $cantidad * (1 + ($this->getBonificacion() / 100));
        parent::ingreso($cantidadBonificada, $descripcion);
    }
    
    /**
     * 
     * @param type $cantidad Cantidad de dinero a retirar
     * @param type $descripcion Descripcion del debito
     * @throws SaldoInsuficienteException
     */
    public function debito(float $cantidad, string $descripcion): void {
        if ($cantidad <= $this->getSaldo()) {
            $operacion = new Operacion($this->getId(), TipoOperacion::DEBITO, $cantidad, $descripcion);
            $operacionId = $this->operacionDAO->crear($operacion);
            $operacion->setId($operacionId);
            $this->agregaOperacion($operacion);
            $this->setSaldo($this->getSaldo() - $cantidad);
        } else {
            throw new SaldoInsuficienteException($this->getId(), $cantidad);
        }
    }

    public function aplicaInteres(float $interes): void {
        $intereses = $this->getSaldo() * $interes / 100;
        $this->ingreso($intereses, "Intereses a tu favor.");
    }

    public function __toString(): string {
        return (parent::__toString() . "<br> Libreta: " . ($this->getLibreta() ? "Si" : "No") . "</br>");
    }
}

