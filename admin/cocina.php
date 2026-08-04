<?php
$tituloPagina = 'Panel Cocina';
$paginaActual = 'cocina';
require __DIR__ . '/_layout_top.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• TABS DE ESTACIONES â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div id="estacionesBar" style="margin-bottom:16px;"></div>

<div class="kn-shell">

    <!-- HERO -->
    <section class="kn-hero card">
        <div class="kn-hero-left">
            <p class="kn-kicker"><i class="fa-solid fa-fire-burner"></i> Operación en vivo</p>
            <h2>Panel de Cocina</h2>
            <p>Los pedidos entran automaticamente· Actualiza el estado con un clic. Se refresca solo cada 8 s</p>
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
                <span class="kn-last-update">Ultima actualización: <strong id="kn-hora-update">--:--:--</strong></span>
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
<div class="kn-modal-overlay" id="knModalConfirm">
    <div class="kn-modal-box">
        <div class="kn-modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3>¿Confirmas cancelar este pedido?</h3>
        <p>Esta acción no se puede deshacer.</p>
        <div class="kn-modal-actions">
            <button type="button" class="kn-modal-btn cancelar" id="knModalCancelar">Volver</button>
            <button type="button" class="kn-modal-btn confirmar" id="knModalConfirmar">Sí, cancelar</button>
        </div>
    </div>
</div>
<!-- TOAST -->
<div class="kn-toast" id="knToast"></div>

<style>
/* â”€â”€ Shell â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.kn-shell { display: grid; gap: 18px; }

/* â”€â”€ Hero â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.kn-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    background: var(--neu-base);
    border: none;
    border-radius: 22px;
    box-shadow: 10px 10px 22px var(--neu-sombra-oscura), -10px -10px 22px var(--neu-sombra-clara);
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

/* â”€â”€ Filtros de perÃ­odo â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.kn-filtros-periodo {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}
.kn-btn-periodo {
    border: none;
    background: var(--neu-base);
    border-radius: 999px;
    padding: 8px 16px;
    font-size: 12px;
    font-weight: 700;
    color: #4a5160;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 4px 4px 9px var(--neu-sombra-oscura), -4px -4px 9px var(--neu-sombra-clara);
    transition: box-shadow .15s, color .15s;
}
.kn-btn-periodo:hover {
    box-shadow: 2px 2px 6px var(--neu-sombra-oscura), -2px -2px 6px var(--neu-sombra-clara);
}
.kn-filtros-periodo .kn-btn-periodo.activo {
    background: linear-gradient(135deg, #ff8a3d, #E8590C) !important;
    color: #fff !important;
    box-shadow: inset 3px 3px 7px rgba(0,0,0,.25) !important;
}

/* â”€â”€ Label periodo â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.kn-periodo-label {
    font-size: 13px;
    color: #666d7a;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: var(--neu-base);
    border: none;
    border-radius: 14px;
    box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara);
}
.kn-periodo-label strong { color: #0f172a; }
.kn-periodo-label.is-hoy strong { color: #e8590c; }

/* â”€â”€ Fichas de estado â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.kn-stats-row {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 10px;
}
.kn-stat {
    border-radius: 16px;
    padding: 14px;
    border: none;
    background: var(--neu-base);
    box-shadow: 6px 6px 14px var(--neu-sombra-oscura), -6px -6px 14px var(--neu-sombra-clara);
    display: grid;
    gap: 4px;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
}
.kn-stat:hover { transform: translateY(-3px); box-shadow: 8px 10px 18px var(--neu-sombra-oscura), -6px -6px 14px var(--neu-sombra-clara); }
.kn-stat .kn-st-icon { font-size: 20px; margin-bottom: 2px; }
.kn-stat .kn-st-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
.kn-stat .kn-st-count { font-size: 26px; font-weight: 800; line-height: 1; }
/* colores por columna */
.kn-stat.st-total {
    background: linear-gradient(135deg, #2a1b6a 0%, #5b4bd6 45%, #7ea8ff 100%);
}
.kn-stat.st-pendiente {
    background: linear-gradient(135deg, #7a2b0f 0%, #e8590c 45%, #ffb37a 100%);
}
.kn-stat.st-cocinando {
    background: linear-gradient(135deg, #6a1b6a 0%, #c23b8a 45%, #ff7a9e 100%);
}
.kn-stat.st-listo {
    background: linear-gradient(135deg, #0f4c3a 0%, #1f9e6d 45%, #7fe0a8 100%);
}
.kn-stat.st-entregado {
    background: linear-gradient(135deg, #14532d 0%, #16a34a 55%, #86efac 100%);
}
.kn-stat.st-cancelado {
    background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 55%, #fca5a5 100%);
}

.kn-stat.st-total .kn-st-label,
.kn-stat.st-pendiente .kn-st-label,
.kn-stat.st-cocinando .kn-st-label,
.kn-stat.st-listo .kn-st-label,
.kn-stat.st-entregado .kn-st-label,
.kn-stat.st-cancelado .kn-st-label {
    color: rgba(255,255,255,.85);
}
.kn-stat.st-total .kn-st-count,
.kn-stat.st-pendiente .kn-st-count,
.kn-stat.st-cocinando .kn-st-count,
.kn-stat.st-listo .kn-st-count,
.kn-stat.st-entregado .kn-st-count,
.kn-stat.st-cancelado .kn-st-count {
    color: #fff;
}
.kn-stat.st-total .kn-st-icon i,
.kn-stat.st-pendiente .kn-st-icon i,
.kn-stat.st-cocinando .kn-st-icon i,
.kn-stat.st-listo .kn-st-icon i,
.kn-stat.st-entregado .kn-st-icon i,
.kn-stat.st-cancelado .kn-st-icon i {
    color: #fff;
}

/* â”€â”€ Board Kanban â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.kn-board {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    align-items: start;
}
.kn-col {
    border-radius: 20px;
    overflow: hidden;
    background: var(--neu-base);
    border: none;
    box-shadow: inset 5px 5px 12px var(--neu-sombra-oscura), inset -5px -5px 12px var(--neu-sombra-clara);
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
    background: rgba(255,255,255,.55);
    box-shadow: inset 1px 1px 3px rgba(0,0,0,.12);
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
}
/* colores cabeceras */
.kn-col.col-pendiente .kn-col-head  { background: #f8e38c; color: #8b3a07; }
.kn-col.col-cocinando .kn-col-head  { background: #ffae51; color: #9a3412; }
.kn-col.col-listo     .kn-col-head  { background: #79b3ff; color: #1e40af; }
.kn-col.col-entregado .kn-col-head  { background: #8bfdb3; color: #166534; }

.kn-col-body {
    padding: 10px;
    min-height: 120px;
    display: grid;
    gap: 10px;
    align-content: start;
}

/* â”€â”€ Card de pedido â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.k-card {
    background: var(--neu-base);
    border-radius: 16px;
    border: none;
    padding: 14px;
    box-shadow: 5px 5px 12px var(--neu-sombra-oscura), -5px -5px 12px var(--neu-sombra-clara);
    display: grid;
    gap: 10px;
    animation: kIn .22s ease;
    transition: transform .15s ease, box-shadow .15s ease;
}
.k-card:hover {
    transform: translateY(-2px);
}
@keyframes kIn { from { opacity: .3; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

/* urgencia: rojo si > 15 min pendiente */
.k-card.urgente { box-shadow: 0 0 0 2px #fca5a5, 5px 5px 12px var(--neu-sombra-oscura), -5px -5px 12px var(--neu-sombra-clara); }
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
    background: var(--neu-base);
    box-shadow: inset 2px 2px 4px var(--neu-sombra-oscura), inset -2px -2px 4px var(--neu-sombra-clara);
    color: #666d7a;
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
    background: var(--neu-base);
    box-shadow: inset 2px 2px 4px var(--neu-sombra-oscura), inset -2px -2px 4px var(--neu-sombra-clara);
    color: #4a5160;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.k-meta-chip.delivery { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.k-meta-chip.recojo   { background: #f0fdf4; border-color: #86efac; color: #15803d; }
.k-items-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 5px; }
.k-items-list li {
    font-size: 12px;
    color: #4a5160;
    background: var(--neu-base);
    border: none;
    border-radius: 10px;
    box-shadow: inset 2px 2px 5px var(--neu-sombra-oscura), inset -2px -2px 5px var(--neu-sombra-clara);
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
.k-btn-avanzar.btn-pendiente  { background: linear-gradient(135deg,#fb923c,#f97316); color: #fff; box-shadow: 3px 3px 8px rgba(249,115,22,.35); }
.k-btn-avanzar.btn-cocinando  { background: linear-gradient(135deg,#60a5fa,#3b82f6); color: #fff; box-shadow: 3px 3px 8px rgba(59,130,246,.35); }
.k-btn-avanzar.btn-listo      { background: linear-gradient(135deg,#4ade80,#22c55e); color: #fff; box-shadow: 3px 3px 8px rgba(34,197,94,.35); }
.k-btn-avanzar:disabled       { opacity: .45; cursor: default; }
.k-btn-cancelar {
    width: 100%;
    border: none;
    border-radius: 10px;
    padding: 8px 12px;
    font-weight: 700;
    font-size: 12px;
    cursor: pointer;
    background: var(--neu-base);
    box-shadow: inset 3px 3px 6px var(--neu-sombra-oscura), inset -3px -3px 6px var(--neu-sombra-clara);
    color: #c0392b;
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

/* â”€â”€ Tabs de estaciones de producciÃ³n â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.est-tabs-bar {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
    background: var(--neu-base);
    border: none;
    border-radius: 18px;
    padding: 10px 14px;
    box-shadow: 6px 6px 14px var(--neu-sombra-oscura), -6px -6px 14px var(--neu-sombra-clara);
}
.est-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 12px;
    border: none;
    background: var(--neu-base);
    box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara);
    color: #4a5160;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: box-shadow .15s, color .15s;
}
.est-tab:hover { box-shadow: 1px 1px 4px var(--neu-sombra-oscura), -1px -1px 4px var(--neu-sombra-clara); }
.est-tab.activo {
    color: #fff;
    border-color: transparent;
}
.est-tab .est-tab-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    background: currentColor;
    opacity: .6;
}
.est-tab.activo .est-tab-dot { opacity: 1; background: rgba(255,255,255,.7); }
.est-tab-badge {
    background: rgba(255,255,255,.25);
    color: inherit;
    font-size: 11px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 999px;
    min-width: 20px;
    text-align: center;
}
.est-tab:not(.activo) .est-tab-badge { background: #e2e8f0; color: #475569; }
.est-panel-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #64748b;
    margin-left: auto;
    flex-shrink: 0;
}

/* â”€â”€ Items resaltados por estaciÃ³n â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.k-items-list li.de-esta-estacion {
    background: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
    font-weight: 700;
}
.k-items-list li.de-esta-estacion::before { background: #f59e0b; }
.k-items-list li.otra-estacion {
    opacity: .45;
}

.kn-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15,23,42,.55);
    backdrop-filter: blur(3px);
    z-index: 9500;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.kn-modal-overlay.show { display: flex; }
.kn-modal-box {
    background: var(--neu-base);
    border-radius: 22px;
    padding: 28px;
    max-width: 380px;
    width: 100%;
    text-align: center;
    box-shadow: 14px 14px 32px rgba(0,0,0,.28), -8px -8px 24px var(--neu-sombra-clara);
}
.kn-modal-icon {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: #fef2f2;
    color: #dc2626;
    font-size: 24px;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 14px;
}
.kn-modal-box h3 { font-size: 16px; margin: 0 0 6px; color: #0f172a; }
.kn-modal-box p { font-size: 13px; color: #64748b; margin: 0 0 20px; }
.kn-modal-actions { display: flex; gap: 10px; }
.kn-modal-btn {
    flex: 1;
    border: none;
    border-radius: 12px;
    padding: 11px 14px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
}
.kn-modal-btn.cancelar {
    background: var(--neu-base);
    color: #4a5160;
    box-shadow: 4px 4px 10px var(--neu-sombra-oscura), -4px -4px 10px var(--neu-sombra-clara);
}
.kn-modal-btn.confirmar {
    background: linear-gradient(135deg,#ef4444,#dc2626);
    color: #fff;
    box-shadow: 4px 4px 10px rgba(220,38,38,.35);
}
body.modo-oscuro .kn-modal-icon {
    background: rgba(220,38,38,.18);
    color: #fca5a5;
}
body.modo-oscuro .kn-modal-box h3 {
    color: #f2f2f4;
}
body.modo-oscuro .kn-modal-box p {
    color: #9aa0ac;
}
body.modo-oscuro .kn-modal-btn.cancelar {
    color: #e8e8ec;
}

/* â”€â”€ Toast â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.kn-toast {    position: fixed;
    bottom: 28px;
    right: 24px;
    background: var(--neu-base);
    color: #4a5160;
    padding: 12px 18px;
    border-radius: 14px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 8px 8px 20px rgba(0,0,0,.22), -4px -4px 14px var(--neu-sombra-clara);
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
.kn-toast.toast-ok  { background: #166534; color: #fff; }
.kn-toast.toast-err { background: #991b1b; color: #fff; }

/* â”€â”€ Responsive â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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









/* ===== Modo oscuro: cocina ===== */
body.modo-oscuro .kn-hero h2,
body.modo-oscuro .kn-col-title,
body.modo-oscuro .k-code,
body.modo-oscuro .k-client,
body.modo-oscuro .k-total { color: #f2f2f4; }
body.modo-oscuro .kn-hero p,
body.modo-oscuro .kn-last-update,
body.modo-oscuro .kn-periodo-label { color: #9aa0ac; }
body.modo-oscuro .kn-st-label { color: #9aa0ac; }
body.modo-oscuro .k-meta-chip,
body.modo-oscuro .k-timer,
body.modo-oscuro .est-tab:not(.activo) { color: #cfd3dc; }
body.modo-oscuro .k-items-list li { color: #cfd3dc; }
body.modo-oscuro .kn-col-empty { color: #7d8492; }

/* Encabezados de columna Kanban en modo oscuro: fondo saturado + texto blanco */
body.modo-oscuro .kn-col.col-pendiente .kn-col-head {
    background: linear-gradient(135deg, #92400e, #b45309);
    color: #fff;
}
body.modo-oscuro .kn-col.col-cocinando .kn-col-head {
    background: linear-gradient(135deg, #9a3412, #ea580c);
    color: #fff;
}
body.modo-oscuro .kn-col.col-listo .kn-col-head {
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
    color: #fff;
}
body.modo-oscuro .kn-col.col-entregado .kn-col-head {
    background: linear-gradient(135deg, #14532d, #16a34a);
    color: #fff;
}
body.modo-oscuro .kn-col-head .kn-col-badge {
    background: rgba(255,255,255,.22);
    color: #fff;
}

/* Tabs de estaciones activos: texto blanco garantizado */
body.modo-oscuro .est-tab.activo {
    color: #fff !important;
}

/* Chips de tipo de entrega (Delivery / Recojo / Comer aquí) en modo oscuro */
body.modo-oscuro .k-meta-chip.delivery {
    background: rgba(59,130,246,.18);
    border-color: rgba(59,130,246,.3);
    color: #93c5fd;
}
body.modo-oscuro .k-meta-chip.recojo {
    background: rgba(34,197,94,.18);
    border-color: rgba(34,197,94,.3);
    color: #86efac;
}
/* Timer urgente (+15 min) en modo oscuro */
body.modo-oscuro .k-timer.urgente {
    background: rgba(220,38,38,.2);
    color: #fca5a5;
}

/* Items resaltados por estación (ej: "1x La Clásica Burger") en modo oscuro */
body.modo-oscuro .k-items-list li.de-esta-estacion {
    background: rgba(245,158,11,.18);
    color: #fcd34d;
}
body.modo-oscuro .k-items-list li.de-esta-estacion::before {
    background: #f59e0b;
}
body.modo-oscuro .k-items-list li.otra-estacion {
    opacity: .35;
}
</style>

<script>
(function () {
    const API          = '../api/kitchen_orders.php';
    const API_EST      = '../api/estaciones.php';
    let pollingId      = null;
    let periodoActual  = 'hoy';
    let estacionActual = 0;          // 0 = todas
    let estaciones     = [];         // estaciones disponibles para este usuario
    let catIdsActuales = [];         // categoria_ids de la estaciÃ³n activa

    const LABELS_PERIODO = {
        hoy:    { texto: 'Pedidos de hoy',         icon: 'fa-sun' },
        semana: { texto: 'Pedidos de esta semana', icon: 'fa-calendar-week' },
        mes:    { texto: 'Pedidos de este mes',    icon: 'fa-calendar' },
        todo:   { texto: 'Todos los pedidos',      icon: 'fa-infinity' },
    };

    const COLUMNAS = [
        { id:'pendiente',  label:'Nuevos pedidos', icon:'fa-bell',          estadosIncluidos:['pendiente','pagado'],    clase:'col-pendiente', btnLabel:'<i class="fa-solid fa-fire-burner"></i> Empezar a cocinar', btnClase:'btn-pendiente', siguienteEstado:'en_preparacion' },
        { id:'cocinando',  label:'Cocinando',      icon:'fa-fire',          estadosIncluidos:['en_preparacion'],       clase:'col-cocinando', btnLabel:'<i class="fa-solid fa-plate-wheat"></i> Marcar como listo', btnClase:'btn-cocinando',  siguienteEstado:'en_camino' },
        { id:'listo',      label:'Listo / Servido',icon:'fa-plate-wheat',   estadosIncluidos:['en_camino'],            clase:'col-listo',     btnLabel:'<i class="fa-solid fa-circle-check"></i> Marcar entregado', btnClase:'btn-listo',      siguienteEstado:'entregado' },
        { id:'entregado',  label:'Entregado',      icon:'fa-circle-check',  estadosIncluidos:['entregado'],            clase:'col-entregado', btnLabel:null, btnClase:null, siguienteEstado:null },
    ];

    function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function minutosDesde(f) { const t = new Date(f).getTime(); return !t ? '0 min' : Math.max(0,Math.floor((Date.now()-t)/60000))+' min'; }
    function minutosNum(f)   { const t = new Date(f).getTime(); return !t ? 0 : Math.max(0,Math.floor((Date.now()-t)/60000)); }

    function mostrarToast(msg, tipo) {
        const t = document.getElementById('knToast');
        t.className = 'kn-toast ' + (tipo==='error' ? 'toast-err' : 'toast-ok');
        t.innerHTML = `<i class="fa-solid ${tipo==='error'?'fa-triangle-exclamation':'fa-circle-check'}"></i> ${esc(msg)}`;
        t.classList.add('visible');
        clearTimeout(t._tid);
        t._tid = setTimeout(() => t.classList.remove('visible'), 3000);
    }
    function actualizarHora() {
        const el = document.getElementById('kn-hora-update');
        if (el) el.textContent = new Date().toLocaleTimeString('es-PE',{timeZone:'America/Lima'});
    }
    function actualizarLabelPeriodo() {
        const el = document.getElementById('knPeriodoLabel');
        if (!el) return;
        const info = LABELS_PERIODO[periodoActual] || LABELS_PERIODO.hoy;
        const ahora = new Date().toLocaleDateString('es-PE',{timeZone:'America/Lima',weekday:'long',year:'numeric',month:'long',day:'numeric'});
        el.className = 'kn-periodo-label'+(periodoActual==='hoy'?' is-hoy':'');
        el.innerHTML = `<i class="fa-solid ${info.icon}"></i> <strong>${info.texto}</strong> &mdash; ${ahora} (Lima)`;
    }

    // â”€â”€ Render tabs de estaciones â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function renderTabs(conteosPorEstacion = {}) {
        const bar = document.getElementById('estacionesBar');
        if (!estaciones.length) { bar.innerHTML = ''; return; }

        const tabs = estaciones.map(e => {
            const activo = estacionActual === e.id;
            const conteo = conteosPorEstacion[e.id] ?? '';
            return `<button class="est-tab ${activo?'activo':''}" data-id="${e.id}"
                     style="${activo?`background:${esc(e.color)};`:''}">
                <i class="ti ${esc(e.icono)}"></i> ${esc(e.nombre)}
                ${conteo !== '' ? `<span class="est-tab-badge">${conteo}</span>` : ''}
            </button>`;
        });

        // Tab "Todas" (solo si hay mÃ¡s de 1 estaciÃ³n)
        const tabTodas = estaciones.length > 1
            ? `<button class="est-tab ${estacionActual===0?'activo':''}" data-id="0"
               style="${estacionActual===0?'background:#0f172a;':''}">
                <i class="fa-solid fa-layer-group"></i> Todas
               </button>`
            : '';

        bar.innerHTML = `<div class="est-tabs-bar">
            ${tabTodas}
            ${tabs.join('')}
            <span class="est-panel-label"><i class="ti ti-info-circle"></i> Panel de cocina</span>
        </div>`;

        bar.querySelectorAll('.est-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                estacionActual = parseInt(btn.dataset.id || '0');
                cargarPedidos();
            });
        });
    }

    // â”€â”€ Cargar estaciones disponibles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    async function cargarEstaciones() {
        try {
            const r = await fetch(API_EST, { headers: { Accept: 'application/json' } });
            const d = await r.json();
            if (!d.ok) return;
            estaciones = d.estaciones || [];
            // Cocinero con 1 sola estaciÃ³n â†’ seleccionarla por defecto
            if (estaciones.length === 1) {
                estacionActual = estaciones[0].id;
            }
            renderTabs();
        } catch (_) { /* silencioso */ }
    }

    // â”€â”€ Stats â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function renderStats(pedidos) {
        const c = {};
        pedidos.forEach(p => { c[p.estado] = (c[p.estado]||0)+1; });
        const activos = pedidos.filter(p => p.estado!=='cancelado'&&p.estado!=='entregado').length;
        const fichas = [
            {clase:'st-total',    icon:'fa-list-check',  label:'En proceso',val:activos},
            {clase:'st-pendiente',icon:'fa-bell',         label:'Nuevos',    val:(c.pendiente||0)+(c.pagado||0)},
            {clase:'st-cocinando',icon:'fa-fire',         label:'Cocinando', val:c.en_preparacion||0},
            {clase:'st-listo',    icon:'fa-plate-wheat',  label:'Listos',    val:c.en_camino||0},
            {clase:'st-entregado',icon:'fa-circle-check', label:'Entregados',val:c.entregado||0},
            {clase:'st-cancelado',icon:'fa-circle-xmark', label:'Cancelados',val:c.cancelado||0},
        ];
        document.getElementById('knStats').innerHTML = fichas.map(f=>`
            <div class="kn-stat ${f.clase}">
                <div class="kn-st-icon"><i class="fa-solid ${f.icon}"></i></div>
                <div class="kn-st-label">${f.label}</div>
                <div class="kn-st-count">${f.val}</div>
            </div>`).join('');
    }

    // â”€â”€ Card pedido â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function renderCard(p, col) {
        const mins    = minutosNum(p.creado_en);
        const urgente = col.id==='pendiente' && mins>15;
        let entregaChip = `<span class="k-meta-chip recojo"><i class="fa-solid fa-house"></i> Recojo</span>`;
        if (p.tipo_entrega==='delivery')   entregaChip = `<span class="k-meta-chip delivery"><i class="fa-solid fa-motorcycle"></i> Delivery</span>`;
        if (p.tipo_entrega==='comer_aqui') entregaChip = `<span class="k-meta-chip recojo"><i class="fa-solid fa-utensils"></i> Comer aqui</span>`;
        const metodoPago = p.metodo_pago==='tarjeta' ? '<i class="fa-solid fa-credit-card"></i>' : p.metodo_pago==='efectivo' ? '<i class="fa-solid fa-money-bill-wave"></i>' : '<i class="fa-brands fa-whatsapp"></i>';
        const mesaChip   = p.tipo_entrega==='comer_aqui'&&p.mesa_nombre
            ? `<span class="k-meta-chip"><i class="fa-solid fa-chair"></i> ${esc(p.mesa_nombre)}${p.zona_nombre?' Â· '+esc(p.zona_nombre):''}</span>` : '';

        // Items: si hay estación seleccionada, la API ya devuelve solo los productos de esa estación
        let itemsHtml;
        if (estacionActual > 0 && p.items_detalle && p.items_detalle.length) {
            itemsHtml = p.items_detalle.slice(0,10).map(it => `<li class="de-esta-estacion">${esc(it.cantidad+'x '+it.nombre_producto)}</li>`).join('');
        } else {
            itemsHtml = (p.items||[]).slice(0,8).map(it=>`<li>${esc(it)}</li>`).join('');
        }

        const btnAvanzar = col.btnLabel && col.siguienteEstado
            ? `<button class="k-btn-avanzar ${col.btnClase}" data-action="avanzar" data-next="${col.siguienteEstado}">${col.btnLabel}</button>` : '';
        const btnCancelar = col.id!=='entregado'
            ? `<button class="k-btn-cancelar" data-action="cancelar"><i class="fa-solid fa-xmark"></i> Cancelar</button>` : '';

        return `<article class="k-card${urgente?' urgente':''}" data-id="${p.id}">
            <div class="k-card-top">
                <div>
                    <div class="k-code"><i class="fa-solid fa-receipt" style="color:#e8590c;margin-right:4px;"></i>${esc(p.codigo)}</div>
                    <div class="k-client">${esc(p.cliente_nombre)}</div>
                </div>
                <div class="k-timer${urgente?' urgente':''}"><i class="fa-regular fa-clock"></i> ${minutosDesde(p.creado_en)}</div>
            </div>
            <div class="k-meta-row">
                ${entregaChip}${mesaChip}
                <span class="k-meta-chip">${metodoPago} ${esc(p.metodo_pago)}</span>
                <span class="k-meta-chip"><i class="fa-solid fa-layer-group"></i> ${(estacionActual > 0 ? (p.total_items_estacion || 0) : p.total_items)} ítem${(estacionActual > 0 ? (p.total_items_estacion || 0) : p.total_items)!==1?'s':''}</span>
                <span class="k-total">S/ ${Number(p.total).toFixed(2)}</span>
            </div>
            <ul class="k-items-list">${itemsHtml||'<li>Sin detalle</li>'}</ul>
            <div class="k-actions">${btnAvanzar}${btnCancelar}</div>
        </article>`;
    }

    // â”€â”€ Board kanban â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    function renderBoard(pedidos) {
        const board = document.getElementById('knBoard');
        board.innerHTML = COLUMNAS.map(col => {
            const cards = pedidos.filter(p => col.estadosIncluidos.includes(p.estado));
            const cardsHtml = cards.length
                ? cards.map(p => renderCard(p,col)).join('')
                : `<div class="kn-col-empty"><i class="fa-solid fa-${col.icon}"></i><span>Sin pedidos aquÃ­</span></div>`;
            return `<div class="kn-col ${col.clase}">
                <div class="kn-col-head">
                    <div class="kn-col-title"><i class="fa-solid ${col.icon}"></i> ${col.label}</div>
                    <span class="kn-col-badge">${cards.length}</span>
                </div>
                <div class="kn-col-body">${cardsHtml}</div>
            </div>`;
        }).join('');
    }

    // â”€â”€ Cargar pedidos â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    async function cargarPedidos() {
        try {
            let url = `${API}?limite=200&periodo=${encodeURIComponent(periodoActual)}`;
            if (estacionActual > 0) url += `&estacion_id=${estacionActual}`;
            const r = await fetch(url, { headers: { Accept: 'application/json' } });
            const data = await r.json();
            if (!data.ok) throw new Error(data.mensaje||'Error al cargar');

            // Actualizar catIds para render de items
            catIdsActuales = data.estacion_cat_ids || [];

            const pedidos = (data.pedidos||[]).filter(p => p.estado!=='cancelado');
            renderStats(data.pedidos||[]);
            renderBoard(pedidos);
            actualizarHora();
            actualizarLabelPeriodo();

            // Calcular conteos por estaciÃ³n para badges en tabs
            const conteos = {};
            if (estaciones.length) {
                // Solo pedidos activos para los badges
                (data.pedidos||[]).filter(p => !['cancelado','entregado'].includes(p.estado)).forEach(p => {
                    // No podemos saber fÃ¡cilmente aquÃ­ quÃ© estaciÃ³n â†’ simplemente actualizamos sin conteos
                });
            }
            renderTabs(conteos);
        } catch (err) {
            mostrarToast('No se pudo cargar pedidos: '+err.message, 'error');
        }
    }

let pedidoIdACancelar = null;

    function abrirModalCancelar(id) {
        pedidoIdACancelar = id;
        document.getElementById('knModalConfirm').classList.add('show');
    }

    function cerrarModalCancelar() {
        pedidoIdACancelar = null;
        document.getElementById('knModalConfirm').classList.remove('show');
    }

    document.getElementById('knModalCancelar').addEventListener('click', cerrarModalCancelar);
    document.getElementById('knModalConfirmar').addEventListener('click', () => {
        const id = pedidoIdACancelar;
        cerrarModalCancelar();
        if (id) cambiarEstado(id, 'cancelado');
    });
    document.getElementById('knModalConfirm').addEventListener('click', (e) => {
        if (e.target.id === 'knModalConfirm') cerrarModalCancelar();
    });

    // â”€â”€ Cambiar estado â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    async function cambiarEstado(id, estado) {
        try {
            const r = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', Accept:'application/json' },
                body: JSON.stringify({ pedido_id: id, estado }),
            });
            const data = await r.json();
            if (!data.ok) throw new Error(data.mensaje||'No se pudo actualizar');
            mostrarToast('Estado actualizado', 'ok');
            await cargarPedidos();
        } catch (err) { mostrarToast('Error: '+err.message, 'error'); }
    }

    // â”€â”€ DelegaciÃ³n de eventos â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('knBoard').addEventListener('click', function(e) {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const card = btn.closest('.k-card');
        if (!card) return;
        const id = parseInt(card.dataset.id, 10);
        if (!id) return;
        if (btn.dataset.action==='avanzar') cambiarEstado(id, btn.dataset.next);
        else if (btn.dataset.action==='cancelar') {
            abrirModalCancelar(id);
        }
    });

    // â”€â”€ Filtros de perÃ­odo â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.querySelectorAll('.kn-btn-periodo').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.kn-btn-periodo').forEach(b => b.classList.remove('activo'));
            btn.classList.add('activo');
            periodoActual = btn.dataset.periodo||'hoy';
            cargarPedidos();
        });
    });

    // â”€â”€ Refrescar manual â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    document.getElementById('btnRefrescarCocina').addEventListener('click', function() {
        const ico = this.querySelector('i');
        ico.classList.add('fa-spin');
        cargarPedidos().finally(() => setTimeout(()=>ico.classList.remove('fa-spin'),700));
    });

    // â”€â”€ Iniciar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    cargarEstaciones().then(() => cargarPedidos());
    pollingId = setInterval(cargarPedidos, 8000);
    window.addEventListener('beforeunload', () => { if (pollingId) clearInterval(pollingId); });
})();
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>

