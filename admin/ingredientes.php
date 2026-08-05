<?php
$tituloPagina = 'Ingredientes y Stock';
$paginaActual = 'ingredientes';
require __DIR__ . '/_layout_top.php';
?>
<style>
/* ── Layout ─────────────────────────────────────────────────── */
.ing-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.ing-header h2 { margin:0; font-size:1.3rem; font-weight:700; }
.ing-search {
    padding: 9px 14px;
    border: none;
    border-radius: 12px;
    font-size: .93rem;
    width: 220px;
    background: var(--neu-base);
    box-shadow: inset 3px 3px 7px var(--neu-sombra-oscura), inset -3px -3px 7px var(--neu-sombra-clara);
}
.ing-search:focus { outline: none; box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara), 0 0 0 2px rgba(232,89,12,.35); }

/* ── Stats ──────────────────────────────────────────────────── */
/* ── Stats ──────────────────────────────────────────────────── */
.ing-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:20px; }



/* El "hueco" — usa el fondo neutro y tiene la sombra hundida real */
.ing-stat-socket {
    background: var(--neu-base);
    border-radius: 20px;
    padding: 7px;
    box-shadow:
        inset 6px 6px 14px var(--neu-sombra-oscura),
        inset -6px -6px 14px var(--neu-sombra-clara);
}

/* La tarjeta de color que "descansa" dentro del hueco */
.ing-stat {
    position: relative;
    overflow: hidden;
    border-radius: 15px;
    padding: 16px 18px;
    color: #fff;
    box-shadow:
        0 2px 4px rgba(0,0,0,.25),
        0 0 0 1px rgba(255,255,255,.12) inset;
}



/* ── Tarjetas clicleables + ripple ─────────────────────────────── */
.ing-stat-socket {
    cursor: pointer;
    transition: transform .15s ease;
}
.ing-stat-socket:active {
    transform: scale(.97);
}

.ing-stat {
    position: relative; /* ya lo tenías, solo confirma que esté */
}



/* Marca visual de la tarjeta activa (filtro seleccionado) — color según tipo */
.ing-stat-socket:has(.ing-stat.total).filtro-activo {
    box-shadow:
        inset 6px 6px 14px var(--neu-sombra-oscura),
        inset -6px -6px 14px var(--neu-sombra-clara),
        0 0 0 2px #7c3aed;
}
.ing-stat-socket:has(.ing-stat.alerta).filtro-activo {
    box-shadow:
        inset 6px 6px 14px var(--neu-sombra-oscura),
        inset -6px -6px 14px var(--neu-sombra-clara),
        0 0 0 2px #f97316;
}
.ing-stat-socket:has(.ing-stat.ok).filtro-activo {
    box-shadow:
        inset 6px 6px 14px var(--neu-sombra-oscura),
        inset -6px -6px 14px var(--neu-sombra-clara),
        0 0 0 2px #10b981;
}


/* Decoración tipo "onda" de fondo (igual a la imagen de referencia) */
.ing-stat::after {
    content: "";
    position: absolute;
    right: -10px;
    bottom: -10px;
    width: 90px;
    height: 90px;
    background: radial-gradient(circle, rgba(255,255,255,.18) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    pointer-events: none;
}

.ing-stat-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: rgba(255,255,255,.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 10px;
    font-size: 1.1rem;
}


/* ── Malla de líneas onduladas (wireframe) ─────────────────────── */
.ing-stat-wires {
    position: absolute;
    left: 0;
    bottom: 0;
    width: 200%;
    height: 46px;
    pointer-events: none;
    opacity: .35;
}
.ing-stat-wires path {
    fill: none;
    stroke: #fff;
    stroke-width: 3;
    stroke-linecap: round;
}
.ing-stat-wires .linea1 { animation: wireMove 9s linear infinite; opacity: .8; }
.ing-stat-wires .linea2 { animation: wireMove 13s linear infinite reverse; opacity: .5; }
.ing-stat-wires .linea3 { animation: wireMove 17s linear infinite; opacity: .3; }

@keyframes wireMove {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
}
/*  */
.ing-stat-val { font-size:1.7rem; font-weight:800; line-height:1; color:#fff; }
.ing-stat-label { font-size:.8rem; color:rgba(255,255,255,.85); margin-top:4px; }

/* Gradientes por tipo, igual estilo que la imagen de ejemplo */
/* Gradientes por tipo, igual estilo que la imagen de ejemplo */
/* !important para que ninguna regla de modo oscuro los sobreescriba */
.ing-stat.total  { background: linear-gradient(135deg, #7c3aed, #4c1d95) !important; }
.ing-stat.alerta { background: linear-gradient(135deg, #f97316, #c2410c) !important; }
.ing-stat.ok     { background: linear-gradient(135deg, #10b981, #047857) !important; }

.ing-stat-val,
.ing-stat-label { color: #fff !important; }

/* ── Tabla ───────────────────────────────────────────────────── */
.ing-table-wrap { background: var(--neu-base); border-radius: 18px; border: none; overflow: hidden; box-shadow: 8px 8px 18px var(--neu-sombra-oscura), -8px -8px 18px var(--neu-sombra-clara); }
.ing-table { width:100%; border-collapse:collapse; font-size:.9rem; }
.ing-table thead { background: transparent; }
.ing-table th { padding:12px 14px; text-align:left; font-weight:700; color:#666d7a; font-size:.8rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:3px solid #E8590C; }
.ing-table td { padding:11px 14px; border-bottom:1px solid rgba(0,0,0,.06); vertical-align:middle; }
.ing-table tbody tr:hover { background: rgba(0,0,0,.02); }
.ing-table tbody tr:last-child td { border-bottom:none; }

/* ── Stock bar ───────────────────────────────────────────────── */
.stock-bar-wrap { display:flex; align-items:center; gap:8px; min-width:140px; }
.stock-bar { flex:1; height:7px; background: var(--neu-base); border-radius:4px; overflow:hidden; box-shadow: inset 1px 1px 3px var(--neu-sombra-oscura); }
.stock-bar-fill { height:100%; border-radius:3px; transition:width .3s; }
.stock-bar-fill.ok   { background:#22c55e; }
.stock-bar-fill.warn { background:#f59e0b; }
.stock-bar-fill.bad  { background:#ef4444; }
.stock-num { font-weight:600; font-size:.88rem; white-space:nowrap; }

/* ── Badge unidad ─────────────────────────────────────────────── */
.badge-unidad { display:inline-block; padding:2px 8px; border-radius:20px; font-size:.75rem; font-weight:600; background:#f1f5f9; color:#475569; }

/* ── Badge bajo stock ─────────────────────────────────────────── */
.badge-alerta { display:inline-flex; align-items:center; gap:3px; padding:2px 8px; border-radius:20px; font-size:.75rem; font-weight:600; background:#fef2f2; color:#ef4444; border:1px solid #fecaca; }

/* ── Botones acción ───────────────────────────────────────────── */
.btn-ing { display:inline-flex; align-items:center; gap:4px; padding:6px 11px; border-radius:9px; border:none; cursor:pointer; font-size:.8rem; font-weight:600; transition:opacity .15s, transform .15s; }
.btn-ing:hover { opacity:.88; transform: translateY(-1px); }
.btn-ing i { color: inherit; font-size: .95rem; }
.btn-ing-edit  { background: linear-gradient(135deg,#818cf8,#6366f1); color:#fff; box-shadow: 2px 2px 6px rgba(99,102,241,.35); }
.btn-ing-stock { background: linear-gradient(135deg,#38bdf8,#0ea5e9); color:#fff; box-shadow: 2px 2px 6px rgba(14,165,233,.35); }
.btn-ing-hist  { background: var(--neu-base); color:#475569; box-shadow: 2px 2px 5px var(--neu-sombra-oscura), -2px -2px 5px var(--neu-sombra-clara); }
.btn-ing-del   { background: linear-gradient(135deg,#f87171,#ef4444); color:#fff; box-shadow: 2px 2px 6px rgba(239,68,68,.35); }
.btn-ing-ing   { background: linear-gradient(135deg,#4ade80,#22c55e); color:#fff; box-shadow: 2px 2px 6px rgba(34,197,94,.35); }

/* ── Modal ───────────────────────────────────────────────────── */
.ing-modal-backdrop { display:none; position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:1000; align-items:center; justify-content:center; }
.ing-modal-backdrop.abierto { display:flex; }
.ing-modal { background: var(--neu-base); border-radius: 22px; padding: 28px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: 14px 14px 32px rgba(0,0,0,.28), -8px -8px 24px var(--neu-sombra-clara); position: relative; }
.ing-modal h3 { margin:0 0 18px; font-size:1.15rem; font-weight:700; }
.ing-modal-close { position:absolute; top:14px; right:14px; background:none; border:none; cursor:pointer; color:#94a3b8; font-size:1.2rem; }
.ing-modal-close:hover { color:#0f172a; }
.ing-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.ing-form-grid .full { grid-column:1/-1; }
.ing-label { display:block; font-size:.8rem; font-weight:600; color:#475569; margin-bottom:4px; }
.ing-input, .ing-select {
    width: 100%;
    padding: 9px 12px;
    border: none;
    border-radius: 10px;
    font-size: .93rem;
    box-sizing: border-box;
    background: var(--neu-base);
    box-shadow: inset 3px 3px 7px var(--neu-sombra-oscura), inset -3px -3px 7px var(--neu-sombra-clara);
    color: #333;
}
.ing-input:focus, .ing-select:focus {
    outline: none;
    box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara), 0 0 0 2px rgba(232,89,12,.35);
}
.ing-modal-footer { display:flex; gap:10px; margin-top:20px; }
.ing-modal-footer .btn-save { flex:1; padding:11px; background: linear-gradient(135deg,#818cf8,#6366f1); color:#fff; border:none; border-radius:12px; font-weight:700; cursor:pointer; box-shadow: 4px 4px 10px rgba(99,102,241,.35); transition: transform .15s; }
.ing-modal-footer .btn-save:hover { transform: translateY(-2px); }
.ing-modal-footer .btn-cancel { padding:11px 18px; background: var(--neu-base); color:#4a5160; border:none; border-radius:12px; font-weight:600; cursor:pointer; box-shadow: 4px 4px 10px var(--neu-sombra-oscura), -4px -4px 10px var(--neu-sombra-clara); }

/* ── Modal stock ─────────────────────────────────────────────── */
.stock-tipo-tabs { display:flex; gap:8px; margin-bottom:16px; }
.stock-tipo-btn { flex:1; padding:9px; border:none; border-radius:10px; background: var(--neu-base); box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara); cursor:pointer; font-weight:600; font-size:.85rem; text-align:center; transition:all .15s; }
.stock-tipo-btn.activo[data-tipo="entrada"] { border-color:#22c55e; background:#f0fdf4; color:#15803d; }
.stock-tipo-btn.activo[data-tipo="salida"]  { border-color:#ef4444; background:#fef2f2; color:#b91c1c; }
.stock-tipo-btn.activo[data-tipo="ajuste"]  { border-color:#f59e0b; background:#fffbeb; color:#b45309; }
.stock-actual-display { background: var(--neu-base); border-radius: 12px; padding: 10px 14px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; box-shadow: inset 3px 3px 7px var(--neu-sombra-oscura), inset -3px -3px 7px var(--neu-sombra-clara); }
.stock-actual-display .label { font-size:.8rem; color:#64748b; }
.stock-actual-display .valor { font-size:1.2rem; font-weight:800; color:#0f172a; }

/* ── Historial ───────────────────────────────────────────────── */
.hist-list { display:flex; flex-direction:column; gap:6px; max-height:340px; overflow-y:auto; }
.hist-item { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:10px; background: var(--neu-base); box-shadow: inset 2px 2px 5px var(--neu-sombra-oscura), inset -2px -2px 5px var(--neu-sombra-clara); border-left: 3px solid #e2e8f0; }
.hist-item.entrada { border-left-color:#22c55e; }
.hist-item.salida  { border-left-color:#ef4444; }
.hist-item.ajuste  { border-left-color:#f59e0b; }
.hist-tipo { font-size:.75rem; font-weight:700; text-transform:uppercase; min-width:55px; }
.hist-item.entrada .hist-tipo { color:#15803d; }
.hist-item.salida  .hist-tipo { color:#b91c1c; }
.hist-item.ajuste  .hist-tipo { color:#b45309; }
.hist-cant { font-weight:700; flex:1; }
.hist-motivo { font-size:.8rem; color:#64748b; }
.hist-fecha { font-size:.75rem; color:#94a3b8; white-space:nowrap; }

/* ── Empty state ─────────────────────────────────────────────── */
.ing-empty { text-align:center; padding:48px; color:#94a3b8; }
.ing-empty i { font-size:2.5rem; display:block; margin-bottom:10px; }



/* ── Stats en móvil: scroll horizontal deslizable ─────────────── */
@media (max-width: 640px) {
    .ing-stats {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        gap: 12px;
        padding-bottom: 6px;
        margin-bottom: 20px;
    }

    .ing-stat-socket {
        flex: 0 0 78%;
        scroll-snap-align: start;
    }

    /* Oculta la barra de scroll mientras se mantiene funcional */
    .ing-stats::-webkit-scrollbar { height: 4px; }
    .ing-stats::-webkit-scrollbar-thumb { background: rgba(0,0,0,.15); border-radius: 4px; }

    /* ── Evita que TODA la página se desborde horizontalmente ── */
    body, html {
        overflow-x: hidden;
        max-width: 100%;
    }

    /* El header (búsqueda + botón) que se desbordaba */
    .ing-header {
        flex-direction: column;
        align-items: stretch;
    }
    .ing-header > div {
        width: 100%;
    }
    .ing-search {
        width: 100%;
        box-sizing: border-box;
    }
    .btn-nuevo {
        width: 100%;
        box-sizing: border-box;
        text-align: center;
    }

    /* La tabla, en vez de estirar la página, tiene su propio scroll interno */
    .ing-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        max-width: 100%;
    }
    .ing-table {
        min-width: 560px; /* fuerza el scroll interno en vez de romper el layout */
    }
}
</style>

<div class="ing-header">
    <h2><i class="ti ti-packages"></i> Ingredientes y Stock</h2>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text" class="ing-search" id="ingSearch" placeholder="Buscar ingrediente…">
        <button class="btn-nuevo" onclick="abrirModalCrear()">+ Nuevo ingrediente</button>
    </div>
</div>

<!-- Stats -->
<!-- Stats -->
<div class="ing-stats" id="ingStats">
    <div class="ing-stat-socket" data-filtro="todos" onclick="filtrarPorTarjeta('todos', this, event)">
        <div class="ing-stat total">
            <svg class="ing-stat-wires" viewBox="0 0 600 46" preserveAspectRatio="none">
                <path class="linea1" d="M0,20 Q37.5,5 75,20 T150,20 T225,20 T300,20 T375,20 T450,20 T525,20 T600,20" />
                <path class="linea2" d="M0,30 Q37.5,42 75,30 T150,30 T225,30 T300,30 T375,30 T450,30 T525,30 T600,30" />
                <path class="linea3" d="M0,12 Q37.5,25 75,12 T150,12 T225,12 T300,12 T375,12 T450,12 T525,12 T600,12" />
            </svg>
            <div class="ing-stat-icon"><i class="ti ti-packages"></i></div>
            <div class="ing-stat-val" id="statTotal">—</div>
            <div class="ing-stat-label">Total ingredientes</div>
        </div>
    </div>
    <div class="ing-stat-socket" data-filtro="bajo" onclick="filtrarPorTarjeta('bajo', this, event)">
        <div class="ing-stat alerta">
            <svg class="ing-stat-wires" viewBox="0 0 600 46" preserveAspectRatio="none">
                <path class="linea1" d="M0,20 Q37.5,5 75,20 T150,20 T225,20 T300,20 T375,20 T450,20 T525,20 T600,20" />
                <path class="linea2" d="M0,30 Q37.5,42 75,30 T150,30 T225,30 T300,30 T375,30 T450,30 T525,30 T600,30" />
                <path class="linea3" d="M0,12 Q37.5,25 75,12 T150,12 T225,12 T300,12 T375,12 T450,12 T525,12 T600,12" />
            </svg>
            <div class="ing-stat-icon"><i class="ti ti-alert-triangle"></i></div>
            <div class="ing-stat-val" id="statBajoStock">—</div>
            <div class="ing-stat-label">Bajo stock / agotados</div>
        </div>
    </div>
    <div class="ing-stat-socket" data-filtro="ok" onclick="filtrarPorTarjeta('ok', this, event)">
        <div class="ing-stat ok">
            <svg class="ing-stat-wires" viewBox="0 0 600 46" preserveAspectRatio="none">
                <path class="linea1" d="M0,20 Q37.5,5 75,20 T150,20 T225,20 T300,20 T375,20 T450,20 T525,20 T600,20" />
                <path class="linea2" d="M0,30 Q37.5,42 75,30 T150,30 T225,30 T300,30 T375,30 T450,30 T525,30 T600,30" />
                <path class="linea3" d="M0,12 Q37.5,25 75,12 T150,12 T225,12 T300,12 T375,12 T450,12 T525,12 T600,12" />
            </svg>
            <div class="ing-stat-icon"><i class="ti ti-check"></i></div>
            <div class="ing-stat-val" id="statOk">—</div>
            <div class="ing-stat-label">Stock OK</div>
        </div>
    </div>
</div>

<!-- Tabla -->
<div class="ing-table-wrap">
    <table class="ing-table">
        <thead>
            <tr>
                <th>Ingrediente</th>
                <th>Unidad</th>
                <th>Stock actual</th>
                <th>Costo unitario</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="ingTbody">
            <tr><td colspan="6" style="text-align:center;padding:32px;color:#94a3b8;">Cargando…</td></tr>
        </tbody>
    </table>
</div>

<!-- ── Modal: Crear/Editar ingrediente ── -->
<div class="ing-modal-backdrop" id="modalIngrediente">
    <div class="ing-modal">
        <button class="ing-modal-close" onclick="cerrarModal('modalIngrediente')"><i class="ti ti-x"></i></button>
        <h3 id="modalIngTitulo">Nuevo ingrediente</h3>
        <div class="ing-form-grid">
            <div class="full">
                <label class="ing-label">Nombre del ingrediente *</label>
                <input type="text" class="ing-input" id="ingNombre" placeholder="Ej: Pollo, Arroz, Aceite…">
            </div>
            <div>
                <label class="ing-label">Unidad de medida *</label>
                <select class="ing-select" id="ingUnidad">
                    <option value="kg">Kilogramos (kg)</option>
                    <option value="g">Gramos (g)</option>
                    <option value="l">Litros (l)</option>
                    <option value="ml">Mililitros (ml)</option>
                    <option value="m">Metros (m)</option>
                    <option value="cm">Centímetros (cm)</option>
                    <option value="unidad" selected>Unidad (und)</option>
                    <option value="porcion">Porción</option>
                </select>
            </div>
            <div>
                <label class="ing-label">Costo unitario (S/)</label>
                <input type="number" class="ing-input" id="ingCosto" placeholder="0.00" step="0.0001" min="0">
            </div>
            <div>
                <label class="ing-label">Stock inicial</label>
                <input type="number" class="ing-input" id="ingStockInicial" placeholder="0" step="0.001" min="0">
            </div>
            <div>
                <label class="ing-label">Stock mínimo (alerta)</label>
                <input type="number" class="ing-input" id="ingStockMinimo" placeholder="0" step="0.001" min="0">
            </div>
            <div class="full">
                <label class="ing-label">Descripción (opcional)</label>
                <input type="text" class="ing-input" id="ingDescripcion" placeholder="Notas sobre el ingrediente…">
            </div>
        </div>
        <div class="ing-modal-footer">
            <button class="btn-save" onclick="guardarIngrediente()">Guardar</button>
            <button class="btn-cancel" onclick="cerrarModal('modalIngrediente')">Cancelar</button>
        </div>
    </div>
</div>

<!-- ── Modal: Ajustar stock ── -->
<div class="ing-modal-backdrop" id="modalStock">
    <div class="ing-modal" style="max-width:400px;">
        <button class="ing-modal-close" onclick="cerrarModal('modalStock')"><i class="ti ti-x"></i></button>
        <h3 id="modalStockTitulo">Ajustar stock</h3>

        <div class="stock-actual-display">
            <span class="label">Stock actual</span>
            <span class="valor"><span id="modalStockActual">0</span> <small id="modalStockUnidad" style="font-size:.8rem;font-weight:400;color:#64748b;"></small></span>
        </div>

        <div class="stock-tipo-tabs">
            <button class="stock-tipo-btn activo" data-tipo="entrada" onclick="setTipoStock('entrada')"><i class="ti ti-arrow-up"></i> Entrada</button>
            <button class="stock-tipo-btn" data-tipo="salida" onclick="setTipoStock('salida')"><i class="ti ti-arrow-down"></i> Salida</button>
            <button class="stock-tipo-btn" data-tipo="ajuste" onclick="setTipoStock('ajuste')"><i class="ti ti-settings"></i> Ajuste</button>
        </div>

        <div style="margin-bottom:12px;">
            <label class="ing-label" id="modalStockCantLabel">Cantidad a ingresar</label>
            <input type="number" class="ing-input" id="modalStockCant" placeholder="0" step="0.001" min="0.001">
        </div>
        <div style="margin-bottom:4px;">
            <label class="ing-label">Motivo (opcional)</label>
            <input type="text" class="ing-input" id="modalStockMotivo" placeholder="Compra, merma, inventario…">
        </div>

        <div class="ing-modal-footer">
            <button class="btn-save" onclick="guardarStock()">Aplicar</button>
            <button class="btn-cancel" onclick="cerrarModal('modalStock')">Cancelar</button>
        </div>
    </div>
</div>

<!-- ── Modal: Historial ── -->
<div class="ing-modal-backdrop" id="modalHistorial">
    <div class="ing-modal" style="max-width:500px;">
        <button class="ing-modal-close" onclick="cerrarModal('modalHistorial')"><i class="ti ti-x"></i></button>
        <h3 id="modalHistTitulo">Historial de movimientos</h3>
        <div class="hist-list" id="histList">
            <p style="text-align:center;color:#94a3b8;">Cargando…</p>
        </div>
    </div>
</div>

<!-- ── Modal: Confirmar eliminar ── -->
<div class="ing-modal-backdrop" id="modalConfirmarElim">
    <div class="ing-modal" style="max-width:380px;text-align:center;">
        <div style="font-size:2.5rem;margin-bottom:8px;">⚠️</div>
        <h3>¿Eliminar ingrediente?</h3>
        <p style="color:#64748b;margin-bottom:20px;">Se eliminará permanentemente. Esta acción no se puede deshacer.</p>
        <div class="ing-modal-footer" style="justify-content:center;">
            <button class="btn-save" style="background:#ef4444;" onclick="confirmarEliminar()">Sí, eliminar</button>
            <button class="btn-cancel" onclick="cerrarModal('modalConfirmarElim')">Cancelar</button>
        </div>
    </div>
</div>

<script>
const API_ING = '../api/ingredientes.php';
let ingredientes = [];
let ingEditId = null;
let stockEditId = null;
let stockTipoActual = 'entrada';
let eliminarId = null;

// ── Cargar lista ──────────────────────────────────────────────────────────────
async function cargarIngredientes() {
    try {
        const r = await fetch(API_ING + '?accion=listar', { headers: { Accept: 'application/json' } });
        const d = await r.json();
        if (!d.ok) throw new Error(d.mensaje);
        ingredientes = d.ingredientes || [];
        renderTabla(ingredientes);

        renderStats(ingredientes);

    } catch(e) {
        document.getElementById('ingTbody').innerHTML =
            `<tr><td colspan="6" style="text-align:center;padding:32px;color:#ef4444;">Error: ${e.message}</td></tr>`;
    }
}

function renderStats(lista) {
    const bajo = lista.filter(i => i.bajo_stock).length;
    document.getElementById('statTotal').textContent = lista.length;
    document.getElementById('statBajoStock').textContent = bajo;
    document.getElementById('statOk').textContent = lista.length - bajo;
}

function renderTabla(lista) {
    const tbody = document.getElementById('ingTbody');
    if (!lista.length) {
        tbody.innerHTML = `<tr><td colspan="6"><div class="ing-empty"><i class="ti ti-packages"></i>Sin ingredientes todavía.<br><small>Crea el primero con el botón de arriba.</small></div></td></tr>`;
        return;
    }

    const UNIDAD_LABEL = {kg:'kg',g:'g',l:'L',ml:'ml',m:'m',cm:'cm',unidad:'und',porcion:'porc.'};

    tbody.innerHTML = lista.map(i => {
        const pct = i.stock_minimo > 0 ? Math.min(100, (i.stock_actual / (i.stock_minimo * 3)) * 100) : (i.stock_actual > 0 ? 100 : 0);
        const clase = i.stock_actual <= 0 ? 'bad' : i.bajo_stock ? 'warn' : 'ok';
        const unidadLabel = UNIDAD_LABEL[i.unidad] || i.unidad;
        const stockDisplay = formatStockDisplay(i.stock_actual, i.unidad);

        return `<tr data-id="${i.id}">
            <td>
                <strong>${esc(i.nombre)}</strong>
                ${i.descripcion ? `<div style="font-size:.78rem;color:#94a3b8;">${esc(i.descripcion)}</div>` : ''}
            </td>
            <td><span class="badge-unidad">${unidadLabel}</span></td>
            <td>
                <div class="stock-bar-wrap">
                    <div class="stock-bar"><div class="stock-bar-fill ${clase}" style="width:${pct}%"></div></div>
                    <span class="stock-num" style="color:${clase==='bad'?'#ef4444':clase==='warn'?'#f59e0b':'#22c55e'}">${stockDisplay}</span>
                </div>
            </td>
            <td>${i.costo_unitario > 0 ? 'S/ ' + i.costo_unitario.toFixed(4) : '—'}</td>
            <td>${i.bajo_stock ? `<span class="badge-alerta"><i class="ti ti-alert-triangle"></i> Bajo stock</span>` : `<span style="color:#22c55e;font-size:.8rem;font-weight:600;">✓ OK</span>`}</td>
            <td>
                <div class="ing-actions">
                    <button class="btn-ing btn-ing-stock" onclick="abrirModalStock(${i.id})" title="Ajustar stock"><i class="ti ti-arrows-exchange"></i> Stock</button>
                    <button class="btn-ing btn-ing-edit"  onclick="abrirModalEditar(${i.id})" title="Editar"><i class="ti ti-pencil"></i></button>
                    <button class="btn-ing btn-ing-hist"  onclick="abrirHistorial(${i.id}, '${esc(i.nombre)}')" title="Historial"><i class="ti ti-history"></i></button>
                    <button class="btn-ing btn-ing-del"   onclick="pedirEliminar(${i.id})" title="Eliminar"><i class="ti ti-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
}



// ── Filtro por tarjeta + efecto ripple ──────────────────────────
let filtroTarjetaActivo = 'todos';

function filtrarPorTarjeta(tipo, elSocket, evento) {
    // Si haces clic en "todos" estando ya en "todos", no pasa nada (se queda igual)
    // Si haces clic en otra tarjeta activa, vuelve a "todos"
    if (tipo === 'todos') {
        filtroTarjetaActivo = 'todos';
    } else {
        filtroTarjetaActivo = (filtroTarjetaActivo === tipo) ? 'todos' : tipo;
    }

    document.querySelectorAll('.ing-stat-socket').forEach(s => s.classList.remove('filtro-activo'));
    elSocket.classList.add('filtro-activo');

    let filtrado = ingredientes;
    if (filtroTarjetaActivo === 'bajo') {
        filtrado = ingredientes.filter(i => i.bajo_stock);
    } else if (filtroTarjetaActivo === 'ok') {
        filtrado = ingredientes.filter(i => !i.bajo_stock);
    }

    // Respeta también lo que haya en el buscador
    const q = document.getElementById('ingSearch').value.toLowerCase();
    if (q) {
        filtrado = filtrado.filter(i => i.nombre.toLowerCase().includes(q) || (i.descripcion||'').toLowerCase().includes(q));
    }

    renderTabla(filtrado);
}



// ── Utilidades ────────────────────────────────────────────────────────────────
function esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }
function formatNum(n) { return parseFloat(n).toLocaleString('es-PE', { maximumFractionDigits: 3 }); }
function formatStockDisplay(cantidad, unidad) {
    const valor = parseFloat(cantidad) || 0;

    if (unidad === 'kg') {
        return valor < 1 ? `${formatNum(valor * 1000)} g` : `${formatNum(valor)} kg`;
    }
    if (unidad === 'g') {
        return valor >= 1000 ? `${formatNum(valor / 1000)} kg` : `${formatNum(valor)} g`;
    }
    if (unidad === 'l') {
        return valor < 1 ? `${formatNum(valor * 1000)} ml` : `${formatNum(valor)} L`;
    }
    if (unidad === 'ml') {
        return valor >= 1000 ? `${formatNum(valor / 1000)} L` : `${formatNum(valor)} ml`;
    }
    if (unidad === 'm') {
        return valor < 1 ? `${formatNum(valor * 100)} cm` : `${formatNum(valor)} m`;
    }
    if (unidad === 'cm') {
        return valor >= 100 ? `${formatNum(valor / 100)} m` : `${formatNum(valor)} cm`;
    }

    return `${formatNum(valor)} ${UNIDAD_LABEL[unidad] || unidad}`;
}
function cerrarModal(id) { document.getElementById(id).classList.remove('abierto'); }
function abrirModal(id) { document.getElementById(id).classList.add('abierto'); }

// Cerrar al hacer clic en backdrop
document.querySelectorAll('.ing-modal-backdrop').forEach(b => {
    b.addEventListener('click', e => { if (e.target === b) b.classList.remove('abierto'); });
});

// ── Buscar ────────────────────────────────────────────────────────────────────
document.getElementById('ingSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    const filtrado = q ? ingredientes.filter(i => i.nombre.toLowerCase().includes(q) || (i.descripcion||'').toLowerCase().includes(q)) : ingredientes;
    renderTabla(filtrado);
});

// ── Modal crear ───────────────────────────────────────────────────────────────
function abrirModalCrear() {
    ingEditId = null;
    document.getElementById('modalIngTitulo').textContent = 'Nuevo ingrediente';
    document.getElementById('ingNombre').value = '';
    document.getElementById('ingUnidad').value = 'unidad';
    document.getElementById('ingCosto').value = '';
    document.getElementById('ingStockInicial').value = '';
    document.getElementById('ingStockMinimo').value = '';
    document.getElementById('ingDescripcion').value = '';
    document.getElementById('ingStockInicial').closest('div').style.display = '';
    abrirModal('modalIngrediente');
    document.getElementById('ingNombre').focus();
}

function abrirModalEditar(id) {
    const i = ingredientes.find(x => x.id === id);
    if (!i) return;
    ingEditId = id;
    document.getElementById('modalIngTitulo').textContent = 'Editar ingrediente';
    document.getElementById('ingNombre').value = i.nombre;
    document.getElementById('ingUnidad').value = i.unidad;
    document.getElementById('ingCosto').value = i.costo_unitario || '';
    document.getElementById('ingStockInicial').value = '';
    document.getElementById('ingStockInicial').closest('div').style.display = 'none'; // no cambiar stock aquí
    document.getElementById('ingStockMinimo').value = i.stock_minimo || '';
    document.getElementById('ingDescripcion').value = i.descripcion || '';
    abrirModal('modalIngrediente');
    document.getElementById('ingNombre').focus();
}

async function guardarIngrediente() {
    const nombre = document.getElementById('ingNombre').value.trim();
    if (!nombre) { alert('El nombre es obligatorio.'); return; }

    const body = {
        nombre,
        unidad:        document.getElementById('ingUnidad').value,
        costo_unitario: parseFloat(document.getElementById('ingCosto').value) || 0,
        stock_minimo:   parseFloat(document.getElementById('ingStockMinimo').value) || 0,
        descripcion:    document.getElementById('ingDescripcion').value.trim(),
    };

    if (!ingEditId) {
        body.accion = 'crear';
        body.stock_actual = parseFloat(document.getElementById('ingStockInicial').value) || 0;
    } else {
        body.accion = 'actualizar';
        body.id = ingEditId;
    }

    try {
        const r = await fetch(API_ING, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
        const d = await r.json();
        if (!d.ok) throw new Error(d.mensaje);
        cerrarModal('modalIngrediente');
        cargarIngredientes();
    } catch(e) { alert('Error: ' + e.message); }
}

// ── Modal stock ───────────────────────────────────────────────────────────────
const UNIDAD_LABEL = {kg:'kg',g:'g',l:'L',ml:'ml',m:'m',cm:'cm',unidad:'und',porcion:'porc.'};

function abrirModalStock(id) {
    const i = ingredientes.find(x => x.id === id);
    if (!i) return;
    stockEditId = id;
    document.getElementById('modalStockTitulo').textContent = 'Ajustar stock: ' + i.nombre;
    const stockDisplay = formatStockDisplay(i.stock_actual, i.unidad);
    const partes = stockDisplay.split(' ');
    document.getElementById('modalStockActual').textContent = partes[0] || stockDisplay;
    document.getElementById('modalStockUnidad').textContent = partes.slice(1).join(' ') || (UNIDAD_LABEL[i.unidad] || i.unidad);
    document.getElementById('modalStockCant').value = '';
    document.getElementById('modalStockMotivo').value = '';
    setTipoStock('entrada');
    abrirModal('modalStock');
    document.getElementById('modalStockCant').focus();
}

function setTipoStock(tipo) {
    stockTipoActual = tipo;
    document.querySelectorAll('.stock-tipo-btn').forEach(b => {
        b.classList.toggle('activo', b.dataset.tipo === tipo);
    });
    const labels = { entrada: 'Cantidad a ingresar', salida: 'Cantidad a retirar', ajuste: 'Nuevo stock (valor exacto)' };
    document.getElementById('modalStockCantLabel').textContent = labels[tipo] || 'Cantidad';
}

async function guardarStock() {
    const cant = parseFloat(document.getElementById('modalStockCant').value);
    if (!cant || cant <= 0) { alert('Ingresa una cantidad válida.'); return; }

    try {
        const r = await fetch(API_ING, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                accion: 'ajustar_stock',
                id: stockEditId,
                tipo: stockTipoActual,
                cantidad: cant,
                motivo: document.getElementById('modalStockMotivo').value.trim(),
            }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.mensaje);
        cerrarModal('modalStock');
        cargarIngredientes();
    } catch(e) { alert('Error: ' + e.message); }
}

// ── Historial ─────────────────────────────────────────────────────────────────
async function abrirHistorial(id, nombre) {
    document.getElementById('modalHistTitulo').textContent = 'Historial: ' + nombre;
    document.getElementById('histList').innerHTML = '<p style="text-align:center;color:#94a3b8;">Cargando…</p>';
    abrirModal('modalHistorial');

    try {
        const r = await fetch(`${API_ING}?accion=historial&id=${id}`, { headers: { Accept: 'application/json' } });
        const d = await r.json();
        if (!d.ok) throw new Error(d.mensaje);

        const movs = d.movimientos || [];
        if (!movs.length) {
            document.getElementById('histList').innerHTML = '<p style="text-align:center;color:#94a3b8;">Sin movimientos registrados.</p>';
            return;
        }

        const ing = ingredientes.find(x => x.id === id);
        document.getElementById('histList').innerHTML = movs.map(m => {
            const fecha = new Date(m.creado_en).toLocaleString('es-PE', { timeZone: 'America/Lima', day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' });
            const cantidadTxt = ing ? formatStockDisplay(m.cantidad, ing.unidad) : `${formatNum(m.cantidad)} `;
            const antesTxt = ing ? formatStockDisplay(m.stock_antes, ing.unidad) : `${formatNum(m.stock_antes)}`;
            const despuesTxt = ing ? formatStockDisplay(m.stock_despues, ing.unidad) : `${formatNum(m.stock_despues)}`;
            return `<div class="hist-item ${m.tipo}">
                <span class="hist-tipo">${m.tipo}</span>
                <span class="hist-cant">${cantidadTxt}</span>
                <span class="hist-motivo">${esc(m.motivo || '')}</span>
                <span class="hist-motivo" style="font-size:.77rem;color:#64748b;">${antesTxt} → ${despuesTxt}</span>
                <span class="hist-fecha">${fecha}</span>
            </div>`;
        }).join('');
    } catch(e) { document.getElementById('histList').innerHTML = `<p style="color:#ef4444;">${e.message}</p>`; }
}

// ── Eliminar ─────────────────────────────────────────────────────────────────
function pedirEliminar(id) {
    eliminarId = id;
    abrirModal('modalConfirmarElim');
}

async function confirmarEliminar() {
    if (!eliminarId) return;
    try {
        const r = await fetch(API_ING, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'eliminar', id: eliminarId }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.mensaje);
        cerrarModal('modalConfirmarElim');
        cargarIngredientes();
    } catch(e) { alert('Error: ' + e.message); }
}

// ── Iniciar ───────────────────────────────────────────────────────────────────
cargarIngredientes();
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
