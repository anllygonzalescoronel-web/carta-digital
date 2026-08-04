<?php
require_once __DIR__ . '/includes/cliente_auth.php';
require_once __DIR__ . '/includes/functions.php';

requerirClienteLogin();
$cliente = obtenerClienteActual();
$nombreNegocio = cfg('nombre_negocio', 'Mi Restaurante');
$colorPrimario = cfg('color_primario', '#1f6b3a');
$colorPrimarioFuerte = cfg('color_primario_fuerte', '#154d29');
$colorSecundario = cfg('color_secundario', '#3ea152');
$colorTexto = cfg('color_texto', '#1c2b22');
$colorFondo = cfg('color_fondo', '#f2f6f2');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mi dashboard - <?= limpiar($nombreNegocio) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
:root{
    --p: <?= limpiar($colorPrimario) ?>;
    --p-strong: <?= limpiar($colorPrimarioFuerte) ?>;
    --s: <?= limpiar($colorSecundario) ?>;
    --t: <?= limpiar($colorTexto) ?>;
    --bg: <?= limpiar($colorFondo) ?>;
    --card:#ffffff;
    --muted:#64748b;
    --line:#dde5df;
}
*{box-sizing:border-box}
body{
    margin:0;
    color:var(--t);
    font-family:'Segoe UI',system-ui,sans-serif;
    background:
      radial-gradient(circle at 10% 0%, color-mix(in oklab, var(--s) 18%, transparent) 0%, transparent 26%),
      radial-gradient(circle at 95% 100%, color-mix(in oklab, var(--p) 18%, transparent) 0%, transparent 30%),
      var(--bg);
}
.wrap{max-width:1180px;margin:0 auto;padding:20px 14px 54px}

.hero{
    border-radius:26px;
    padding:22px;
    color:#fff;
    background:linear-gradient(140deg,var(--p-strong) 0%,var(--p) 52%,var(--s) 100%);
    box-shadow:0 18px 40px rgba(15,23,42,.18);
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:16px;
    flex-wrap:wrap;
}
.hero h1{margin:8px 0 6px;font-size:34px;line-height:1.02}
.hero p{margin:0;color:rgba(255,255,255,.9)}
.hero-top{font-size:11px;font-weight:800;letter-spacing:.13em;color:rgba(255,255,255,.76)}
.hero-actions{display:flex;gap:9px;flex-wrap:wrap}

.btn{border:none;border-radius:999px;padding:11px 15px;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;gap:7px;cursor:pointer}
.btn-light{background:#fff;color:#0f172a}
.btn-ghost{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.2)}
.btn-save{width:100%;justify-content:center;background:linear-gradient(135deg,var(--p-strong),var(--p));color:#fff}
.btn-order{background:linear-gradient(135deg,var(--p-strong),var(--p));color:#fff;box-shadow:0 12px 24px color-mix(in oklab,var(--p) 45%, transparent)}

.layout{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);gap:16px;margin-top:16px}
.col{display:grid;gap:16px}
.card{background:var(--card);border:1px solid #e6ece8;border-radius:22px;padding:16px;box-shadow:0 8px 24px rgba(15,23,42,.06)}
.section-title{margin:0 0 12px;font-size:17px}

.kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:11px}
.kpi-item{position:relative;overflow:hidden;border-radius:16px;padding:14px;color:#fff;min-height:112px;box-shadow:0 10px 24px rgba(0,0,0,.16)}
.kpi-item .lbl{font-size:11px;opacity:.9}
.kpi-item .num{font-size:24px;font-weight:900;line-height:1.15;margin-top:2px}
.kpi-item .sub{font-size:11px;opacity:.86;margin-top:6px}
.kpi-item i{position:absolute;right:10px;top:10px;font-size:20px;opacity:.28}
.kpi-a{background:linear-gradient(135deg,#2a1b6a 0%,#5b4bd6 46%,#7ea8ff 100%)}
.kpi-b{background:linear-gradient(135deg,#0f4c3a 0%,#1f9e6d 46%,#7fe0a8 100%)}
.kpi-c{background:linear-gradient(135deg,#7a2b0f 0%,#e8590c 46%,#ffb37a 100%)}
.kpi-d{background:linear-gradient(135deg,#6a1b6a 0%,#c23b8a 46%,#ff7a9e 100%)}

.charts-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.chart-card{border:1px solid #edf2ee;border-radius:18px;padding:12px;background:#fff}
.chart-card h4{margin:0 0 10px;font-size:13px;color:#334155}

.donut-wrap{display:flex;gap:12px;align-items:center}
.donut{
    --d1:#1f9e6d; --d2:#f59e0b; --d3:#0ea5e9; --d4:#ef4444;
    width:120px;height:120px;border-radius:50%;
    background:conic-gradient(var(--d1) 0 25%, var(--d2) 25% 50%, var(--d3) 50% 75%, var(--d4) 75% 100%);
    position:relative;flex:0 0 auto;
}
.donut::after{content:'';position:absolute;inset:18px;border-radius:50%;background:#fff;box-shadow:inset 0 0 0 1px #edf2ee}
.donut-center{position:absolute;inset:0;display:grid;place-items:center;font-size:11px;font-weight:800;color:#0f172a;z-index:1;text-align:center;line-height:1.2}
.legend{display:grid;gap:6px;min-width:0}
.legend-item{display:flex;align-items:center;gap:7px;font-size:11px;color:#475569}
.legend-item i{width:9px;height:9px;border-radius:50%}

.bars{display:flex;align-items:flex-end;gap:8px;height:132px;padding-top:6px}
.bar-col{flex:1;min-width:0;display:flex;flex-direction:column;align-items:center;gap:6px}
.bar{width:100%;border-radius:10px 10px 7px 7px;min-height:4px;background:linear-gradient(180deg,var(--s),var(--p));box-shadow:0 4px 10px rgba(0,0,0,.08)}
.bar-val{font-size:10px;color:#334155;font-weight:700}
.bar-lbl{font-size:10px;color:#64748b;white-space:nowrap;max-width:100%;overflow:hidden;text-overflow:ellipsis}

.club{background:linear-gradient(135deg,var(--p-strong),var(--p) 58%,var(--s));color:#fff;position:relative;overflow:hidden}
.club::after{content:'';position:absolute;right:-30px;bottom:-30px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.08)}
.club p,.club .mini,.club strong,.club span{position:relative;z-index:1}
.progress{height:11px;border-radius:999px;background:rgba(255,255,255,.18);overflow:hidden;margin:9px 0 7px}
.progress span{display:block;height:100%;width:0;background:linear-gradient(90deg,#ffe48f,#fff)}
.club-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;margin-top:10px}
.club-mini{padding:11px;border-radius:14px;background:rgba(255,255,255,.11);border:1px solid rgba(255,255,255,.13)}
.club-mini span{display:block;font-size:11px;color:rgba(255,255,255,.8);margin-bottom:4px}
.club-mini strong{display:block;font-size:15px}

.orders{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.order{
    border-radius:16px;
    padding:13px;
    border:1px solid #e8ede9;
    background:linear-gradient(145deg,#ffffff,#f8fbf9);
}
.order-top{display:flex;justify-content:space-between;gap:8px;align-items:center}
.order-code{font-weight:800;font-size:14px;color:#0f172a}
.order-date{font-size:11px;color:#64748b;margin-top:3px}
.order-meta{display:flex;gap:7px;flex-wrap:wrap;font-size:11px;color:#475569;margin-top:8px}
.order-pill{display:inline-flex;padding:4px 8px;border-radius:999px;font-size:10px;font-weight:800;text-transform:uppercase}
.p-pendiente{background:#fff4d2;color:#7a5a00}
.p-pagado{background:#daf5df;color:#0f6c2f}
.p-en_preparacion{background:#d7eef9;color:#0b4f68}
.p-en_camino{background:#dce8ff;color:#1e3a8a}
.p-entregado{background:#ffe4cc;color:#8a3f0f}
.p-cancelado{background:#fee2e2;color:#991b1b}

.empty{
    border:1px dashed #ced9d1;
    border-radius:18px;
    padding:20px;
    text-align:center;
    background:#fbfdfb;
}
.empty h4{margin:0 0 6px;font-size:17px}
.empty p{margin:0 0 12px;color:#64748b;font-size:13px}

.profile-line{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.avatar{width:54px;height:54px;border-radius:50%;background:#edf6ef;display:grid;place-items:center;font-size:22px;color:var(--p);overflow:hidden}
.avatar img{width:100%;height:100%;object-fit:cover}
.kv{display:grid;gap:9px}
.kv-item{padding:11px 12px;border:1px solid #e8efea;border-radius:14px}
.kv-item span{display:block;font-size:11px;color:var(--muted);margin-bottom:4px}
.kv-item strong{display:block;font-size:13px}
.field{margin-bottom:10px}
.field label{display:block;font-size:12px;font-weight:700;margin-bottom:6px;color:#334155}
.field input{width:100%;border:1px solid #d8e2dc;border-radius:14px;padding:11px 12px;font-size:14px}
.field input:focus{outline:none;border-color:var(--p)}
.msg{display:none;border-radius:12px;padding:10px 11px;font-size:13px;margin-bottom:10px}
.msg.ok{display:block;background:#e8f8ee;color:#166534}
.msg.err{display:block;background:#fef0f0;color:#b42318}
.top-links{display:flex;gap:9px;flex-wrap:wrap;margin-top:12px}

@media (max-width:960px){
    .layout{grid-template-columns:1fr}
}
@media (max-width:760px){
    .hero h1{font-size:29px}
    .kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .charts-grid{grid-template-columns:1fr}
    .orders{grid-template-columns:1fr}
}
@media (max-width:560px){
    .wrap{padding:14px 10px 40px}
    .hero{padding:16px;border-radius:20px}
    .hero h1{font-size:23px}
    .btn{padding:10px 12px;font-size:12px}
    .card{border-radius:18px;padding:13px}
}
</style>
</head>
<body>
<div class="wrap">
    <section class="hero">
        <div>
            <div class="hero-top">MI DASHBOARD</div>
            <h1>Hola, <?= limpiar($cliente['nombre'] ?? 'Cliente') ?></h1>
            <p>Visualiza tu historial, fidelizacion y estado de pedidos en tiempo real.</p>
        </div>
        <div class="hero-actions">
            <a class="btn btn-light" href="index.php"><i class="ti ti-world"></i> Ver carta</a>
            <a class="btn btn-ghost" href="estado-pedido.php"><i class="ti ti-route-2"></i> Estado</a>
            <button class="btn btn-ghost" type="button" id="btnCerrarSesion"><i class="ti ti-logout"></i> Salir</button>
        </div>
    </section>

    <div class="layout">
        <div class="col">
            <section class="card">
                <h3 class="section-title">Resumen de tu cuenta</h3>
                <div class="kpi-grid" id="statsBox">
                    <div class="kpi-item kpi-a"><i class="ti ti-shopping-bag"></i><div class="lbl">Pedidos</div><div class="num" id="mPedidos">0</div><div class="sub">historial total</div></div>
                    <div class="kpi-item kpi-b"><i class="ti ti-checks"></i><div class="lbl">Entregados</div><div class="num" id="mEntregados">0</div><div class="sub">pedidos finalizados</div></div>
                    <div class="kpi-item kpi-c"><i class="ti ti-coins"></i><div class="lbl">Total gastado</div><div class="num" id="mGastado">S/ 0.00</div><div class="sub">acumulado</div></div>
                    <div class="kpi-item kpi-d"><i class="ti ti-receipt-2"></i><div class="lbl">Ticket promedio</div><div class="num" id="mTicket">S/ 0.00</div><div class="sub">por pedido</div></div>
                </div>
            </section>

            <section class="card">
                <h3 class="section-title">Graficos rapidos</h3>
                <div class="charts-grid">
                    <article class="chart-card">
                        <h4>Pedidos por estado</h4>
                        <div class="donut-wrap">
                            <div class="donut" id="donutEstados">
                                <div class="donut-center" id="donutCenter">0<br>pedidos</div>
                            </div>
                            <div class="legend" id="legendEstados"></div>
                        </div>
                    </article>
                    <article class="chart-card">
                        <h4>Tendencia de compras</h4>
                        <div class="bars" id="barsPedidos"></div>
                    </article>
                </div>
            </section>

            <section class="card club">
                <h3 style="margin:0 0 8px;position:relative;z-index:1">Fidelizacion</h3>
                <p id="clubMainText" class="mini" style="margin:0 0 6px">Estamos cargando tu progreso...</p>
                <div class="progress"><span id="clubBar"></span></div>
                <div class="mini" id="clubMeta">0 de 3 pedidos</div>
                <div class="club-grid">
                    <div class="club-mini"><span>Nivel</span><strong id="clubNivel">Nuevo</strong></div>
                    <div class="club-mini"><span>Te faltan</span><strong id="clubFaltan">3 pedidos</strong></div>
                    <div class="club-mini"><span>Favorito</span><strong id="clubFavorito">Sin dato</strong></div>
                    <div class="club-mini"><span>Ultima compra</span><strong id="clubUltima">-</strong></div>
                </div>
            </section>

            <section class="card">
                <h3 class="section-title">Tus pedidos recientes</h3>
                <div id="ordersBox" class="orders"><div class="empty">Cargando pedidos...</div></div>
            </section>
        </div>

        <aside class="col" style="align-content:start">
            <section class="card">
                <h3 class="section-title">Tu cuenta</h3>
                <div class="profile-line">
                    <div class="avatar" id="avatarCliente"></div>
                    <div>
                        <strong id="clienteNombre"><?= limpiar($cliente['nombre'] ?? '') ?></strong>
                        <div id="clienteProveedor" style="font-size:12px;color:#64748b"></div>
                    </div>
                </div>
                <div class="kv">
                    <div class="kv-item"><span>Correo</span><strong id="clienteEmail"></strong></div>
                    <div class="kv-item"><span>Telefono</span><strong id="clienteTelefono"></strong></div>
                    <div class="kv-item"><span>Ultimo acceso</span><strong id="clienteUltimoLogin"></strong></div>
                    <div class="kv-item"><span>Producto favorito</span><strong id="clienteFavorito"></strong></div>
                </div>
                <div class="top-links">
                    <a class="btn btn-light" id="btnEstadoEnlace" href="estado-pedido.php"><i class="ti ti-route-2"></i> Ver estado</a>
                    <a class="btn btn-order" href="index.php"><i class="ti ti-shopping-cart"></i> Pedir ahora</a>
                </div>
            </section>

            <section class="card">
                <h3 class="section-title">Editar perfil</h3>
                <div id="perfilMsg" class="msg"></div>
                <form id="formPerfilCliente">
                    <div class="field">
                        <label>Nombre</label>
                        <input type="text" id="perfilNombre" placeholder="Tu nombre completo">
                    </div>
                    <div class="field">
                        <label>Correo</label>
                        <input type="email" id="perfilEmail" disabled>
                    </div>
                    <div class="field">
                        <label>Telefono</label>
                        <input type="tel" id="perfilTelefono" placeholder="987654321">
                    </div>
                    <div class="field">
                        <label>Nueva contrasena</label>
                        <input type="password" id="perfilPassword" placeholder="Solo si quieres cambiarla">
                    </div>
                    <button class="btn btn-save" type="submit"><i class="ti ti-device-floppy"></i> Guardar cambios</button>
                </form>
            </section>
        </aside>
    </div>
</div>
<script>
function fmtMoney(v){return 'S/ ' + Number(v || 0).toFixed(2)}
function fmtDate(v){if(!v)return '-';const d=new Date(v);return isNaN(d.getTime())?'-':d.toLocaleString('es-PE',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})}
function renderBadge(estado){return `<span class="order-pill p-${estado}">${String(estado||'').replaceAll('_',' ')}</span>`}
function showPerfilMsg(text,type){const box=document.getElementById('perfilMsg');box.textContent=text;box.className='msg '+(type==='ok'?'ok':'err')}

function rellenarPerfilEditable(cliente){
    document.getElementById('perfilNombre').value = cliente.nombre || '';
    document.getElementById('perfilEmail').value = cliente.email || '';
    document.getElementById('perfilTelefono').value = cliente.telefono || '';
}

function renderDonutEstados(pedidos){
    const donut = document.getElementById('donutEstados');
    const center = document.getElementById('donutCenter');
    const legend = document.getElementById('legendEstados');
    if (!donut || !center || !legend) return;

    const palette = {
        pendiente:'#f59e0b',
        pagado:'#16a34a',
        en_preparacion:'#0ea5e9',
        en_camino:'#6366f1',
        entregado:'#ef4444',
        cancelado:'#94a3b8'
    };

    const counts = {};
    pedidos.forEach((p) => {
        const e = String(p.estado || 'pendiente');
        counts[e] = (counts[e] || 0) + 1;
    });

    const entries = Object.entries(counts);
    const total = entries.reduce((acc, x) => acc + x[1], 0);
    if (!total) {
        donut.style.background = '#e5e7eb';
        center.innerHTML = '0<br>pedidos';
        legend.innerHTML = '<span class="legend-item"><i style="background:#cbd5e1"></i>Sin pedidos</span>';
        return;
    }

    let from = 0;
    const gradientParts = [];
    legend.innerHTML = entries.map(([estado, cantidad]) => {
        const pct = (cantidad / total) * 100;
        const to = from + pct;
        const color = palette[estado] || '#94a3b8';
        gradientParts.push(`${color} ${from}% ${to}%`);
        from = to;
        return `<span class="legend-item"><i style="background:${color}"></i>${estado.replaceAll('_',' ')} (${cantidad})</span>`;
    }).join('');

    donut.style.background = `conic-gradient(${gradientParts.join(',')})`;
    center.innerHTML = `${total}<br>pedidos`;
}

function renderBarrasPedidos(pedidos){
    const box = document.getElementById('barsPedidos');
    if (!box) return;

    if (!pedidos || !pedidos.length) {
        box.innerHTML = '<div class="bar-col"><div class="bar" style="height:8px"></div><div class="bar-lbl">Sin datos</div></div>';
        return;
    }

    const porDia = {};
    pedidos.forEach((p) => {
        const fecha = new Date(p.creado_en);
        const key = isNaN(fecha.getTime()) ? 'N/A' : fecha.toLocaleDateString('es-PE', { day:'2-digit', month:'2-digit' });
        porDia[key] = (porDia[key] || 0) + 1;
    });

    const entries = Object.entries(porDia).slice(-6);
    const max = Math.max(...entries.map((x) => x[1]), 1);

    box.innerHTML = entries.map(([dia, val]) => {
        const h = Math.max(8, Math.round((val / max) * 94));
        return `<div class="bar-col"><div class="bar-val">${val}</div><div class="bar" style="height:${h}px"></div><div class="bar-lbl">${dia}</div></div>`;
    }).join('');
}

async function cargarDashboard(){
    const resp = await fetch('api/cliente_dashboard.php', { headers: { 'Accept': 'application/json' } });
    const data = await resp.json();
    if(!data.ok){throw new Error(data.mensaje || 'No se pudo cargar el dashboard');}

    const d = data.dashboard;
    const m = d.metricas;
    const f = d.fidelizacion;
    const c = d.cliente;

    document.getElementById('mPedidos').textContent = String(m.pedidos_totales || 0);
    document.getElementById('mEntregados').textContent = String(m.pedidos_entregados || 0);
    document.getElementById('mGastado').textContent = fmtMoney(m.total_gastado || 0);
    document.getElementById('mTicket').textContent = fmtMoney(m.ticket_promedio || 0);

    document.getElementById('clubMainText').textContent = f.mensaje_principal || 'Sin actividad todavia.';
    document.getElementById('clubMeta').textContent = `${Number(f.progreso_actual || 0)} de ${Number(f.objetivo_premio || 3)} pedidos en tu ciclo actual`;
    document.getElementById('clubBar').style.width = `${Math.max(0, Math.min(100, (Number(f.progreso_actual || 0) / Number(f.objetivo_premio || 3)) * 100))}%`;
    document.getElementById('clubNivel').textContent = f.nivel || 'Nuevo';
    document.getElementById('clubFaltan').textContent = `${Number(f.faltan_para_premio || 0)} pedido(s)`;
    document.getElementById('clubFavorito').textContent = f.producto_favorito || 'Aun sin favorito';
    document.getElementById('clubUltima').textContent = fmtDate(m.ultima_compra || f.ultima_compra || null);

    document.getElementById('clienteNombre').textContent = c.nombre || '';
    document.getElementById('clienteProveedor').textContent = c.proveedor === 'google' ? 'Conectado con Google' : 'Cuenta creada en la web';
    document.getElementById('clienteEmail').textContent = c.email || '-';
    document.getElementById('clienteTelefono').textContent = c.telefono || '-';
    document.getElementById('clienteUltimoLogin').textContent = fmtDate(c.ultimo_login_at);
    document.getElementById('clienteFavorito').textContent = m.producto_favorito || 'Aun sin favorito';
    document.getElementById('btnEstadoEnlace').href = 'estado-pedido.php?telefono=' + encodeURIComponent(c.telefono || '');
    rellenarPerfilEditable(c);

    const avatar = document.getElementById('avatarCliente');
    avatar.innerHTML = c.avatar_url ? `<img src="${c.avatar_url}" alt="avatar">` : `<i class="ti ti-user"></i>`;

    renderDonutEstados(d.pedidos || []);
    renderBarrasPedidos(d.pedidos || []);

    const ordersBox = document.getElementById('ordersBox');
    if (!d.pedidos || !d.pedidos.length) {
        ordersBox.innerHTML = `
            <div class="empty" style="grid-column:1 / -1;">
                <h4>Aun no tienes pedidos</h4>
                <p>Tu historial aparecera aqui cuando realices tu primer pedido desde la web.</p>
                <a class="btn btn-order" href="index.php"><i class="ti ti-shopping-cart"></i> Hacer mi primer pedido</a>
            </div>`;
        return;
    }

    ordersBox.innerHTML = d.pedidos.map((p) => `
        <article class="order">
            <div class="order-top">
                <div>
                    <div class="order-code">${p.codigo}</div>
                    <div class="order-date">${fmtDate(p.creado_en)}</div>
                </div>
                ${renderBadge(p.estado)}
            </div>
            <div class="order-meta">
                <span><i class="ti ti-coins"></i> ${fmtMoney(p.total)}</span>
                <span><i class="ti ti-truck-delivery"></i> ${p.tipo_entrega}</span>
                <span><i class="ti ti-credit-card"></i> ${p.metodo_pago}</span>
            </div>
            ${(p.comprobante_numero || p.comprobante_pdf_url || p.comprobante_xml_url || p.comprobante_cdr_url) ? `
            <div class="order-meta" style="margin-top:8px;align-items:center;">
                ${p.comprobante_numero ? `<span><i class="ti ti-file-invoice"></i> ${p.comprobante_numero}</span>` : ''}
                ${p.comprobante_pdf_url ? `<a class="btn btn-light" style="padding:7px 11px;font-size:11px;" href="${p.comprobante_pdf_url}" target="_blank" rel="noopener noreferrer"><i class="ti ti-file-download"></i> PDF</a>` : ''}
                ${p.comprobante_xml_url ? `<a class="btn btn-light" style="padding:7px 11px;font-size:11px;" href="${p.comprobante_xml_url}" target="_blank" rel="noopener noreferrer"><i class="ti ti-code"></i> XML</a>` : ''}
                ${p.comprobante_cdr_url ? `<a class="btn btn-light" style="padding:7px 11px;font-size:11px;" href="${p.comprobante_cdr_url}" target="_blank" rel="noopener noreferrer"><i class="ti ti-shield-check"></i> CDR</a>` : ''}
            </div>` : ''}
        </article>
    `).join('');
}

document.getElementById('formPerfilCliente').addEventListener('submit', async (e) => {
    e.preventDefault();
    const resp = await fetch('api/cliente_auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            accion: 'actualizar_perfil',
            nombre: document.getElementById('perfilNombre').value.trim(),
            telefono: document.getElementById('perfilTelefono').value.trim(),
            password: document.getElementById('perfilPassword').value,
        })
    });
    const data = await resp.json();
    if (!data.ok) {
        showPerfilMsg(data.mensaje || 'No se pudo actualizar el perfil.', 'err');
        return;
    }

    document.getElementById('perfilPassword').value = '';
    showPerfilMsg(data.mensaje || 'Perfil actualizado correctamente.', 'ok');
    await cargarDashboard();
});

document.getElementById('btnCerrarSesion').addEventListener('click', async () => {
    await fetch('api/cliente_auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'logout' })
    });
    window.location.href = 'cliente-login.php';
});

cargarDashboard().catch((error) => {
    const box = document.getElementById('ordersBox');
    if (box) box.innerHTML = `<div class="empty" style="grid-column:1 / -1;">${error.message}</div>`;
});
</script>
</body>
</html>
