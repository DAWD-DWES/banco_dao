<?php

require_once "../src/modelo/Cuenta.php";
require_once "../src/modelo/TipoCuenta.php";
require_once "../src/dao/OperacionDAO.php";

/**
 * Clase CuentaCorriente 
 */
class CuentaCorriente extends Cuenta {

    public function __construct(int $idCliente, float $saldo = 0, string $fechaCreacion = "now") {
        parent::__construct($idCliente, TipoCuenta::CORRIENTE, $saldo, $fechaCreacion);
    }

    /**
     * 
     * @param type $cantidad Cantidad de dinero a retirar
     * @param type $descripcion Descripcion del debito
     */
    public function debito(float $cantidad, string $descripcion): Operacion {
        $operacion = new Operacion($this->getId(), TipoOperacion::DEBITO, $cantidad, $descripcion);
        $this->agregaOperacion($operacion);
        $this->setSaldo($this->getSaldo() - $cantidad);
        return $operacion;
    }

    public function aplicaComision($comision, $minSaldo): Operacion {
        if ($this->getSaldo() < $minSaldo) {
            $operacion = $this->debito($comision, "Cargo de comisión de mantenimiento");
            return $operacion;
        } else {
            throw new SaldoInsuficienteException($this->getId(), $comision);
        }
    }
}
