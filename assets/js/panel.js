/* Panel de reportes: pide los datos por Ajax y arma KPIs, gráficos y tablas */
$(function () {
    'use strict';

    var COLORES = ['#2563eb', '#16a34a', '#f59e0b', '#db2777', '#7c3aed', '#0891b2'];
    var MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    var graficos = {};
    var $form = $('#filtros');

    var pesos = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 });
    var numero = new Intl.NumberFormat('es-AR');
    var compacto = new Intl.NumberFormat('es-AR', { notation: 'compact', maximumFractionDigits: 1 });

    function esc(t) {
        return $('<div>').text(t == null ? '' : String(t)).html();
    }

    function nombreMes(aaaamm) {
        var partes = aaaamm.split('-');
        return MESES[parseInt(partes[1], 10) - 1] + ' ' + partes[0].slice(2);
    }

    function dibujar(id, config) {
        if (graficos[id]) {
            graficos[id].destroy();
        }
        graficos[id] = new Chart(document.getElementById(id), config);
    }

    function mostrarKpis(actual, previo) {
        $.each(actual, function (clave, valor) {
            var $v = $('[data-kpi="' + clave + '"]');
            if (!$v.length) { return; }
            $v.text(clave === 'facturado' || clave === 'ticket_promedio' ? pesos.format(valor) : numero.format(valor));

            var $var = $('[data-variacion="' + clave + '"]');
            if (!previo[clave]) {
                $var.html('<span class="text-muted">Sin datos del período anterior</span>');
                return;
            }
            var pct = (valor - previo[clave]) * 100 / previo[clave];
            var sube = pct >= 0;
            $var.html('<span class="' + (sube ? 'text-success' : 'text-danger') + '"><i class="bi bi-arrow-' + (sube ? 'up' : 'down') + '-right"></i> ' +
                Math.abs(pct).toFixed(1).replace('.', ',') + ' %</span> <span class="text-muted">vs. período anterior</span>');
        });
    }

    function mostrarVendedores(filas) {
        if (!filas.length) {
            $('#t-vendedores').html('<tr><td colspan="6" class="text-center text-muted py-3">Sin ventas en el período</td></tr>');
            return;
        }
        $('#t-vendedores').html($.map(filas, function (v) {
            return '<tr><td class="fw-semibold">' + esc(v.vendedor) + '</td>' +
                '<td class="d-none d-sm-table-cell">' + esc(v.zona) + '</td>' +
                '<td class="text-end">' + numero.format(v.ventas) + '</td>' +
                '<td class="text-end">' + pesos.format(v.facturado) + '</td>' +
                '<td class="text-end d-none d-md-table-cell">' + pesos.format(v.ticket_promedio) + '</td>' +
                '<td><div class="d-flex align-items-center gap-2">' +
                '<div class="progress flex-grow-1" role="progressbar" aria-label="Participación" aria-valuenow="' + v.participacion + '" aria-valuemin="0" aria-valuemax="100">' +
                '<div class="progress-bar" style="width:' + v.participacion + '%"></div></div>' +
                '<span class="small text-muted text-nowrap">' + String(v.participacion).replace('.', ',') + ' %</span></div></td></tr>';
        }).join(''));
    }

    function cargar() {
        var params = $form.serialize();
        $('#btn-exportar').attr('href', 'exportar.php?' + params);
        $('main').addClass('cargando');

        $.getJSON('datos.php', params).done(function (r) {
            mostrarKpis(r.kpis, r.kpis_previos);
            mostrarVendedores(r.vendedores);

            dibujar('g-meses', {
                type: 'bar',
                data: {
                    labels: $.map(r.por_mes, function (m) { return nombreMes(m.mes); }),
                    datasets: [
                        { type: 'line', label: 'Ventas', data: $.map(r.por_mes, function (m) { return m.ventas; }),
                          yAxisID: 'y2', borderColor: COLORES[2], backgroundColor: COLORES[2], tension: .3 },
                        { label: 'Facturado', data: $.map(r.por_mes, function (m) { return m.facturado; }),
                          backgroundColor: COLORES[0], borderRadius: 4 }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { ticks: { callback: function (v) { return '$' + compacto.format(v); } } },
                        y2: { position: 'right', grid: { drawOnChartArea: false } }
                    },
                    plugins: { tooltip: { callbacks: { label: function (c) {
                        return c.dataset.label + ': ' + (c.dataset.yAxisID === 'y2' ? numero.format(c.raw) : pesos.format(c.raw));
                    } } } }
                }
            });

            dibujar('g-categorias', {
                type: 'doughnut',
                data: {
                    labels: $.map(r.por_categoria, function (c) { return c.categoria; }),
                    datasets: [{ data: $.map(r.por_categoria, function (c) { return c.facturado; }), backgroundColor: COLORES }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: { callbacks: { label: function (c) { return c.label + ': ' + pesos.format(c.raw); } } }
                    }
                }
            });

            dibujar('g-productos', {
                type: 'bar',
                data: {
                    labels: $.map(r.top_productos, function (p) { return p.producto; }),
                    datasets: [{ label: 'Facturado', data: $.map(r.top_productos, function (p) { return p.facturado; }),
                                 backgroundColor: COLORES[1], borderRadius: 4 }]
                },
                options: {
                    indexAxis: 'y',
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false },
                               tooltip: { callbacks: { label: function (c) {
                                   return pesos.format(c.raw) + ' · ' + numero.format(r.top_productos[c.dataIndex].unidades) + ' u.';
                               } } } },
                    scales: { x: { ticks: { callback: function (v) { return '$' + compacto.format(v); } } } }
                }
            });
        }).fail(function () {
            alert('No se pudieron cargar los reportes.');
        }).always(function () {
            $('main').removeClass('cargando');
        });
    }

    $form.on('change', 'input, select', function () {
        $('#rangos .btn').removeClass('active');
        cargar();
    });

    $('#rangos').on('click', '.btn', function () {
        var meses = $(this).data('meses');
        var hasta = new Date();
        var desde = new Date(hasta.getFullYear(), hasta.getMonth() - meses + 1, 1);
        function iso(d) {
            return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
        }
        $('#desde').val(iso(desde));
        $('#hasta').val(iso(hasta));
        $(this).addClass('active').siblings().removeClass('active');
        cargar();
    });

    $form.on('submit', function (e) { e.preventDefault(); });
    $('#rangos [data-meses=12]').addClass('active');
    cargar();
});
