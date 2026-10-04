<?php
/**
 * Genera datos de ejemplo reproducibles (misma semilla = mismos datos):
 * 24 meses de ventas de una distribuidora (el panel muestra 12 y compara con los 12 anteriores), con estacionalidad.
 * Devuelve array(tabla => array(filas)).
 */
function generar_datos($hasta = null)
{
    mt_srand(2021);
    $hasta = $hasta ? strtotime($hasta) : time();
    $desde = strtotime(date('Y-m-01', strtotime('-23 months', $hasta)));

    $vendedores = array(
        array(1, 'Carla Gómez', 'Oeste'),
        array(2, 'Diego Fernández', 'Norte'),
        array(3, 'Sofía Martínez', 'CABA'),
        array(4, 'Lucas Pereyra', 'Sur'),
        array(5, 'Valentina Ruiz', 'Oeste'),
    );
    $categorias = array(
        array(1, 'Almacén'), array(2, 'Bebidas'), array(3, 'Limpieza'), array(4, 'Perfumería'), array(5, 'Librería'),
    );
    $productos = array(
        array(1, 'Yerba 1 kg', 1, 4200), array(2, 'Aceite girasol 1,5 L', 1, 3100), array(3, 'Fideos 500 g', 1, 1200),
        array(4, 'Arroz 1 kg', 1, 1500), array(5, 'Gaseosa cola 2,25 L', 2, 2600), array(6, 'Agua mineral 2 L', 2, 1100),
        array(7, 'Cerveza lata 473 ml', 2, 1700), array(8, 'Lavandina 2 L', 3, 1300), array(9, 'Detergente 750 ml', 3, 1900),
        array(10, 'Jabón en polvo 3 kg', 3, 8900), array(11, 'Shampoo 400 ml', 4, 3900), array(12, 'Desodorante', 4, 2800),
        array(13, 'Papel higiénico x4', 4, 2300), array(14, 'Cuaderno A4', 5, 3500), array(15, 'Resma A4', 5, 7800),
    );
    $clientes = array('Almacén Don José', 'Super Chino Lin', 'Kiosco Luna', 'Autoservicio El Sol', 'Despensa Mary',
                      'Mayorista Oeste', 'Librería Del Centro', 'Mini Market 24', 'Almacén La Esquina', 'Super Familia');

    // Más ventas en diciembre, marzo (inicio de clases) y meses de verano para bebidas
    $factorMes = array(1 => 0.9, 2 => 0.85, 3 => 1.15, 4 => 1.0, 5 => 1.0, 6 => 0.95,
                       7 => 1.05, 8 => 1.0, 9 => 1.0, 10 => 1.05, 11 => 1.1, 12 => 1.4);

    $ventas = array();
    $items = array();
    $ventaId = 0;
    $itemId = 0;

    for ($dia = $desde; $dia <= $hasta; $dia = strtotime('+1 day', $dia)) {
        if (date('w', $dia) === '0') {
            continue; // domingos cerrado
        }
        $mes = (int) date('n', $dia);
        $cantVentas = (int) round(mt_rand(1, 4) * $factorMes[$mes]);
        for ($v = 0; $v < $cantVentas; $v++) {
            $ventaId++;
            $vendedor = $vendedores[mt_rand(0, count($vendedores) - 1)];
            $ventas[] = array($ventaId, date('Y-m-d', $dia), $vendedor[0], $clientes[mt_rand(0, count($clientes) - 1)]);

            $renglones = mt_rand(1, 5);
            $usados = array();
            for ($r = 0; $r < $renglones; $r++) {
                $p = $productos[mt_rand(0, count($productos) - 1)];
                if (isset($usados[$p[0]])) {
                    continue;
                }
                $usados[$p[0]] = true;
                $cantidad = mt_rand(1, 24);
                if ($p[2] === 2 && in_array($mes, array(12, 1, 2), true)) {
                    $cantidad = (int) ($cantidad * 1.6);
                }
                // Inflación aproximada: los precios suben un 2,5 % por mes hacia el presente
                $mesesAtras = (int) floor(($hasta - $dia) / (30 * 86400));
                $precio = round($p[3] / pow(1.025, $mesesAtras), 2);
                $itemId++;
                $items[] = array($itemId, $ventaId, $p[0], $cantidad, $precio);
            }
        }
    }

    return array(
        'vendedores'  => $vendedores,
        'categorias'  => $categorias,
        'productos'   => $productos,
        'ventas'      => $ventas,
        'venta_items' => $items,
    );
}

function columnas_tabla($tabla)
{
    $columnas = array(
        'vendedores'  => array('id', 'nombre', 'zona'),
        'categorias'  => array('id', 'nombre'),
        'productos'   => array('id', 'nombre', 'categoria_id', 'precio'),
        'ventas'      => array('id', 'fecha', 'vendedor_id', 'cliente'),
        'venta_items' => array('id', 'venta_id', 'producto_id', 'cantidad', 'precio_unitario'),
    );
    return $columnas[$tabla];
}
