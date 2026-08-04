<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirRol(['admin']);

$db = getDB();
$turnoAbierto = $db->query("SELECT id FROM cajas_turnos WHERE estado = 'abierta' ORDER BY id DESC LIMIT 1")->fetch();
if (!$turnoAbierto) {
    header('Location: cajas.php');
    exit;
}

$tituloPagina = 'POS Restaurante';
$paginaActual = 'pos';
require __DIR__ . '/_layout_top.php';
?>

<style>
:root {
    --pos-bg: var(--neu-base, #e9edf5);
    --pos-sombra-oscura: var(--neu-sombra-oscura, rgba(163,177,198,.55));
    --pos-sombra-clara: var(--neu-sombra-clara, rgba(255,255,255,.85));
    --pos-primary: #E8590C;
    --pos-texto: #444a5a;
    --pos-muted: #8a93a3;
    --pos-ok: #1e8449;
    --pos-ok-bg: #dcfce7;
    --pos-busy: #92400e;
    --pos-busy-bg: #fef3c7;
}

body.modo-oscuro .pos-shell {
    --pos-texto: #e8e8ec;
    --pos-muted: #9aa0ac;
}

.pos-shell {
    display: grid;
    gap: 16px;
    overflow-x: hidden;
    color: var(--pos-texto);
}

.pos-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    background: var(--pos-bg);
    border: none;
    border-radius: 18px;
    padding: 14px 18px;
    box-shadow: 6px 6px 14px var(--pos-sombra-oscura), -6px -6px 14px var(--pos-sombra-clara);
}

.pos-top h2 {
    margin: 0;
    color: var(--pos-texto);
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.pos-top h2 i { color: var(--pos-primary); }

.pos-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 7px 14px;
    background: var(--pos-bg);
    color: var(--pos-texto);
    font-size: 12px;
    font-weight: 700;
    box-shadow: inset 3px 3px 6px var(--pos-sombra-oscura), inset -3px -3px 6px var(--pos-sombra-clara);
    margin-left: 8px;
}

.pos-grid {
    display: grid;
    grid-template-columns: 1fr 420px;
    gap: 16px;
}

.pos-grid > .pos-card {
    min-width: 0;
}

@media (max-width: 1180px) {
    .pos-grid {
        grid-template-columns: 1fr;
    }
}

.pos-card {
    background: var(--pos-bg);
    border: none;
    border-radius: 20px;
    padding: 16px;
    box-shadow: 8px 8px 18px var(--pos-sombra-oscura), -8px -8px 18px var(--pos-sombra-clara);
}

.pos-card h3 {
    margin: 0 0 12px;
    color: var(--pos-texto);
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.pos-card h3 i { color: var(--pos-primary); }

.pos-left-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.pos-left-head h3 { margin: 0; }

.pos-mesa-activa {
    font-size: 12px;
    color: var(--pos-primary);
    font-weight: 800;
    margin-bottom: 10px;
}

/* ----- Botones de zona: "SALON PRINCIPAL" / "TERRAZA" / "BARRA" ----- */
.pos-categorias {
    display: flex;
    flex-wrap: wrap;
    gap: 14px 16px;
    margin: 10px 0;
}

.pos-zonas {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 10px 0;
}

.pos-zona-btn {
    border: none;
    background: var(--pos-bg);
    color: var(--pos-texto);
    border-radius: 999px;
    padding: 9px 16px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 4px 4px 10px var(--pos-sombra-oscura), -4px -4px 10px var(--pos-sombra-clara);
    transition: box-shadow .15s ease, transform .15s ease, background .15s ease, color .15s ease;
}

.pos-zona-btn:hover { transform: translateY(-2px); }

.pos-zona-btn.activa {
    background: linear-gradient(135deg, #ff8a3d, var(--pos-primary));
    color: #fff;
    box-shadow: 3px 3px 8px rgba(232,89,12,.4);
    transform: translateY(0);
}

/* ----- Categorías: círculo con foto arriba, nombre debajo ----- */
.pos-categoria-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    width: 74px;
    border: none;
    background: transparent;
    box-shadow: none;
    padding: 2px;
    cursor: pointer;
    transition: transform .15s ease;
}

.pos-categoria-btn:hover { transform: translateY(-2px); }

.pos-categoria-img-wrap {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: var(--pos-bg);
    box-shadow: 4px 4px 10px var(--pos-sombra-oscura), -4px -4px 10px var(--pos-sombra-clara);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transition: box-shadow .15s ease;
}

.pos-categoria-btn.activa .pos-categoria-img-wrap {
    box-shadow: 0 0 0 3px var(--pos-primary), 3px 3px 8px rgba(232,89,12,.4);
}

.pos-categoria-btn img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    margin: 0;
    border: none;
}

.pos-cat-fallback {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(232,89,12,.15);
    color: var(--pos-primary);
    font-size: 18px;
    font-weight: 800;
}

.pos-categoria-texto {
    display: block;
    width: 100%;
    font-size: 11px;
    font-weight: 700;
    color: var(--pos-texto);
    text-align: center;
    line-height: 1.25;
    word-break: break-word;
    white-space: normal;
}

.pos-categoria-btn.activa .pos-categoria-texto {
    color: var(--pos-primary);
}

/* ----- Mapa de mesas ----- */
.pos-board-wrap {
    overflow: auto;
    border: none;
    border-radius: 16px;
    background: var(--pos-bg);
    box-shadow: inset 6px 6px 14px var(--pos-sombra-oscura), inset -6px -6px 14px var(--pos-sombra-clara);
}

.pos-board {
    position: relative;
    min-width: 900px;
    min-height: 540px;
}

.pos-mesa {
    position: absolute;
    width: 130px;
    min-height: 76px;
    border-radius: 16px;
    border: none;
    background: var(--pos-bg);
    padding: 10px;
    text-align: center;
    cursor: pointer;
    box-shadow: 5px 5px 12px var(--pos-sombra-oscura), -5px -5px 12px var(--pos-sombra-clara);
    transition: transform .15s ease, box-shadow .15s ease;
}

.pos-mesa.redonda { border-radius: 999px; }

.pos-mesa:hover { transform: translateY(-2px); }

.pos-mesa.libre {
    box-shadow: 5px 5px 12px var(--pos-sombra-oscura), -5px -5px 12px var(--pos-sombra-clara), inset 0 0 0 2px rgba(46,204,113,.5);
}

.pos-mesa.ocupada {
    box-shadow: 5px 5px 12px var(--pos-sombra-oscura), -5px -5px 12px var(--pos-sombra-clara), inset 0 0 0 2px rgba(242,201,76,.6);
}

.pos-mesa.seleccionada {
    box-shadow: inset 4px 4px 9px var(--pos-sombra-oscura), inset -4px -4px 9px var(--pos-sombra-clara), 0 0 0 2px var(--pos-primary);
    transform: translateY(0);
}

.pos-mesa .name {
    display: block;
    font-size: 13px;
    font-weight: 800;
    color: var(--pos-texto);
}

.pos-mesa .meta {
    display: block;
    font-size: 11px;
    color: var(--pos-muted);
    margin-top: 2px;
}

.pos-order-disabled {
    border: none;
    border-radius: 16px;
    background: var(--pos-bg);
    color: var(--pos-muted);
    text-align: center;
    padding: 22px;
    font-size: 13px;
    font-weight: 700;
    box-shadow: inset 5px 5px 12px var(--pos-sombra-oscura), inset -5px -5px 12px var(--pos-sombra-clara);
}

/* ----- Resumen / precuenta ----- */
.pos-resumen {
    border: none;
    border-radius: 16px;
    background: var(--pos-bg);
    padding: 14px;
    margin-bottom: 12px;
    box-shadow: inset 5px 5px 12px var(--pos-sombra-oscura), inset -5px -5px 12px var(--pos-sombra-clara);
}

.pos-resumen strong { color: var(--pos-texto); }

.pos-resumen-list {
    max-height: 140px;
    overflow: auto;
    margin-top: 8px;
    border-top: 1px dashed rgba(0,0,0,.12);
    padding-top: 8px;
}

.pos-resumen-item {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: var(--pos-texto);
    margin-bottom: 4px;
}

.pos-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 12px;
}

@media (max-width: 680px) {
    .pos-fields { grid-template-columns: 1fr; }
}

.pos-field label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 5px;
    color: var(--pos-texto);
}

.pos-field input,
.pos-field select,
.pos-field textarea {
    width: 100%;
    border: none;
    border-radius: 12px;
    padding: 10px 12px;
    background: var(--pos-bg);
    color: var(--pos-texto);
    box-shadow: inset 3px 3px 7px var(--pos-sombra-oscura), inset -3px -3px 7px var(--pos-sombra-clara);
    transition: box-shadow .15s ease;
}

.pos-field input:focus,
.pos-field select:focus,
.pos-field textarea:focus {
    outline: none;
    box-shadow: inset 4px 4px 9px var(--pos-sombra-oscura), inset -4px -4px 9px var(--pos-sombra-clara), 0 0 0 2px rgba(232,89,12,.35);
}

/* Quitamos la flechita nativa del <select> y ponemos una propia,
   para que combine con el resto del diseño neumórfico */
.pos-field select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
    padding-right: 34px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23E8590C' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 16px;
}

.pos-field select:disabled {
    opacity: .55;
    cursor: not-allowed;
}

/* ----- Catálogo de productos ----- */
.pos-products {
    border: none;
    border-radius: 16px;
    background: var(--pos-bg);
    max-height: 430px;
    overflow: auto;
    padding: 12px;
    margin-bottom: 12px;
    box-shadow: inset 5px 5px 12px var(--pos-sombra-oscura), inset -5px -5px 12px var(--pos-sombra-clara);
}

.pos-prod-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

@media (max-width: 1200px) { .pos-prod-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 900px)  { .pos-prod-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 560px)  { .pos-prod-grid { grid-template-columns: 1fr; } }

.pos-prod {
    border: none;
    border-radius: 16px;
    background: var(--pos-bg);
    padding: 10px;
    position: relative;
    box-shadow: 5px 5px 12px var(--pos-sombra-oscura), -5px -5px 12px var(--pos-sombra-clara);
    transition: transform .15s ease;
}

.pos-prod:hover { transform: translateY(-2px); }
.pos-prod { cursor: pointer; }
.pos-prod:active { transform: scale(.985); }


.pos-prod-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: linear-gradient(135deg, #ff8a3d, var(--pos-primary));
    color: #fff;
    border-radius: 999px;
    padding: 5px 9px;
    font-size: 10px;
    font-weight: 800;
    box-shadow: 2px 2px 6px rgba(232,89,12,.4);
}

.pos-prod-img {
    width: 100%;
    height: 120px;
    border-radius: 12px;
    object-fit: contain;
    object-position: center;
    border: none;
    margin-bottom: 8px;
    background: var(--pos-bg);
    box-shadow: inset 3px 3px 8px var(--pos-sombra-oscura), inset -3px -3px 8px var(--pos-sombra-clara);
    padding: 4px;
}

.pos-prod-fallback {
    width: 100%;
    height: 120px;
    border-radius: 12px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--pos-bg);
    box-shadow: inset 3px 3px 8px var(--pos-sombra-oscura), inset -3px -3px 8px var(--pos-sombra-clara);
    color: var(--pos-muted);
    font-size: 12px;
    font-weight: 700;
}

.pos-prod strong { display: block; color: var(--pos-texto); font-size: 13px; }
.pos-prod small { display: block; color: var(--pos-muted); margin: 2px 0 6px; font-size: 11px; }

.pos-prod-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 6px;
}

.pos-stepper {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--pos-bg);
    border: none;
    border-radius: 999px;
    padding: 4px;
    box-shadow: inset 2px 2px 5px var(--pos-sombra-oscura), inset -2px -2px 5px var(--pos-sombra-clara);
}

.pos-stepper-btn {
    width: 26px;
    height: 26px;
    border-radius: 999px;
    border: none;
    background: linear-gradient(135deg, #ff8a3d, var(--pos-primary));
    color: #fff;
    font-size: 14px;
    font-weight: 800;
    line-height: 1;
    cursor: pointer;
    box-shadow: 2px 2px 5px rgba(232,89,12,.4);
    transition: transform .1s ease;
}

.pos-stepper-btn:active { transform: scale(.9); }

.pos-stepper-val {
    min-width: 18px;
    text-align: center;
    font-size: 12px;
    font-weight: 800;
    color: var(--pos-texto);
}

/* ----- Botones generales ----- */
.pos-btn {
    border: none;
    border-radius: 12px;
    padding: 10px 14px;
    font-weight: 700;
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease;
}

.pos-btn:hover { transform: translateY(-2px); }
.pos-btn:active { transform: translateY(0); }

.pos-btn.dark {
    background: linear-gradient(135deg, #3a3f52, #1f2430);
    color: #fff;
    box-shadow: 4px 4px 10px rgba(0,0,0,.3);
}

.pos-btn.soft {
    background: var(--pos-bg);
    color: var(--pos-texto);
    box-shadow: 4px 4px 10px var(--pos-sombra-oscura), -4px -4px 10px var(--pos-sombra-clara);
}

.pos-btn.warn {
    background: linear-gradient(135deg, #ff8a3d, var(--pos-primary));
    color: #fff;
    box-shadow: 4px 4px 10px rgba(232,89,12,.4);
}

/* ----- Carrito ----- */
.pos-cart {
    border: none;
    border-radius: 16px;
    background: var(--pos-bg);
    max-height: 180px;
    overflow: auto;
    margin-bottom: 10px;
    box-shadow: inset 4px 4px 10px var(--pos-sombra-oscura), inset -4px -4px 10px var(--pos-sombra-clara);
}

.pos-cart.rebote { animation: posCartBounce .55s ease; }

@keyframes posCartBounce {
    0% { transform: scale(1); }
    30% { transform: scale(1.03); }
    55% { transform: scale(0.985); }
    100% { transform: scale(1); }
}

.pos-cart table { width: 100%; border-collapse: collapse; }

.pos-cart th,
.pos-cart td {
    border-bottom: 1px solid rgba(0,0,0,.08);
    padding: 8px;
    font-size: 12px;
    text-align: left;
    color: var(--pos-texto);
}

.pos-cart-main { display: flex; gap: 10px; align-items: flex-start; }

.pos-cart-thumb {
    width: 54px;
    height: 54px;
    border-radius: 12px;
    object-fit: cover;
    flex: 0 0 auto;
    box-shadow: inset 2px 2px 5px var(--pos-sombra-oscura), inset -2px -2px 5px var(--pos-sombra-clara);
}

.pos-cart-thumb-fallback {
    width: 54px;
    height: 54px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    background: var(--pos-bg);
    box-shadow: inset 2px 2px 5px var(--pos-sombra-oscura), inset -2px -2px 5px var(--pos-sombra-clara);
    color: var(--pos-muted);
    font-size: 11px;
    font-weight: 800;
}

.pos-cart-info { flex: 1; min-width: 0; }
.pos-cart-name { font-size: 13px; font-weight: 800; color: var(--pos-texto); margin-bottom: 3px; }
.pos-cart-unit { font-size: 11px; color: var(--pos-muted); }
.pos-cart-options { list-style: none; margin: 6px 0 0; padding: 0 0 0 10px; }
.pos-cart-options li { font-size: 11px; color: var(--pos-muted); line-height: 1.45; }

.pos-fly {
    position: fixed;
    z-index: 9999;
    pointer-events: none;
    border-radius: 16px;
    object-fit: cover;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.22);
    transition: left .7s cubic-bezier(.4,-0.2,.6,1), top .7s cubic-bezier(.4,-0.2,.6,1), width .7s ease, height .7s ease, opacity .7s ease, transform .7s ease;
}

.pos-total {
    font-size: 19px;
    font-weight: 800;
    color: var(--pos-primary);
    margin-bottom: 12px;
}

.pos-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.pos-actions.stack { flex-direction: column; }
.pos-btn.full { width: 100%; justify-content: center; }

.pos-msg { min-height: 20px; margin-top: 10px; font-size: 13px; font-weight: 700; }
.pos-msg.ok { color: #1e8449; }
.pos-msg.err { color: #b91c1c; }

.pos-scroll-cart-btn {
    position: fixed;
    right: 16px;
    bottom: 86px;
    width: 48px;
    height: 48px;
    border: none;
    border-radius: 999px;
    display: none;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ff8a3d, var(--pos-primary));
    color: #fff;
    box-shadow: 0 10px 20px rgba(232,89,12,.35);
    z-index: 10002;
}

.pos-scroll-cart-btn.show {
    display: inline-flex;
}

.pos-scroll-cart-btn i {
    font-size: 24px;
    line-height: 1;
}

@media (min-width: 681px) {
    .pos-scroll-cart-btn {
        bottom: 22px;
    }
}

/* ----- Modales ----- */
.pos-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(20, 20, 30, 0.55);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 14px;
}

.pos-modal.show { display: flex; }

.pos-modal-box {
    width: 100%;
    max-width: 560px;
    background: var(--pos-bg);
    border-radius: 20px;
    border: none;
    padding: 18px;
    max-height: 88vh;
    overflow: auto;
    box-shadow: 0 25px 60px rgba(0,0,0,.4);
}

.pos-modal-box h4 {
    margin: 0 0 12px;
    color: var(--pos-texto);
    display: flex;
    align-items: center;
    gap: 8px;
}

.pos-modal-box h4 i { color: var(--pos-primary); }

.pos-modal-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

@media (max-width: 680px) { .pos-modal-grid { grid-template-columns: 1fr; } }

.pos-opciones-producto {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 14px;
    background: var(--pos-bg);
    border-radius: 14px;
    padding: 10px;
    box-shadow: inset 3px 3px 8px var(--pos-sombra-oscura), inset -3px -3px 8px var(--pos-sombra-clara);
}

.pos-opciones-producto img {
    width: 72px;
    height: 72px;
    border-radius: 14px;
    object-fit: cover;
    box-shadow: inset 2px 2px 5px var(--pos-sombra-oscura), inset -2px -2px 5px var(--pos-sombra-clara);
}

.pos-opciones-nombre { font-size: 15px; font-weight: 800; color: var(--pos-texto); }
.pos-opciones-precio { font-size: 13px; font-weight: 700; color: var(--pos-primary); margin-top: 4px; }

.pos-opciones-grupo {
    border: none;
    border-radius: 16px;
    background: var(--pos-bg);
    padding: 12px;
    margin-bottom: 12px;
    box-shadow: 4px 4px 10px var(--pos-sombra-oscura), -4px -4px 10px var(--pos-sombra-clara);
}

.pos-opciones-titulo { font-size: 13px; font-weight: 800; color: var(--pos-texto); margin-bottom: 8px; }

.pos-opciones-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 4px 9px;
    margin-left: 6px;
    background: rgba(232,89,12,.12);
    color: var(--pos-primary);
    font-size: 10px;
    font-weight: 800;
}

.pos-opciones-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    border: none;
    border-radius: 12px;
    background: var(--pos-bg);
    padding: 9px 12px;
    margin-bottom: 8px;
    cursor: pointer;
    box-shadow: 3px 3px 7px var(--pos-sombra-oscura), -3px -3px 7px var(--pos-sombra-clara);
    transition: box-shadow .15s ease;
}

.pos-opciones-label.seleccionada {
    box-shadow: inset 3px 3px 7px var(--pos-sombra-oscura), inset -3px -3px 7px var(--pos-sombra-clara), 0 0 0 2px rgba(232,89,12,.4);
}

.pos-opciones-label input { margin-right: 8px; }

.pos-opciones-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px dashed rgba(0,0,0,.12);
    margin-top: 14px;
    padding-top: 12px;
    font-weight: 800;
    color: var(--pos-primary);
    font-size: 16px;
}
/* Forzar estilos de botones POS en modo oscuro, por si un estilo global los sobrescribe */
body.modo-oscuro .pos-btn.warn {
    background: linear-gradient(135deg, #ff8a3d, var(--pos-primary)) !important;
    color: #fff !important;
    box-shadow: 4px 4px 10px rgba(232,89,12,.4) !important;
    border: none !important;
}

body.modo-oscuro .pos-btn.dark {
    background: linear-gradient(135deg, #3a3f52, #1f2430) !important;
    color: #fff !important;
    box-shadow: 4px 4px 10px rgba(0,0,0,.3) !important;
    border: none !important;
}

body.modo-oscuro .pos-btn.soft {
background: #2c3144 !important;    color: var(--pos-texto) !important;
    box-shadow: 4px 4px 10px var(--pos-sombra-oscura), -4px -4px 10px var(--pos-sombra-clara) !important;
    border: none !important;
}

body.modo-oscuro .pos-zona-btn.activa,
body.modo-oscuro .pos-categoria-btn.activa .pos-categoria-img-wrap {
    background: linear-gradient(135deg, #ff8a3d, var(--pos-primary)) !important;
    border: none !important;
}

body.modo-oscuro .pos-prod-badge {
    background: linear-gradient(135deg, #ff8a3d, var(--pos-primary)) !important;
    color: #fff !important;
    border: none !important;
}
</style>

<div class="pos-shell">
    <div class="pos-top">
        <h2><i class="ti ti-device-desktop"></i> POS Restaurante</h2>
        <div>
            <span class="pos-chip" id="chipCajaTurno">Caja activa</span>
            <span class="pos-chip" id="chipMesaActual">Mesa: sin seleccionar</span>
        </div>
    </div>

    <div class="pos-grid">
        <section class="pos-card">
            <div id="mesaSelectorView">
                <h3><i class="ti ti-layout-grid"></i> Mapa de mesas</h3>
                <div class="pos-zonas" id="posZonas"></div>
                <div class="pos-board-wrap">
                    <div class="pos-board" id="posBoard"></div>
                </div>
            </div>

            <div id="platosView" style="display:none;">
                <div class="pos-left-head">
                    <h3><i class="ti ti-tools-kitchen-2"></i> Platos de la mesa</h3>
                    <button type="button" class="pos-btn soft" id="btnVolverMesas">Volver a mesas</button>
                </div>
                <div class="pos-mesa-activa" id="platosMesaLabel">Mesa: -</div>

                <div class="pos-categorias" id="posCategorias"></div>

                <div class="pos-field" style="margin:10px 0 8px;">
                    <label>Buscar producto</label>
                    <input type="text" id="filtroProducto" placeholder="Nombre o descripción">
                </div>
                <div class="pos-products" id="catalogoPos"></div>
            </div>
        </section>

        <section class="pos-card">
            <h3><i class="ti ti-receipt"></i> Orden y facturación</h3>

            <div id="orderDisabled" class="pos-order-disabled">
                Selecciona una mesa en el mapa para iniciar la orden, enviar comanda y luego facturar.
            </div>

            <div id="orderPanel" style="display:none;">
                <div class="pos-resumen" id="posResumenMesa"></div>

                <div class="pos-actions" style="margin-bottom:10px;">
                    <button type="button" class="pos-btn warn" id="btnActualizarPrecuenta">Actualizar precuenta</button>
                    <button type="button" class="pos-btn soft" id="btnImprimirPrecuenta">Imprimir precuenta</button>
                </div>

                <div class="pos-cart">
                    <table>
                        <thead><tr><th>Producto</th><th>Cant.</th><th>Sub</th><th></th></tr></thead>
                        <tbody id="carritoBody"></tbody>
                    </table>
                </div>

                <div class="pos-total" id="posTotal">Total: S/ 0.00</div>

                <div class="pos-actions stack">
                    <button type="button" class="pos-btn dark full" id="btnEnviarComanda">Enviar comanda a cocina</button>
                    <button type="button" class="pos-btn warn full" id="btnAbrirFacturacion">Facturar mesa</button>
                    <button type="button" class="pos-btn soft full" id="btnLimpiarCarrito">Limpiar carrito local</button>
                </div>

                <div id="posMsg" class="pos-msg"></div>
            </div>
        </section>
    </div>
</div>

<button type="button" class="pos-scroll-cart-btn" id="btnIrCarritoAndroid" aria-label="Bajar al carrito">
    <i class="ti ti-arrow-down"></i>
</button>

<div class="pos-modal" id="modalPrecuenta">
    <div class="pos-modal-box">
        <h4><i class="ti ti-file-invoice"></i> Precuenta de mesa</h4>
        <div id="precuentaContenido"></div>
        <div class="pos-actions" style="margin-top:10px;">
            <button type="button" class="pos-btn soft" id="btnCerrarPrecuenta">Cerrar</button>
        </div>
    </div>
</div>

<div class="pos-modal" id="modalOpcionesPos">
    <div class="pos-modal-box">
        <h4><i class="ti ti-adjustments-horizontal"></i> Personalizar producto</h4>
        <div id="posOpcionesProductoInfo" class="pos-opciones-producto"></div>
        <div id="posOpcionesGrupos"></div>
        <div class="pos-opciones-total">
            <span>Total producto</span>
            <strong id="posOpcionesTotalTexto">S/ 0.00</strong>
        </div>
        <div class="pos-actions" style="margin-top:12px;">
            <button type="button" class="pos-btn dark" id="btnConfirmarOpcionesPos">Agregar al carrito</button>
            <button type="button" class="pos-btn soft" id="btnCerrarOpcionesPos">Cancelar</button>
        </div>
    </div>
</div>

<div class="pos-modal" id="modalFacturacionPos">
    <div class="pos-modal-box">
        <h4><i class="ti ti-file-invoice"></i> Facturar mesa</h4>
        <div class="pos-modal-grid">
            <div class="pos-field"><label>Tipo de emisión</label><select id="posFactTipoEmision"><option value="ticket">Ticket de venta</option><option value="boleta">Boleta</option><option value="factura">Factura</option></select></div>
            <div class="pos-field"><label>Método de pago</label><select id="posFactPago"><option value="efectivo">Efectivo</option><option value="tarjeta">Tarjeta</option><option value="yape_plin">Yape/Plin</option></select></div>
            <div class="pos-field"><label>Cliente</label><input type="text" id="posFactNombre" value="Cliente Mesa"></div>
            <div class="pos-field"><label>Teléfono</label><input type="text" id="posFactTelefono" value="999999999"></div>
            <div class="pos-field"><label>Tipo doc</label><select id="posFactTipoDoc"><option value="dni">DNI</option><option value="ruc">RUC</option></select></div>
            <div class="pos-field"><label>Número doc</label><input type="text" id="posFactNumDoc" value="00000000"></div>
            <div class="pos-field" style="grid-column:1/-1;"><button type="button" class="pos-btn soft" id="btnBuscarDocumentoPos">Buscar cliente por DNI/RUC</button></div>
            <div class="pos-field" style="grid-column:1/-1;"><label>Notas</label><textarea id="posFactNotas" rows="2" placeholder="Observaciones del cobro"></textarea></div>
        </div>
        <div id="posFactInfo" style="font-size:12px;color:#64748b;margin-top:8px;">Si SUNAT no está disponible, el sistema emitirá ticket de venta local y permitirá imprimirlo.</div>
        <div class="pos-actions" style="margin-top:12px;">
            <button type="button" class="pos-btn dark" id="btnConfirmarFacturacion">Completar y facturar</button>
            <button type="button" class="pos-btn soft" id="btnCerrarFacturacion">Cancelar</button>
        </div>
    </div>
</div>

<script>
(() => {
    const API_MESAS = '../api/pos_mesas_estado.php';
    const API_PRECUENTA = '../api/pos_precuenta.php';
    const API_CATALOGO = '../api/pos_catalogo.php';
    const API_COMANDA = '../api/pos_comanda.php';
    const API_FACTURAR = '../api/pos_facturar_mesa.php';
    const API_CONSULTAR_DOCUMENTO = '../api/consultar_documento.php';

    let zonas = [];
    let zonaActivaId = 0;
    let mesaSeleccionada = null;
    let catalogo = [];
    let categoriaActiva = 'all';
    let carrito = [];
    let timerRefresh = null;
    let productoOpcionesActual = null;
    let lastPrecuenta = null;
    const isAndroid = /Android/i.test(navigator.userAgent || '');

    const ui = {
        zonas: document.getElementById('posZonas'),
        board: document.getElementById('posBoard'),
        mesaSelectorView: document.getElementById('mesaSelectorView'),
        platosView: document.getElementById('platosView'),
        platosMesaLabel: document.getElementById('platosMesaLabel'),
        categorias: document.getElementById('posCategorias'),
        chipCaja: document.getElementById('chipCajaTurno'),
        chipMesa: document.getElementById('chipMesaActual'),
        orderDisabled: document.getElementById('orderDisabled'),
        orderPanel: document.getElementById('orderPanel'),
        resumenMesa: document.getElementById('posResumenMesa'),
        filtro: document.getElementById('filtroProducto'),
        catalogo: document.getElementById('catalogoPos'),
        cartBody: document.getElementById('carritoBody'),
        total: document.getElementById('posTotal'),
        msg: document.getElementById('posMsg'),
        modal: document.getElementById('modalPrecuenta'),
        precuentaCont: document.getElementById('precuentaContenido'),
        cart: document.querySelector('.pos-cart'),
        modalOpciones: document.getElementById('modalOpcionesPos'),
        opcionesProductoInfo: document.getElementById('posOpcionesProductoInfo'),
        opcionesGrupos: document.getElementById('posOpcionesGrupos'),
        opcionesTotal: document.getElementById('posOpcionesTotalTexto'),
        modalFacturacion: document.getElementById('modalFacturacionPos'),
        btnIrCarritoAndroid: document.getElementById('btnIrCarritoAndroid'),
    };

    function actualizarBotonCarritoAndroid() {
        if (!ui.btnIrCarritoAndroid) {
            return;
        }

        const hayProductos = carrito.length > 0;
        const panelVisible = ui.orderPanel.style.display !== 'none';
        ui.btnIrCarritoAndroid.classList.toggle('show', isAndroid && hayProductos && panelVisible);
    }

    function mostrarVistaMesas() {
        ui.mesaSelectorView.style.display = '';
        ui.platosView.style.display = 'none';
        actualizarBotonCarritoAndroid();
    }

    function mostrarVistaPlatos() {
        ui.mesaSelectorView.style.display = 'none';
        ui.platosView.style.display = '';
        ui.platosMesaLabel.textContent = mesaSeleccionada
            ? `Mesa activa: ${mesaSeleccionada.nombre}`
            : 'Mesa: -';
        actualizarBotonCarritoAndroid();
    }

    function esc(t) {
        return String(t || '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[ch]));
    }

    function fmt(n) {
        return 'S/ ' + Number(n || 0).toFixed(2);
    }

    function fmtFecha(fecha) {
        const d = fecha ? new Date(fecha.replace(' ', 'T')) : new Date();
        return Number.isNaN(d.getTime()) ? String(fecha || '') : d.toLocaleString('es-PE');
    }

    function showMsg(text, isErr = false) {
        ui.msg.textContent = text;
        ui.msg.className = 'pos-msg ' + (isErr ? 'err' : 'ok');
        if (!isErr) {
            setTimeout(() => {
                ui.msg.textContent = '';
                ui.msg.className = 'pos-msg';
            }, 2800);
        }
    }

    async function getJson(url, opts = {}) {
        const r = await fetch(url, { headers: { Accept: 'application/json' }, ...opts });
        const d = await r.json();
        if (!d.ok) throw new Error(d.mensaje || 'Error de servidor');
        return d;
    }

    function abrirModalFacturacion() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa primero.', true);
            return;
        }
        if (carrito.length > 0) {
            showMsg('Primero envía la comanda o limpia el carrito antes de facturar la mesa.', true);
            return;
        }
        document.getElementById('posFactNombre').value = `Cliente ${mesaSeleccionada.nombre}`;
        document.getElementById('posFactTelefono').value = '999999999';
        document.getElementById('posFactTipoEmision').value = 'ticket';
        document.getElementById('posFactTipoDoc').value = 'dni';
        document.getElementById('posFactNumDoc').value = '00000000';
        document.getElementById('posFactPago').value = 'efectivo';
        document.getElementById('posFactNotas').value = '';
        actualizarFormularioFacturacion();
        ui.modalFacturacion.classList.add('show');
    }

    function cerrarModalFacturacion() {
        ui.modalFacturacion.classList.remove('show');
    }

    function actualizarFormularioFacturacion() {
        const tipoEmision = document.getElementById('posFactTipoEmision').value;
        const selectTipoDoc = document.getElementById('posFactTipoDoc');
        const numDoc = document.getElementById('posFactNumDoc');
        const info = document.getElementById('posFactInfo');
        const btnBuscar = document.getElementById('btnBuscarDocumentoPos');

        if (tipoEmision === 'factura') {
            selectTipoDoc.value = 'ruc';
        }
        selectTipoDoc.disabled = tipoEmision === 'ticket' || tipoEmision === 'factura';
        numDoc.disabled = tipoEmision === 'ticket';
        btnBuscar.disabled = tipoEmision === 'ticket';

        if (tipoEmision === 'ticket') {
            numDoc.value = '00000000';
        }

        btnBuscar.textContent = selectTipoDoc.value === 'ruc'
            ? 'Buscar cliente por RUC'
            : 'Buscar cliente por DNI';

        if (tipoEmision === 'ticket') {
            info.textContent = 'Se imprimirá ticket de venta local al completar el cobro.';
        } else {
            info.textContent = 'Si SUNAT no está disponible, el sistema emitirá ticket de venta local y permitirá imprimirlo.';
        }
    }

    async function consultarDocumentoFacturacionPos() {
        const tipoEmision = document.getElementById('posFactTipoEmision').value;
        if (tipoEmision === 'ticket') {
            showMsg('La búsqueda de documento aplica para boleta o factura.', true);
            return;
        }

        const tipo = document.getElementById('posFactTipoDoc').value;
        const numero = (document.getElementById('posFactNumDoc').value || '').trim();
        const esValido = tipo === 'dni' ? /^\d{8}$/.test(numero) : /^\d{11}$/.test(numero);
        if (!esValido) {
            showMsg(tipo === 'dni' ? 'El DNI debe tener 8 dígitos.' : 'El RUC debe tener 11 dígitos.', true);
            return;
        }

        const btn = document.getElementById('btnBuscarDocumentoPos');
        btn.disabled = true;
        const textoOriginal = btn.textContent;
        btn.textContent = 'Consultando...';

        try {
            const d = await getJson(`${API_CONSULTAR_DOCUMENTO}?tipo=${encodeURIComponent(tipo)}&numero=${encodeURIComponent(numero)}`);
            const datos = d.datos || {};
            if (tipo === 'dni') {
                document.getElementById('posFactNombre').value = datos.nombreCompleto || `${datos.apellidoPaterno || ''} ${datos.apellidoMaterno || ''} ${datos.nombres || ''}`.replace(/\s+/g, ' ').trim();
            } else {
                document.getElementById('posFactNombre').value = datos.razonSocial || datos.nombreComercial || '';
            }
            showMsg('Cliente consultado correctamente.');
        } catch (e) {
            showMsg(e.message, true);
        } finally {
            btn.disabled = tipoEmision === 'ticket';
            btn.textContent = textoOriginal;
        }
    }

    function imprimirDocumentoPos(data, titulo) {
        const ventana = window.open('', '_blank', 'width=420,height=720');
        if (!ventana) {
            showMsg('No se pudo abrir la ventana de impresión.', true);
            return;
        }

        const html = `
            <!doctype html>
            <html lang="es">
            <head>
                <meta charset="utf-8">
                <title>${esc(titulo)}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 16px; color: #111; }
                    h1 { font-size: 18px; margin: 0 0 8px; }
                    .muted { color: #555; font-size: 12px; }
                    .row { display: flex; justify-content: space-between; gap: 8px; font-size: 12px; margin: 4px 0; }
                    .blk { margin: 12px 0; }
                    .item { border-bottom: 1px dashed #ccc; padding: 8px 0; }
                    .item-name { font-weight: 700; font-size: 13px; }
                    .item-sub { font-size: 11px; color: #555; }
                    .total { font-size: 16px; font-weight: 700; margin-top: 14px; display: flex; justify-content: space-between; }
                    ul { margin: 4px 0 0 16px; padding: 0; }
                    li { font-size: 11px; color: #444; }
                </style>
            </head>
            <body>
                <h1>${esc(titulo)}</h1>
                <div class="muted">${esc(fmtFecha(data.fecha || new Date().toISOString()))}</div>
                <div class="blk">
                    <div class="row"><span>Mesa</span><strong>${esc(data.mesa?.nombre || '-')}</strong></div>
                    <div class="row"><span>Zona</span><strong>${esc(data.mesa?.zona || '-')}</strong></div>
                    ${data.numero_comprobante ? `<div class="row"><span>Comprobante</span><strong>${esc(data.numero_comprobante)}</strong></div>` : ''}
                    ${data.cliente_nombre ? `<div class="row"><span>Cliente</span><strong>${esc(data.cliente_nombre)}</strong></div>` : ''}
                    ${data.metodo_pago ? `<div class="row"><span>Pago</span><strong>${esc(data.metodo_pago)}</strong></div>` : ''}
                </div>
                <div class="blk">
                    ${(data.items || []).map((item) => `
                        <div class="item">
                            <div class="row"><span class="item-name">${esc(item.nombre_producto)}</span><strong>${fmt(item.subtotal)}</strong></div>
                            <div class="item-sub">${item.cantidad} x ${fmt(item.precio_unitario)}</div>
                            ${item.opciones && item.opciones.length ? `<ul>${item.opciones.map((op) => `<li>${esc(op.grupo_nombre)}: ${esc(op.opcion_nombre)}${Number(op.precio_extra) > 0 ? ' +' + fmt(op.precio_extra) : ''}</li>`).join('')}</ul>` : ''}
                        </div>
                    `).join('')}
                </div>
                <div class="total"><span>Total</span><span>${fmt(data.total || 0)}</span></div>
                ${data.estado_sunat ? `<div class="muted" style="margin-top:10px;">Estado SUNAT: ${esc(data.estado_sunat)}</div>` : ''}
                <script>window.onload = function(){ window.print(); };<\/script>
            </body>
            </html>`;

        ventana.document.open();
        ventana.document.write(html);
        ventana.document.close();
    }

    function mesaActualTexto() {
        if (!mesaSeleccionada) return 'Mesa: sin seleccionar';
        return `Mesa: ${mesaSeleccionada.nombre} (${mesaSeleccionada.estado})`;
    }

    function renderZonas() {
        ui.zonas.innerHTML = zonas.map((z) =>
            `<button type="button" class="pos-zona-btn${Number(z.id) === Number(zonaActivaId) ? ' activa' : ''}" data-zona="${z.id}">${esc(z.nombre)}</button>`
        ).join('');

        ui.zonas.querySelectorAll('[data-zona]').forEach((btn) => {
            btn.addEventListener('click', () => {
                zonaActivaId = Number(btn.dataset.zona);
                renderZonas();
                renderBoard();
            });
        });
    }

    function renderBoard() {
        const zona = zonas.find((z) => Number(z.id) === Number(zonaActivaId));
        if (!zona) {
            ui.board.innerHTML = '<div style="padding:12px;color:#64748b;">Sin zonas disponibles.</div>';
            return;
        }

        ui.board.style.width = `${Math.max(900, Number(zona.ancho || 900))}px`;
        ui.board.style.height = `${Math.max(540, Number(zona.alto || 540))}px`;

        ui.board.innerHTML = (zona.mesas || []).map((m) => {
            const cls = `pos-mesa ${m.forma === 'redonda' ? 'redonda' : ''} ${m.estado}${mesaSeleccionada && Number(mesaSeleccionada.id) === Number(m.id) ? ' seleccionada' : ''}`;
            return `
                <button type="button" class="${cls}" data-mesa="${m.id}" style="left:${Number(m.pos_x)}px; top:${Number(m.pos_y)}px;">
                    <span class="name">${esc(m.nombre)}</span>
                    <span class="meta">${m.sillas || m.capacidad} sillas</span>
                    <span class="meta">${m.estado === 'ocupada' ? `Ocupada · ${fmt(m.total_activo)}` : 'Libre'}</span>
                </button>
            `;
        }).join('');

        ui.board.querySelectorAll('[data-mesa]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = Number(btn.dataset.mesa);
                const mesa = (zona.mesas || []).find((m) => Number(m.id) === id) || null;
                mesaSeleccionada = mesa;
                ui.chipMesa.textContent = mesaActualTexto();
                ui.orderDisabled.style.display = 'none';
                ui.orderPanel.style.display = '';
                mostrarVistaPlatos();
                renderBoard();
                actualizarPrecuenta();
            });
        });
    }

    function calcTotalCarrito() {
        return carrito.reduce((acc, i) => acc + Number(i.precio) * Number(i.cantidad), 0);
    }

    function obtenerProductoPorId(prodId) {
        for (const categoria of catalogo) {
            const encontrado = (categoria.productos || []).find((producto) => Number(producto.id) === Number(prodId));
            if (encontrado) {
                return encontrado;
            }
        }
        return null;
    }

    function claveOpciones(opciones) {
        if (!Array.isArray(opciones) || !opciones.length) {
            return null;
        }
        return opciones.map((opcion) => Number(opcion.opcion_id)).sort((a, b) => a - b).join('_');
    }

    function renderCarrito() {
        ui.cartBody.innerHTML = carrito.length
            ? carrito.map((i, idx) => `
                <tr>
                    <td>
                        <div class="pos-cart-main">
                            ${i.imagen ? `<img class="pos-cart-thumb" src="${esc(i.imagen)}" alt="${esc(i.nombre)}">` : `<div class="pos-cart-thumb-fallback">IMG</div>`}
                            <div class="pos-cart-info">
                                <div class="pos-cart-name">${esc(i.nombre)}</div>
                                <div class="pos-cart-unit">Unitario: ${fmt(i.precio)}</div>
                                ${i.opciones && i.opciones.length ? `<ul class="pos-cart-options">${i.opciones.map((opcion) => `<li>${esc(opcion.grupo_nombre)}: <strong>${esc(opcion.opcion_nombre)}</strong>${Number(opcion.precio_extra) > 0 ? ` +${fmt(opcion.precio_extra)}` : ''}</li>`).join('')}</ul>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>${i.cantidad}</td>
                    <td>${fmt(i.precio * i.cantidad)}</td>
                    <td><button type="button" class="pos-btn soft" data-del="${idx}">X</button></td>
                </tr>
            `).join('')
            : '<tr><td colspan="4" style="text-align:center;color:#64748b;">Sin productos</td></tr>';

        ui.total.textContent = `Total: ${fmt(calcTotalCarrito())}`;

        ui.cartBody.querySelectorAll('[data-del]').forEach((btn) => {
            btn.addEventListener('click', () => {
                carrito.splice(Number(btn.dataset.del), 1);
                renderCarrito();
                renderCatalogo();
            });
        });

        actualizarBotonCarritoAndroid();
    }

    function rebotarCesta() {
        if (!ui.cart) {
            return;
        }
        ui.cart.classList.remove('rebote');
        void ui.cart.offsetWidth;
        ui.cart.classList.add('rebote');
    }

    function volarAlCarritoPos(imgEl) {
        if (!imgEl || !ui.cart) {
            rebotarCesta();
            return;
        }

        const origenRect = imgEl.getBoundingClientRect();
        const destinoRect = ui.cart.getBoundingClientRect();
        if (!origenRect.width || !destinoRect.width) {
            rebotarCesta();
            return;
        }

        const clone = document.createElement('img');
        clone.className = 'pos-fly';
        clone.src = imgEl.currentSrc || imgEl.src;
        clone.style.left = origenRect.left + 'px';
        clone.style.top = origenRect.top + 'px';
        clone.style.width = origenRect.width + 'px';
        clone.style.height = origenRect.height + 'px';
        document.body.appendChild(clone);

        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                clone.style.left = (destinoRect.left + destinoRect.width / 2 - 18) + 'px';
                clone.style.top = (destinoRect.top + 14) + 'px';
                clone.style.width = '36px';
                clone.style.height = '36px';
                clone.style.opacity = '0.22';
                clone.style.transform = 'scale(.72)';
            });
        });

        clone.addEventListener('transitionend', () => {
            clone.remove();
            rebotarCesta();
        }, { once: true });
    }

    function cerrarModalOpcionesPos() {
        productoOpcionesActual = null;
        ui.modalOpciones.classList.remove('show');
    }

    function actualizarTotalOpcionesPos() {
        if (!productoOpcionesActual) {
            ui.opcionesTotal.textContent = 'S/ 0.00';
            return;
        }

        let extra = 0;
        ui.opcionesGrupos.querySelectorAll('input:checked').forEach((input) => {
            extra += Number(input.dataset.precioExtra || 0);
        });
        ui.opcionesTotal.textContent = fmt(Number(productoOpcionesActual.precio) + extra);
    }

    function abrirModalOpcionesPos(prod, imgEl) {
        productoOpcionesActual = { producto: prod, imgEl };
        ui.opcionesProductoInfo.innerHTML = `
            ${prod.imagen ? `<img src="${esc(prod.imagen)}" alt="${esc(prod.nombre)}">` : '<div class="pos-cart-thumb-fallback">IMG</div>'}
            <div>
                <div class="pos-opciones-nombre">${esc(prod.nombre)}</div>
                <div class="pos-opciones-precio">${fmt(prod.precio)}</div>
            </div>
        `;

        ui.opcionesGrupos.innerHTML = (prod.grupos_opciones || []).map((grupo) => `
            <div class="pos-opciones-grupo" data-grupo-id="${grupo.id}" data-tipo="${esc(grupo.tipo)}" data-requerido="${grupo.requerido ? '1' : '0'}" data-max="${Number(grupo.max || 1)}">
                <div class="pos-opciones-titulo">
                    ${esc(grupo.nombre)}
                    <span class="pos-opciones-badge">${grupo.tipo === 'checkbox' ? 'Varios' : 'Uno'}</span>
                    ${grupo.requerido ? '<span class="pos-opciones-badge">Obligatorio</span>' : ''}
                </div>
                ${(grupo.opciones || []).map((opcion) => `
                    <label class="pos-opciones-label">
                        <span>
                            <input type="${grupo.tipo === 'checkbox' ? 'checkbox' : 'radio'}" name="grupo_${grupo.id}" value="${opcion.id}" data-precio-extra="${Number(opcion.precio_extra)}" data-opcion-nombre="${esc(opcion.nombre)}" data-grupo-nombre="${esc(grupo.nombre)}" data-grupo-id="${grupo.id}">
                            ${esc(opcion.nombre)}
                        </span>
                        <strong>${Number(opcion.precio_extra) > 0 ? '+' + fmt(opcion.precio_extra) : 'Gratis'}</strong>
                    </label>
                `).join('')}
            </div>
        `).join('');

        ui.opcionesGrupos.querySelectorAll('.pos-opciones-label').forEach((label) => {
            const input = label.querySelector('input');
            input.addEventListener('change', () => {
                const wrapper = label.closest('.pos-opciones-grupo');
                if (wrapper && wrapper.dataset.tipo === 'radio') {
                    wrapper.querySelectorAll('.pos-opciones-label').forEach((item) => item.classList.remove('seleccionada'));
                }
                label.classList.toggle('seleccionada', input.checked);
                actualizarTotalOpcionesPos();
            });
        });

        actualizarTotalOpcionesPos();
        ui.modalOpciones.classList.add('show');
    }

    function confirmarOpcionesPos() {
        if (!productoOpcionesActual) {
            return;
        }

        const grupos = ui.opcionesGrupos.querySelectorAll('.pos-opciones-grupo');
        let valido = true;
        const opcionesSeleccionadas = [];

        grupos.forEach((grupo) => {
            const requerido = grupo.dataset.requerido === '1';
            const tipo = grupo.dataset.tipo || 'radio';
            const max = Math.max(1, Number(grupo.dataset.max || 1));
            const marcados = grupo.querySelectorAll('input:checked');
            grupo.style.outline = '';

            if (requerido && marcados.length === 0) {
                valido = false;
                grupo.style.outline = '2px solid #e11d48';
                grupo.style.borderRadius = '14px';
            }
            if (tipo === 'checkbox' && marcados.length > max) {
                valido = false;
                grupo.style.outline = '2px solid #e11d48';
                grupo.style.borderRadius = '14px';
            }

            marcados.forEach((input) => {
                opcionesSeleccionadas.push({
                    grupo_id: Number(input.dataset.grupoId || 0),
                    grupo_nombre: input.dataset.grupoNombre || '',
                    opcion_id: Number(input.value || 0),
                    opcion_nombre: input.dataset.opcionNombre || '',
                    precio_extra: Number(input.dataset.precioExtra || 0),
                });
            });
        });

        if (!valido) {
            return;
        }

        addProducto(productoOpcionesActual.producto, opcionesSeleccionadas, productoOpcionesActual.imgEl);
        cerrarModalOpcionesPos();
    }

    function addProducto(prod, opcionesSeleccionadas = [], imgEl = null) {
        const precioBase = Number(prod.precio);
        const extraTotal = (opcionesSeleccionadas || []).reduce((acc, opcion) => acc + Number(opcion.precio_extra || 0), 0);
        const precioFinal = precioBase + extraTotal;
        const keyOpciones = claveOpciones(opcionesSeleccionadas);
        let item = null;

        if (keyOpciones) {
            item = carrito.find((carritoItem) => Number(carritoItem.id) === Number(prod.id) && carritoItem.key === keyOpciones);
        } else {
            item = carrito.find((carritoItem) => Number(carritoItem.id) === Number(prod.id) && !carritoItem.key);
        }

        if (item) {
            item.cantidad += 1;
        } else {
            carrito.push({
                id: prod.id,
                key: keyOpciones,
                nombre: prod.nombre,
                precio: precioFinal,
                precioBase,
                imagen: prod.imagen || null,
                opciones: opcionesSeleccionadas,
                cantidad: 1,
            });
        }
        renderCarrito();
        renderCatalogo();
        volarAlCarritoPos(imgEl);
    }

    function quitarProducto(prodId) {
        const idx = carrito.findIndex((i) => Number(i.id) === Number(prodId));
        if (idx < 0) {
            return;
        }
        carrito[idx].cantidad -= 1;
        if (carrito[idx].cantidad <= 0) {
            carrito.splice(idx, 1);
        }
        renderCarrito();
        renderCatalogo();
    }

    function cantidadEnCarrito(prodId) {
        return carrito
            .filter((i) => Number(i.id) === Number(prodId))
            .reduce((acc, i) => acc + Number(i.cantidad), 0);
    }

    function renderCategorias() {
        const items = [{ id: 'all', nombre: 'Todas', imagen: null }, ...catalogo.map((c) => ({ id: String(c.id), nombre: c.nombre, imagen: c.imagen || null }))];

        if (!items.some((i) => i.id === categoriaActiva)) {
            categoriaActiva = 'all';
        }

        ui.categorias.innerHTML = items.map((i) => `
            <button type="button" class="pos-categoria-btn${i.id === categoriaActiva ? ' activa' : ''}" data-categoria="${esc(i.id)}">
                <span class="pos-categoria-img-wrap">
                    ${i.imagen ? `<img src="${esc(i.imagen)}" alt="${esc(i.nombre)}">` : `<span class="pos-cat-fallback">${esc((i.nombre || 'C').slice(0, 1).toUpperCase())}</span>`}
                </span>
                <span class="pos-categoria-texto">${esc(i.nombre)}</span>
            </button>
        `).join('');

        ui.categorias.querySelectorAll('[data-categoria]').forEach((btn) => {
            btn.addEventListener('click', () => {
                categoriaActiva = String(btn.dataset.categoria || 'all');
                renderCategorias();
                renderCatalogo();
            });
        });
    }

    function renderCatalogo() {
        const term = (ui.filtro.value || '').toLowerCase().trim();

        const productos = [];
        catalogo.forEach((cat) => {
            if (categoriaActiva !== 'all' && String(cat.id) !== categoriaActiva) {
                return;
            }
            (cat.productos || []).forEach((p) => {
                if (term && !p.nombre.toLowerCase().includes(term) && !(p.descripcion || '').toLowerCase().includes(term)) {
                    return;
                }
                productos.push(p);
            });
        });

        ui.catalogo.innerHTML = productos.length
            ? `<div class="pos-prod-grid">${productos.map((p) => `
<div class="pos-prod" data-prod-card="${p.id}">                    ${p.tiene_opciones ? '<span class="pos-prod-badge">Extras</span>' : ''}
                    ${p.imagen ? `<img class="pos-prod-img" src="${esc(p.imagen)}" alt="${esc(p.nombre)}">` : `<div class="pos-prod-fallback">Sin imagen</div>`}
                    <strong>${esc(p.nombre)}</strong>
                    <small>${esc(p.descripcion || '')}</small>
                    <div class="pos-prod-footer">
                        <span>${fmt(p.precio)}</span>
                        <div class="pos-stepper">
                            <button type="button" class="pos-stepper-btn" data-minus="${p.id}">-</button>
                            <span class="pos-stepper-val">${cantidadEnCarrito(p.id)}</span>
                            <button type="button" class="pos-stepper-btn" data-plus="${p.id}">+</button>
                        </div>
                    </div>
                </div>
            `).join('')}</div>`
            : '<div style="font-size:12px;color:#64748b;">No hay productos para mostrar.</div>';

ui.catalogo.querySelectorAll('[data-plus]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = Number(btn.dataset.plus);
                const producto = obtenerProductoPorId(id);
                if (!producto) {
                    return;
                }

                const card = btn.closest('.pos-prod');
                const imgEl = card ? card.querySelector('.pos-prod-img') : null;
                if (producto.tiene_opciones && Array.isArray(producto.grupos_opciones) && producto.grupos_opciones.length) {
                    abrirModalOpcionesPos(producto, imgEl);
                    return;
                }

                addProducto(producto, [], imgEl);
            });
        });

        ui.catalogo.querySelectorAll('[data-minus]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = Number(btn.dataset.minus);
                quitarProducto(id);
            });
        });

        ui.catalogo.querySelectorAll('[data-prod-card]').forEach((card) => {
            card.addEventListener('click', () => {
                const btnPlus = card.querySelector('[data-plus]');
                if (btnPlus) btnPlus.click();
            });
        });
    }

    async function cargarCatalogo() {
        const d = await getJson(API_CATALOGO);
        catalogo = Array.isArray(d.categorias) ? d.categorias : [];
        renderCategorias();
        renderCatalogo();
    }

    async function cargarMesasEstado() {
        const d = await getJson(API_MESAS);
        zonas = Array.isArray(d.zonas) ? d.zonas : [];
        ui.chipCaja.textContent = `Caja activa · Turno #${d.caja.turno_id}`;

        if (!zonas.length) {
            ui.zonas.innerHTML = '';
            ui.board.innerHTML = '<div style="padding:12px;color:#64748b;">No hay zonas/mesas configuradas.</div>';
            mostrarVistaMesas();
            return;
        }

        if (!zonas.some((z) => Number(z.id) === Number(zonaActivaId))) {
            zonaActivaId = Number(zonas[0].id);
        }

        if (mesaSeleccionada) {
            let found = null;
            for (const z of zonas) {
                const m = (z.mesas || []).find((x) => Number(x.id) === Number(mesaSeleccionada.id));
                if (m) {
                    found = m;
                    zonaActivaId = Number(z.id);
                    break;
                }
            }
            mesaSeleccionada = found;
            ui.chipMesa.textContent = mesaActualTexto();
            if (!mesaSeleccionada) {
                ui.orderDisabled.style.display = '';
                ui.orderPanel.style.display = 'none';
                mostrarVistaMesas();
                actualizarBotonCarritoAndroid();
            }
        }

        renderZonas();
        renderBoard();
    }

    async function actualizarPrecuenta() {
        if (!mesaSeleccionada) {
            ui.resumenMesa.innerHTML = '<strong>Selecciona una mesa.</strong>';
            lastPrecuenta = null;
            return;
        }

        try {
            const d = await getJson(`${API_PRECUENTA}?mesa_id=${Number(mesaSeleccionada.id)}`);
            lastPrecuenta = d;
            const pedidosActivos = d.pedidos_activos || [];
            const lineas = d.resumen_lineas || [];

            ui.resumenMesa.innerHTML = `
                <div><strong>${esc(d.mesa.nombre)}</strong> · Pedidos activos: <strong>${pedidosActivos.length}</strong></div>
                <div>Total consumido (precuenta): <strong>${fmt(d.total_precuenta)}</strong></div>
                <div class="pos-resumen-list">
                    ${lineas.length ? lineas.map((l) => `<div class="pos-resumen-item"><span>${esc(l.nombre_producto)} x${l.cantidad}</span><span>${fmt(l.subtotal)}</span></div>`).join('') : '<div style="font-size:12px;color:#64748b;">Sin consumos activos en la mesa.</div>'}
                </div>
            `;
        } catch (e) {
            lastPrecuenta = null;
            ui.resumenMesa.innerHTML = `<strong style="color:#b91c1c;">${esc(e.message)}</strong>`;
        }
    }

    async function mostrarModalPrecuenta() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa primero.', true);
            return;
        }

        try {
            const d = await getJson(`${API_PRECUENTA}?mesa_id=${Number(mesaSeleccionada.id)}`);
            const pedidosActivos = d.pedidos_activos || [];
            const lineas = d.resumen_lineas || [];

            ui.precuentaCont.innerHTML = `
                <div style="font-size:13px;color:#334155;margin-bottom:8px;">Mesa: <strong>${esc(d.mesa.nombre)}</strong></div>
                <div style="font-size:13px;color:#334155;margin-bottom:8px;">Pedidos activos: <strong>${pedidosActivos.length}</strong></div>
                <div style="font-size:14px;color:#0f172a;margin-bottom:8px;">Total: <strong>${fmt(d.total_precuenta)}</strong></div>
                <div style="border-top:1px dashed #cbd5e1;padding-top:8px;">
                    ${lineas.length ? lineas.map((l) => `<div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;"><span>${esc(l.nombre_producto)} x${l.cantidad}</span><span>${fmt(l.subtotal)}</span></div>`).join('') : '<div style="font-size:12px;color:#64748b;">Sin consumos activos.</div>'}
                </div>
            `;
            ui.modal.classList.add('show');
        } catch (e) {
            showMsg(e.message, true);
        }
    }

    async function enviarComanda() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa.', true);
            return;
        }
        if (!carrito.length) {
            showMsg('Agrega productos al carrito antes de enviar a cocina.', true);
            return;
        }

        const btn = document.getElementById('btnEnviarComanda');
        btn.disabled = true;
        btn.textContent = 'Enviando comanda...';

        try {
            const d = await getJson(API_COMANDA, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    mesa_id: Number(mesaSeleccionada.id),
                    notas: 'Comanda POS',
                    items: carrito.map((i) => ({
                        id: i.id,
                        cantidad: i.cantidad,
                        opciones: Array.isArray(i.opciones) ? i.opciones : [],
                    })),
                }),
            });

            carrito = [];
            renderCarrito();
            renderCatalogo();
            showMsg(d.mensaje || 'Comanda enviada a cocina.');
            await cargarMesasEstado();
            await actualizarPrecuenta();
        } catch (e) {
            showMsg(e.message, true);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Enviar comanda a cocina';
        }
    }

    async function imprimirPrecuenta() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa primero.', true);
            return;
        }

        try {
            const d = await getJson(`${API_PRECUENTA}?mesa_id=${Number(mesaSeleccionada.id)}`);
            lastPrecuenta = d;
            imprimirDocumentoPos({
                mesa: d.mesa,
                items: d.resumen_lineas || [],
                total: d.total_precuenta || 0,
                fecha: new Date().toISOString(),
            }, 'Precuenta de mesa');
        } catch (e) {
            showMsg(e.message, true);
        }
    }

    async function facturarMesaFinal() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa.', true);
            return;
        }
        if (carrito.length > 0) {
            showMsg('Primero envía la comanda actual o limpia el carrito.', true);
            return;
        }

        const tipoEmision = document.getElementById('posFactTipoEmision').value;
        const tipoDocumento = document.getElementById('posFactTipoDoc').value;
        const numeroDocumento = (document.getElementById('posFactNumDoc').value || '').replace(/\D/g, '');

        if (tipoEmision === 'boleta') {
            const validoBoleta = tipoDocumento === 'dni' ? /^\d{8}$/.test(numeroDocumento) : /^\d{11}$/.test(numeroDocumento);
            if (!validoBoleta) {
                showMsg('Documento inválido para boleta.', true);
                return;
            }
        }
        if (tipoEmision === 'factura' && !/^\d{11}$/.test(numeroDocumento)) {
            showMsg('Para factura debes ingresar un RUC válido de 11 dígitos.', true);
            return;
        }

        const btn = document.getElementById('btnConfirmarFacturacion');
        btn.disabled = true;
        btn.textContent = 'Procesando...';

        try {
            const d = await getJson(API_FACTURAR, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    mesa_id: Number(mesaSeleccionada.id),
                    tipo_emision: tipoEmision,
                    cliente_nombre: document.getElementById('posFactNombre').value.trim() || 'Cliente POS',
                    cliente_telefono: document.getElementById('posFactTelefono').value.trim() || '999999999',
                    tipo_documento: tipoDocumento,
                    numero_documento: numeroDocumento,
                    metodo_pago: document.getElementById('posFactPago').value,
                    notas: document.getElementById('posFactNotas').value.trim(),
                }),
            });

            cerrarModalFacturacion();
            await cargarMesasEstado();
            await actualizarPrecuenta();
            const tituloImpresion = d.tipo_emitido === 'factura'
                ? `Factura ${d.comprobante?.numero_comprobante || ''}`.trim()
                : d.tipo_emitido === 'boleta'
                    ? `Boleta ${d.comprobante?.numero_comprobante || ''}`.trim()
                    : 'Ticket de venta';

            if (d.nubefact_error) {
                // Mostrar el error exacto de Nubefact para que el admin pueda corregir la config
                alert('⚠️ ERROR NUBEFACT — No se pudo emitir el comprobante:\n\n' + d.nubefact_error + '\n\nSe imprimió el ticket de venta local. Revisa la configuración de NubeFacT en el panel de administración.');
                imprimirDocumentoPos(d.print || {}, 'Ticket de venta');
            } else if (d.pdf_url && d.tipo_emitido !== 'ticket') {
                showMsg(d.mensaje || 'Mesa facturada correctamente.');
                const win = window.open(d.pdf_url, '_blank');
                if (win) {
                    try { win.focus(); } catch (_) {}
                }
            } else {
                showMsg(d.mensaje || 'Mesa facturada correctamente.');
                imprimirDocumentoPos(d.print || {}, tituloImpresion);
            }
        } catch (e) {
            showMsg(e.message, true);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Completar y facturar';
        }
    }

    function iniciarRefresh() {
        if (timerRefresh) {
            clearInterval(timerRefresh);
        }
        timerRefresh = setInterval(async () => {
            try {
                await cargarMesasEstado();
                if (mesaSeleccionada) {
                    await actualizarPrecuenta();
                }
            } catch (_) {
                // Silencioso para no interrumpir la operación.
            }
        }, 12000);
    }

    document.getElementById('btnActualizarPrecuenta').addEventListener('click', () => {
        actualizarPrecuenta();
    });

    document.getElementById('btnImprimirPrecuenta').addEventListener('click', () => {
        imprimirPrecuenta();
    });

    document.getElementById('btnCerrarPrecuenta').addEventListener('click', () => {
        ui.modal.classList.remove('show');
    });

    ui.modal.addEventListener('click', (e) => {
        if (e.target === ui.modal) {
            ui.modal.classList.remove('show');
        }
    });

    ui.modalOpciones.addEventListener('click', (e) => {
        if (e.target === ui.modalOpciones) {
            cerrarModalOpcionesPos();
        }
    });

    document.getElementById('btnLimpiarCarrito').addEventListener('click', () => {
        carrito = [];
        renderCarrito();
        renderCatalogo();
    });

    document.getElementById('btnEnviarComanda').addEventListener('click', () => {
        enviarComanda();
    });

    document.getElementById('btnAbrirFacturacion').addEventListener('click', () => {
        abrirModalFacturacion();
    });

    document.getElementById('btnCerrarFacturacion').addEventListener('click', () => {
        cerrarModalFacturacion();
    });

    document.getElementById('btnConfirmarFacturacion').addEventListener('click', () => {
        facturarMesaFinal();
    });

    document.getElementById('posFactTipoEmision').addEventListener('change', () => {
        actualizarFormularioFacturacion();
    });

    document.getElementById('posFactTipoDoc').addEventListener('change', () => {
        actualizarFormularioFacturacion();
    });

    document.getElementById('btnBuscarDocumentoPos').addEventListener('click', () => {
        consultarDocumentoFacturacionPos();
    });

    document.getElementById('posFactNumDoc').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            consultarDocumentoFacturacionPos();
        }
    });

    document.getElementById('btnConfirmarOpcionesPos').addEventListener('click', () => {
        confirmarOpcionesPos();
    });

    document.getElementById('btnCerrarOpcionesPos').addEventListener('click', () => {
        cerrarModalOpcionesPos();
    });

    document.getElementById('btnVolverMesas').addEventListener('click', () => {
        mesaSeleccionada = null;
        ui.chipMesa.textContent = mesaActualTexto();
        ui.orderDisabled.style.display = '';
        ui.orderPanel.style.display = 'none';
        mostrarVistaMesas();
        renderBoard();
        actualizarBotonCarritoAndroid();
    });

    ui.btnIrCarritoAndroid.addEventListener('click', () => {
        if (ui.cart) {
            ui.cart.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });

    ui.modalFacturacion.addEventListener('click', (e) => {
        if (e.target === ui.modalFacturacion) {
            cerrarModalFacturacion();
        }
    });

    ui.filtro.addEventListener('input', () => {
        renderCatalogo();
    });

    (async () => {
        try {
            await Promise.all([cargarMesasEstado(), cargarCatalogo()]);
            renderCarrito();
            actualizarBotonCarritoAndroid();
            iniciarRefresh();
        } catch (e) {
            showMsg(e.message, true);
        }
    })();
})();
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
