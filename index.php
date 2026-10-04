<?php
require_once dirname(__FILE__) . '/includes/reportes.php';

function e($t)
{
    return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
}

$f = leer_filtros();
$vendedores = listar_vendedores();
$categorias = listar_categorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(APP_NOMBRE); ?></title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>
<div class="d-flex">
    <aside class="barra-lateral d-none d-lg-flex flex-column p-3 text-white">
        <a href="index.php" class="fs-5 fw-semibold text-white text-decoration-none mb-4"><i class="bi bi-bar-chart-line-fill"></i> Reportes</a>
        <ul class="nav nav-pills flex-column gap-1">
            <li><a href="#" class="nav-link active"><i class="bi bi-speedometer2"></i> Ventas</a></li>
            <li><a href="#vendedores" class="nav-link text-white-50"><i class="bi bi-people"></i> Vendedores</a></li>
            <li><a href="#productos" class="nav-link text-white-50"><i class="bi bi-box-seam"></i> Productos</a></li>
        </ul>
        <div class="mt-auto small text-white-50">Datos de ejemplo de una distribuidora mayorista</div>
    </aside>

    <main class="flex-grow-1 p-3 p-lg-4 contenido">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h1 class="h3 m-0">Ventas</h1>
            <a class="btn btn-success btn-sm" id="btn-exportar" href="exportar.php"><i class="bi bi-filetype-csv"></i> Exportar CSV</a>
        </div>

        <form class="card card-body shadow-sm mb-4" id="filtros">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1" for="desde">Desde</label>
                    <input type="date" class="form-control form-control-sm" id="desde" name="desde" value="<?php echo e($f['desde']); ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1" for="hasta">Hasta</label>
                    <input type="date" class="form-control form-control-sm" id="hasta" name="hasta" value="<?php echo e($f['hasta']); ?>">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1" for="vendedor">Vendedor</label>
                    <select class="form-select form-select-sm" id="vendedor" name="vendedor">
                        <option value="0">Todos</option>
                        <?php foreach ($vendedores as $v): ?>
                        <option value="<?php echo (int) $v['id']; ?>"><?php echo e($v['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1" for="categoria">Categoría</label>
                    <select class="form-select form-select-sm" id="categoria" name="categoria">
                        <option value="0">Todas</option>
                        <?php foreach ($categorias as $c): ?>
                        <option value="<?php echo (int) $c['id']; ?>"><?php echo e($c['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="btn-group btn-group-sm w-100" role="group" aria-label="Rangos rápidos" id="rangos">
                        <button type="button" class="btn btn-outline-secondary" data-meses="1">1M</button>
                        <button type="button" class="btn btn-outline-secondary" data-meses="3">3M</button>
                        <button type="button" class="btn btn-outline-secondary" data-meses="12">12M</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="row g-3 mb-4" id="kpis">
            <?php foreach (array('facturado' => array('Facturado', 'bi-cash-stack'), 'ventas' => array('Ventas', 'bi-receipt'),
                                 'ticket_promedio' => array('Ticket promedio', 'bi-calculator'), 'unidades' => array('Unidades', 'bi-boxes')) as $clave => $info): ?>
            <div class="col-6 col-xl-3">
                <div class="card shadow-sm kpi h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between text-muted small"><?php echo $info[0]; ?> <i class="bi <?php echo $info[1]; ?>"></i></div>
                        <div class="valor" data-kpi="<?php echo $clave; ?>">–</div>
                        <div class="variacion small" data-variacion="<?php echo $clave; ?>"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-8">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <h2 class="h6">Facturación por mes</h2>
                    <div class="grafico"><canvas id="g-meses" aria-label="Facturación por mes" role="img"></canvas></div>
                </div></div>
            </div>
            <div class="col-xl-4">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <h2 class="h6">Por categoría</h2>
                    <div class="grafico"><canvas id="g-categorias" aria-label="Facturación por categoría" role="img"></canvas></div>
                </div></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-7" id="vendedores">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <h2 class="h6">Ranking de vendedores</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Vendedor</th><th class="d-none d-sm-table-cell">Zona</th><th class="text-end">Ventas</th><th class="text-end">Facturado</th><th class="text-end d-none d-md-table-cell">Ticket prom.</th><th style="min-width:9rem">Participación</th></tr></thead>
                            <tbody id="t-vendedores"></tbody>
                        </table>
                    </div>
                </div></div>
            </div>
            <div class="col-xl-5" id="productos">
                <div class="card shadow-sm h-100"><div class="card-body">
                    <h2 class="h6">Top 5 productos</h2>
                    <div class="grafico grafico-chico"><canvas id="g-productos" aria-label="Productos más vendidos" role="img"></canvas></div>
                </div></div>
            </div>
        </div>
    </main>
</div>

<script src="assets/vendor/jquery/jquery.min.js"></script>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/vendor/chartjs/chart.umd.js"></script>
<script src="assets/js/panel.js"></script>
</body>
</html>
