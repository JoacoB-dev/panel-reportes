<?php
/**
 * Devuelve en JSON todos los datos del panel para los filtros recibidos.
 * GET datos.php?desde=2026-01-01&hasta=2026-06-30&vendedor=0&categoria=0
 */
require_once dirname(__FILE__) . '/includes/reportes.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $f = leer_filtros();
    $anterior = periodo_anterior($f);

    $porMes = ventas_por_mes($f);
    foreach ($porMes as &$m) {
        $m['facturado'] = round((float) $m['facturado'], 2);
        $m['ventas'] = (int) $m['ventas'];
    }
    unset($m);

    $porCategoria = ventas_por_categoria($f);
    foreach ($porCategoria as &$c) {
        $c['facturado'] = round((float) $c['facturado'], 2);
    }
    unset($c);

    $top = top_productos($f);
    foreach ($top as &$p) {
        $p['unidades'] = (int) $p['unidades'];
        $p['facturado'] = round((float) $p['facturado'], 2);
    }
    unset($p);

    echo json_encode(array(
        'ok'           => true,
        'filtros'      => $f,
        'kpis'         => kpis($f),
        'kpis_previos' => kpis($anterior),
        'por_mes'      => $porMes,
        'por_categoria'=> $porCategoria,
        'top_productos'=> $top,
        'vendedores'   => ranking_vendedores($f),
    ));
} catch (Exception $e) {
    error_log('datos.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(array('ok' => false, 'mensaje' => 'No se pudieron calcular los reportes.'));
}
