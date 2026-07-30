<?php
$tituloPagina = 'Panel Cocina';
$paginaActual = 'cocina';
require __DIR__ . '/_layout_top.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<div class="kn-shell">

    <!-- HERO -->
    <section class="kn-hero card">
        <div class="kn-hero-left">
            <p class="kn-kicker"><i class="fa-solid fa-fire-burner"></i> Operación en vivo</p>
            <h2>Panel de Cocina</h2>
            <p>Los pedidos entran automáticamente · Actualiza el estado con un clic · Se refresca solo cada 8 s</p>
        </div>
        <div class="kn-hero-right">
            <div class="kn-filtros-periodo">
                <button class="kn-btn-periodo activo" data-periodo="hoy"><i class="fa-solid fa-sun"></i> Hoy</button>
                <button class="kn-btn-periodo" data-periodo="semana"><i class="fa-solid fa-calendar-week"></i> Esta semana</button>
                <button class="kn-btn-periodo" data-periodo="mes"><i class="fa-solid fa-calendar"></i> Este mes</button>
                <button class="kn-btn-periodo" data-periodo="todo"><i class="fa-solid fa-infinity"></i> Todo</button>
            </div>
            <div class="kn-hero-actions">
                <button class="btn btn-primario kn-btn-refresh" id="btnRefrescarCocina" type="button">
                    <i class="fa-solid fa-rotate"></i> Refrescar
                </button>
                <span class="kn-last-update">Última actualización: <strong id="kn-hora-update">--:--:--</strong></span>
            </div>
        </div>
    </section>

    <!-- LABEL DE PERIODO ACTIVO -->
    <div class="kn-periodo-label" id="knPeriodoLabel"></div>

    <!-- FICHAS DE ESTADO -->
    <div class="kn-stats-row" id="knStats">
        <!-- renderizado por JS -->
    </div>

    <!-- TABLERO KANBAN -->
    <div class="kn-board" id="knBoard">
        <!-- renderizado por JS -->
    </div>

</div>

<!-- TOAST -->
<div class="kn-toast" id="knToast"></div>

<style>
/* ── Shell ───────────────────────────────────────── */
.kn-shell { display: grid; gap: 18px; }

/* ── Hero ────────────────────────────────────────── */
.kn-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    background: linear-gradient(135deg, #fff8f2 0%, #ffffff 60%);
    border: 1px solid #ffe5cc;
    box-shadow: 0 10px 28px rgba(232,89,12,.08);
}
.kn-kicker {
    color: #e8590c;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.kn-hero h2 { font-size: 26px; }
.kn-hero p  { color: #64748b; font-size: 13px; margin-top: 6px; }
.kn-hero-right { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
.kn-hero-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.kn-btn-refresh { display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
.kn-last-update { font-size: 12px; color: #64748b; white-space: nowrap; }

/* ── Filtros de período ──────────────────────────── */
.kn-filtros-periodo {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}
.kn-btn-periodo {
    border: 1.5px solid #e2e8f0;
    background: #fff;
    border-radius: 999px;
    padding: 7px 14px;
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .15s, color .15s, border-color .15s;
}
.kn-btn-periodo:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}
.kn-btn-periodo.activo {
    background: #e8590c;
    border-color: #e8590c;
    color: #fff;
}

/* ── Label periodo ───────────────────────────────── */
.kn-periodo-label {
    font-size: 13px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
}
.kn-periodo-label strong { color: #0f172a; }
.kn-periodo-label.is-hoy strong { color: #e8590c; }

/* ── Fichas de estado ────────────────────────────── */
.kn-stats-row {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 10px;
}
.kn-stat {
    border-radius: 14px;
    padding: 14px;
    border: 2px solid transparent;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,.05);
    display: grid;
    gap: 4px;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
}
.kn-stat:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.1); }
.kn-stat .kn-st-icon { font-size: 20px; margin-bottom: 2px; }
.kn-stat .kn-st-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
.kn-stat .kn-st-count { font-size: 26px; font-weight: 800; line-height: 1; }
/* colores por columna */
.kn-stat.st-pendiente   { border-color: #fbbf24; background: #fffbeb; }
.kn-stat.st-pendiente   .kn-st-count { color: #b45309; }
.kn-stat.st-cocinando   { border-color: #f97316; background: #fff7ed; }
.kn-stat.st-cocinando   .kn-st-count { color: #c2410c; }
.kn-stat.st-listo       { border-color: #3b82f6; background: #eff6ff; }
.kn-stat.st-listo       .kn-st-count { color: #1d4ed8; }
.kn-stat.st-entregado   { border-color: #22c55e; background: #f0fdf4; }
.kn-stat.st-entregado   .kn-st-count { color: #15803d; }
.kn-stat.st-cancelado   { border-color: #ef4444; background: #fef2f2; }
.kn-stat.st-cancelado   .kn-st-count { color: #b91c1c; }
.kn-stat.st-total       { border-color: #94a3b8; background: #f8fafc; }
.kn-stat.st-total       .kn-st-count { color: #0f172a; }

/* ── Board Kanban ────────────────────────────────── */
.kn-board {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    align-items: start;
}
.kn-col {
    border-radius: 16px;
    overflow: hidden;
    background: #f4f6f9;
    border: 1px solid #e2e8f0;
}
.kn-col-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 14px;
    font-size: 13px;
    font-weight: 800;
}
.kn-col-head .kn-col-title { display: flex; align-items: center; gap: 8px; }
.kn-col-head .kn-col-badge {
    min-width: 22px;
    height: 22px;
    border-radius: 999px;
    background: rgba(255,255,255,.7);
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
}
/* colores cabeceras */
.kn-col.col-pendiente .kn-col-head  { background: #fef3c7; color: #92400e; }
.kn-col.col-cocinando .kn-col-head  { background: #fed7aa; color: #9a3412; }
.kn-col.col-listo     .kn-col-head  { background: #dbeafe; color: #1e40af; }
.kn-col.col-entregado .kn-col-head  { background: #dcfce7; color: #166534; }

.kn-col-body {
    padding: 10px;
    min-height: 120px;
    display: grid;
    gap: 10px;
    align-content: start;
}

/* ── Card de pedido ──────────────────────────────── */
.k-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e5ebf4;
    padding: 14px;
    box-shadow: 0 4px 12px rgba(15,23,42,.07);
    display: grid;
    gap: 10px;
    animation: kIn .22s ease;
}
@keyframes kIn { from { opacity: .3; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* urgencia: rojo si > 15 min pendiente */
.k-card.urgente { border-color: #fca5a5; box-shadow: 0 0 0 2px #fee2e2, 0 4px 12px rgba(239,68,68,.15); }

.k-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 8px;
}
.k-code { font-weight: 800; font-size: 14px; color: #0f172a; }
.k-timer {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #64748b;
    white-space: nowrap;
}
.k-timer.urgente { background: #fef2f2; color: #dc2626; }
.k-client { font-weight: 700; font-size: 13px; color: #0f172a; }
.k-meta-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}
.k-meta-chip {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.k-meta-chip.delivery { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.k-meta-chip.recojo   { background: #f0fdf4; border-color: #86efac; color: #15803d; }
.k-items-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 5px; }
.k-items-list li {
    font-size: 12px;
    color: #374151;
    background: #f8fafc;
    border: 1px solid #e5ebf4;
    border-radius: 8px;
    padding: 6px 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.k-items-list li::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #e8590c; flex: 0 0 auto; }
.k-total { font-size: 13px; font-weight: 800; color: #0f172a; }
.k-actions {
    display: grid;
    gap: 6px;
}
.k-btn-avanzar {
    width: 100%;
    border: none;
    border-radius: 10px;
    padding: 10px 12px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: opacity .15s, transform .1s;
}
.k-btn-avanzar:active { transform: scale(.97); }
.k-btn-avanzar.btn-pendiente  { background: #f97316; color: #fff; }
.k-btn-avanzar.btn-cocinando  { background: #3b82f6; color: #fff; }
.k-btn-avanzar.btn-listo      { background: #22c55e; color: #fff; }
.k-btn-avanzar:disabled       { opacity: .45; cursor: default; }
.k-btn-cancelar {
    width: 100%;
    border: none;
    border-radius: 10px;
    padding: 8px 12px;
    font-weight: 700;
    font-size: 12px;
    cursor: pointer;
    background: #fef2f2;
    color: #b91c1c;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.kn-col-empty {
    text-align: center;
    padding: 28px 14px;
    color: #94a3b8;
    font-size: 13px;
    font-style: italic;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}
.kn-col-empty i { font-size: 26px; opacity: .4; }

/* ── Toast ───────────────────────────────────────── */
.kn-toast {
    position: fixed;
    bottom: 28px;
    right: 24px;
    background: #0f172a;
    color: #fff;
    padding: 12px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 8px 24px rgba(0,0,0,.22);
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
    transition: opacity .25s, transform .25s;
    z-index: 9000;
    display: flex;
    align-items: center;
    gap: 8px;
}
.kn-toast.visible { opacity: 1; transform: translateY(0); }
.kn-toast.toast-ok  { background: #166534; }
.kn-toast.toast-err { background: #991b1b; }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 1180px) {
    .kn-board { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .kn-stats-row { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 680px) {
    .kn-board { grid-template-columns: 1fr; }
    .kn-stats-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .kn-hero { flex-direction: column; align-items: flex-start; }
    .kn-hero-right { align-items: flex-start; }
}
</style>

<script>
(function () {
    const API = '../api/kitchen_orders.php';
    let pollingId  = null;
    let periodoActual = 'hoy';   // siempre arranca en hoy (hora Lima)

    const LABELS_PERIODO = {
        hoy:    { texto: 'Mostrando pedidos de hoy',         icon: 'fa-sun' },
        semana: { texto: 'Mostrando pedidos de esta semana', icon: 'fa-calendar-week' },
        mes:    { texto: 'Mostrando pedidos de este mes',    icon: 'fa-calendar' },
        todo:   { texto: 'Mostrando todos los pedidos',      icon: 'fa-infinity' },
    };

    // Columnas del kanban: estados que las forman y estilos
    const COLUMNAS = [
        {
            id: 'pendiente',
            label: 'Nuevos pedidos',
            icon: 'fa-bell',
            estadosIncluidos: ['pendiente', 'pagado'],
            clase: 'col-pendiente',
            btnLabel: '<i class="fa-solid fa-fire-burner"></i> Empezar a cocinar',
            btnClase: 'btn-pendiente',
            siguienteEstado: 'en_preparacion',
        },
        {
            id: 'cocinando',
            label: 'Cocinando',
            icon: 'fa-fire',
            estadosIncluidos: ['en_preparacion'],
            clase: 'col-cocinando',
            btnLabel: '<i class="fa-solid fa-plate-wheat"></i> Marcar como listo',
            btnClase: 'btn-cocinando',
            siguienteEstado: 'en_camino',
        },
        {
            id: 'listo',
            label: 'Listo / Servido',
            icon: 'fa-plate-wheat',
            estadosIncluidos: ['en_camino'],
            clase: 'col-listo',
            btnLabel: '<i class="fa-solid fa-circle-check"></i> Marcar entregado',
            btnClase: 'btn-listo',
            siguienteEstado: 'entregado',
        },
        {
            id: 'entregado',
            label: 'Entregado',
            icon: 'fa-circle-check',
            estadosIncluidos: ['entregado'],
            clase: 'col-entregado',
            btnLabel: null,
            btnClase: null,
            siguienteEstado: null,
        },
    ];

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function minutosDesde(fechaIso) {
        const ts = new Date(fechaIso).getTime();
        if (!ts) return '0 min';
        return Math.max(0, Math.floor((Date.now() - ts) / 60000)) + ' min';
    }

    function minutosNum(fechaIso) {
        const ts = new Date(fechaIso).getTime();
        if (!ts) return 0;
        return Math.max(0, Math.floor((Date.now() - ts) / 60000));
    }

    function mostrarToast(msg, tipo) {
        const t = document.getElementById('knToast');
        t.className = 'kn-toast ' + (tipo === 'error' ? 'toast-err' : 'toast-ok');
        t.innerHTML = `<i class="fa-solid ${tipo === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check'}"></i> ${esc(msg)}`;
        t.classList.add('visible');
        clearTimeout(t._tid);
        t._tid = setTimeout(() => t.classList.remove('visible'), 3000);
    }

    function actualizarHora() {
        const el = document.getElementById('kn-hora-update');
        if (el) el.textContent = new Date().toLocaleTimeString('es-PE', { timeZone: 'America/Lima' });
    }

    function actualizarLabelPeriodo() {
        const el = document.getElementById('knPeriodoLabel');
        if (!el) return;
        const info = LABELS_PERIODO[periodoActual] || LABELS_PERIODO.hoy;
        const ahora = new Date().toLocaleDateString('es-PE', {
            timeZone: 'America/Lima',
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
        });
        el.className = 'kn-periodo-label' + (periodoActual === 'hoy' ? ' is-hoy' : '');
        el.innerHTML = `<i class="fa-solid ${info.icon}"></i> <strong>${info.texto}</strong> &mdash; ${ahora} (Lima)`;
    }

    // ── Renderizar fichas de estado
    function renderStats(pedidos) {
        const conteo = {};
        COLUMNAS.forEach(c => c.estadosIncluidos.forEach(e => { conteo[e] = (conteo[e] || 0); }));
        conteo.cancelado = 0;
        pedidos.forEach(p => { conteo[p.estado] = (conteo[p.estado] || 0) + 1; });

        const totalActivos = pedidos.filter(p => p.estado !== 'cancelado' && p.estado !== 'entregado').length;

        const fichas = [
            { clase: 'st-total',    icon: 'fa-list-check',    label: 'En proceso',   val: totalActivos },
            { clase: 'st-pendiente',icon: 'fa-bell',           label: 'Nuevos',       val: (conteo.pendiente||0) + (conteo.pagado||0) },
            { clase: 'st-cocinando',icon: 'fa-fire',           label: 'Cocinando',    val: conteo.en_preparacion || 0 },
            { clase: 'st-listo',    icon: 'fa-plate-wheat',    label: 'Listos',       val: conteo.en_camino || 0 },
            { clase: 'st-entregado',icon: 'fa-circle-check',   label: 'Entregados',   val: conteo.entregado || 0 },
            { clase: 'st-cancelado',icon: 'fa-circle-xmark',   label: 'Cancelados',   val: conteo.cancelado || 0 },
        ];

        document.getElementById('knStats').innerHTML = fichas.map(f => `
            <div class="kn-stat ${f.clase}">
                <div class="kn-st-icon"><i class="fa-solid ${f.icon}"></i></div>
                <div class="kn-st-label">${f.label}</div>
                <div class="kn-st-count">${f.val}</div>
            </div>
        `).join('');
    }

    // ── Renderizar una card de pedido
    function renderCard(p, col) {
        const mins = minutosNum(p.creado_en);
        const urgente = col.id === 'pendiente' && mins > 15;
        const entregaChip = p.tipo_entrega === 'delivery'
            ? `<span class="k-meta-chip delivery"><i class="fa-solid fa-motorcycle"></i> Delivery</span>`
            : `<span class="k-meta-chip recojo"><i class="fa-solid fa-house"></i> Recojo</span>`;
        const metodoPago = p.metodo_pago === 'efectivo'
            ? '<i class="fa-solid fa-money-bill-wave"></i>'
            : p.metodo_pago === 'tarjeta'
                ? '<i class="fa-solid fa-credit-card"></i>'
                : '<i class="fa-brands fa-whatsapp"></i>';
        const itemsHtml = (p.items || []).slice(0, 8).map(it => `<li>${esc(it)}</li>`).join('');
        const btnAvanzar = col.btnLabel && col.siguienteEstado
            ? `<button class="k-btn-avanzar ${col.btnClase}" data-action="avanzar" data-next="${col.siguienteEstado}">${col.btnLabel}</button>`
            : '';
        const btnCancelar = col.id !== 'entregado'
            ? `<button class="k-btn-cancelar" data-action="cancelar"><i class="fa-solid fa-xmark"></i> Cancelar</button>`
            : '';

        return `
        <article class="k-card${urgente ? ' urgente' : ''}" data-id="${p.id}">
            <div class="k-card-top">
                <div>
                    <div class="k-code"><i class="fa-solid fa-receipt" style="color:#e8590c;margin-right:4px;"></i>${esc(p.codigo)}</div>
                    <div class="k-client">${esc(p.cliente_nombre)}</div>
                </div>
                <div>
                    <div class="k-timer${urgente ? ' urgente' : ''}"><i class="fa-regular fa-clock"></i> ${minutosDesde(p.creado_en)}</div>
                </div>
            </div>
            <div class="k-meta-row">
                ${entregaChip}
                <span class="k-meta-chip">${metodoPago} ${esc(p.metodo_pago)}</span>
                <span class="k-meta-chip"><i class="fa-solid fa-layer-group"></i> ${p.total_items} ítem${p.total_items !== 1 ? 's' : ''}</span>
                <span class="k-total">S/ ${Number(p.total).toFixed(2)}</span>
            </div>
            <ul class="k-items-list">${itemsHtml || '<li>Sin detalle</li>'}</ul>
            <div class="k-actions">
                ${btnAvanzar}
                ${btnCancelar}
            </div>
        </article>`;
    }

    // ── Renderizar tablero kanban completo
    function renderBoard(pedidos) {
        const board = document.getElementById('knBoard');

        board.innerHTML = COLUMNAS.map(col => {
            const cards = pedidos.filter(p => col.estadosIncluidos.includes(p.estado));
            const cardsHtml = cards.length
                ? cards.map(p => renderCard(p, col)).join('')
                : `<div class="kn-col-empty"><i class="fa-solid fa-${col.icon}"></i><span>Sin pedidos aquí</span></div>`;

            return `
            <div class="kn-col ${col.clase}">
                <div class="kn-col-head">
                    <div class="kn-col-title"><i class="fa-solid ${col.icon}"></i> ${col.label}</div>
                    <span class="kn-col-badge">${cards.length}</span>
                </div>
                <div class="kn-col-body">${cardsHtml}</div>
            </div>`;
        }).join('');
    }

    // ── Cargar pedidos desde API
    async function cargarPedidos() {
        try {
            const url = `${API}?limite=200&periodo=${encodeURIComponent(periodoActual)}`;
            const r = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await r.json();
            if (!data.ok) throw new Error(data.mensaje || 'Error al cargar');

            // Solo mostramos los que NO son cancelados en el tablero principal
            const pedidos = (data.pedidos || []).filter(p => p.estado !== 'cancelado');
            // Pero sí los contamos en stats (pasamos todos)
            renderStats(data.pedidos || []);
            renderBoard(pedidos);
            actualizarHora();
            actualizarLabelPeriodo();
        } catch (err) {
            mostrarToast('No se pudo cargar pedidos: ' + err.message, 'error');
        }
    }

    // ── Cambiar estado en API
    async function cambiarEstado(id, estado) {
        try {
            const r = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ pedido_id: id, estado }),
            });
            const data = await r.json();
            if (!data.ok) throw new Error(data.mensaje || 'No se pudo actualizar');
            mostrarToast('Estado actualizado correctamente', 'ok');
            await cargarPedidos();
        } catch (err) {
            mostrarToast('Error: ' + err.message, 'error');
        }
    }

    // ── Delegación de eventos en el tablero
    document.getElementById('knBoard').addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const card = btn.closest('.k-card');
        if (!card) return;
        const id = parseInt(card.dataset.id, 10);
        if (!id) return;

        if (btn.dataset.action === 'avanzar') {
            cambiarEstado(id, btn.dataset.next);
        } else if (btn.dataset.action === 'cancelar') {
            if (confirm('¿Confirmas cancelar este pedido?')) {
                cambiarEstado(id, 'cancelado');
            }
        }
    });

    // ── Botones de filtro de período
    document.querySelectorAll('.kn-btn-periodo').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.kn-btn-periodo').forEach(b => b.classList.remove('activo'));
            btn.classList.add('activo');
            periodoActual = btn.dataset.periodo || 'hoy';
            cargarPedidos();
        });
    });

    // ── Botón refrescar manual + animación icono
    document.getElementById('btnRefrescarCocina').addEventListener('click', function () {
        const ico = this.querySelector('i');
        ico.classList.add('fa-spin');
        cargarPedidos().finally(() => {
            setTimeout(() => ico.classList.remove('fa-spin'), 700);
        });
    });

    // ── Iniciar
    cargarPedidos();
    pollingId = setInterval(cargarPedidos, 8000);
    window.addEventListener('beforeunload', () => { if (pollingId) clearInterval(pollingId); });
})();
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
