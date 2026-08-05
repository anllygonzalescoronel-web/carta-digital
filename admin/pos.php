<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirRol(['admin', 'mesero']);

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

.pos-top-status {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
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

.pos-leyenda {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 8px 0 10px;
}

.pos-leyenda-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 11px;
    font-weight: 700;
    color: var(--pos-texto);
    background: var(--pos-bg);
    box-shadow: inset 2px 2px 6px var(--pos-sombra-oscura), inset -2px -2px 6px var(--pos-sombra-clara);
}

.pos-leyenda-dot {
    width: 10px;
    height: 10px;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, 0.2);
    flex: 0 0 auto;
}

.pos-leyenda-dot.libre { background: #198754; }
.pos-leyenda-dot.ocupada { background: #dc3545; }
.pos-leyenda-dot.proceso_pago { background: #f39c12; }

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

@media (max-width: 860px) {
    .pos-board {
        min-width: 760px;
        min-height: 500px;
    }
}

@media (max-width: 560px) {
    .pos-board {
        min-width: 640px;
        min-height: 460px;
    }
}

.pos-zona-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 20;
}

.pos-mesa {
    position: absolute;
    width: 130px;
    height: 76px;
    border-radius: 16px;
    border: none;
    background: transparent;
    padding: 0;
    text-align: center;
    cursor: pointer;
    box-shadow: none;
    transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
    overflow: visible;
}

.pos-mesa.redonda { border-radius: 999px; }

.pos-mesa:hover { transform: translateY(-2px); }

.pos-mesa-tablero {
    position: absolute;
    inset: 0;
    border-radius: 8px;
    border: 2px solid #334155;
    background:
      repeating-linear-gradient(0deg, rgba(71, 85, 105, 0.2) 0 1px, rgba(255,255,255,0.94) 1px 6px),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
    z-index: 1;
}

.pos-mesa.redonda .pos-mesa-tablero { border-radius: 999px; }

.pos-mesa-content {
    position: relative;
    z-index: 4;
    pointer-events: none;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    flex-direction: column;
    padding: 4px 6px;
    width: 100%;
    overflow: hidden;
    box-sizing: border-box;
}

.pos-mesa-sillas {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 3;
}

.pos-mesa-silla {
    position: absolute;
    width: 14px;
    height: 8px;
    border-radius: 4px;
    border: 2px solid #334155;
    background: #ffffff;
    transform-origin: center center;
}

.pos-mesa-silla::after {
    content: '';
    position: absolute;
    width: 11px;
    height: 8px;
    left: 50%;
    top: -9px;
    transform: translateX(-50%);
    border-radius: 8px 8px 0 0;
    border: 2px solid #334155;
    border-bottom: none;
    background: transparent;
}

.pos-mesa.libre {
    filter: drop-shadow(0 0 0 rgba(46, 204, 113, 0));
}

.pos-mesa.libre .pos-mesa-tablero {
    border-color: #0d9c58;
    background:
      repeating-linear-gradient(0deg, rgba(13, 156, 88, 0.25) 0 1px, rgba(255,255,255,0.94) 1px 6px),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.pos-mesa.ocupada {
    filter: drop-shadow(0 0 0 rgba(242, 201, 76, 0));
}

.pos-mesa.ocupada .pos-mesa-tablero {
    border-color: #c0202f;
    background:
      repeating-linear-gradient(0deg, rgba(192, 32, 47, 0.28) 0 1px, rgba(255,255,255,0.94) 1px 6px),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.pos-mesa.proceso_pago {
    filter: drop-shadow(0 0 0 rgba(242, 201, 76, 0));
}

.pos-mesa.proceso_pago .pos-mesa-tablero {
    border-color: #d4820a;
    background:
      repeating-linear-gradient(0deg, rgba(212, 130, 10, 0.26) 0 1px, rgba(255,255,255,0.94) 1px 6px),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.pos-mesa.unida .pos-mesa-tablero {
    border-color: #7c3aed;
    border-width: 3px;
    background:
      repeating-linear-gradient(0deg, rgba(124, 58, 237, 0.18) 0 1px, rgba(255,255,255,0.94) 1px 6px),
      linear-gradient(180deg, #faf5ff 0%, #f3e8ff 100%);
}

body.modo-oscuro .pos-mesa.unida .pos-mesa-tablero {
    border-color: #a78bfa;
    background:
      repeating-linear-gradient(0deg, rgba(167, 139, 250, 0.18) 0 1px, rgba(30,20,50,0.94) 1px 6px),
      linear-gradient(180deg, #1e1432 0%, #16102a 100%);
}

.pos-mesa.seleccionada {
    box-shadow: 0 0 0 2px var(--pos-primary), 0 6px 14px rgba(15,23,42,.2);
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
    font-size: 12px;
    color: #0f172a;
    font-weight: 700;
    margin-top: 3px;
}

.pos-mesa-estado {
    display: block;
    width: 100%;
    text-align: center;
    margin-top: 4px;
    padding: 0;
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .3px;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    box-sizing: border-box;
    text-shadow: 0 0 4px rgba(0,0,0,0.15);
}

.pos-mesa-estado.libre {
    color: #0d6e3f;
}

.pos-mesa-estado.ocupada {
    color: #a3182a;
}

.pos-mesa-estado.proceso_pago {
    color: #a0620a;
}

.pos-mesa-decoracion {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transform-origin: center center;
    pointer-events: none;
}

.pos-mesa-decoracion img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.pos-mesa-decoracion .txt {
    font-weight: 800;
    text-align: center;
    line-height: 1.1;
}

.pos-mesa-decoracion .ico {
    font-size: inherit;
    line-height: 1;
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
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 13px;
    line-height: 1.3;
    font-weight: 700;
    cursor: pointer;
    transition: transform .15s ease, box-shadow .15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
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

.pos-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.pos-actions.stack { flex-direction: column; gap: 8px; }
.pos-btn.full {
    width: 100%;
    justify-content: center;
    min-height: 38px;
    padding: 8px 12px;
}

.pos-actions .pos-btn {
    flex: 1 1 180px;

}
.pos-actions.stack .pos-btn {
    flex: 0 0 auto !important;
    width: 100%;
}

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

.pos-union-help {
    font-size: 13px;
    color: var(--pos-muted);
    margin-bottom: 10px;
}

.pos-union-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
}

.pos-union-meta {
    font-size: 12px;
    color: var(--pos-muted);
    margin-top: 6px;
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

body.modo-oscuro .pos-leyenda-item {
    color: #e2e8f0;
}

body.modo-oscuro .pos-leyenda-dot {
    border-color: rgba(255, 255, 255, 0.28);
}

body.modo-oscuro .pos-mesa .meta {
    color: #f8fafc;
}

body.modo-oscuro .pos-mesa-estado.libre,
body.modo-oscuro .pos-mesa-estado.ocupada {
    color: #ffffff;
}

body.modo-oscuro .pos-mesa-estado.proceso_pago {
    color: #111827;
}

.pos-mesa-union-badge {
    display: block;
    width: 100%;
    text-align: center;
    font-size: 8px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .3px;
    color: #6d28d9;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

body.modo-oscuro .pos-mesa-union-badge {
    color: #c4b5fd;
}

@media (max-width: 860px) {
    .pos-top {
        flex-direction: column;
        align-items: flex-start;
    }

    .pos-top-status {
        width: 100%;
        justify-content: flex-start;
    }

    .pos-card {
        padding: 14px;
    }

    .pos-products {
        max-height: 380px;
    }

    .pos-cart {
        overflow-x: auto;
    }

    .pos-cart table {
        min-width: 540px;
    }
}

@media (max-width: 600px) {
    .pos-shell {
        gap: 12px;
    }

    .pos-card {
        border-radius: 16px;
        padding: 12px;
    }

    .pos-zonas {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .pos-zona-btn {
        width: 100%;
        text-align: center;
        justify-content: center;
    }

    .pos-categorias {
        gap: 10px;
    }

    .pos-categoria-btn {
        width: 64px;
    }

    .pos-categoria-img-wrap {
        width: 50px;
        height: 50px;
    }

    .pos-opciones-producto {
        flex-direction: column;
        align-items: flex-start;
    }

    .pos-opciones-producto img {
        width: 64px;
        height: 64px;
    }

    .pos-actions .pos-btn {
        width: 100%;
        flex: 1 1 100%;
    }
}
</style>

<div class="pos-shell">
    <div class="pos-top">
        <h2><i class="ti ti-device-desktop"></i> POS Restaurante</h2>
        <div class="pos-top-status">
            <span class="pos-chip" id="chipCajaTurno">Caja activa</span>
            <span class="pos-chip" id="chipMesaActual">Mesa: sin seleccionar</span>
        </div>
    </div>

    <div class="pos-grid">
        <section class="pos-card">
            <div id="mesaSelectorView">
                <h3><i class="ti ti-layout-grid"></i> Mapa de mesas</h3>
                <div class="pos-leyenda" aria-label="Leyenda de estados de mesa">
                    <span class="pos-leyenda-item"><span class="pos-leyenda-dot libre"></span> Libre</span>
                    <span class="pos-leyenda-item"><span class="pos-leyenda-dot ocupada"></span> Ocupada</span>
                    <span class="pos-leyenda-item"><span class="pos-leyenda-dot proceso_pago"></span> En proceso de pago</span>
                </div>
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
                    <button type="button" class="pos-btn soft full" id="btnUnirMesa">Unir con otra mesa</button>
                    <button type="button" class="pos-btn soft full" id="btnLiberarMesa">Liberar mesa</button>
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

<div class="pos-modal" id="modalUnirMesaPos">
    <div class="pos-modal-box">
        <h4><i class="ti ti-arrows-join-2"></i> Unir mesas</h4>
        <div class="pos-union-help" id="unionMesaOrigenTexto"></div>
        <div class="pos-union-grid">
            <div class="pos-field">
                <label>Mesa destino</label>
                <select id="posUnionMesaDestino"></select>
                <div class="pos-union-meta" id="posUnionMesaMeta"></div>
            </div>
        </div>
        <div class="pos-actions" style="margin-top:12px;">
            <button type="button" class="pos-btn dark" id="btnConfirmarUnionMesa">Confirmar unión</button>
            <button type="button" class="pos-btn soft" id="btnCerrarUnionMesa">Cancelar</button>
        </div>
    </div>
</div>


<div class="pos-modal" id="modalConfirmarLiberarMesa">
    <div class="pos-modal-box" style="max-width:420px;text-align:center;">
        <div style="width:56px;height:56px;margin:0 auto 14px;border-radius:50%;background:rgba(220,53,69,.12);color:#dc3545;font-size:26px;display:flex;align-items:center;justify-content:center;">
            <i class="ti ti-alert-triangle"></i>
        </div>
        <h4 style="justify-content:center;">¿Liberar esta mesa?</h4>
        <p id="textoConfirmarLiberarMesa" style="color:var(--pos-muted);font-size:13.5px;margin-bottom:20px;"></p>
        <div class="pos-actions" style="justify-content:center;">
            <button type="button" class="pos-btn soft" id="btnCancelarLiberarMesa">Cancelar</button>
            <button type="button" class="pos-btn" id="btnConfirmarLiberarMesaModal" style="background:linear-gradient(135deg,#ff5c6c,#dc3545);color:#fff;box-shadow:4px 4px 10px rgba(220,53,69,.35);">
                <i class="ti ti-lock-open"></i> Sí, liberar
            </button>
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
    const API_UNIR_MESAS = '../api/pos_unir_mesas.php';
    const API_LIBERAR_MESA = '../api/pos_liberar_mesa.php';
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
    let candidatas = [];
    let mesasCandidatasUnion = [];
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
        modalUnionMesa: document.getElementById('modalUnirMesaPos'),
        unionMesaOrigenTexto: document.getElementById('unionMesaOrigenTexto'),
        selectUnionMesaDestino: document.getElementById('posUnionMesaDestino'),
        unionMesaMeta: document.getElementById('posUnionMesaMeta'),
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
        return `Mesa: ${mesaSeleccionada.nombre} (${estadoMesaLabel(mesaSeleccionada.estado)})`;
    }

    function estadoMesaLabel(estado) {
        const key = String(estado || '').toLowerCase();
        if (key === 'proceso_pago') return 'En proceso de pago';
        if (key === 'ocupada') return 'Ocupada';
        return 'Libre';
    }

    function normalizarRutaImagen(value) {
        const ruta = String(value || '').trim();
        if (!ruta) return '';
        if (/^(https?:)?\/\//i.test(ruta) || ruta.startsWith('data:')) return ruta;
        return '/' + ruta.replace(/^\.\//, '').replace(/^\/+/, '');
    }

    function sanitizeIconClass(value) {
        return String(value || '').replace(/[^a-zA-Z0-9\-\s]/g, '').trim() || 'ti ti-star';
    }

    function parseDecoraciones(value) {
        if (Array.isArray(value)) return value;
        if (typeof value === 'string' && value.trim() !== '') {
            try {
                const parsed = JSON.parse(value);
                return Array.isArray(parsed) ? parsed : [];
            } catch (_) {
                return [];
            }
        }
        return [];
    }

    function normalizarElementoDecoracion(elemento) {
        return {
            id: String(elemento?.id || ''),
            tipo: ['texto', 'icono', 'imagen'].includes(elemento?.tipo) ? elemento.tipo : 'texto',
            contenido: String(elemento?.contenido ?? ''),
            color: String(elemento?.color || '#0f172a'),
            fondo: String(elemento?.fondo || 'transparent'),
            fuente: Math.max(10, Math.min(48, Number(elemento?.fuente || 18))),
            ancho: Math.max(20, Math.min(260, Number(elemento?.ancho || 48))),
            alto: Math.max(20, Math.min(260, Number(elemento?.alto || 48))),
            x: Math.max(0, Number(elemento?.x || 0)),
            y: Math.max(0, Number(elemento?.y || 0)),
            rotacion: Math.max(-180, Math.min(180, Number(elemento?.rotacion || 0))),
            capa: Math.max(0, Math.min(9999, Number(elemento?.capa || 1))),
            redondeo: Math.max(0, Math.min(999, Number(elemento?.redondeo || 12))),
            scope: String(elemento?.scope || 'mesa') === 'zona' ? 'zona' : 'mesa',
        };
    }

    function obtenerPosicionDecoracion(mesa, elemento) {
        const item = normalizarElementoDecoracion(elemento);
        if (item.scope === 'zona') {
            return { x: item.x, y: item.y };
        }
        return {
            x: Math.max(0, Number(mesa?.pos_x || 0) + item.x),
            y: Math.max(0, Number(mesa?.pos_y || 0) + item.y),
        };
    }

    function renderDecoracionNode(mesa, elemento) {
        const item = normalizarElementoDecoracion(elemento);
        const pos = obtenerPosicionDecoracion(mesa, item);
        const node = document.createElement('div');
        node.className = 'pos-mesa-decoracion';
        node.style.left = `${pos.x}px`;
        node.style.top = `${pos.y}px`;
        node.style.width = `${item.ancho}px`;
        node.style.height = `${item.alto}px`;
        node.style.borderRadius = `${item.redondeo}px`;
        node.style.background = item.fondo && item.fondo !== 'transparent' ? item.fondo : 'transparent';
        node.style.color = item.color;
        node.style.zIndex = String(200 + item.capa);
        node.style.transform = `rotate(${item.rotacion}deg)`;

        if (item.tipo === 'imagen') {
            const img = document.createElement('img');
            img.src = normalizarRutaImagen(item.contenido);
            img.alt = 'Decoracion';
            node.appendChild(img);
        } else if (item.tipo === 'icono') {
            const ico = document.createElement('i');
            ico.className = `${sanitizeIconClass(item.contenido)} ico`;
            ico.style.fontSize = `${Math.max(14, item.fuente)}px`;
            node.appendChild(ico);
        } else {
            const txt = document.createElement('span');
            txt.className = 'txt';
            txt.style.fontSize = `${Math.max(10, item.fuente)}px`;
            txt.textContent = item.contenido || 'Texto';
            node.appendChild(txt);
        }

        return node;
    }

    function calcularPlantillaSillasRectangular(totalSillas, ancho, alto, separacion) {
        const plantilla = {
            2: [
                { x: ancho / 2, y: -separacion, rot: 0 },
                { x: ancho / 2, y: alto + separacion, rot: 180 },
            ],
            4: [
                { x: ancho / 2, y: -separacion, rot: 0 },
                { x: ancho + separacion, y: alto / 2, rot: 90 },
                { x: ancho / 2, y: alto + separacion, rot: 180 },
                { x: -separacion, y: alto / 2, rot: 270 },
            ],
            6: [
                { x: ancho * 0.3, y: -separacion, rot: 0 },
                { x: ancho * 0.7, y: -separacion, rot: 0 },
                { x: ancho + separacion, y: alto / 2, rot: 90 },
                { x: ancho * 0.7, y: alto + separacion, rot: 180 },
                { x: ancho * 0.3, y: alto + separacion, rot: 180 },
                { x: -separacion, y: alto / 2, rot: 270 },
            ],
            8: [
                { x: ancho * 0.24, y: -separacion, rot: 0 },
                { x: ancho * 0.5, y: -separacion, rot: 0 },
                { x: ancho * 0.76, y: -separacion, rot: 0 },
                { x: ancho + separacion, y: alto * 0.36, rot: 90 },
                { x: ancho + separacion, y: alto * 0.7, rot: 90 },
                { x: ancho * 0.76, y: alto + separacion, rot: 180 },
                { x: ancho * 0.24, y: alto + separacion, rot: 180 },
                { x: -separacion, y: alto * 0.5, rot: 270 },
            ],
        };
        return plantilla[totalSillas] || null;
    }

    function calcularPlantillaSillasRedonda(totalSillas, ancho, alto, separacion) {
        const sx = separacion;
        const sy = Math.max(10, Math.round(separacion * 0.9));
        const plantilla = {
            2: [
                { x: ancho / 2, y: -sy, rot: 0 },
                { x: ancho / 2, y: alto + sy, rot: 180 },
            ],
            4: [
                { x: ancho / 2, y: -sy, rot: 0 },
                { x: ancho + sx, y: alto / 2, rot: 90 },
                { x: ancho / 2, y: alto + sy, rot: 180 },
                { x: -sx, y: alto / 2, rot: 270 },
            ],
            6: [
                { x: ancho * 0.36, y: -sy, rot: -4 },
                { x: ancho * 0.64, y: -sy, rot: 4 },
                { x: ancho + sx * 0.75, y: alto * 0.28, rot: 55 },
                { x: ancho * 0.64, y: alto + sy, rot: 176 },
                { x: ancho * 0.36, y: alto + sy, rot: 184 },
                { x: -sx * 0.75, y: alto * 0.28, rot: -55 },
            ],
            8: [
                { x: -sx * 0.75, y: alto * 0.28, rot: -55 },
                { x: ancho * 0.36, y: -sy, rot: -6 },
                { x: ancho * 0.5, y: -sy * 1.08, rot: 0 },
                { x: ancho * 0.64, y: -sy, rot: 6 },
                { x: ancho + sx * 0.75, y: alto * 0.28, rot: 55 },
                { x: ancho * 0.64, y: alto + sy, rot: 174 },
                { x: ancho * 0.5, y: alto + sy * 1.08, rot: 180 },
                { x: ancho * 0.36, y: alto + sy, rot: 186 },
            ],
        };
        return plantilla[totalSillas] || null;
    }

    function crearSillasMesaPos(mesa, mesaNode) {
        const totalSillas = Math.max(0, Math.min(20, Number(mesa?.sillas || 0)));
        if (totalSillas <= 0) return;

        const anchoMesa = Math.max(80, Number(mesa?.ancho) || 120);
        const altoMesa = Math.max(60, Number(mesa?.alto) || 74);
        const redonda = mesa?.forma === 'redonda';
        const capa = document.createElement('div');
        capa.className = 'pos-mesa-sillas';

        const separacion = Math.max(12, Math.round(Math.min(anchoMesa, altoMesa) * 0.22));
        const plantilla = redonda
            ? calcularPlantillaSillasRedonda(totalSillas, anchoMesa, altoMesa, separacion)
            : calcularPlantillaSillasRectangular(totalSillas, anchoMesa, altoMesa, separacion);

        const puntos = Array.isArray(plantilla) && plantilla.length
            ? plantilla
            : (() => {
                if (redonda) {
                    const cx = anchoMesa / 2;
                    const cy = altoMesa / 2;
                    const rx = (anchoMesa / 2) + separacion;
                    const ry = (altoMesa / 2) + separacion;
                    return Array.from({ length: totalSillas }, (_, i) => {
                        const ang = (-Math.PI / 2) + ((Math.PI * 2) * i / totalSillas);
                        return {
                            x: cx + Math.cos(ang) * rx,
                            y: cy + Math.sin(ang) * ry,
                            rot: (ang * 180 / Math.PI) + 90,
                        };
                    });
                }

                const perimetro = (2 * anchoMesa) + (2 * altoMesa);
                return Array.from({ length: totalSillas }, (_, i) => {
                    const d = ((i + 0.5) * perimetro) / totalSillas;
                    if (d < anchoMesa) return { x: d, y: -separacion, rot: 0 };
                    if (d < (anchoMesa + altoMesa)) return { x: anchoMesa + separacion, y: d - anchoMesa, rot: 90 };
                    if (d < (2 * anchoMesa + altoMesa)) return { x: anchoMesa - (d - anchoMesa - altoMesa), y: altoMesa + separacion, rot: 180 };
                    return { x: -separacion, y: altoMesa - (d - (2 * anchoMesa + altoMesa)), rot: 270 };
                });
            })();
        puntos.forEach((p) => {
            const silla = document.createElement('div');
            silla.className = 'pos-mesa-silla';
            silla.style.left = `${p.x}px`;
            silla.style.top = `${p.y}px`;
            silla.style.transform = `translate(-50%, -50%) rotate(${Number(p.rot) || 0}deg)`;
            capa.appendChild(silla);
        });

        mesaNode.appendChild(capa);
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

        ui.board.style.width = `${Math.max(300, Number(zona.ancho || 1000))}px`;
        ui.board.style.height = `${Math.max(240, Number(zona.alto || 620))}px`;

        ui.board.innerHTML = '';

        const mesas = Array.isArray(zona.mesas) ? zona.mesas : [];
        mesas.forEach((m) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.mesa = String(m.id);
            btn.className = `pos-mesa ${m.forma === 'redonda' ? 'redonda' : 'rectangular'} ${m.estado}${m.mesa_union_id ? ' unida' : ''}${mesaSeleccionada && Number(mesaSeleccionada.id) === Number(m.id) ? ' seleccionada' : ''}`;
            btn.style.left = `${Number(m.pos_x || 0)}px`;
            btn.style.top = `${Number(m.pos_y || 0)}px`;
            btn.style.width = `${Math.max(80, Number(m.ancho) || 120)}px`;
            btn.style.height = `${Math.max(60, Number(m.alto) || 74)}px`;

            const tablero = document.createElement('div');
            tablero.className = 'pos-mesa-tablero';
            btn.appendChild(tablero);

            const content = document.createElement('div');
            content.className = 'pos-mesa-content';
            const estadoLabel = estadoMesaLabel(m.estado);
            const estadoClass = ['libre', 'ocupada', 'proceso_pago'].includes(String(m.estado)) ? String(m.estado) : 'libre';
            const detalleEstado = m.estado !== 'libre' ? `<span class="meta">Consumo: ${fmt(m.total_activo)}</span>` : '';
            const unionBadge = m.mesa_union_id ? `<span class="pos-mesa-union-badge">⛓ ${esc(m.union_nombre || '?')}</span>` : '';
            content.innerHTML = `
                <span class="name">${esc(m.nombre)}</span>
                <span class="meta">${Number(m.sillas || m.capacidad || 0)} sillas</span>
                <span class="pos-mesa-estado ${estadoClass}">${estadoLabel}</span>
                ${unionBadge}
                ${detalleEstado}
            `;
            btn.appendChild(content);

            crearSillasMesaPos(m, btn);

            btn.addEventListener('click', () => {
                mesaSeleccionada = m;
                ui.chipMesa.textContent = mesaActualTexto();
                ui.orderDisabled.style.display = 'none';
                ui.orderPanel.style.display = '';
                mostrarVistaPlatos();
                renderBoard();
                actualizarPrecuenta();
            });

            ui.board.appendChild(btn);
        });

        const overlay = document.createElement('div');
        overlay.className = 'pos-zona-overlay';
        mesas.forEach((m) => {
            const decoraciones = parseDecoraciones(m.decoraciones);
            decoraciones.forEach((elemento) => {
                overlay.appendChild(renderDecoracionNode(m, elemento));
            });
        });
        ui.board.appendChild(overlay);
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

    // Devuelve el ID de la mesa principal: si la actual es secundaria (unida), usa mesa_union_id
    function mesaPrincipalId() {
        return Number(mesaSeleccionada?.mesa_union_id || mesaSeleccionada?.id || 0);
    }

    async function actualizarPrecuenta() {
        if (!mesaSeleccionada) {
            ui.resumenMesa.innerHTML = '<strong>Selecciona una mesa.</strong>';
            lastPrecuenta = null;
            return;
        }

        // Si la mesa seleccionada es secundaria (unida), consultar la mesa principal
        const mesaConsultaId = Number(mesaSeleccionada.mesa_union_id || mesaSeleccionada.id);
        const nombreMostrar = mesaSeleccionada.mesa_union_id
            ? `${mesaSeleccionada.nombre} + ${mesaSeleccionada.union_nombre || '?'}`
            : null;

        try {
            const d = await getJson(`${API_PRECUENTA}?mesa_id=${mesaConsultaId}`);
            lastPrecuenta = d;
            const pedidosActivos = d.pedidos_activos || [];
            const lineas = d.resumen_lineas || [];
            const tituloMesa = nombreMostrar ? `${esc(nombreMostrar)} <span style="font-size:11px;color:#7c3aed;">(unidas)</span>` : esc(d.mesa.nombre);

            ui.resumenMesa.innerHTML = `
                <div><strong>${tituloMesa}</strong> · Pedidos activos: <strong>${pedidosActivos.length}</strong></div>
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
            const d = await getJson(`${API_PRECUENTA}?mesa_id=${mesaPrincipalId()}`);
            const pedidosActivos = d.pedidos_activos || [];
            const lineas = d.resumen_lineas || [];
            const tituloUnion = mesaSeleccionada?.mesa_union_id ? ` + ${mesaSeleccionada.union_nombre || '?'} <span style="font-size:10px;color:#7c3aed">(unidas)</span>` : '';

            ui.precuentaCont.innerHTML = `
                <div style="font-size:13px;color:#334155;margin-bottom:8px;">Mesa: <strong>${esc(d.mesa.nombre)}${tituloUnion}</strong></div>
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
            const d = await getJson(`${API_PRECUENTA}?mesa_id=${mesaPrincipalId()}`);
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
                    mesa_id: mesaPrincipalId(),
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

    function listarMesasCandidatasUnion() {
        const actualId = Number(mesaSeleccionada?.id || 0);
        const candidatas = [];
        zonas.forEach((zona) => {
            (zona.mesas || []).forEach((mesa) => {
                const mesaId = Number(mesa.id || 0);
                if (mesaId <= 0 || mesaId === actualId) {
                    return;
                }
                candidatas.push({
                    id: mesaId,
                    nombre: String(mesa.nombre || `Mesa ${mesaId}`),
                    zona: String(zona.nombre || '-'),
                    estado: String(mesa.estado || 'libre'),
                    union_id: mesa.mesa_union_id || null,
                    union_nombre: mesa.union_nombre || null,
                });
            });
        });
        return candidatas;
    }

    function actualizarMetaMesaDestinoUnion() {
        if (!ui.selectUnionMesaDestino || !ui.unionMesaMeta) {
            return;
        }
        const mesaDestinoId = Number(ui.selectUnionMesaDestino.value || 0);
        const mesaDestino = mesasCandidatasUnion.find((m) => m.id === mesaDestinoId);
        if (!mesaDestino) {
            ui.unionMesaMeta.textContent = '';
            return;
        }
        ui.unionMesaMeta.textContent = `Zona: ${mesaDestino.zona} · Estado: ${estadoMesaLabel(mesaDestino.estado)}`;
    }

    function abrirModalUnirMesa() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa primero.', true);
            return false;
        }
        if (carrito.length > 0) {
            showMsg('Primero envía la comanda o limpia el carrito antes de unir mesas.', true);
            return false;
        }

        mesasCandidatasUnion = listarMesasCandidatasUnion()
            .filter((m) => !m.union_id);  // no mostrar mesas ya unidas a otras
        candidatas = mesasCandidatasUnion;
        if (!mesasCandidatasUnion.length) {
            showMsg('No hay mesas disponibles para unir.', true);
            return false;
        }

        const estaUnida = mesaSeleccionada.mesa_union_id ? ` (ya unida con ${esc(mesaSeleccionada.union_nombre || '?')})` : '';
        ui.unionMesaOrigenTexto.textContent = `Mesa origen: ${mesaSeleccionada.nombre}${estaUnida}. Selecciona la mesa destino: los pedidos de esta mesa se asignarán a la destino.`;
        ui.selectUnionMesaDestino.innerHTML = mesasCandidatasUnion
            .map((m) => `<option value="${m.id}">${esc(m.nombre)} · ${esc(m.zona)} · ${esc(estadoMesaLabel(m.estado))}</option>`)
            .join('');

        actualizarMetaMesaDestinoUnion();
        ui.modalUnionMesa.classList.add('show');
        return true;
    }

    function cerrarModalUnirMesa() {
        ui.modalUnionMesa.classList.remove('show');
        candidatas = [];
        mesasCandidatasUnion = [];
    }

    async function unirMesaConOtra() {
        if (!abrirModalUnirMesa()) {
            return;
        }
    }

    async function confirmarUnionMesa() {
        if (!mesaSeleccionada) {
            showMsg('Selecciona una mesa primero.', true);
            return;
        }

        if (!mesasCandidatasUnion.length) {
            mesasCandidatasUnion = listarMesasCandidatasUnion();
        }
        if (!mesasCandidatasUnion.length) {
            showMsg('No hay mesas disponibles para unir.', true);
            return;
        }

        const mesaDestinoId = Number(ui.selectUnionMesaDestino?.value || 0);
        if (!Number.isInteger(mesaDestinoId) || mesaDestinoId <= 0) {
            showMsg('Selecciona una mesa destino válida.', true);
            return;
        }
        if (mesaDestinoId === Number(mesaSeleccionada.id)) {
            showMsg('La mesa destino debe ser distinta a la mesa actual.', true);
            return;
        }

        const mesaDestino = mesasCandidatasUnion.find((m) => m.id === mesaDestinoId);
        if (!mesaDestino) {
            showMsg('La mesa destino no está disponible en el mapa.', true);
            return;
        }

        const ok = window.confirm(
            `¿Unir ${mesaSeleccionada.nombre} con ${mesaDestino.nombre}?\n\nLos pedidos de ${mesaSeleccionada.nombre} se asignarán a ${mesaDestino.nombre}. Al terminar la cuenta, las mesas se separan automáticamente.`
        );
        if (!ok) {
            return;
        }

        const btn = document.getElementById('btnConfirmarUnionMesa');
        btn.disabled = true;
        const textoOriginal = btn.textContent;
        btn.textContent = 'Uniendo...';

        try {
            const d = await getJson(API_UNIR_MESAS, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    mesa_origen_id: Number(mesaSeleccionada.id),
                    mesa_destino_id: mesaDestinoId,
                }),
            });

            cerrarModalUnirMesa();
            carrito = [];
            renderCarrito();
            renderCatalogo();
            await cargarMesasEstado();
            // Actualizar mesaSeleccionada a la mesa destino real
            for (const zona of zonas) {
                const found = (zona.mesas || []).find((mm) => Number(mm.id) === mesaDestinoId);
                if (found) { mesaSeleccionada = found; break; }
            }
            ui.chipMesa.textContent = mesaActualTexto();
            await actualizarPrecuenta();
            showMsg(d.mensaje || 'Mesas unidas correctamente.');
        } catch (e) {
            showMsg(String(e?.message || 'No se pudo unir las mesas.'), true);
        } finally {
            btn.disabled = false;
            btn.textContent = textoOriginal;
        }
    }
function abrirModalLiberarMesa() {
    if (!mesaSeleccionada) {
        showMsg('Selecciona una mesa primero.', true);
        return;
    }
    document.getElementById('textoConfirmarLiberarMesa').textContent =
        `Se liberará ${mesaSeleccionada.nombre}. Esto cancelará sus consumos activos no facturados.`;
    document.getElementById('modalConfirmarLiberarMesa').classList.add('show');
}

function cerrarModalLiberarMesa() {
    document.getElementById('modalConfirmarLiberarMesa').classList.remove('show');
}

async function liberarMesaActual() {
    if (!mesaSeleccionada) {
        showMsg('Selecciona una mesa primero.', true);
        return;
    }

    const btn = document.getElementById('btnConfirmarLiberarMesaModal');
    btn.disabled = true;
    const textoOriginal = btn.innerHTML;
    btn.textContent = 'Liberando...';

    try {
        const d = await getJson(API_LIBERAR_MESA, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                mesa_id: mesaPrincipalId(),
                motivo: 'Liberación manual desde POS',
            }),
        });

        cerrarModalLiberarMesa();
        carrito = [];
        renderCarrito();
        renderCatalogo();
        await cargarMesasEstado();
        await actualizarPrecuenta();
        showMsg(d.mensaje || 'Mesa liberada correctamente.');
    } catch (e) {
        showMsg(e.message, true);
    } finally {
        btn.disabled = false;
        btn.innerHTML = textoOriginal;
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

    document.getElementById('btnUnirMesa').addEventListener('click', () => {
        unirMesaConOtra();
    });

    document.getElementById('btnConfirmarUnionMesa').addEventListener('click', () => {
        confirmarUnionMesa();
    });

    document.getElementById('btnCerrarUnionMesa').addEventListener('click', () => {
        cerrarModalUnirMesa();
    });

    document.getElementById('posUnionMesaDestino').addEventListener('change', () => {
        actualizarMetaMesaDestinoUnion();
    });

    document.getElementById('btnLiberarMesa').addEventListener('click', () => {
    abrirModalLiberarMesa();
});

document.getElementById('btnConfirmarLiberarMesaModal').addEventListener('click', () => {
    liberarMesaActual();
});

document.getElementById('btnCancelarLiberarMesa').addEventListener('click', () => {
    cerrarModalLiberarMesa();
});

document.getElementById('modalConfirmarLiberarMesa').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) {
        cerrarModalLiberarMesa();
    }
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

    ui.modalUnionMesa.addEventListener('click', (e) => {
        if (e.target === ui.modalUnionMesa) {
            cerrarModalUnirMesa();
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
