// ==================================================================
// Gráficos del Dashboard: tendencia de pedidos (Chart.js) +
// anillos de progreso estilo "reloj" con brillo (SVG a mano)
// ==================================================================
document.addEventListener('DOMContentLoaded', function () {
    if (!window.datosGraficosDashboard) return;
    const datos = window.datosGraficosDashboard;
    const coloresBase = ['#E8590C', '#4ade80', '#7ea8ff', '#c23b8a', '#f2c94c', '#5b4bd6', '#ff8a8a', '#1f9e6d', '#9b6bd6', '#3fb8af'];

    // ---------- 1. Pedidos: gráfico de área, últimos 7 días (Chart.js) ----------
    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
        Chart.defaults.color = '#888';

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
                            pan: { enabled: true, mode: 'x' },
                            zoom: { wheel: { enabled: true }, pinch: { enabled: true }, mode: 'x' },
                            limits: { x: { min: 'original', max: 'original' } }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    }

    // ---------- 2. Ventas hoy vs Ayer: se queda como dona normal de Chart.js ----------
    function crearDonaSimple(idCanvas, vista) {
        const canvas = document.getElementById(idCanvas);
        if (!canvas || typeof Chart === 'undefined' || !vista || !vista.labels.length) return;
        const colores = coloresBase.slice(0, vista.labels.length);
        new Chart(canvas, {
            type: 'doughnut',
            data: { labels: vista.labels, datasets: [{ data: vista.valores, backgroundColor: colores, borderWidth: 0, hoverOffset: 6 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
        });
        const contenedor = canvas.closest('.grafico-box');
        const leyendaEl = contenedor ? contenedor.querySelector('.grafico-leyenda') : null;
        if (leyendaEl) {
            leyendaEl.innerHTML = vista.labels.map(function (etq, i) {
                return '<span class="leyenda-item"><i style="background:' + colores[i % colores.length] + '"></i>' + etq + '</span>';
            }).join('');
        }
    }
    if (datos.ventas) {
        crearDonaSimple('graficoVentas', datos.ventas.hoyVsAyer);
    }

    // ---------- 3. Anillo de progreso continuo (usado por "Productos activos") ----------
    function pintarAnilloProgreso(idContenedor, vistas, indiceInicial) {
        const wrap = document.getElementById(idContenedor);
        if (!wrap) return;

        const contenedor = wrap.closest('.grafico-box');
        const tabsWrap = contenedor ? contenedor.querySelector('.grafico-tabs') : null;
        const leyendaEl = contenedor ? contenedor.querySelector('.grafico-leyenda') : null;

        const cx = 100, cy = 100, rTicksInt = 46, rTicksExt = 58, rArco = 76, numTicks = 60;

        function construirSVG() {
            let ticks = '';
            for (let i = 0; i < numTicks; i++) {
                const angulo = (i / numTicks) * 360;
                const rad = (angulo * Math.PI) / 180;
                const x1 = cx + rTicksInt * Math.cos(rad);
                const y1 = cy + rTicksInt * Math.sin(rad);
                const x2 = cx + rTicksExt * Math.cos(rad);
                const y2 = cy + rTicksExt * Math.sin(rad);
                const retraso = (i * (0.9 / numTicks)).toFixed(3);
                ticks += '<line class="anillo-tick anillo-tick-anim" style="animation-delay:' + retraso + 's" '
                       + 'x1="' + x1 + '" y1="' + y1 + '" x2="' + x2 + '" y2="' + y2 + '"/>';
            }
            return '<svg viewBox="0 0 200 200">' +
                '<g>' + ticks + '</g>' +
                '<g id="' + idContenedor + '_arcos"></g>' +
                '<text id="' + idContenedor + '_num" x="100" y="96" text-anchor="middle" class="anillo-centro-num">0</text>' +
                '<text id="' + idContenedor + '_lbl" x="100" y="118" text-anchor="middle" class="anillo-centro-lbl"></text>' +
                '</svg>';
        }

        wrap.innerHTML = construirSVG();

        function render(indiceVista) {
            const vista = vistas[indiceVista];
            const grupoArcos = document.getElementById(idContenedor + '_arcos');
            const numEl = document.getElementById(idContenedor + '_num');
            const lblEl = document.getElementById(idContenedor + '_lbl');
            if (!vista || !vista.labels.length || !grupoArcos) return;

            const colores = coloresBase.slice(0, vista.labels.length);
            const total = vista.valores.reduce(function (a, b) { return a + b; }, 0);
            const circun = 2 * Math.PI * rArco;

            grupoArcos.innerHTML = '';
            let acumulado = 0;
            vista.valores.forEach(function (valor, i) {
                const frac = total > 0 ? valor / total : 0;
                const largo = circun * frac;
                const offset = circun * (1 - acumulado);
                const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', cx);
                circle.setAttribute('cy', cy);
                circle.setAttribute('r', rArco);
                circle.setAttribute('class', 'anillo-segmento');
                circle.setAttribute('stroke', colores[i]);
                circle.setAttribute('stroke-width', '26');
                circle.setAttribute('transform', 'rotate(-90 ' + cx + ' ' + cy + ')');
                circle.setAttribute('stroke-dasharray', '0 ' + circun);
                circle.setAttribute('stroke-dashoffset', offset);
                grupoArcos.appendChild(circle);
                requestAnimationFrame(function () {
                    circle.setAttribute('stroke-dasharray', largo + ' ' + (circun - largo));
                });
                acumulado += frac;
            });

            if (vista.labels.length <= 2) {
                numEl.textContent = vista.valores[0];
                lblEl.textContent = vista.labels[0];
            } else {
                numEl.textContent = total;
                lblEl.textContent = 'Total';
            }

            if (leyendaEl) {
                leyendaEl.innerHTML = vista.labels.map(function (etq, i) {
                    return '<span class="leyenda-item"><i style="background:' + colores[i] + '"></i>' + etq + ' (' + vista.valores[i] + ')</span>';
                }).join('');
            }
        }

        render(indiceInicial || 0);

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

    // ---------- 4. Anillo de aspas fijas (usado por "Pedidos pendientes por estado") ----------
    function pintarAnilloAspas(idContenedor, vista) {
        const wrap = document.getElementById(idContenedor);
        if (!wrap || !vista || !vista.labels.length) return;

        const contenedor = wrap.closest('.grafico-box');
        const leyendaEl = contenedor ? contenedor.querySelector('.grafico-leyenda') : null;

        const cx = 100, cy = 100, rTicksInt = 46, rTicksExt = 58;
        const rIn = 64, rOut = 84, rTip = 90;
        const numAspas = vista.labels.length;
        const gapAngle = 18;
        const segAngle = (360 / numAspas) - gapAngle;
        const notch = 10;
        const colorActivo = coloresBase.slice(0, numAspas);
        const colorInactivo = '#dcdfe6';

        function punto(anguloDeg, radio) {
            const rad = (anguloDeg * Math.PI) / 180;
            return { x: cx + radio * Math.cos(rad), y: cy + radio * Math.sin(rad) };
        }

        function pathAspa(anguloInicio) {
            const anguloFinNotch = anguloInicio + segAngle - notch;
            const anguloTip = anguloInicio + segAngle;
            const pOutIni = punto(anguloInicio, rOut);
            const pOutFin = punto(anguloFinNotch, rOut);
            const pTip = punto(anguloTip, rTip);
            const pInFin = punto(anguloFinNotch, rIn);
            const pInIni = punto(anguloInicio, rIn);
            return 'M ' + pOutIni.x + ' ' + pOutIni.y +
                ' A ' + rOut + ' ' + rOut + ' 0 0 1 ' + pOutFin.x + ' ' + pOutFin.y +
                ' L ' + pTip.x + ' ' + pTip.y +
                ' L ' + pInFin.x + ' ' + pInFin.y +
                ' A ' + rIn + ' ' + rIn + ' 0 0 0 ' + pInIni.x + ' ' + pInIni.y +
                ' Z';
        }

        let ticks = '';
        const numTicks = 60;
        for (let i = 0; i < numTicks; i++) {
            const angulo = (i / numTicks) * 360;
            const rad = (angulo * Math.PI) / 180;
            const x1 = cx + rTicksInt * Math.cos(rad);
            const y1 = cy + rTicksInt * Math.sin(rad);
            const x2 = cx + rTicksExt * Math.cos(rad);
            const y2 = cy + rTicksExt * Math.sin(rad);
            const retraso = (i * (0.5 / numTicks)).toFixed(3);
            ticks += '<line class="anillo-tick anillo-tick-anim" style="animation-delay:' + retraso + 's" '
                   + 'x1="' + x1 + '" y1="' + y1 + '" x2="' + x2 + '" y2="' + y2 + '"/>';
        }

        let aspas = '';
        const total = vista.valores.reduce(function (a, b) { return a + b; }, 0);
        vista.valores.forEach(function (valor, i) {
            const anguloInicio = (360 / numAspas) * i - 90;
            const color = valor > 0 ? colorActivo[i] : colorInactivo;
            const retraso = (0.5 + i * 0.08).toFixed(2);
            aspas += '<path class="anillo-aspa" style="animation-delay:' + retraso + 's" '
                   + 'd="' + pathAspa(anguloInicio) + '" fill="' + color + '"/>';
        });

        wrap.innerHTML = '<svg viewBox="0 0 200 200">' +
            '<g>' + ticks + '</g>' +
            '<g>' + aspas + '</g>' +
            '<text x="100" y="96" text-anchor="middle" class="anillo-centro-num">' + total + '</text>' +
            '<text x="100" y="118" text-anchor="middle" class="anillo-centro-lbl">Total</text>' +
            '</svg>';

        if (leyendaEl) {
            leyendaEl.innerHTML = vista.labels.map(function (etq, i) {
                const color = vista.valores[i] > 0 ? colorActivo[i] : colorInactivo;
                return '<span class="leyenda-item"><i style="background:' + color + '"></i>' + etq + ' (' + vista.valores[i] + ')</span>';
            }).join('');
        }
    }

    if (datos.pendientes) {
        pintarAnilloAspas('anilloPendientes', datos.pendientes.porEstado);
    }
    if (datos.productos) {
        pintarAnilloProgreso('anilloProductos', [datos.productos.vsInactivos, datos.productos.porCategoria], 0);
    }
});