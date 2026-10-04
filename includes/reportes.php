<?php
/**
 * Consultas de reportes. Cada función recibe los filtros ya validados:
 * array('desde' => 'Y-m-d', 'hasta' => 'Y-m-d', 'vendedor' => int|0, 'categoria' => int|0)
 */
require_once dirname(__FILE__) . '/db.php';

/**
 * Lee y valida los filtros de $_GET. Por defecto: últimos 12 meses completos.
 */
function leer_filtros()
{
    $hasta = isset($_GET['hasta']) ? $_GET['hasta'] : date('Y-m-d');
    $desde = isset($_GET['desde']) ? $_GET['desde'] : date('Y-m-01', strtotime('-11 months'));

    if (!fecha_valida($desde) || !fecha_valida($hasta) || $desde > $hasta) {
        $desde = date('Y-m-01', strtotime('-11 months'));
        $hasta = date('Y-m-d');
    }

    return array(
        'desde'     => $desde,
        'hasta'     => $hasta,
        'vendedor'  => isset($_GET['vendedor']) ? max(0, (int) $_GET['vendedor']) : 0,
        'categoria' => isset($_GET['categoria']) ? max(0, (int) $_GET['categoria']) : 0,
    );
}

function fecha_valida($fecha)
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

/**
 * FROM + WHERE comunes a todos los reportes. Devuelve array(sql, params).
 */
function base_consulta(array $f)
{
    $sql = ' FROM ventas v
             JOIN venta_items i ON i.venta_id = v.id
             JOIN productos p   ON p.id = i.producto_id
             JOIN categorias c  ON c.id = p.categoria_id
             JOIN vendedores ve ON ve.id = v.vendedor_id
             WHERE v.fecha BETWEEN :desde AND :hasta';
    $params = array(':desde' => $f['desde'], ':hasta' => $f['hasta']);

    if ($f['vendedor']) {
        $sql .= ' AND v.vendedor_id = :vendedor';
        $params[':vendedor'] = $f['vendedor'];
    }
    if ($f['categoria']) {
        $sql .= ' AND p.categoria_id = :categoria';
        $params[':categoria'] = $f['categoria'];
    }
    return array($sql, $params);
}

function consultar($sql, array $params)
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Indicadores principales del período. */
function kpis(array $f)
{
    list($from, $params) = base_consulta($f);
    $fila = consultar('SELECT COALESCE(SUM(i.cantidad * i.precio_unitario), 0) AS facturado,
                              COUNT(DISTINCT v.id) AS ventas,
                              COALESCE(SUM(i.cantidad), 0) AS unidades,
                              COUNT(DISTINCT v.cliente) AS clientes' . $from, $params);
    $k = $fila[0];
    $k['facturado'] = round((float) $k['facturado'], 2);
    $k['ventas'] = (int) $k['ventas'];
    $k['unidades'] = (int) $k['unidades'];
    $k['clientes'] = (int) $k['clientes'];
    $k['ticket_promedio'] = $k['ventas'] ? round($k['facturado'] / $k['ventas'], 2) : 0;
    return $k;
}

/** Período anterior de igual duración, para comparar. */
function periodo_anterior(array $f)
{
    $dias = (strtotime($f['hasta']) - strtotime($f['desde'])) / 86400 + 1;
    $f['hasta'] = date('Y-m-d', strtotime($f['desde'] . ' -1 day'));
    $f['desde'] = date('Y-m-d', strtotime($f['hasta'] . ' -' . ($dias - 1) . ' days'));
    return $f;
}

function ventas_por_mes(array $f)
{
    list($from, $params) = base_consulta($f);
    $mes = es_sqlite() ? "strftime('%Y-%m', v.fecha)" : "DATE_FORMAT(v.fecha, '%Y-%m')";
    return consultar("SELECT $mes AS mes,
                             SUM(i.cantidad * i.precio_unitario) AS facturado,
                             COUNT(DISTINCT v.id) AS ventas
                      $from
                      GROUP BY $mes
                      ORDER BY mes", $params);
}

function ventas_por_categoria(array $f)
{
    list($from, $params) = base_consulta($f);
    return consultar("SELECT c.nombre AS categoria, SUM(i.cantidad * i.precio_unitario) AS facturado
                      $from
                      GROUP BY c.id, c.nombre
                      ORDER BY facturado DESC", $params);
}

function top_productos(array $f, $limite = 5)
{
    list($from, $params) = base_consulta($f);
    return consultar("SELECT p.nombre AS producto, c.nombre AS categoria,
                             SUM(i.cantidad) AS unidades,
                             SUM(i.cantidad * i.precio_unitario) AS facturado
                      $from
                      GROUP BY p.id, p.nombre, c.nombre
                      ORDER BY facturado DESC
                      LIMIT " . (int) $limite, $params);
}

/**
 * Ranking de vendedores. La participación sobre el total sale de una subconsulta
 * con los mismos filtros, así el porcentaje es correcto aunque se filtre por categoría.
 */
function ranking_vendedores(array $f)
{
    list($from, $params) = base_consulta($f);
    $filas = consultar("SELECT ve.nombre AS vendedor, ve.zona,
                               COUNT(DISTINCT v.id) AS ventas,
                               SUM(i.cantidad * i.precio_unitario) AS facturado
                        $from
                        GROUP BY ve.id, ve.nombre, ve.zona
                        HAVING SUM(i.cantidad * i.precio_unitario) > 0
                        ORDER BY facturado DESC", $params);

    $total = 0;
    foreach ($filas as $fila) {
        $total += $fila['facturado'];
    }
    foreach ($filas as &$fila) {
        $fila['ventas'] = (int) $fila['ventas'];
        $fila['facturado'] = round((float) $fila['facturado'], 2);
        $fila['ticket_promedio'] = $fila['ventas'] ? round($fila['facturado'] / $fila['ventas'], 2) : 0;
        $fila['participacion'] = $total ? round($fila['facturado'] * 100 / $total, 1) : 0;
    }
    unset($fila);
    return $filas;
}

function listar_vendedores()
{
    return consultar('SELECT id, nombre FROM vendedores ORDER BY nombre', array());
}

function listar_categorias()
{
    return consultar('SELECT id, nombre FROM categorias ORDER BY nombre', array());
}
