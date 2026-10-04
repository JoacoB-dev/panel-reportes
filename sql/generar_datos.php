<?php
/**
 * Genera los INSERT de datos de ejemplo para MySQL.
 * Uso: php sql/generar_datos.php > sql/datos.sql
 */
if (PHP_SAPI !== 'cli') {
    exit('Solo por consola.');
}
require_once dirname(dirname(__FILE__)) . '/includes/semilla.php';

$datos = generar_datos();
echo "-- Datos de ejemplo generados el " . date('Y-m-d') . "\nSET NAMES utf8;\n";

foreach ($datos as $tabla => $filas) {
    $columnas = implode(', ', columnas_tabla($tabla));
    foreach (array_chunk($filas, 500) as $lote) {
        $valores = array();
        foreach ($lote as $fila) {
            $celdas = array();
            foreach ($fila as $v) {
                $celdas[] = is_int($v) || is_float($v) ? $v : "'" . addslashes($v) . "'";
            }
            $valores[] = '(' . implode(', ', $celdas) . ')';
        }
        echo "INSERT INTO $tabla ($columnas) VALUES\n" . implode(",\n", $valores) . ";\n";
    }
}
