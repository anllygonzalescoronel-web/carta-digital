<?php
$tituloPagina = 'Ingredientes y Stock';
$paginaActual = 'ingredientes';
require __DIR__ . '/_layout_top.php';
?>
<style>
/* ── Layout ─────────────────────────────────────────────────── */
.ing-header { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.ing-header h2 { margin:0; font-size:1.3rem; font-weight:700; }
.ing-search { padding:8px 14px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:.93rem; width:220px; }
.ing-search:focus { outline:none; border-color:#6366f1; }

/* ── Stats ──────────────────────────────────────────────────── */
.ing-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-bottom:20px; }
.ing-stat { background:#fff; border-radius:10px; padding:14px 16px; border:1px solid #e2e8f0; }
.ing-stat-val { font-size:1.7rem; font-weight:800; line-height:1; }
.ing-stat-label { font-size:.78rem; color:#64748b; margin-top:4px; }
.ing-stat.alerta .ing-stat-val { color:#ef4444; }
.ing-stat.ok .ing-stat-val { color:#22c55e; }
.ing-stat.total .ing-stat-val { color:#6366f1; }

/* ── Tabla ───────────────────────────────────────────────────── */
.ing-table-wrap { background:#fff; border-radius:12px; border:1px solid #e2e8f0; overflow:hidden; }
.ing-table { width:100%; border-collapse:collapse; font-size:.9rem; }
.ing-table thead { background:#f8fafc; }
.ing-table th { padding:11px 14px; text-align:left; font-weight:600; color:#475569; font-size:.8rem; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid #e2e8f0; }
.ing-table td { padding:11px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.ing-table tbody tr:hover { background:#fafbfc; }
.ing-table tbody tr:last-child td { border-bottom:none; }

/* ── Stock bar ───────────────────────────────────────────────── */
.stock-bar-wrap { display:flex; align-items:center; gap:8px; min-width:140px; }
.stock-bar { flex:1; height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden; }
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
.ing-actions { display:flex; gap:6px; }
.btn-ing { display:inline-flex; align-items:center; gap:4px; padding:5px 10px; border-radius:7px; border:none; cursor:pointer; font-size:.8rem; font-weight:600; transition:opacity .15s; }
.btn-ing:hover { opacity:.85; }
.btn-ing-edit  { background:#6366f1; color:#fff; }
.btn-ing-stock { background:#0ea5e9; color:#fff; }
.btn-ing-hist  { background:#f1f5f9; color:#475569; }
.btn-ing-del   { background:#ef4444; color:#fff; }
.btn-ing-ing   { background:#22c55e; color:#fff; }

/* ── Modal ───────────────────────────────────────────────────── */
.ing-modal-backdrop { display:none; position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:1000; align-items:center; justify-content:center; }
.ing-modal-backdrop.abierto { display:flex; }
.ing-modal { background:#fff; border-radius:14px; padding:28px; width:100%; max-width:520px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.18); position:relative; }
.ing-modal h3 { margin:0 0 18px; font-size:1.15rem; font-weight:700; }
.ing-modal-close { position:absolute; top:14px; right:14px; background:none; border:none; cursor:pointer; color:#94a3b8; font-size:1.2rem; }
.ing-modal-close:hover { color:#0f172a; }
.ing-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.ing-form-grid .full { grid-column:1/-1; }
.ing-label { display:block; font-size:.8rem; font-weight:600; color:#475569; margin-bottom:4px; }
.ing-input { width:100%; padding:8px 12px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:.93rem; box-sizing:border-box; }
.ing-input:focus { outline:none; border-color:#6366f1; }
.ing-select { width:100%; padding:8px 12px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:.93rem; background:#fff; box-sizing:border-box; }
.ing-modal-footer { display:flex; gap:10px; margin-top:20px; }
.ing-modal-footer .btn-save { flex:1; padding:10px; background:#6366f1; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer; }
.ing-modal-footer .btn-save:hover { background:#4f46e5; }
.ing-modal-footer .btn-cancel { padding:10px 18px; background:#f1f5f9; color:#475569; border:none; border-radius:8px; font-weight:600; cursor:pointer; }

/* ── Modal stock ─────────────────────────────────────────────── */
.stock-tipo-tabs { display:flex; gap:8px; margin-bottom:16px; }
.stock-tipo-btn { flex:1; padding:8px; border:2px solid #e2e8f0; border-radius:8px; background:#fff; cursor:pointer; font-weight:600; font-size:.85rem; text-align:center; transition:all .15s; }
.stock-tipo-btn.activo[data-tipo="entrada"] { border-color:#22c55e; background:#f0fdf4; color:#15803d; }
.stock-tipo-btn.activo[data-tipo="salida"]  { border-color:#ef4444; background:#fef2f2; color:#b91c1c; }
.stock-tipo-btn.activo[data-tipo="ajuste"]  { border-color:#f59e0b; background:#fffbeb; color:#b45309; }
.stock-actual-display { background:#f8fafc; border-radius:8px; padding:10px 14px; margin-bottom:14px; display:flex; justify-content:space-between; align-items:center; }
.stock-actual-display .label { font-size:.8rem; color:#64748b; }
.stock-actual-display .valor { font-size:1.2rem; font-weight:800; color:#0f172a; }

/* ── Historial ───────────────────────────────────────────────── */
.hist-list { display:flex; flex-direction:column; gap:6px; max-height:340px; overflow-y:auto; }
.hist-item { display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; background:#f8fafc; border-left:3px solid #e2e8f0; }
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
</style>

<div class="ing-header">
    <h2><i class="ti ti-packages"></i> Ingredientes y Stock</h2>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <input type="text" class="ing-search" id="ingSearch" placeholder="Buscar ingrediente…">
        <button class="btn-nuevo" onclick="abrirModalCrear()">+ Nuevo ingrediente</button>
    </div>
</div>

<!-- Stats -->
<div class="ing-stats" id="ingStats">
    <div class="ing-stat total"><div class="ing-stat-val" id="statTotal">—</div><div class="ing-stat-label">Total ingredientes</div></div>
    <div class="ing-stat alerta"><div class="ing-stat-val" id="statBajoStock">—</div><div class="ing-stat-label">Bajo stock / agotados</div></div>
    <div class="ing-stat ok"><div class="ing-stat-val" id="statOk">—</div><div class="ing-stat-label">Stock OK</div></div>
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
