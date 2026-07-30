<?php
require_once __DIR__ . '/includes/functions.php';
$nombreNegocio = cfg('nombre_negocio', 'Mi Restaurante');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Estado de Pedido - <?= limpiar($nombreNegocio) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
:root {
    --p: #e8590c;
    --p2: #1d2939;
    --bg: #f3f6fb;
    --card: #ffffff;
    --ok: #1f9d55;
    --muted: #667085;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: 'Segoe UI', Roboto, sans-serif;
    color: #101828;
    background:
        radial-gradient(circle at 8% 0%, #ffe8d8 0, transparent 30%),
        radial-gradient(circle at 100% 100%, #dce9ff 0, transparent 35%),
        var(--bg);
}
.wrap {
    max-width: 980px;
    margin: 0 auto;
    padding: 26px 16px 40px;
}
.hero {
    background: linear-gradient(150deg, #ffffff, #fff4ed 48%, #f6fbff);
    border: 1px solid #ecf0f5;
    border-radius: 22px;
    box-shadow: 0 18px 44px rgba(16, 24, 40, .08);
    padding: 22px;
}
.hero h1 { margin: 0; font-size: 30px; }
.hero p { margin: 8px 0 0; color: var(--muted); }
.search {
    margin-top: 14px;
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 10px;
}
.search input {
    width: 100%;
    border: 1px solid #d8dde5;
    border-radius: 12px;
    padding: 12px;
    font-size: 14px;
    background: #fff;
}
.search button {
    border: none;
    border-radius: 12px;
    background: var(--p);
    color: #fff;
    padding: 0 18px;
    font-weight: 800;
    cursor: pointer;
}
.small-link { margin-top: 10px; }
.small-link a { color: #334155; font-size: 13px; }
.result {
    margin-top: 18px;
    display: grid;
    gap: 12px;
}
.p-card {
    background: var(--card);
    border: 1px solid #e9edf3;
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 8px 24px rgba(16, 24, 40, .06);
}
.p-head { display: flex; justify-content: space-between; gap: 8px; align-items: center; }
.p-code { font-weight: 800; font-size: 15px; }
.p-client { color: #475467; font-size: 13px; margin-top: 4px; }
.badge { display: inline-block; padding: 4px 11px; border-radius: 99px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
.b-pendiente { background: #fff4d2; color: #7a5a00; }
.b-pagado { background: #daf5df; color: #0f6c2f; }
.b-en_preparacion { background: #d7eef9; color: #0b4f68; }
.b-en_camino { background: #dce8ff; color: #1e3a8a; }
.b-entregado { background: #ffe4cc; color: #8a3f0f; }
.b-cancelado { background: #fee2e2; color: #991b1b; }
.timeline {
    margin-top: 12px;
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 6px;
}
.step {
    text-align: center;
    padding: 9px 6px;
    border-radius: 10px;
    border: 1px solid #ebeff5;
    color: #6b7280;
    font-size: 11px;
    font-weight: 700;
    background: #fafcff;
}
.step.on { background: #eaf8ee; color: #1f6d3a; border-color: #cce9d4; }
.step.cancel { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.meta { margin-top: 10px; color: #667085; font-size: 12px; display: flex; flex-wrap: wrap; gap: 10px; }
.empty { color: #667085; font-size: 14px; text-align: center; padding: 16px; }
@media (max-width: 760px) {
    .hero h1 { font-size: 24px; }
    .search { grid-template-columns: 1fr; }
    .timeline { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>
<div class="wrap">
    <section class="hero">
        <h1><i class="ti ti-route-2"></i> Sigue tu pedido</h1>
        <p>Consulta en vivo el estado de tus pedidos. Busca con tu nombre y telefono.</p>

        <div class="search">
            <input id="inputNombre" type="text" placeholder="Tu nombre">
            <input id="inputTelefono" type="text" placeholder="Tu telefono (ej. 987654321)">
            <button id="btnBuscar" type="button">Buscar</button>
        </div>
        <div class="small-link">
            <a href="index.php">Volver a la carta</a>
        </div>
    </section>

    <section id="result" class="result">
        <div class="empty">Ingresa tus datos para ver tus pedidos.</div>
    </section>
</div>

<script>
(function() {
    const API = 'api/estado_pedidos_cliente.php';
    const estadoPaso = {
        pendiente: 1,
        pagado: 2,
        en_preparacion: 3,
        en_camino: 4,
        entregado: 5,
        cancelado: 0
    };
    const labels = ['Pendiente', 'Pagado', 'Preparando', 'En camino', 'Entregado'];

    const inputNombre = document.getElementById('inputNombre');
    const inputTelefono = document.getElementById('inputTelefono');
    const btnBuscar = document.getElementById('btnBuscar');
    const result = document.getElementById('result');

    let timer = null;

    function esc(texto) {
        return String(texto || '').replace(/[&<>"']/g, function(ch) {
            return ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' })[ch];
        });
    }

    function fmtFecha(fechaIso) {
        const d = new Date(fechaIso);
        if (!d.getTime()) return '-';
        return d.toLocaleString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
    }

    function renderTimeline(estado) {
        if (estado === 'cancelado') {
            return '<div class="timeline"><div class="step cancel">Pedido cancelado</div></div>';
        }

        const paso = estadoPaso[estado] || 0;
        return '<div class="timeline">' + labels.map(function(lbl, idx) {
            const on = (idx + 1) <= paso ? ' on' : '';
            return '<div class="step' + on + '">' + lbl + '</div>';
        }).join('') + '</div>';
    }

    function renderPedidos(pedidos) {
        if (!pedidos.length) {
            result.innerHTML = '<div class="empty">No encontramos pedidos con esos datos.</div>';
            return;
        }

        result.innerHTML = pedidos.map(function(p) {
            const badgeClass = 'b-' + p.estado;
            const entrega = p.tipo_entrega === 'delivery' ? 'Delivery' : 'Recojo';
            return `
                <article class="p-card">
                    <div class="p-head">
                        <div>
                            <div class="p-code">${esc(p.codigo)}</div>
                            <div class="p-client">${esc(p.cliente_nombre)} · ${esc(p.cliente_telefono)}</div>
                        </div>
                        <span class="badge ${badgeClass}">${esc(p.estado.replace(/_/g, ' '))}</span>
                    </div>
                    ${renderTimeline(p.estado)}
                    <div class="meta">
                        <span><i class="ti ti-truck-delivery"></i> ${entrega}</span>
                        <span><i class="ti ti-credit-card"></i> ${esc(p.metodo_pago)}</span>
                        <span><i class="ti ti-coins"></i> S/ ${Number(p.total).toFixed(2)}</span>
                        <span><i class="ti ti-clock"></i> ${fmtFecha(p.creado_en)}</span>
                    </div>
                </article>
            `;
        }).join('');
    }

    async function buscar() {
        const nombre = inputNombre.value.trim();
        const telefono = inputTelefono.value.trim();

        if (!nombre || !telefono) {
            result.innerHTML = '<div class="empty">Completa nombre y telefono.</div>';
            return;
        }

        result.innerHTML = '<div class="empty">Buscando pedidos...</div>';
        const qs = new URLSearchParams({ nombre, telefono });

        try {
            const r = await fetch(API + '?' + qs.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await r.json();
            if (!data.ok) throw new Error(data.mensaje || 'No se pudo consultar');
            renderPedidos(data.pedidos || []);
        } catch (err) {
            result.innerHTML = '<div class="empty">Error: ' + esc(err.message || String(err)) + '</div>';
        }
    }

    btnBuscar.addEventListener('click', function() {
        buscar();
        if (timer) clearInterval(timer);
        timer = setInterval(buscar, 10000);
    });

    const params = new URLSearchParams(window.location.search);
    const preNombre = params.get('nombre') || localStorage.getItem('cliente_nombre') || '';
    const preTelefono = params.get('telefono') || localStorage.getItem('cliente_telefono') || '';
    if (preNombre) inputNombre.value = preNombre;
    if (preTelefono) inputTelefono.value = preTelefono;

    if (preNombre && preTelefono) {
        buscar();
        timer = setInterval(buscar, 10000);
    }
})();
</script>
</body>
</html>
