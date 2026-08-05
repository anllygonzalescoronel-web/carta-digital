<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirRol(['admin']);

$tituloPagina = 'Estaciones de Producción';
$paginaActual = 'estaciones';
require __DIR__ . '/_layout_top.php';
?>
<style>
/* ── Layout ── */
.est-wrap { max-width: 1080px; margin: 0 auto; }

/* ── Cards estación ── */
.est-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; margin-bottom: 20px; }

.est-card {
    background: var(--neu-base);
    border: none;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: inset 6px 6px 14px var(--neu-sombra-oscura), inset -6px -6px 14px var(--neu-sombra-clara);
    display: flex;
    flex-direction: column;
    transition: box-shadow .2s ease;
}
.est-card:hover { box-shadow: inset 8px 8px 18px var(--neu-sombra-oscura), inset -8px -8px 18px var(--neu-sombra-clara); }

.est-card-header {
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    color: #fff;
}
.est-card-header .est-icon {
    width: 46px; height: 46px;
    background: rgba(255,255,255,.18);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
    box-shadow: inset 2px 2px 5px rgba(0,0,0,.15);
}
.est-card-header h3 { margin: 0; font-size: 15px; font-weight: 800; }
.est-card-header p  { margin: 2px 0 0; font-size: 11.5px; opacity: .85; }
.est-card-header .est-badge-activa {
    font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 999px;
    background: rgba(255,255,255,.2); white-space: nowrap;
}
.est-card-body { padding: 16px 20px; flex: 1; }
.est-tag-group { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.est-tag {
    font-size: 11px; font-weight: 700; padding: 4px 10px;
    border-radius: 8px; background: var(--neu-base); color: var(--pos-texto, #4a5160);
    box-shadow: 2px 2px 5px var(--neu-sombra-oscura), -2px -2px 5px var(--neu-sombra-clara);
    display: inline-flex; align-items: center; gap: 4px;
}
.est-tag.cat { color: #1e40af; }
.est-tag.usr { color: #166534; }
.est-tag.empty { color: #92400e; font-style: italic; }
.est-card-footer {
    padding: 12px 20px;
    display: flex; gap: 10px;
}

/* ── Botón nueva estación (naranja, mismo lenguaje que el resto del panel) ── */
.btn-nueva-est {
    display: inline-flex; align-items: center; gap: 8px;
    background: linear-gradient(135deg, #ff8a3d, #E8590C);
    color: #fff; border: none; border-radius: 14px;
    padding: 12px 22px; font-size: 14px; font-weight: 700;
    cursor: pointer; margin-bottom: 20px;
    box-shadow: 4px 4px 12px rgba(232,89,12,.4);
    transition: transform .15s ease, box-shadow .15s ease;
}
.btn-nueva-est:hover { transform: translateY(-2px); box-shadow: 6px 10px 18px rgba(232,89,12,.45); }
.btn-nueva-est:active { transform: translateY(0); box-shadow: inset 3px 3px 6px rgba(0,0,0,.3); }

/* ── Botones card ── */
.btn-est {
    border: none; border-radius: 10px; padding: 8px 14px;
    font-weight: 700; font-size: 12px; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px;
    background: var(--neu-base);
    box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara);
    transition: transform .15s ease;
}
.btn-est:hover { transform: translateY(-2px); }
.btn-est-edit { color: #E8590C; }
.btn-est-del  { color: #c0392b; }

/* ── Modal ── */
.est-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(20,20,30,.55);
    display: none; align-items: center; justify-content: center; padding: 20px;
    z-index: 1200;
}
.est-modal-backdrop.show { display: flex; }
.est-modal {
    width: 100%; max-width: 680px;
    background: var(--neu-base);
    border-radius: 22px;
    box-shadow: 0 28px 70px rgba(0,0,0,.4);
    overflow: hidden;
    max-height: 90vh;
    display: flex; flex-direction: column;
}
.est-modal-head {
    background: linear-gradient(135deg, #ff8a3d, #E8590C);
    color: #fff; padding: 18px 22px;
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
}
.est-modal-head h4 { margin: 0; font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
.est-modal-close { border: none; background: rgba(255,255,255,.18); color: #fff; width: 34px; height: 34px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.est-modal-body { padding: 22px; overflow-y: auto; flex: 1; }
.est-modal-footer { padding: 16px 22px; display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0; }

/* ── Form ── */
.ef-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 560px) { .ef-row { grid-template-columns: 1fr; } }
.ef-field { margin-bottom: 14px; background: var(--neu-base); border-radius: 14px; padding: 10px 14px; box-shadow: 4px 4px 10px var(--neu-sombra-oscura), -4px -4px 10px var(--neu-sombra-clara); }
.ef-field label { display: block; font-size: 11px; font-weight: 700; color: var(--pos-texto, #4a5160); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 7px; }
.ef-field input, .ef-field select, .ef-field textarea {
    width: 100%; border: none; border-radius: 10px;
    padding: 9px 11px; font-size: 13px; box-sizing: border-box; color: var(--pos-texto, #333);
    background: var(--neu-base);
    box-shadow: inset 3px 3px 7px var(--neu-sombra-oscura), inset -3px -3px 7px var(--neu-sombra-clara);
    transition: box-shadow .15s;
}
.ef-field input:focus, .ef-field select:focus, .ef-field textarea:focus {
    outline: none; box-shadow: inset 4px 4px 9px var(--neu-sombra-oscura), inset -4px -4px 9px var(--neu-sombra-clara), 0 0 0 2px rgba(232,89,12,.35);
}

/* ── Checkboxes de categorías y usuarios ── */
.est-check-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 8px; max-height: 200px; overflow-y: auto; padding: 2px; }
.est-check-item {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 10px; border-radius: 12px;
    background: var(--neu-base);
    box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara);
    cursor: pointer; transition: box-shadow .15s ease;
}
.est-check-item:hover { box-shadow: 4px 4px 9px var(--neu-sombra-oscura), -4px -4px 9px var(--neu-sombra-clara); }
.est-check-item.checked {
    background: rgba(232,89,12,.12);
    box-shadow: inset 3px 3px 7px var(--neu-sombra-oscura), inset -3px -3px 7px var(--neu-sombra-clara), 0 0 0 2px rgba(232,89,12,.4);
}
.est-check-item.checked span { color: #E8590C; font-weight: 700; }
.est-check-item input[type=checkbox] {
    appearance: none;
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--neu-base);
    box-shadow: inset 2px 2px 5px var(--neu-sombra-oscura), inset -2px -2px 5px var(--neu-sombra-clara);
    cursor: pointer;
    position: relative;
    flex-shrink: 0;
    transition: background .15s ease, box-shadow .15s ease;
}
.est-check-item input[type=checkbox]:checked {
    background: linear-gradient(135deg, #ff8a3d, #E8590C);
    box-shadow: 2px 2px 6px rgba(232,89,12,.45);
}
.est-check-item input[type=checkbox]:checked::after {
    content: '';
    position: absolute;
    left: 6px;
    top: 2px;
    width: 5px;
    height: 9px;
    border: solid #fff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}.est-check-item span { font-size: 12px; font-weight: 600; color: var(--pos-texto, #334155); }
body.modo-oscuro .est-check-item span { color: #d8dae0; }
body.modo-oscuro .est-check-item.checked span { color: #ff8a3d; }

/* ── Color picker preview ── */
.color-row { display: flex; align-items: center; gap: 10px; }
.color-preview { width: 36px; height: 36px; border-radius: 10px; box-shadow: inset 2px 2px 5px var(--neu-sombra-oscura), inset -2px -2px 5px var(--neu-sombra-clara); flex-shrink: 0; }

/* ── Iconos sugeridos ── */
.icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(44px, 1fr)); gap: 8px; }
.icon-opt {
    border: none; border-radius: 12px; background: var(--neu-base);
    width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 20px; color: var(--pos-texto, #4a5160);
    box-shadow: 3px 3px 7px var(--neu-sombra-oscura), -3px -3px 7px var(--neu-sombra-clara);
    transition: box-shadow .15s ease, color .15s ease;
}
.icon-opt:hover { box-shadow: 4px 4px 9px var(--neu-sombra-oscura), -4px -4px 9px var(--neu-sombra-clara); }
.icon-opt.selected { color: #E8590C; box-shadow: inset 3px 3px 7px var(--neu-sombra-oscura), inset -3px -3px 7px var(--neu-sombra-clara), 0 0 0 2px rgba(232,89,12,.4); }
body.modo-oscuro .icon-opt { color: #d8dae0; }
body.modo-oscuro .icon-opt.selected { color: #ff8a3d; }

/* ── Modal confirmación ── */
.est-confirm-backdrop {
    position: fixed; inset: 0; background: rgba(20,20,30,.55);
    display: none; align-items: center; justify-content: center; padding: 20px; z-index: 1300;
}
.est-confirm-backdrop.show { display: flex; }
.est-confirm-box { background: var(--neu-base); border-radius: 20px; padding: 30px; max-width: 420px; width: 100%; box-shadow: 0 25px 60px rgba(0,0,0,.4); text-align: center; }
.est-confirm-box h4 { font-size: 17px; margin: 0 0 8px; color: var(--pos-texto, #333); }
.est-confirm-box p { color: var(--pos-muted, #8a93a3); font-size: 13px; margin: 0 0 20px; }

.est-confirm-nombre { font-weight: 700; color: var(--pos-texto, #0f172a); margin-bottom: 6px; }
body.modo-oscuro .est-confirm-box h4 { color: #fff; }
body.modo-oscuro .est-confirm-box p { color: #cfd3dc; }
body.modo-oscuro .est-confirm-nombre { color: #fff; }

/* ── Estado vacío ── */
.est-empty { text-align: center; padding: 56px 20px; color: var(--pos-muted, #94a3b8); }
.est-empty i { font-size: 52px; display: block; margin-bottom: 14px; opacity: .4; }
.est-empty h3 { font-size: 16px; color: var(--pos-texto, #475569); margin: 0 0 6px; }
.est-empty p { font-size: 13px; }

/* ── Msg ── */
.est-msg { font-size: 13px; font-weight: 700; min-height: 18px; }
.est-msg.ok { color: #1e8449; }
.est-msg.err { color: #b91c1c; }

/* ── Botones neumórficos genéricos (cancelar / guardar / eliminar) ── */
.btn-neu-soft {
    border: none; border-radius: 12px; padding: 10px 18px;
    background: linear-gradient(135deg, #4a5160, #2b3142); color: #fff; font-weight: 700; cursor: pointer;
    box-shadow: 4px 4px 10px var(--neu-sombra-oscura), -4px -4px 10px var(--neu-sombra-clara);
    transition: transform .15s ease, box-shadow .15s ease;
}
.btn-neu-soft:hover { transform: translateY(-2px); box-shadow: 6px 8px 16px rgba(0,0,0,.28); }

.btn-neu-primario {
    border: none; border-radius: 12px; padding: 10px 22px;
    background: linear-gradient(135deg, #ff8a3d, #E8590C); color: #fff; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 7px;
    box-shadow: 4px 4px 10px rgba(232,89,12,.4);
    transition: transform .15s ease, box-shadow .15s ease;
}
.btn-neu-primario:hover { transform: translateY(-2px); box-shadow: 6px 8px 16px rgba(232,89,12,.45); }

.btn-neu-peligro {
    border: none; border-radius: 12px; padding: 10px 20px;
    background: linear-gradient(135deg, #ff5c6c, #dc3545); color: #fff; font-weight: 700; cursor: pointer;
    box-shadow: 4px 4px 10px rgba(220,53,69,.35);
    transition: transform .15s ease;
}
.btn-neu-peligro:hover { transform: translateY(-2px); }

.est-titulo-principal {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: var(--pos-texto, #0f172a);
}

.est-subtitulo-principal {
    margin: 4px 0 0;
    font-size: 13px;
    color: var(--pos-muted, #64748b);
}

.est-label-sub {
    color: var(--pos-muted, #64748b);
}

body.modo-oscuro .est-titulo-principal {
    color: #ffffff;
}

body.modo-oscuro .est-subtitulo-principal,
body.modo-oscuro .est-label-sub {
    color: #cfd3dc;
}



/* ── Textura de ruido/grano sutil en el encabezado de la tarjeta ── */
/* ── Circuito tipo "placa" con nodos pulsantes en el encabezado ── */
.est-card-header {
    position: relative;
    overflow: hidden;
}

.est-card-header > *:not(.est-circuito) {
    position: relative;
    z-index: 1;
}

.est-circuito {
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
}

.est-circuito svg {
    width: 100%;
    height: 100%;
    display: block;
}

.est-circuito .linea {
    fill: none;
    stroke: rgba(255,255,255,.18);
    stroke-width: 1.4;
    stroke-linecap: round;
}

.est-circuito .linea-brillo {
    fill: none;
    stroke: rgba(255,255,255,.75);  /* 75 es el brillo neon :v */
    stroke-width: 1;
    stroke-linecap: round;
    filter: none;
    stroke-dasharray: 40 300;
    animation: estCircuitoRecorrido 3s linear infinite;
}

.est-circuito .nodo {
    fill: rgba(255,255,255,.55);
    animation: estNodoPulso 2.4s ease-in-out infinite;
}

@keyframes estCircuitoRecorrido {
    from { stroke-dashoffset: 0; }
    to   { stroke-dashoffset: -340; }
}

@keyframes estNodoPulso {
    0%, 100% { opacity: .25; }
    50% { opacity: 1; filter: drop-shadow(0 0 3px rgba(255,255,255,.9)); }
}

@media (prefers-reduced-motion: reduce) {
    .est-circuito .nodo { animation: none; opacity: .6; }
    .est-circuito .linea-brillo { animation: none; }
}
</style>
<div class="est-wrap">

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
<h2 class="est-titulo-principal">Estaciones de Producción</h2>
<p class="est-subtitulo-principal">Define los paneles de cocina: Cocina, Barra, Pollos, Sopas, etc. Asigna categorías de productos y usuarios responsables.</p>
        </div>
        <button class="btn-nueva-est" id="btnNuevaEstacion" type="button">
            <i class="ti ti-plus"></i> Nueva estación
        </button>
    </div>

    <div id="estMsg" class="est-msg" style="margin-bottom:10px;"></div>
    <div class="est-grid" id="estGrid">
        <div class="est-empty"><i class="ti ti-loader-2"></i><p>Cargando...</p></div>
    </div>
</div>

<!-- ════════════ MODAL: Crear / Editar estación ════════════ -->
<div class="est-modal-backdrop" id="modalEstacion">
    <div class="est-modal">
        <div class="est-modal-head">
            <h4 id="modalEstTitulo"><i class="ti ti-plus"></i> Nueva estación</h4>
            <button type="button" class="est-modal-close" id="btnCerrarModalEst"><i class="ti ti-x"></i></button>
        </div>
        <div class="est-modal-body">
            <input type="hidden" id="estId">

            <div class="ef-row">
                <div class="ef-field">
                    <label>Nombre de la estación *</label>
                    <input type="text" id="estNombre" placeholder="Ej: Cocina, Barra, Pollos…">
                </div>
                <div class="ef-field">
                    <label>Estado</label>
                    <select id="estActiva">
                        <option value="1">Activa</option>
                        <option value="0">Inactiva</option>
                    </select>
                </div>
            </div>

            <div class="ef-field">
                <label>Descripción (opcional)</label>
                <input type="text" id="estDescripcion" placeholder="Ej: Área de preparación de bebidas y cocteles">
            </div>

            <div class="ef-row">
                <div class="ef-field">
                    <label>Color de la estación</label>
                    <div class="color-row">
                        <div class="color-preview" id="colorPreview"></div>
                        <input type="color" id="estColor" value="#0f172a" style="width:100%;">
                    </div>
                </div>
                <div class="ef-field">
                    <label>Ícono</label>
                    <input type="hidden" id="estIcono" value="ti-chef-hat">
                    <div class="icon-grid">
                        <?php
                        $iconos = [
                            'ti-chef-hat', 'ti-glass-full', 'ti-flame', 'ti-soup',
                            'ti-pizza', 'ti-salad', 'ti-coffee', 'ti-meat',
                            'ti-fish', 'ti-plant-2', 'ti-drills', 'ti-star',
                        ];
                        foreach ($iconos as $ico): ?>
                        <div class="icon-opt" data-icono="<?= $ico ?>" title="<?= $ico ?>">
                            <i class="ti <?= $ico ?>"></i>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="ef-field">
                <label><i class="ti ti-category"></i> Categorías de productos que llegan a esta estación</label>
                <div class="est-check-grid" id="checkCategorias">
                    <p style="color:#94a3b8;font-size:12px;">Cargando categorías…</p>
                </div>
            </div>

            <div class="ef-field" style="margin-bottom:0;">
                <label><i class="ti ti-users"></i> Usuarios asignados a esta estación</label>
                <div class="est-check-grid" id="checkUsuarios">
                    <p style="color:#94a3b8;font-size:12px;">Cargando usuarios…</p>
                </div>
            </div>
        </div>
        <div class="est-modal-footer">
            <button type="button" id="btnCancelarEst" class="btn-neu-soft">Cancelar</button>
<button type="button" id="btnGuardarEst" class="btn-neu-primario">
    <i class="ti ti-check"></i> Guardar estación
</button>
        </div>
    </div>
</div>

<!-- ════════════ MODAL: Confirmación eliminar ════════════ -->
<div class="est-confirm-backdrop" id="modalConfirmEst">
    <div class="est-confirm-box">
        <i class="ti ti-alert-triangle" style="font-size:42px;color:#f59e0b;display:block;margin-bottom:12px;"></i>
        <h4>¿Eliminar esta estación?</h4>
<p id="confirmEstNombre" class="est-confirm-nombre"></p>        <p>Se eliminarán también las asignaciones de categorías y usuarios. Los pedidos no se verán afectados.</p>
        <div style="display:flex;gap:10px;justify-content:center;">
<button type="button" id="btnCancelarConfirmEst" class="btn-neu-soft">Cancelar</button>
<button type="button" id="btnConfirmarElimEst" class="btn-neu-peligro">Eliminar</button>
        </div>
    </div>
</div>

<script>
(() => {
    const API = '../api/estaciones.php';
    let datos = { estaciones: [], todas_categorias: [], todos_usuarios: [] };
    let eliminarId = null;

    function esc(t) {
        return String(t || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }
    function showMsg(msg, isErr = false) {
        const el = document.getElementById('estMsg');
        el.textContent = msg; el.className = 'est-msg ' + (isErr ? 'err' : 'ok');
        setTimeout(() => { el.textContent = ''; el.className = 'est-msg'; }, 3500);
    }
    async function apiGet() {
        const r = await fetch(API, { headers: { Accept: 'application/json' } });
        return r.json();
    }
    async function apiPost(body) {
        const r = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(body),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.mensaje || 'Error');
        return d;
    }


function estCircuitoSVG(variante) {
    const sets = [
        { paths: ['M0,18 H55 V48 H130', 'M0,74 H32 V44 H96 V12 H210'], nodos: [[0,18],[130,48],[0,74],[210,12]] },
        { paths: ['M220,95 H160 V65 H95', 'M170,18 V48 H120 H60 V80 H0'], nodos: [[220,95],[95,65],[170,18],[0,80]] },
        { paths: ['M0,50 H70 V15 H160', 'M0,95 H45 V60 H130 V30 H220'], nodos: [[0,50],[160,15],[0,95],[220,30]] },
    ];
    const s = sets[variante % sets.length];
    const nodos = s.nodos.map((n, i) => `<circle class="nodo" cx="${n[0]}" cy="${n[1]}" r="2.2" style="animation-delay:${(i * 0.35).toFixed(2)}s"></circle>`).join('');
    const paths = s.paths.map(d => `<path class="linea" d="${d}"></path><path class="linea-brillo" d="${d}"></path>`).join('');
    return `<div class="est-circuito"><svg viewBox="0 0 220 100" preserveAspectRatio="none">${paths}${nodos}</svg></div>`;
}

    // ── Render tarjetas ────────────────────────────────────────────
    function renderGrid() {
        const grid = document.getElementById('estGrid');
        if (!datos.estaciones.length) {
            grid.innerHTML = `<div class="est-empty" style="grid-column:1/-1;">
                <i class="ti ti-chef-hat"></i>
                <h3>No hay estaciones creadas</h3>
                <p>Crea tu primera estación de producción para empezar a enrutar comandas.</p>
            </div>`;
            return;
        }
grid.innerHTML = datos.estaciones.map((e, idx) => {            const cats = e.categorias.length
                ? e.categorias.map(c => `<span class="est-tag cat"><i class="ti ti-category" style="font-size:10px;"></i>${esc(c.nombre)}</span>`).join('')
                : '<span class="est-tag empty">Sin categorías</span>';
            const usrs = e.usuarios.length
                ? e.usuarios.map(u => `<span class="est-tag usr"><i class="ti ti-user" style="font-size:10px;"></i>${esc(u.nombre)}</span>`).join('')
                : '<span class="est-tag empty">Sin usuarios</span>';
            const activaBadge = e.activa
                ? '<span class="est-badge-activa">Activa</span>'
                : '<span class="est-badge-activa" style="background:rgba(0,0,0,.2);">Inactiva</span>';
            return `<div class="est-card">
                <div class="est-card-header" style="background:${esc(e.color)};">
                    ${estCircuitoSVG(idx)}

                    <div>
                        <h3>${esc(e.nombre)}</h3>
                        ${e.descripcion ? `<p>${esc(e.descripcion)}</p>` : ''}
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                        <div class="est-icon"><i class="ti ${esc(e.icono)}"></i></div>
                        ${activaBadge}
                    </div>
                </div>
                <div class="est-card-body">
<p class="est-label-sub" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin:0 0 6px;">Categorías asignadas</p>                    <div class="est-tag-group">${cats}</div>
<p class="est-label-sub" style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin:8px 0 6px;">Encargados</p>                    <div class="est-tag-group">${usrs}</div>
                </div>
                <div class="est-card-footer">
                    <button class="btn-est btn-est-edit" data-id="${e.id}" onclick="editarEst(${e.id})"><i class="ti ti-pencil"></i> Editar</button>
                    <button class="btn-est btn-est-del" data-id="${e.id}" onclick="confirmarEliminarEst(${e.id}, '${esc(e.nombre)}')"><i class="ti ti-trash"></i> Eliminar</button>
                </div>
            </div>`;
        }).join('');
    }

    // ── Cargar datos ───────────────────────────────────────────────
    async function cargar() {
        const d = await apiGet();
        if (!d.ok) { showMsg(d.mensaje || 'Error al cargar', true); return; }
        datos = d;
        renderGrid();
        renderCheckboxes();
    }

    // ── Checkboxes ─────────────────────────────────────────────────
    function renderCheckboxes(selectedCats = [], selectedUsrs = []) {
        const gridCats = document.getElementById('checkCategorias');
        const gridUsrs = document.getElementById('checkUsuarios');

        gridCats.innerHTML = datos.todas_categorias.length
            ? datos.todas_categorias.map(c => {
                const checked = selectedCats.includes(c.id);
                return `<label class="est-check-item ${checked ? 'checked' : ''}" data-id="${c.id}" data-group="cat">
                    <input type="checkbox" value="${c.id}" ${checked ? 'checked' : ''}> <span>${esc(c.nombre)}</span>
                </label>`;
            }).join('')
            : '<p style="color:#94a3b8;font-size:12px;">No hay categorías creadas.</p>';

        gridUsrs.innerHTML = datos.todos_usuarios.length
            ? datos.todos_usuarios.map(u => {
                const checked = selectedUsrs.includes(u.id);
                return `<label class="est-check-item ${checked ? 'checked' : ''}" data-id="${u.id}" data-group="usr">
                    <input type="checkbox" value="${u.id}" ${checked ? 'checked' : ''}> <span>${esc(u.nombre)} <small style="opacity:.6;">(${esc(u.rol)})</small></span>
                </label>`;
            }).join('')
            : '<p style="color:#94a3b8;font-size:12px;">No hay usuarios.</p>';

        // Toggle clase checked al hacer clic
        document.querySelectorAll('.est-check-item').forEach(lbl => {
            lbl.addEventListener('click', () => {
                const cb = lbl.querySelector('input[type=checkbox]');
                cb.checked = !cb.checked;
                lbl.classList.toggle('checked', cb.checked);
            });
        });
    }

    function getCheckedIds(group) {
        return [...document.querySelectorAll(`.est-check-item[data-group="${group}"] input:checked`)]
            .map(el => Number(el.value));
    }

    // ── Abrir modal nuevo ──────────────────────────────────────────
    function abrirModalNuevo() {
        document.getElementById('estId').value = '';
        document.getElementById('estNombre').value = '';
        document.getElementById('estDescripcion').value = '';
        document.getElementById('estColor').value = '#0f172a';
        document.getElementById('estActiva').value = '1';
        document.getElementById('modalEstTitulo').innerHTML = '<i class="ti ti-plus"></i> Nueva estación';
        actualizarColorPreview('#0f172a');
        seleccionarIcono('ti-chef-hat');
        renderCheckboxes([], []);
        document.getElementById('modalEstacion').classList.add('show');
        document.getElementById('estNombre').focus();
    }

    // ── Editar estación ────────────────────────────────────────────
    window.editarEst = function(id) {
        const e = datos.estaciones.find(x => x.id === id);
        if (!e) return;
        document.getElementById('estId').value = id;
        document.getElementById('estNombre').value = e.nombre;
        document.getElementById('estDescripcion').value = e.descripcion || '';
        document.getElementById('estColor').value = e.color || '#0f172a';
        document.getElementById('estActiva').value = String(e.activa);
        document.getElementById('modalEstTitulo').innerHTML = `<i class="ti ti-pencil"></i> Editar: ${esc(e.nombre)}`;
        actualizarColorPreview(e.color || '#0f172a');
        seleccionarIcono(e.icono || 'ti-chef-hat');
        renderCheckboxes(e.categorias.map(c => c.id), e.usuarios.map(u => u.id));
        document.getElementById('modalEstacion').classList.add('show');
    };

    // ── Guardar ────────────────────────────────────────────────────
    document.getElementById('btnGuardarEst').addEventListener('click', async () => {
        const btn = document.getElementById('btnGuardarEst');
        const id = parseInt(document.getElementById('estId').value || '0');
        const nombre = document.getElementById('estNombre').value.trim();
        if (!nombre) { showMsg('El nombre es obligatorio.', true); return; }
        btn.disabled = true;
        try {
            await apiPost({
                accion: id > 0 ? 'actualizar' : 'crear',
                id: id > 0 ? id : undefined,
                nombre,
                descripcion: document.getElementById('estDescripcion').value.trim(),
                color: document.getElementById('estColor').value,
                icono: document.getElementById('estIcono').value,
                activa: parseInt(document.getElementById('estActiva').value),
                categorias: getCheckedIds('cat'),
                usuarios: getCheckedIds('usr'),
            });
            document.getElementById('modalEstacion').classList.remove('show');
            showMsg(id > 0 ? 'Estación actualizada.' : 'Estación creada correctamente.');
            await cargar();
        } catch (e) {
            showMsg(e.message, true);
        } finally { btn.disabled = false; }
    });

    // ── Eliminar ───────────────────────────────────────────────────
    window.confirmarEliminarEst = function(id, nombre) {
        eliminarId = id;
        document.getElementById('confirmEstNombre').textContent = nombre;
        document.getElementById('modalConfirmEst').classList.add('show');
    };

    document.getElementById('btnConfirmarElimEst').addEventListener('click', async () => {
        if (!eliminarId) return;
        try {
            await apiPost({ accion: 'eliminar', id: eliminarId });
            document.getElementById('modalConfirmEst').classList.remove('show');
            eliminarId = null;
            showMsg('Estación eliminada.');
            await cargar();
        } catch (e) { showMsg(e.message, true); }
    });

    document.getElementById('btnCancelarConfirmEst').addEventListener('click', () => {
        document.getElementById('modalConfirmEst').classList.remove('show');
        eliminarId = null;
    });

    // ── Color preview ──────────────────────────────────────────────
    function actualizarColorPreview(color) {
        document.getElementById('colorPreview').style.background = color;
    }
    document.getElementById('estColor').addEventListener('input', function () {
        actualizarColorPreview(this.value);
    });

    // ── Iconos ─────────────────────────────────────────────────────
    function seleccionarIcono(icono) {
        document.getElementById('estIcono').value = icono;
        document.querySelectorAll('.icon-opt').forEach(el => {
            el.classList.toggle('selected', el.dataset.icono === icono);
        });
    }
    document.querySelectorAll('.icon-opt').forEach(el => {
        el.addEventListener('click', () => seleccionarIcono(el.dataset.icono));
    });

    // ── Cerrar modales ─────────────────────────────────────────────
    [document.getElementById('btnCerrarModalEst'), document.getElementById('btnCancelarEst')].forEach(b => {
        b.addEventListener('click', () => document.getElementById('modalEstacion').classList.remove('show'));
    });
    document.getElementById('modalEstacion').addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('show');
    });
    document.getElementById('modalConfirmEst').addEventListener('click', function(e) {
        if (e.target === this) { this.classList.remove('show'); eliminarId = null; }
    });

    // ── Iniciar ────────────────────────────────────────────────────
    document.getElementById('btnNuevaEstacion').addEventListener('click', abrirModalNuevo);
    cargar().catch(e => showMsg(e.message, true));
})();
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
