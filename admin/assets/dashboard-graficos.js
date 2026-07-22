// ==================================================================
// Gráficos del Dashboard: tendencia de pedidos + 3 donas con toggle
// Requiere Chart.js cargado ANTES de este archivo (ver instrucciones)
// y que index.php haya definido window.datosGraficosDashboard
// ==================================================================
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined' || !window.datosGraficosDashboard) {
        return;
    }

    const datos = window.datosGraficosDashboard;
    const coloresBase = ['#E8590C', '#4ade80', '#7ea8ff', '#c23b8a', '#f2c94c', '#5b4bd6', '#ff8a8a', '#1f9e6d', '#9b6bd6', '#3fb8af'];

    Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
    Chart.defaults.color = '#888';

    // ---------- 1. Pedidos: gráfico de área, últimos 7 días ----------
const ctxTendencia = document.getElementById('graficoPedidosTendencia');
    if (ctxTendencia && datos.tendencia) {
        new Chart(ctxTendencia, {
            type: 'line',
            data: {
                labels: datos.tendencia.labels,
                datasets: [{
                    label: 'Pedidos',
                    data: datos.tendencia.valores,
                    fill: true,
                    borderColor: '#E8590C',
                    backgroundColor: 'rgba(232,89,12,.15)',
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#E8590C',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    zoom: {
                        pan: {
                            enabled: true,
                            mode: 'x',
                        },
                        zoom: {
                            wheel: { enabled: true },
                            pinch: { enabled: true },
                            mode: 'x',
                        },
                        limits: {
                            x: { min: 'original', max: 'original' }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ---------- Función genérica: dona con toggle entre "vistas" ----------
    function crearDonaConToggle(idCanvas, nombreGrafico, vistas) {
        const canvas = document.getElementById(idCanvas);
        if (!canvas) return;

        const contenedor = canvas.closest('.grafico-box');
        const tabsWrap = contenedor
            ? contenedor.querySelector('.grafico-tabs[data-grafico="' + nombreGrafico + '"]')
            : null;
        const leyendaEl = contenedor ? contenedor.querySelector('.grafico-leyenda') : null;

        let grafico = null;

        function pintarLeyenda(labels, colores) {
            if (!leyendaEl) return;
            leyendaEl.innerHTML = labels.map(function (etiqueta, i) {
                return '<span class="leyenda-item"><i style="background:' + colores[i % colores.length] + '"></i>' + etiqueta + '</span>';
            }).join('');
        }

        function render(indiceVista) {
            const vista = vistas[indiceVista];
            if (!vista || !vista.labels || !vista.labels.length) {
                if (leyendaEl) leyendaEl.innerHTML = '<span style="color:#aaa;">Sin datos para mostrar</span>';
                if (grafico) { grafico.destroy(); grafico = null; }
                return;
            }
            const colores = coloresBase.slice(0, vista.labels.length);

            if (grafico) { grafico.destroy(); }
            grafico = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: vista.labels,
                    datasets: [{
                        data: vista.valores,
                        backgroundColor: colores,
                        borderWidth: 0,
                        hoverOffset: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true }
                    }
                }
            });
            pintarLeyenda(vista.labels, colores);
        }

        render(0);

        if (tabsWrap) {
            const botones = tabsWrap.querySelectorAll('button');
            botones.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    botones.forEach(function (b) { b.classList.remove('tab-activa'); });
                    btn.classList.add('tab-activa');
                    render(parseInt(btn.getAttribute('data-vista'), 10));
                });
            });
        }
    }

    // ---------- 2. Ventas hoy vs Ayer (sin toggle) ----------
    if (datos.ventas) {
        crearDonaConToggle('graficoVentas', 'ventas', [
            datos.ventas.hoyVsAyer
        ]);
    }

    // ---------- 3. Pedidos pendientes: vs Total / Por estado ----------
    if (datos.pendientes) {
        crearDonaConToggle('graficoPendientes', 'pendientes', [
            datos.pendientes.vsTotal,
            datos.pendientes.porEstado
        ]);
    }

    // ---------- 4. Productos activos: vs Inactivos / Por categoría ----------
    if (datos.productos) {
        crearDonaConToggle('graficoProductos', 'productos', [
            datos.productos.vsInactivos,
            datos.productos.porCategoria
        ]);
    }
});