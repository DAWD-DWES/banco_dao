<?php

require_once "../src/modelo/Cuenta.php";
require_once "../src/modelo/TipoCuenta.php";
require_once "../src/dao/OperacionDAO.php";

/**
 * Clase CuentaAhorros 
 */
class CuentaAhorros extends Cuenta {

    private int $libreta;

    public function __construct(OperacionDAO $operacionDAO, int $idCliente, float $saldo = 0, float $bonificacion = 0, bool $libreta = false) {
        $this->libreta = (int)$libreta;
        $saldoBonificado = $saldo * (1 + ($bonificacion / 100));
        parent::__construct($operacionDAO, $idCliente, TipoCuenta::AHORROS, $saldoBonificado);
    }

    public function ingreso(float $cantidad, string $descripcion, float $bonificacion = 0): void {
        $cantidadBonificada = $cantidad * (1 + ($bonificacion / 100));
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
            $this->agregaOperacion($operacion);
            $this->setSaldo($this->getSaldo() - $cantidad);
        } else {
            throw new SaldoInsuficienteException($this->getId(), $cantidad);
        }
    }

    public function getLibreta(): bool {
        return (bool)$this->libreta;
    }

    public function setLibreta(bool $libreta): void {
        $this->libreta = (int)$libreta;
    }

    public function aplicaInteres(float $interes): void {
        $intereses = $this->getSaldo() * $interes / 100;
        $this->ingreso($intereses, "Intereses a tu favor.");
    }

    public function __toString() {
        return (parent::__toString() . "<br> Libreta: " . ($this->getLibreta() ? "Si" : "No") . "</br>");
    }
}
