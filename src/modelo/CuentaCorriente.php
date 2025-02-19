<?php

require_once "../src/modelo/Cuenta.php";
require_once "../src/modelo/TipoCuenta.php";
require_once "../src/dao/OperacionDAO.php";


/**
 * Clase CuentaCorriente 
 */
class CuentaCorriente extends Cuenta {

    public function __construct(OperacionDAO $operacionDAO, int $idCliente, float $saldo = 0, string $fechaCreacion = "now") {
        parent::__construct($operacionDAO, $idCliente, TipoCuenta::CORRIENTE, $saldo, $fechaCreacion);
    }
    
    /**
     * 
     * @param type $cantidad Cantidad de dinero a retirar
     * @param type $descripcion Descripcion del debito
     */
    public function debito(float $cantidad, string $descripcion): void {
            $operacion = new Operacion($this->getId(), TipoOperacion::DEBITO, $cantidad, $descripcion);
            $operacionId = $this->operacionDAO->crear($operacion);
            $operacion->setId($operacionId);
            $this->agregaOperacion($operacion);
            $this->setSaldo($this->getSaldo() - $cantidad);
    }

    public function aplicaComision($comision, $minSaldo): void {
        if ($this->getSaldo() < $minSaldo) {
            $this->debito($comision, "Cargo de comisión de mantenimiento");
        }
    }
}
