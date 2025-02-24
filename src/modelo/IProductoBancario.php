<?php

/**
 * Interface IProductoBancario
 */
Interface IProductoBancario {

    public function ingreso(float $cantidad, string $descripcion): Operacion;

    public function debito(float $cantidad, string $asunto): Operacion;
}
