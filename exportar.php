<?php
/**
 * Exporta a CSV (compatible con Excel en español) el detalle de ventas con los filtros actuales.
 */
require_once dirname(__FILE__) . '/includes/reportes.php';

$f = leer_filtros();
list($from, $params) = base_consulta($f);

$stmt = db()->prepare("SELECT v.id AS venta, v.fecha, ve.nombre AS vendedor, v.cliente, p.nombre AS producto,
                              c.nombre AS categoria, i.cantidad, i.precio_unitario,
                              i.cantidad * i.precio_unitario AS subtotal
                       $from
                       ORDER BY v.fecha, v.id");
$stmt->execute($params);

$nombre = 'ventas_' . $f['desde'] . '_a_' . $f['hasta'] . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombre . '"');

$salida = fopen('php://output', 'w');
fwrite($salida, "\xEF\xBB\xBF"); // BOM para que Excel reconozca UTF-8
fputcsv($salida, array('Venta', 'Fecha', 'Vendedor', 'Cliente', 'Producto', 'Categoría', 'Cantidad', 'Precio unitario', 'Subtotal'), ';');

// Se recorre fila por fila para no cargar todo en memoria
while ($fila = $stmt->fetch()) {
    $fila['fecha'] = date('d/m/Y', strtotime($fila['fecha']));
    $fila['precio_unitario'] = number_format($fila['precio_unitario'], 2, ',', '');
    $fila['subtotal'] = number_format($fila['subtotal'], 2, ',', '');
    fputcsv($salida, $fila, ';');
}
fclose($salida);
