<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirRol(['admin']);

$tituloPagina = 'Gestión de Caja';
$paginaActual = 'cajas';
require __DIR__ . '/_layout_top.php';
?>
<style>
/* ── Contenedor centrado ── */
.caja-wrap { max-width: 1060px; margin: 0 auto; }

/* ── Vistas ── */
#vistaAbrirCaja, #vistaAbierta { display: none; }

/* ── Card base ── */
.cj-card {
    background: #fff;
    border: 1px solid #e8edf5;
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 18px;
    box-shadow: 0 1px 6px rgba(15,23,42,.05);
}
.cj-card-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 18px;
}
.cj-card-title i { font-size: 17px; color: #64748b; }

/* ════════════════════════════════════
   HERO: Caja cerrada — layout 2 col
════════════════════════════════════ */
.cc-hero {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid #e8edf5;
    box-shadow: 0 4px 20px rgba(15,23,42,.10);
    margin-bottom: 18px;
}
@media (max-width: 700px) { .cc-hero { grid-template-columns: 1fr; } }

.cc-izq {
    background: linear-gradient(140deg, #0f172a 0%, #1e293b 70%, #0f172a 100%);
    padding: 52px 44px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
@media (max-width: 700px) { .cc-izq { padding: 36px 28px; } }
.cc-izq .cc-icon-wrap {
    width: 64px; height: 64px;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 24px;
}
.cc-izq .cc-icon-wrap i { font-size: 30px; color: #94a3b8; }
.cc-izq h2 { color: #f1f5f9; font-size: 22px; font-weight: 800; margin: 0 0 12px; }
.cc-izq p  { color: #94a3b8; font-size: 13.5px; line-height: 1.7; margin: 0; }

.cc-der {
    background: #fff;
    padding: 44px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
@media (max-width: 700px) { .cc-der { padding: 28px; } }
.cc-der h3 { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0 0 22px; display: flex; align-items: center; gap: 8px; }

/* ── Chips ── */
.chip { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
.chip-info { background: #f1f5f9; color: #334155; }

/* ── Header turno activo (fondo oscuro) ── */
.turno-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 16px;
    padding: 18px 24px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.turno-header .th-chips { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.th-chip-open { background: rgba(134,239,172,.18); color: #86efac; border: 1px solid rgba(134,239,172,.25); }
.th-chip-info { background: rgba(255,255,255,.09); color: rgba(255,255,255,.75); border: 1px solid rgba(255,255,255,.12); }

/* ── Resumen 4 cards ── */
.res-grid { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin-bottom: 18px; }
@media (max-width: 840px) { .res-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
.res-item {
    background: #fff;
    border: 1px solid #e8edf5;
    border-radius: 14px;
    padding: 18px 16px 14px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(15,23,42,.04);
}
.res-item .ri-bar { position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 14px 14px 0 0; }
.c-slate .ri-bar { background: linear-gradient(90deg,#334155,#64748b); }
.c-blue  .ri-bar { background: linear-gradient(90deg,#1d4ed8,#3b82f6); }
.c-amber .ri-bar { background: linear-gradient(90deg,#d97706,#f59e0b); }
.c-green .ri-bar { background: linear-gradient(90deg,#16a34a,#22c55e); }
.res-item > i { font-size: 20px; color: #94a3b8; margin-bottom: 8px; display: block; }
.res-item .valor { font-size: 21px; font-weight: 800; color: #0f172a; line-height: 1; }
.res-item .etiq  { font-size: 10.5px; color: #64748b; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; }

/* ── Tabla movimientos ── */
.tabla-wrap { overflow: auto; border: 1px solid #e8edf5; border-radius: 12px; }
.tabla-wrap table { width: 100%; border-collapse: collapse; min-width: 480px; }
.tabla-wrap thead tr { background: #f8fafc; }
.tabla-wrap th { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; padding: 10px 14px; text-align: left; border-bottom: 1px solid #e8edf5; }
.tabla-wrap td { border-top: 1px solid #f1f5f9; padding: 10px 14px; font-size: 13px; color: #334155; }
.tabla-wrap tbody tr:hover { background: #fafbfc; }
.tag { display: inline-flex; align-items: center; gap: 3px; padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; }
.tag-venta   { background: #dbeafe; color: #1e40af; }
.tag-ingreso { background: #dcfce7; color: #166534; }
.tag-egreso  { background: #fee2e2; color: #b91c1c; }

/* ── Form movimiento ── */
.mov-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
.cj-field { flex: 1; min-width: 130px; }
.cj-field label { display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .4px; }
.cj-field input, .cj-field select, .cj-field textarea {
    width: 100%; border: 1px solid #cbd5e1; border-radius: 10px;
    padding: 9px 11px; font-size: 13px; box-sizing: border-box; color: #0f172a; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.cj-field input:focus, .cj-field select:focus, .cj-field textarea:focus {
    outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}
.cj-field textarea { resize: vertical; }

/* ── Botones ── */
.btn {
    border: none; border-radius: 10px; padding: 10px 20px;
    font-weight: 700; font-size: 13px; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    transition: transform .1s, opacity .15s;
}
.btn:hover   { opacity: .9; }
.btn:active  { transform: scale(.98); }
.btn:disabled{ opacity: .55; cursor: not-allowed; }
.btn-primary { background: #0f172a; color: #fff; box-shadow: 0 2px 8px rgba(15,23,42,.2); }
.btn-success { background: linear-gradient(135deg,#166534,#15803d); color: #fff; box-shadow: 0 2px 8px rgba(22,101,52,.22); }
.btn-danger  { background: linear-gradient(135deg,#b91c1c,#dc2626); color: #fff; box-shadow: 0 2px 8px rgba(185,28,28,.22); }
.btn-soft    { background: #f1f5f9; color: #334155; }
.btn-sm      { padding: 7px 14px; font-size: 12px; }

/* ── Modal cierre ── */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.55);
    backdrop-filter: blur(3px);
    z-index: 1000;
    display: flex; align-items: center; justify-content: center; padding: 16px;
}
.modal-box {
    background: #fff; border-radius: 20px; padding: 30px;
    max-width: 500px; width: 100%;
    box-shadow: 0 24px 60px rgba(15,23,42,.22);
}
.modal-box h3 { margin: 0 0 20px; font-size: 18px; display: flex; align-items: center; gap: 9px; }
.arqueo-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 9px 0; border-bottom: 1px solid #f1f5f9;
    font-size: 14px; color: #334155;
}
.arqueo-row.total { font-weight: 800; font-size: 15px; border-bottom: none; padding-top: 12px; color: #0f172a; }
.arqueo-diff {
    font-size: 16px; font-weight: 800; text-align: center;
    padding: 13px; border-radius: 12px; margin: 14px 0;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.arqueo-diff.ok   { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.arqueo-diff.warn { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.arqueo-diff.bad  { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

/* ── Historial de turnos ── */
.hist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap: 14px; }
.hist-item {
    background: #f8fafc; border: 1px solid #e8edf5;
    border-radius: 14px; padding: 16px;
    position: relative; overflow: hidden;
}
.hist-item .hi-barra { position: absolute; bottom: 0; left: 0; right: 0; height: 3px; border-radius: 0 0 14px 14px; }
.hist-item.eq  .hi-barra { background: #f59e0b; }
.hist-item.pos .hi-barra { background: #22c55e; }
.hist-item.neg .hi-barra { background: #ef4444; }
.hi-head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.hi-num  { font-size: 13px; font-weight: 800; color: #0f172a; }
.hi-fecha{ font-size: 11px; color: #94a3b8; margin-top: 2px; }
.hi-row  { display: flex; justify-content: space-between; font-size: 12.5px; color: #475569; padding: 4px 0; }
.hi-row.strong { font-weight: 700; color: #0f172a; font-size: 13px; }
.hi-sep  { border: none; border-top: 1px solid #e2e8f0; margin: 8px 0; }
.hi-result {
    display: flex; justify-content: flex-end; align-items: center; gap: 6px;
    font-size: 13.5px; font-weight: 800;
    margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0;
}
.hi-result.pos { color: #166534; }
.hi-result.neg { color: #b91c1c; }
.hi-result.eq  { color: #92400e; }

/* ── Empty state ── */
.empty-state { text-align: center; padding: 36px 20px; color: #94a3b8; }
.empty-state i { font-size: 38px; display: block; margin-bottom: 10px; opacity: .4; }
.empty-state p { font-size: 13px; }

/* ── Mensaje ── */
.cj-msg { min-height: 18px; font-size: 13px; font-weight: 700; }
.cj-msg.ok  { color: #166534; }
.cj-msg.err { color: #b91c1c; }
</style>

<div class="caja-wrap">

    <!-- ════════════════════════ VISTA: Caja cerrada ═══════════════════════ -->
    <div id="vistaAbrirCaja">

        <!-- Hero 2 columnas -->
        <div class="cc-hero">
            <div class="cc-izq">
                <div class="cc-icon-wrap">
                    <i class="ti ti-lock"></i>
                </div>
                <h2>Caja cerrada</h2>
                <p>Para registrar ventas y movimientos, primero debes abrir la caja del día con el monto de efectivo inicial que hay en la caja.</p>
            </div>
            <div class="cc-der">
                <h3><i class="ti ti-cash" style="color:#64748b;"></i> Abrir caja</h3>
                <div class="cj-field" style="margin-bottom:13px;">
                    <label>Monto inicial en efectivo (S/)</label>
                    <input type="number" step="0.01" id="montoApertura" value="0.00" placeholder="Ej: 100.00" style="font-size:16px;font-weight:700;">
                </div>
                <div class="cj-field" style="margin-bottom:22px;">
                    <label>Observación (opcional)</label>
                    <textarea id="obsApertura" rows="2" placeholder="Ej: turno mañana, cajero Juan…"></textarea>
                </div>
                <button class="btn btn-success" id="btnAbrirCaja" type="button" style="width:100%;justify-content:center;padding:13px 20px;font-size:14px;">
                    <i class="ti ti-lock-open"></i> Abrir caja ahora
                </button>
                <div id="msgApertura" class="cj-msg" style="margin-top:10px;text-align:center;"></div>
            </div>
        </div>

        <!-- Historial en vista cerrada -->
        <div class="cj-card">
            <div class="cj-card-title"><i class="ti ti-history"></i> Historial de turnos</div>
            <div id="historialLista"></div>
        </div>
    </div>

    <!-- ════════════════════════ VISTA: Caja abierta ═══════════════════════ -->
    <div id="vistaAbierta">

        <!-- Header oscuro con info del turno -->
        <div class="turno-header">
            <div class="th-chips">
                <span class="chip th-chip-open"><i class="ti ti-circle-check"></i>&nbsp;Caja abierta</span>
                <span class="chip th-chip-info" id="chipTurno"></span>
                <span class="chip th-chip-info" id="chipAbierta"></span>
                <span class="chip th-chip-info" id="chipUsuario"></span>
            </div>
            <button class="btn btn-danger btn-sm" id="btnAbrirCierre" type="button">
                <i class="ti ti-lock"></i> Cerrar caja
            </button>
        </div>

        <!-- Resumen 4 cards -->
        <div class="res-grid" id="resumenGrid"></div>

        <!-- Registrar movimiento manual -->
        <div class="cj-card">
            <div class="cj-card-title"><i class="ti ti-arrows-exchange"></i> Registrar movimiento</div>
            <div class="mov-row">
                <div class="cj-field" style="max-width:140px;">
                    <label>Tipo</label>
                    <select id="movTipo">
                        <option value="ingreso">Ingreso</option>
                        <option value="egreso">Egreso</option>
                    </select>
                </div>
                <div class="cj-field" style="max-width:150px;">
                    <label>Monto (S/)</label>
                    <input type="number" step="0.01" id="movMonto" placeholder="0.00">
                </div>
                <div class="cj-field" style="flex:2;min-width:180px;">
                    <label>Concepto</label>
                    <input type="text" id="movConcepto" placeholder="Ej: compra de insumos, pago proveedor…">
                </div>
                <div>
                    <button class="btn btn-primary" id="btnRegistrarMov" type="button">
                        <i class="ti ti-plus"></i> Registrar
                    </button>
                </div>
            </div>
            <div id="msgMovimiento" class="cj-msg" style="margin-top:8px;"></div>
        </div>

        <!-- Movimientos del turno -->
        <div class="cj-card">
            <div class="cj-card-title"><i class="ti ti-list"></i> Movimientos del turno</div>
            <div class="tabla-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th style="text-align:right;">Monto</th>
                            <th>Hora</th>
                        </tr>
                    </thead>
                    <tbody id="movTableBody"></tbody>
                </table>
            </div>
        </div>

        <!-- Historial dentro de vista abierta -->
        <div class="cj-card">
            <div class="cj-card-title"><i class="ti ti-history"></i> Historial de turnos anteriores</div>
            <div id="historialListaAbierta"></div>
        </div>

    </div><!-- /vistaAbierta -->
</div><!-- /caja-wrap -->

<!-- ════════════ MODAL: Cierre de caja y Arqueo ════════════ -->
<div id="modalCierre" style="display:none;" class="modal-overlay">
    <div class="modal-box">
        <h3><i class="ti ti-lock" style="color:#b91c1c;"></i> Cerrar caja — Arqueo</h3>

        <div id="arqResumen"></div>

        <div class="cj-field" style="margin:16px 0 8px;">
            <label>💵 Efectivo contado físicamente (S/)</label>
            <input type="number" step="0.01" id="montoCierre" placeholder="0.00" style="font-size:18px;font-weight:700;">
        </div>

        <div id="arqDiferencia" class="arqueo-diff ok" style="display:none;"></div>

        <div class="cj-field" style="margin-bottom:20px;">
            <label>Observación (opcional)</label>
            <textarea id="obsCierre" rows="2" placeholder="Ej: cierre sin novedades"></textarea>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button class="btn btn-soft" id="btnCancelarCierre" type="button">Cancelar</button>
            <button class="btn btn-danger" id="btnConfirmarCierre" type="button">
                <i class="ti ti-lock"></i> Confirmar cierre
            </button>
        </div>
        <div id="msgCierre" class="cj-msg" style="margin-top:10px;text-align:center;"></div>
    </div>
</div>

<script>
(() => {
    const API = '../api/caja.php';

    function fmt(n) { return 'S/ ' + Number(n || 0).toFixed(2); }
    function esc(t) { return String(t || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])); }

    function showMsg(id, text, isErr = false) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = text;
        el.className = 'cj-msg ' + (isErr ? 'err' : 'ok');
        setTimeout(() => { el.textContent = ''; el.className = 'cj-msg'; }, 3200);
    }

    async function apiGet() {
        const r = await fetch(API, { headers: { Accept: 'application/json' } });
        const d = await r.json();
        if (!d.ok) throw new Error(d.mensaje || 'Error');
        return d;
    }

    async function apiPost(payload) {
        const r = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.mensaje || 'Error de operación');
        return d;
    }

    // ── Render historial ────────────────────────────────────────
    function renderHistorial(turnos, containerId) {
        const el = document.getElementById(containerId);
        if (!el) return;
        if (!turnos || !turnos.length) {
            el.innerHTML = '<div class="empty-state"><i class="ti ti-clock-off"></i><p>No hay turnos cerrados aún.</p></div>';
            return;
        }
        el.innerHTML = '<div class="hist-grid">' + turnos.map(t => {
            const apertura = Number(t.monto_apertura || 0);
            const ventas   = Number(t.total_ventas   || 0);
            const ingresos = Number(t.total_ingresos || 0);
            const egresos  = Number(t.total_egresos  || 0);
            const esperado = apertura + ventas + ingresos - egresos;
            const cierre   = Number(t.monto_cierre   || 0);
            const diff     = cierre - esperado;
            const cls      = diff > 0.01 ? 'pos' : diff < -0.01 ? 'neg' : 'eq';
            const ico      = diff > 0.01 ? 'ti-trending-up' : diff < -0.01 ? 'ti-trending-down' : 'ti-check';
            const lbl      = diff > 0.01 ? `Sobrante ${fmt(diff)}` : diff < -0.01 ? `Faltante ${fmt(Math.abs(diff))}` : 'Cuadrado';
            return `<div class="hist-item ${cls}">
                <div class="hi-barra"></div>
                <div class="hi-head">
                    <div>
                        <div class="hi-num">Turno #${esc(String(t.id))}</div>
                        <div class="hi-fecha">${esc(t.abierta_en || '')} → ${esc(t.cerrada_en || '')}</div>
                    </div>
                    <span class="chip chip-info" style="font-size:10.5px;">${esc(t.usuario_nombre || 'Admin')}</span>
                </div>
                <div class="hi-row"><span>Apertura</span><span>${fmt(apertura)}</span></div>
                <div class="hi-row"><span>Ventas</span><span style="color:#1d4ed8;font-weight:600;">${fmt(ventas)}</span></div>
                <div class="hi-row"><span>Ingresos</span><span style="color:#166534;font-weight:600;">+${fmt(ingresos)}</span></div>
                <div class="hi-row"><span>Egresos</span><span style="color:#b91c1c;font-weight:600;">−${fmt(egresos)}</span></div>
                <hr class="hi-sep">
                <div class="hi-row strong"><span>Saldo esperado</span><span>${fmt(esperado)}</span></div>
                <div class="hi-row"><span>Efectivo contado</span><span>${fmt(cierre)}</span></div>
                <div class="hi-result ${cls}"><i class="ti ${ico}"></i>${lbl}</div>
                ${t.observacion_cierre ? `<p style="font-size:11px;color:#94a3b8;margin:8px 0 0;border-top:1px solid #e8edf5;padding-top:6px;">${esc(t.observacion_cierre)}</p>` : ''}
            </div>`;
        }).join('') + '</div>';
    }

    // ── Cargar estado caja ──────────────────────────────────────
    async function cargar() {
        const d   = await apiGet();
        const turno = d.turno;
        const res   = d.resumen || {};

        if (turno) {
            document.getElementById('vistaAbrirCaja').style.display = 'none';
            document.getElementById('vistaAbierta').style.display   = 'block';

            document.getElementById('chipTurno').textContent   = 'Turno #' + turno.id;
            document.getElementById('chipAbierta').textContent = 'Abierta: ' + (turno.abierta_en || '');
            document.getElementById('chipUsuario').textContent = turno.usuario_nombre || 'Admin';

            const saldo = Number(turno.monto_apertura || 0) + Number(res.ventas || 0) + Number(res.ingresos || 0) - Number(res.egresos || 0);
            document.getElementById('resumenGrid').innerHTML = `
                <div class="res-item c-slate"><div class="ri-bar"></div><i class="ti ti-cash"></i><div class="valor">${fmt(turno.monto_apertura)}</div><div class="etiq">Apertura</div></div>
                <div class="res-item c-blue"><div class="ri-bar"></div><i class="ti ti-receipt"></i><div class="valor">${fmt(res.ventas)}</div><div class="etiq">Ventas del turno</div></div>
                <div class="res-item c-amber"><div class="ri-bar"></div><i class="ti ti-arrows-exchange"></i><div class="valor">${fmt(Number(res.ingresos||0) - Number(res.egresos||0))}</div><div class="etiq">Otros movimientos</div></div>
                <div class="res-item c-green"><div class="ri-bar"></div><i class="ti ti-chart-bar"></i><div class="valor">${fmt(saldo)}</div><div class="etiq">Saldo estimado</div></div>
            `;

            const movs = Array.isArray(d.movimientos) ? d.movimientos : [];
            document.getElementById('movTableBody').innerHTML = movs.length
                ? movs.map(m => {
                    const tagCls    = m.tipo === 'venta' ? 'tag tag-venta' : m.tipo === 'egreso' ? 'tag tag-egreso' : 'tag tag-ingreso';
                    const montoHtml = m.tipo === 'egreso'
                        ? `<span style="color:#b91c1c;font-weight:700;">− ${fmt(m.monto)}</span>`
                        : `<span style="color:#166534;font-weight:700;">+ ${fmt(m.monto)}</span>`;
                    return `<tr>
                        <td><span class="${tagCls}">${esc(m.tipo)}</span></td>
                        <td>${esc(m.concepto)}</td>
                        <td style="text-align:right;">${montoHtml}</td>
                        <td style="color:#94a3b8;font-size:12px;">${esc(m.creado_en)}</td>
                    </tr>`;
                }).join('')
                : '<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:22px;">Sin movimientos aún en este turno</td></tr>';

            renderHistorial(d.historial || [], 'historialListaAbierta');
        } else {
            document.getElementById('vistaAbrirCaja').style.display = 'block';
            document.getElementById('vistaAbierta').style.display   = 'none';
            renderHistorial(d.historial || [], 'historialLista');
        }
    }

    // ── Abrir caja ──────────────────────────────────────────────
    document.getElementById('btnAbrirCaja').addEventListener('click', async () => {
        const btn = document.getElementById('btnAbrirCaja');
        btn.disabled = true;
        try {
            await apiPost({
                accion: 'abrir',
                monto_apertura: Number(document.getElementById('montoApertura').value || 0),
                observacion:    document.getElementById('obsApertura').value || '',
            });
            await cargar();
        } catch (e) {
            showMsg('msgApertura', e.message, true);
        } finally { btn.disabled = false; }
    });

    // ── Modal cierre ────────────────────────────────────────────
    document.getElementById('btnAbrirCierre').addEventListener('click', async () => {
        const d   = await apiGet();
        const res = d.resumen || {};
        const apertura  = Number(d.turno?.monto_apertura || 0);
        const ventas    = Number(res.ventas   || 0);
        const ingresos  = Number(res.ingresos || 0);
        const egresos   = Number(res.egresos  || 0);
        const esperado  = apertura + ventas + ingresos - egresos;

        document.getElementById('arqResumen').innerHTML = `
            <div class="arqueo-row"><span>Monto apertura</span><strong>${fmt(apertura)}</strong></div>
            <div class="arqueo-row"><span>Ventas del turno</span><strong>${fmt(ventas)}</strong></div>
            <div class="arqueo-row"><span>Ingresos manuales</span><strong>${fmt(ingresos)}</strong></div>
            <div class="arqueo-row"><span>Egresos</span><strong style="color:#b91c1c;">− ${fmt(egresos)}</strong></div>
            <div class="arqueo-row total"><span>Saldo esperado en caja</span><strong>${fmt(esperado)}</strong></div>
        `;

        document.getElementById('montoCierre').value            = '';
        document.getElementById('obsCierre').value              = '';
        document.getElementById('arqDiferencia').style.display  = 'none';
        document.getElementById('montoCierre').dataset.esperado = esperado;
        document.getElementById('modalCierre').style.display    = 'flex';
    });

    document.getElementById('montoCierre').addEventListener('input', function () {
        const esperado = Number(this.dataset.esperado || 0);
        const contado  = Number(this.value || 0);
        const diff     = contado - esperado;
        const el       = document.getElementById('arqDiferencia');
        el.style.display = 'flex';
        if (Math.abs(diff) < 0.01) {
            el.className = 'arqueo-diff ok';
            el.innerHTML = '<i class="ti ti-check"></i> Caja cuadrada — ' + fmt(contado);
        } else if (diff > 0) {
            el.className = 'arqueo-diff warn';
            el.innerHTML = '<i class="ti ti-trending-up"></i> Sobrante: ' + fmt(diff) + ' &nbsp;(esperado ' + fmt(esperado) + ')';
        } else {
            el.className = 'arqueo-diff bad';
            el.innerHTML = '<i class="ti ti-trending-down"></i> Faltante: ' + fmt(Math.abs(diff)) + ' &nbsp;(esperado ' + fmt(esperado) + ')';
        }
    });

    document.getElementById('btnCancelarCierre').addEventListener('click', () => {
        document.getElementById('modalCierre').style.display = 'none';
    });

    document.getElementById('btnConfirmarCierre').addEventListener('click', async () => {
        const btn       = document.getElementById('btnConfirmarCierre');
        const montoCierre = document.getElementById('montoCierre').value;
        if (montoCierre === '' || isNaN(Number(montoCierre))) {
            showMsg('msgCierre', 'Ingresa el monto contado en efectivo.', true);
            return;
        }
        btn.disabled = true;
        try {
            await apiPost({
                accion:       'cerrar',
                monto_cierre: Number(montoCierre),
                observacion:  document.getElementById('obsCierre').value || '',
            });
            document.getElementById('modalCierre').style.display = 'none';
            await cargar();
        } catch (e) {
            showMsg('msgCierre', e.message, true);
        } finally { btn.disabled = false; }
    });

    // ── Registrar movimiento ────────────────────────────────────
    document.getElementById('btnRegistrarMov').addEventListener('click', async () => {
        const btn = document.getElementById('btnRegistrarMov');
        btn.disabled = true;
        try {
            await apiPost({
                accion:   'movimiento',
                tipo:     document.getElementById('movTipo').value,
                monto:    Number(document.getElementById('movMonto').value || 0),
                concepto: document.getElementById('movConcepto').value || '',
            });
            document.getElementById('movMonto').value   = '';
            document.getElementById('movConcepto').value = '';
            showMsg('msgMovimiento', 'Movimiento registrado correctamente.');
            await cargar();
        } catch (e) {
            showMsg('msgMovimiento', e.message, true);
        } finally { btn.disabled = false; }
    });

    // ── Cerrar modal al click en overlay ───────────────────────
    document.getElementById('modalCierre').addEventListener('click', function (e) {
        if (e.target === this) this.style.display = 'none';
    });

    // ── Iniciar ─────────────────────────────────────────────────
    cargar().catch(e => console.error(e));
})();
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
