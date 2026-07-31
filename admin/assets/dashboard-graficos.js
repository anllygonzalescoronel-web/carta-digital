// ==================================================================
// Gráficos del Dashboard: tendencia de pedidos (Chart.js) +
// anillos de progreso estilo "reloj" con brillo (SVG a mano)
// ==================================================================
document.addEventListener('DOMContentLoaded', function () {
    if (!window.datosGraficosDashboard) return;
    const datos = window.datosGraficosDashboard;
    const coloresBase = ['#f2c94c', '#4ade80', '#38d5fc', '#1769d3', '#ff843d', '#e44040', '#ff8a8a', '#1f9e6d', '#9b6bd6', '#3fb8af'];
    const coloresProductos = ['#016bb3', '#40d7eb', '#7ea8ff', '#ec823c', '#f2c94c', '#5b4bd6', '#ff8a8a', '#e44040', '#9b6bd6', '#3fb8af'];

    function formatoSoles(valor) {
        return 'S/ ' + Number(valor).toFixed(2);
    }

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

    // ---------- 2. Ventas hoy vs Ayer: dona hecha a mano con degradados,
    //              separación al pasar el cursor, tooltip y total en el centro ----------
    function pintarDonaVentas(idContenedor, vista) {
        const wrap = document.getElementById(idContenedor);
        if (!wrap || !vista || vista.valores.length < 2) return;

        const contenedor = wrap.closest('.grafico-box');
        const leyendaEl = contenedor ? contenedor.querySelector('.grafico-leyenda') : null;

        const cx = 100, cy = 100, rIn = 58, rOut = 84;
        const gapDeg = 6; // espacio entre los 2 arcos, a cada lado
        const hoy = vista.valores[0] || 0;
        const ayer = vista.valores[1] || 0;
        const total = hoy + ayer;

        // Degradados: naranja (Hoy) y verde (Ayer), 2 tonos cada uno = 4 tonos en total
        const defsGrad =
            '<defs>' +
            '<linearGradient id="' + idContenedor + '_gradHoy" x1="0%" y1="0%" x2="100%" y2="100%">' +
            '<stop offset="0%" stop-color="#ff9a4d"/><stop offset="100%" stop-color="#c9450c"/>' +
            '</linearGradient>' +
            '<linearGradient id="' + idContenedor + '_gradAyer" x1="0%" y1="0%" x2="100%" y2="100%">' +
            '<stop offset="0%" stop-color="#7cf5b0"/><stop offset="100%" stop-color="#189a5f"/>' +
            '</linearGradient>' +
            '</defs>';

        function punto(anguloDeg, radio) {
            const rad = (anguloDeg * Math.PI) / 180;
            return { x: cx + radio * Math.cos(rad), y: cy + radio * Math.sin(rad) };
        }

        // Path tipo "dona" (arco relleno entre rIn y rOut) para un segmento
        function pathSegmento(anguloInicio, anguloFin) {
            const largeArc = (anguloFin - anguloInicio) > 180 ? 1 : 0;
            const pOutIni = punto(anguloInicio, rOut);
            const pOutFin = punto(anguloFin, rOut);
            const pInFin = punto(anguloFin, rIn);
            const pInIni = punto(anguloInicio, rIn);
            return 'M ' + pOutIni.x + ' ' + pOutIni.y +
                ' A ' + rOut + ' ' + rOut + ' 0 ' + largeArc + ' 1 ' + pOutFin.x + ' ' + pOutFin.y +
                ' L ' + pInFin.x + ' ' + pInFin.y +
                ' A ' + rIn + ' ' + rIn + ' 0 ' + largeArc + ' 0 ' + pInIni.x + ' ' + pInIni.y +
                ' Z';
        }

        // Ángulos (empezando arriba, -90°), proporcionales al monto, con espacio entre ambos
        const anguloTotalDisponible = 360 - (2 * gapDeg);
        const fracHoy = total > 0 ? hoy / total : 0.5;
        const anguloHoy = anguloTotalDisponible * fracHoy;
        const anguloAyer = anguloTotalDisponible * (1 - fracHoy);

        const inicioHoy = -90 + (gapDeg / 2);
        const finHoy = inicioHoy + anguloHoy;
        const inicioAyer = finHoy + gapDeg;
        const finAyer = inicioAyer + anguloAyer;

        const segmentos = [
            { label: 'Hoy', valor: hoy, ini: inicioHoy, fin: finHoy, gradId: idContenedor + '_gradHoy', colorLeyenda: '#E8590C' },
            { label: 'Ayer', valor: ayer, ini: inicioAyer, fin: finAyer, gradId: idContenedor + '_gradAyer', colorLeyenda: '#1f9e6d' },
        ];

        let pathsHtml = '';
        segmentos.forEach(function (seg, i) {
            const bisector = (seg.ini + seg.fin) / 2;
            const rad = (bisector * Math.PI) / 180;
            const dist = 10; // qué tanto se separa al pasar el cursor
            const dx = (Math.cos(rad) * dist).toFixed(2);
            const dy = (Math.sin(rad) * dist).toFixed(2);
            pathsHtml += '<path class="dona-ventas-segmento" data-i="' + i + '" '
                + 'style="--dx:' + dx + 'px; --dy:' + dy + 'px;" '
                + 'd="' + pathSegmento(seg.ini, seg.fin) + '" fill="url(#' + seg.gradId + ')"/>';
        });

        // Mini gráfico de barritas en el centro, comparando las alturas de Hoy vs Ayer
        // (sin números, todo visual — la barra más alta es la que vendió más)
        const maxAltura = 42;
        const minAltura = 6;
        const maxValor = Math.max(hoy, ayer, 0.01);
        const alturaHoy = Math.max((hoy / maxValor) * maxAltura, hoy > 0 ? minAltura : 2);
        const alturaAyer = Math.max((ayer / maxValor) * maxAltura, ayer > 0 ? minAltura : 2);
        const baseY = 118;
        const barW = 16, gap = 10;
        const xHoy = 100 - barW - (gap / 2);
        const xAyer = 100 + (gap / 2);

        const barritasHtml =
            '<line x1="72" y1="' + baseY + '" x2="128" y2="' + baseY + '" stroke="rgba(150,150,160,.35)" stroke-width="1.5"/>' +
            '<rect x="' + xHoy + '" y="' + (baseY - alturaHoy) + '" width="' + barW + '" height="' + alturaHoy + '" rx="4" fill="url(#' + idContenedor + '_gradHoy)"/>' +
            '<rect x="' + xAyer + '" y="' + (baseY - alturaAyer) + '" width="' + barW + '" height="' + alturaAyer + '" rx="4" fill="url(#' + idContenedor + '_gradAyer)"/>';

        wrap.innerHTML =
            '<svg viewBox="0 0 200 200">' +
            defsGrad +
            '<g>' + pathsHtml + '</g>' +
            '<g>' + barritasHtml + '</g>' +
            '</svg>' +
            '<div class="dona-ventas-tooltip"></div>';

        // Tooltip que sigue al cursor, con el monto exacto de cada segmento
        const tooltip = wrap.querySelector('.dona-ventas-tooltip');
        wrap.querySelectorAll('.dona-ventas-segmento').forEach(function (path) {
            const seg = segmentos[parseInt(path.getAttribute('data-i'), 10)];
            path.addEventListener('mousemove', function (ev) {
                const rect = wrap.getBoundingClientRect();
                tooltip.style.left = (ev.clientX - rect.left) + 'px';
                tooltip.style.top = (ev.clientY - rect.top - 22) + 'px';
                tooltip.textContent = seg.label + ': ' + formatoSoles(seg.valor);
                tooltip.classList.add('visible');
            });
            path.addEventListener('mouseleave', function () {
                tooltip.classList.remove('visible');
            });
        });

        if (leyendaEl) {
            leyendaEl.innerHTML = [segmentos[1], segmentos[0]].map(function (seg) {
                return '<span class="leyenda-item"><i style="background:' + seg.colorLeyenda + '"></i>' + seg.label + ' (' + formatoSoles(seg.valor) + ')</span>';
            }).join('');
        }
    }

    if (datos.ventas) {
        pintarDonaVentas('anilloVentas', datos.ventas.hoyVsAyer);
    }

    // ---------- 3. Anillo de progreso continuo (usado por "Productos activos") ----------
    function pintarAnilloProgreso(idContenedor, vistas, indiceInicial, coloresPropios) {
        const wrap = document.getElementById(idContenedor);
        if (!wrap) return;
        const coloresUsar = coloresPropios || coloresBase;

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

            const colores = coloresUsar.slice(0, vista.labels.length);
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

            // Dirección hacia la que se "levanta" el aspa al pasar el cursor
            // (usa el ángulo medio de esa aspa, igual que en la dona de ventas)
            const bisector = anguloInicio + (segAngle / 2);
            const rad = (bisector * Math.PI) / 180;
            const dist = 8;
            const dx = (Math.cos(rad) * dist).toFixed(2);
            const dy = (Math.sin(rad) * dist).toFixed(2);

            aspas += '<path class="anillo-aspa" data-i="' + i + '" '
                   + 'style="animation-delay:' + retraso + 's; --dx:' + dx + 'px; --dy:' + dy + 'px;" '
                   + 'd="' + pathAspa(anguloInicio) + '" fill="' + color + '"/>';
        });

        wrap.innerHTML = '<svg viewBox="0 0 200 200">' +
            '<g>' + ticks + '</g>' +
            '<g>' + aspas + '</g>' +
            '<text x="100" y="96" text-anchor="middle" class="anillo-centro-num">' + total + '</text>' +
            '<text x="100" y="118" text-anchor="middle" class="anillo-centro-lbl">Total</text>' +
            '</svg>' +
            '<div class="dona-ventas-tooltip"></div>';

        // Tooltip con el número exacto de esa aspa al pasar el cursor
        const tooltip = wrap.querySelector('.dona-ventas-tooltip');
        wrap.querySelectorAll('.anillo-aspa').forEach(function (path) {
            const i = parseInt(path.getAttribute('data-i'), 10);
            path.addEventListener('mousemove', function (ev) {
                const rect = wrap.getBoundingClientRect();
                tooltip.style.left = (ev.clientX - rect.left) + 'px';
                tooltip.style.top = (ev.clientY - rect.top - 22) + 'px';
                tooltip.textContent = vista.labels[i] + ': ' + vista.valores[i];
                tooltip.classList.add('visible');
            });
            path.addEventListener('mouseleave', function () {
                tooltip.classList.remove('visible');
            });
        });

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



    // ---------- 4.5 Comprobantes: Boletas vs Facturas, año calendario (Chart.js, barras agrupadas) ----------
    if (typeof Chart !== 'undefined' && datos.comprobantes && datos.comprobantes.porMes) {
        const ctxComprobantesMes = document.getElementById('graficoComprobantesMensual');
        if (ctxComprobantesMes) {
            const vm = datos.comprobantes.porMes;
            const totalMeses = vm.labels.length;
            const mesesVisibles = Math.min(6, totalMeses);
            let indiceInicio = Math.max(totalMeses - mesesVisibles, 0);
 
            const chartComprobantes = new Chart(ctxComprobantesMes, {
                type: 'bar',
                data: {
                    labels: vm.labels,
datasets: [
                        {
                            label: 'Boleta',
                            data: vm.boleta,
                            backgroundColor: '#f2c94c',
                            borderRadius: 6,
                            barPercentage: 0.5,
                            categoryPercentage: 0.6,
                        },
                        {
                            label: 'Factura',
                            data: vm.factura,
                            backgroundColor: '#4ade80',
                            borderRadius: 6,
                            barPercentage: 0.5,
                            categoryPercentage: 0.6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'top', labels: { boxWidth: 10, font: { size: 11 } } },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.dataset.label + ': ' + ctx.parsed.y;
                                }
                            }
                        },
                        zoom: {
                            pan: { enabled: true, mode: 'x' },
                            zoom: { wheel: { enabled: true }, pinch: { enabled: true }, mode: 'x' },
                            limits: { x: { min: 0, max: totalMeses - 1, minRange: 1 } }
                        }
                    },


              scales: {
                        x: {
                            grid: { display: false },
                            min: vm.labels[indiceInicio],
                            max: vm.labels[totalMeses - 1],
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                            max: 20,
                        },
                    }

                    
                }



            });
 
            // Botones para deslizar de a un mes (además de la ruedita/pan del mouse)
            const btnIzq = document.getElementById('btn-comprobantes-izq');
            const btnDer = document.getElementById('btn-comprobantes-der');
 
            function desplazarMeses(pasos) {
                indiceInicio = Math.min(Math.max(indiceInicio + pasos, 0), totalMeses - mesesVisibles);
                const indiceFin = Math.min(indiceInicio + mesesVisibles - 1, totalMeses - 1);
                chartComprobantes.options.scales.x.min = vm.labels[indiceInicio];
                chartComprobantes.options.scales.x.max = vm.labels[indiceFin];
                chartComprobantes.update();
            }
 
            if (btnIzq) btnIzq.addEventListener('click', function () { desplazarMeses(-1); });
            if (btnDer) btnDer.addEventListener('click', function () { desplazarMeses(1); });
        }
    }


    
    if (datos.productos) {
        pintarAnilloProgreso('anilloProductos', [datos.productos.vsInactivos, datos.productos.porCategoria], 0, coloresProductos);
    }

    // ---------- 5. Paginación de "Últimos pedidos" sin recargar la página ----------
    const contPaginacion = document.getElementById('paginacion-pedidos');
    if (contPaginacion) {
        const tbody = document.getElementById('tbody-ultimos-pedidos');
        const btnPrev = document.getElementById('btn-pag-prev');
        const btnNext = document.getElementById('btn-pag-next');
        const txtPagina = document.getElementById('txt-pagina-actual');

        function irAPagina(pagina) {
            btnPrev.disabled = true;
            btnNext.disabled = true;
            fetch('ajax-ultimos-pedidos.php?pagina=' + pagina)
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    tbody.innerHTML = data.filasHtml;
                    txtPagina.textContent = 'Página ' + data.pagina + ' de ' + data.totalPaginas;
                    contPaginacion.setAttribute('data-pagina-actual', data.pagina);
                    btnPrev.disabled = data.pagina <= 1;
                    btnNext.disabled = data.pagina >= data.totalPaginas;
                })
                .catch(function () {
                    btnPrev.disabled = false;
                    btnNext.disabled = false;
                });
        }

        btnPrev.addEventListener('click', function () {
            const actual = parseInt(contPaginacion.getAttribute('data-pagina-actual'), 10);
            irAPagina(Math.max(actual - 1, 1));
        });
        btnNext.addEventListener('click', function () {
            const actual = parseInt(contPaginacion.getAttribute('data-pagina-actual'), 10);
            const total = parseInt(contPaginacion.getAttribute('data-total-paginas'), 10);
            irAPagina(Math.min(actual + 1, total));
        });
    }

});