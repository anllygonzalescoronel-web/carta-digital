<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirLogin();
requerirRol(['admin']);

$tituloPagina = 'Mesas y Zonas';
$paginaActual = 'mesas';
require __DIR__ . '/_layout_top.php';
?>

<style>
.mesas-layout {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 16px;
}

@media (max-width: 980px) {
    .mesas-layout {
        grid-template-columns: 1fr;
    }
}

.mesas-panel,
.mesas-canvas-wrap {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    padding: 14px;
}

.mesas-panel h3,
.mesas-canvas-wrap h3 {
    margin: 0 0 10px;
    color: #0f172a;
}

.mesas-field {
    margin-bottom: 10px;
}

.mesas-field label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 4px;
}

.mesas-field input,
.mesas-field select {
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 8px 10px;
    font-size: 13px;
}

.mesas-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.mesas-btn {
    border: none;
    border-radius: 10px;
    padding: 9px 12px;
    font-weight: 700;
    cursor: pointer;
}

.mesas-btn-primary {
    background: #0f172a;
    color: #fff;
}

.mesas-btn-soft {
    background: #e2e8f0;
    color: #0f172a;
}

.mesas-btn-danger {
    background: #fee2e2;
    color: #b91c1c;
}

.mesas-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

#mesa-board-scroll {
    width: 100%;
    overflow: auto;
    border-radius: 14px;
    border: 1px solid #dbe3ef;
    background:
      radial-gradient(circle at 1px 1px, rgba(148, 163, 184, 0.35) 1px, transparent 0) 0 0 / 28px 28px,
      linear-gradient(135deg, #f8fbff 0%, #f1f5f9 100%);
}

#mesa-board {
    position: relative;
    min-width: 900px;
    min-height: 560px;
}

.mesa-item {
    position: absolute;
    width: 120px;
    min-height: 74px;
    border-radius: 12px;
    background: #ffffff;
    border: 2px solid #94a3b8;
    box-shadow: 0 10px 16px rgba(15, 23, 42, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 8px;
    cursor: grab;
    user-select: none;
}

.mesa-item.redonda {
    border-radius: 999px;
}

.mesa-item.selected {
    border-color: #0f172a;
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.16), 0 10px 16px rgba(15, 23, 42, 0.08);
}

.mesa-item.dragging {
    opacity: 0.85;
    cursor: grabbing;
}

.mesa-item .name {
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
}

.mesa-item .meta {
    display: block;
    margin-top: 2px;
    font-size: 11px;
    color: #64748b;
    font-weight: 700;
}

.mesas-toolbar {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.mesas-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 700;
    background: #e2e8f0;
    color: #1e293b;
}

.mesas-toast {
    margin-top: 10px;
    min-height: 20px;
    font-size: 13px;
    font-weight: 700;
}

.mesas-toast.ok { color: #166534; }
.mesas-toast.error { color: #b91c1c; }

.mesas-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(3px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 1200;
}

.mesas-modal-backdrop.show {
    display: flex;
}

.mesas-modal {
    width: 100%;
    max-width: 520px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.24);
    overflow: hidden;
}

.mesas-modal-head {
    padding: 18px 20px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.mesas-modal-head h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 8px;
}

.mesas-modal-close {
    border: none;
    background: rgba(255,255,255,0.12);
    color: #fff;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.mesas-modal-body {
    padding: 20px;
}

.mesas-modal-body p {
    margin: 0 0 16px;
    color: #475569;
    line-height: 1.6;
    font-size: 13px;
}

.mesas-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 18px;
}

.mesas-danger-box {
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #991b1b;
    border-radius: 14px;
    padding: 14px;
    font-size: 13px;
    line-height: 1.5;
}

.mesas-segment {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 16px;
}

.mesas-segment button {
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: #f8fafc;
    color: #334155;
    font-weight: 800;
    font-size: 12px;
    padding: 11px 12px;
    cursor: pointer;
}

.mesas-segment button.active {
    background: #0f172a;
    color: #fff;
    border-color: #0f172a;
}

.mesa-mode-panel {
    display: none;
}

.mesa-mode-panel.active {
    display: block;
}

body.modo-oscuro .mesas-panel,
body.modo-oscuro .mesas-canvas-wrap {
    background: #1f2430;
    border-color: rgba(255,255,255,0.08);
}

body.modo-oscuro .mesas-panel h3,
body.modo-oscuro .mesas-canvas-wrap h3,
body.modo-oscuro .mesa-item .name {
    color: #f8fafc;
}

body.modo-oscuro .mesa-item {
    background: #111827;
    border-color: #475569;
}

body.modo-oscuro .mesas-field label,
body.modo-oscuro .mesa-item .meta {
    color: #cbd5e1;
}

body.modo-oscuro .mesas-field input,
body.modo-oscuro .mesas-field select {
    background: #0f172a;
    color: #e2e8f0;
    border-color: #334155;
}

body.modo-oscuro .mesas-modal {
    background: #1f2430;
    border-color: rgba(255,255,255,0.08);
}

body.modo-oscuro .mesas-modal-body p {
    color: #cbd5e1;
}

body.modo-oscuro .mesas-danger-box {
    background: rgba(127, 29, 29, 0.22);
    border-color: rgba(248, 113, 113, 0.28);
    color: #fecaca;
}
</style>

<div class="mesas-layout">
    <section class="mesas-panel">
        <h3><i class="ti ti-map-2"></i> Zonas</h3>

        <div class="mesas-field">
            <label>Zona activa</label>
            <select id="zona-select"></select>
        </div>

        <div class="mesas-row">
            <div class="mesas-field">
                <label>Ancho canvas (px)</label>
                <input type="number" id="zona-ancho" min="800" max="2400" value="1200">
            </div>
            <div class="mesas-field">
                <label>Alto canvas (px)</label>
                <input type="number" id="zona-alto" min="500" max="1600" value="700">
            </div>
        </div>

        <div class="mesas-field">
            <label>Nombre de zona</label>
            <input type="text" id="zona-nombre" placeholder="Ej: Salon 2">
        </div>

        <div class="mesas-actions">
            <button type="button" class="mesas-btn mesas-btn-primary" id="btn-zona-guardar">Guardar zona</button>
            <button type="button" class="mesas-btn mesas-btn-soft" id="btn-zona-nueva">Nueva zona</button>
            <button type="button" class="mesas-btn mesas-btn-danger" id="btn-zona-eliminar">Eliminar zona</button>
        </div>

        <hr style="margin:14px 0;border:none;border-top:1px solid #e2e8f0;">

        <h3><i class="ti ti-armchair"></i> Mesas</h3>

        <div class="mesas-field">
            <label>Mesa seleccionada</label>
            <select id="mesa-select"><option value="">(ninguna)</option></select>
        </div>

        <div class="mesas-field">
            <label>Nombre de mesa</label>
            <input type="text" id="mesa-nombre" placeholder="Ej: Mesa 8">
        </div>

        <div class="mesas-row">
            <div class="mesas-field">
                <label>Capacidad</label>
                <input type="number" id="mesa-capacidad" min="1" max="20" value="4">
            </div>
            <div class="mesas-field">
                <label>Sillas</label>
                <input type="number" id="mesa-sillas" min="1" max="20" value="4">
            </div>
        </div>

        <div class="mesas-row">
            <div class="mesas-field">
                <label>Forma</label>
                <select id="mesa-forma">
                    <option value="rectangular">Rectangular</option>
                    <option value="redonda">Redonda</option>
                </select>
            </div>
            <div class="mesas-field">
                <label>Activa</label>
                <select id="mesa-activa">
                    <option value="1">Si</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>

        <div class="mesas-actions">
            <button type="button" class="mesas-btn mesas-btn-primary" id="btn-mesa-guardar">Guardar mesa</button>
            <button type="button" class="mesas-btn mesas-btn-soft" id="btn-mesa-nueva">Nueva mesa</button>
            <button type="button" class="mesas-btn mesas-btn-danger" id="btn-mesa-eliminar">Eliminar mesa</button>
        </div>

        <div id="mesas-toast" class="mesas-toast"></div>
    </section>

    <section class="mesas-canvas-wrap">
        <div class="mesas-toolbar">
            <h3><i class="ti ti-layout-grid"></i> Plano de mesas</h3>
            <div>
                <span class="mesas-badge" id="zona-info">Zona: -</span>
                <button type="button" class="mesas-btn mesas-btn-primary" id="btn-guardar-layout">Guardar posiciones</button>
            </div>
        </div>

        <div id="mesa-board-scroll">
            <div id="mesa-board"></div>
        </div>
    </section>
</div>

<div class="mesas-modal-backdrop" id="modal-zona">
    <div class="mesas-modal" role="dialog" aria-modal="true" aria-labelledby="modal-zona-title">
        <div class="mesas-modal-head">
            <h4 id="modal-zona-title"><i class="ti ti-map-2"></i> Nueva zona</h4>
            <button type="button" class="mesas-modal-close" data-close-modal="modal-zona"><i class="ti ti-x"></i></button>
        </div>
        <div class="mesas-modal-body">
            <p>Configura la nueva zona del salón con su nombre y tamaño del canvas para ubicar las mesas.</p>
            <div class="mesas-field">
                <label>Nombre de la zona</label>
                <input type="text" id="modal-zona-nombre" placeholder="Ej: Terraza, Salón VIP">
            </div>
            <div class="mesas-row">
                <div class="mesas-field">
                    <label>Ancho canvas (px)</label>
                    <input type="number" id="modal-zona-ancho" min="800" max="2400" value="1200">
                </div>
                <div class="mesas-field">
                    <label>Alto canvas (px)</label>
                    <input type="number" id="modal-zona-alto" min="500" max="1600" value="700">
                </div>
            </div>
            <div class="mesas-modal-actions">
                <button type="button" class="mesas-btn mesas-btn-soft" data-close-modal="modal-zona">Cancelar</button>
                <button type="button" class="mesas-btn mesas-btn-primary" id="btn-modal-zona-crear">Crear zona</button>
            </div>
        </div>
    </div>
</div>

<div class="mesas-modal-backdrop" id="modal-mesa">
    <div class="mesas-modal" role="dialog" aria-modal="true" aria-labelledby="modal-mesa-title">
        <div class="mesas-modal-head">
            <h4 id="modal-mesa-title"><i class="ti ti-armchair"></i> Nueva mesa</h4>
            <button type="button" class="mesas-modal-close" data-close-modal="modal-mesa"><i class="ti ti-x"></i></button>
        </div>
        <div class="mesas-modal-body">
            <p>Crea una nueva mesa dentro de la zona activa. Luego podrás moverla directamente en el plano.</p>
            <div class="mesas-segment">
                <button type="button" class="active" id="mesa-mode-unitaria">Una mesa</button>
                <button type="button" id="mesa-mode-lote">Varias mesas</button>
            </div>

            <div class="mesa-mode-panel active" id="mesa-panel-unitaria">
                <div class="mesas-field">
                    <label>Nombre de mesa</label>
                    <input type="text" id="modal-mesa-nombre" placeholder="Ej: Mesa 8">
                </div>
            </div>

            <div class="mesa-mode-panel" id="mesa-panel-lote">
                <div class="mesas-field">
                    <label>Prefijo para las mesas</label>
                    <input type="text" id="modal-mesa-prefijo" value="Mesa" placeholder="Ej: Mesa, VIP, Terraza">
                </div>
                <div class="mesas-row">
                    <div class="mesas-field">
                        <label>Cantidad</label>
                        <input type="number" id="modal-mesa-cantidad" min="1" max="100" value="10">
                    </div>
                    <div class="mesas-field">
                        <label>Numeración inicial</label>
                        <input type="number" id="modal-mesa-inicio" min="1" max="9999" value="1">
                    </div>
                </div>
            </div>

            <div class="mesas-row">
                <div class="mesas-field">
                    <label>Capacidad</label>
                    <input type="number" id="modal-mesa-capacidad" min="1" max="20" value="4">
                </div>
                <div class="mesas-field">
                    <label>Sillas</label>
                    <input type="number" id="modal-mesa-sillas" min="1" max="20" value="4">
                </div>
            </div>
            <div class="mesas-field">
                <label>Forma</label>
                <select id="modal-mesa-forma">
                    <option value="rectangular">Rectangular</option>
                    <option value="redonda">Redonda</option>
                </select>
            </div>
            <div class="mesas-modal-actions">
                <button type="button" class="mesas-btn mesas-btn-soft" data-close-modal="modal-mesa">Cancelar</button>
                <button type="button" class="mesas-btn mesas-btn-primary" id="btn-modal-mesa-crear">Crear</button>
            </div>
        </div>
    </div>
</div>

<div class="mesas-modal-backdrop" id="modal-confirmacion">
    <div class="mesas-modal" role="dialog" aria-modal="true" aria-labelledby="modal-confirmacion-title">
        <div class="mesas-modal-head">
            <h4 id="modal-confirmacion-title"><i class="ti ti-alert-triangle"></i> Confirmar acción</h4>
            <button type="button" class="mesas-modal-close" data-close-modal="modal-confirmacion"><i class="ti ti-x"></i></button>
        </div>
        <div class="mesas-modal-body">
            <p id="modal-confirmacion-texto">¿Deseas continuar?</p>
            <div class="mesas-danger-box" id="modal-confirmacion-extra" style="display:none;"></div>
            <div class="mesas-modal-actions">
                <button type="button" class="mesas-btn mesas-btn-soft" data-close-modal="modal-confirmacion">Cancelar</button>
                <button type="button" class="mesas-btn mesas-btn-danger" id="btn-confirmacion-aceptar">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const API = '../api/mesas_layout.php';

    let zonas = [];
    let zonaActualId = 0;
    let mesaActualId = 0;

    const zonaSelect = document.getElementById('zona-select');
    const zonaNombre = document.getElementById('zona-nombre');
    const zonaAncho = document.getElementById('zona-ancho');
    const zonaAlto = document.getElementById('zona-alto');
    const zonaInfo = document.getElementById('zona-info');

    const mesaSelect = document.getElementById('mesa-select');
    const mesaNombre = document.getElementById('mesa-nombre');
    const mesaCapacidad = document.getElementById('mesa-capacidad');
    const mesaSillas = document.getElementById('mesa-sillas');
    const mesaForma = document.getElementById('mesa-forma');
    const mesaActiva = document.getElementById('mesa-activa');

    const board = document.getElementById('mesa-board');
    const toast = document.getElementById('mesas-toast');
    const modalZona = document.getElementById('modal-zona');
    const modalMesa = document.getElementById('modal-mesa');
    const modalConfirmacion = document.getElementById('modal-confirmacion');
    const modalConfirmacionTexto = document.getElementById('modal-confirmacion-texto');
    const modalConfirmacionExtra = document.getElementById('modal-confirmacion-extra');
    const btnConfirmacionAceptar = document.getElementById('btn-confirmacion-aceptar');
    const btnMesaModeUnitaria = document.getElementById('mesa-mode-unitaria');
    const btnMesaModeLote = document.getElementById('mesa-mode-lote');
    const mesaPanelUnitaria = document.getElementById('mesa-panel-unitaria');
    const mesaPanelLote = document.getElementById('mesa-panel-lote');
    let confirmAction = null;
    let mesaCreateMode = 'unitaria';

    function showToast(msg, isError = false) {
        toast.textContent = msg;
        toast.className = 'mesas-toast ' + (isError ? 'error' : 'ok');
        setTimeout(() => {
            toast.textContent = '';
            toast.className = 'mesas-toast';
        }, 2800);
    }

    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('show');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('show');
    }

    function openConfirmModal({ text, extra = '', buttonText = 'Eliminar', onConfirm }) {
        modalConfirmacionTexto.textContent = text;
        modalConfirmacionExtra.style.display = extra ? 'block' : 'none';
        modalConfirmacionExtra.textContent = extra || '';
        btnConfirmacionAceptar.textContent = buttonText;
        confirmAction = onConfirm;
        openModal(modalConfirmacion);
    }

    function setMesaCreateMode(mode) {
        mesaCreateMode = mode === 'lote' ? 'lote' : 'unitaria';
        btnMesaModeUnitaria.classList.toggle('active', mesaCreateMode === 'unitaria');
        btnMesaModeLote.classList.toggle('active', mesaCreateMode === 'lote');
        mesaPanelUnitaria.classList.toggle('active', mesaCreateMode === 'unitaria');
        mesaPanelLote.classList.toggle('active', mesaCreateMode === 'lote');
        document.getElementById('btn-modal-mesa-crear').textContent = mesaCreateMode === 'lote' ? 'Crear mesas' : 'Crear mesa';
    }

    async function apiPost(payload) {
        const r = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await r.json();
        if (!data.ok) {
            throw new Error(data.mensaje || 'Operacion no completada');
        }
        return data;
    }

    function getZonaActual() {
        return zonas.find((z) => Number(z.id) === Number(zonaActualId)) || null;
    }

    function getMesaActual() {
        const zona = getZonaActual();
        if (!zona) return null;
        return (zona.mesas || []).find((m) => Number(m.id) === Number(mesaActualId)) || null;
    }

    function renderZonas() {
        const opts = zonas.map((z) => `<option value="${z.id}">${z.nombre}</option>`);
        zonaSelect.innerHTML = opts.join('');

        if (!zonas.length) {
            zonaSelect.innerHTML = '<option value="">Sin zonas</option>';
            zoneClear();
            return;
        }

        if (!zonas.some((z) => Number(z.id) === Number(zonaActualId))) {
            zonaActualId = Number(zonas[0].id);
        }

        zonaSelect.value = String(zonaActualId);
        loadZonaForm();
        renderMesas();
    }

    function zoneClear() {
        zonaNombre.value = '';
        zonaAncho.value = '1200';
        zonaAlto.value = '700';
        zonaInfo.textContent = 'Zona: -';
        board.innerHTML = '';
        board.style.width = '1200px';
        board.style.height = '700px';
        mesaSelect.innerHTML = '<option value="">(ninguna)</option>';
    }

    function loadZonaForm() {
        const zona = getZonaActual();
        if (!zona) {
            zoneClear();
            return;
        }
        zonaNombre.value = zona.nombre;
        zonaAncho.value = zona.ancho;
        zonaAlto.value = zona.alto;
        zonaInfo.textContent = `Zona: ${zona.nombre}`;
        board.style.width = `${Number(zona.ancho)}px`;
        board.style.height = `${Number(zona.alto)}px`;
    }

    function renderMesas() {
        const zona = getZonaActual();
        board.innerHTML = '';
        mesaSelect.innerHTML = '<option value="">(ninguna)</option>';
        mesaActualId = 0;

        if (!zona) return;

        const mesasOrdenadas = [...(zona.mesas || [])].sort((a, b) => Number(a.orden) - Number(b.orden));

        mesasOrdenadas.forEach((m) => {
            const op = document.createElement('option');
            op.value = m.id;
            op.textContent = `${m.nombre} (${m.sillas} sillas)`;
            mesaSelect.appendChild(op);

            const el = document.createElement('div');
            el.className = 'mesa-item ' + (m.forma === 'redonda' ? 'redonda' : 'rectangular');
            if (Number(m.activa) !== 1) {
                el.style.opacity = '0.45';
            }
            el.dataset.id = m.id;
            el.style.left = `${Number(m.pos_x)}px`;
            el.style.top = `${Number(m.pos_y)}px`;
            el.innerHTML = `<div><span class="name">${m.nombre}</span><span class="meta">${m.sillas} sillas</span></div>`;

            makeDraggable(el, m, zona);

            el.addEventListener('click', () => {
                seleccionarMesa(m.id);
            });

            board.appendChild(el);
        });
    }

    function seleccionarMesa(idMesa) {
        mesaActualId = Number(idMesa);

        board.querySelectorAll('.mesa-item').forEach((el) => {
            el.classList.toggle('selected', Number(el.dataset.id) === mesaActualId);
        });

        const mesa = getMesaActual();
        if (!mesa) {
            mesaSelect.value = '';
            mesaNombre.value = '';
            mesaCapacidad.value = 4;
            mesaSillas.value = 4;
            mesaForma.value = 'rectangular';
            mesaActiva.value = '1';
            return;
        }

        mesaSelect.value = String(mesa.id);
        mesaNombre.value = mesa.nombre;
        mesaCapacidad.value = mesa.capacidad;
        mesaSillas.value = mesa.sillas;
        mesaForma.value = mesa.forma;
        mesaActiva.value = String(mesa.activa);
    }

    function makeDraggable(el, mesa, zona) {
        let dragging = false;
        let startX = 0;
        let startY = 0;
        let left = 0;
        let top = 0;

        const onMove = (ev) => {
            if (!dragging) return;
            const e = ev.touches ? ev.touches[0] : ev;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            const maxX = Math.max(0, Number(zona.ancho) - el.offsetWidth);
            const maxY = Math.max(0, Number(zona.alto) - el.offsetHeight);

            const x = Math.max(0, Math.min(maxX, left + dx));
            const y = Math.max(0, Math.min(maxY, top + dy));

            el.style.left = `${Math.round(x)}px`;
            el.style.top = `${Math.round(y)}px`;
        };

        const onUp = () => {
            if (!dragging) return;
            dragging = false;
            el.classList.remove('dragging');
            mesa.pos_x = Number(el.style.left.replace('px', ''));
            mesa.pos_y = Number(el.style.top.replace('px', ''));
            window.removeEventListener('mousemove', onMove);
            window.removeEventListener('mouseup', onUp);
            window.removeEventListener('touchmove', onMove);
            window.removeEventListener('touchend', onUp);
        };

        const onDown = (ev) => {
            if (ev.button !== undefined && ev.button !== 0) return;
            dragging = true;
            el.classList.add('dragging');
            const e = ev.touches ? ev.touches[0] : ev;
            startX = e.clientX;
            startY = e.clientY;
            left = Number(el.style.left.replace('px', ''));
            top = Number(el.style.top.replace('px', ''));
            seleccionarMesa(mesa.id);
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
            window.addEventListener('touchmove', onMove, { passive: true });
            window.addEventListener('touchend', onUp);
        };

        el.addEventListener('mousedown', onDown);
        el.addEventListener('touchstart', onDown, { passive: true });
    }

    async function cargarLayout() {
        const r = await fetch(API, { headers: { Accept: 'application/json' } });
        const data = await r.json();
        if (!data.ok) {
            throw new Error(data.mensaje || 'No se pudo cargar layout');
        }

        zonas = Array.isArray(data.zonas) ? data.zonas : [];
        renderZonas();
    }

    async function guardarPosiciones() {
        const zona = getZonaActual();
        if (!zona) {
            showToast('Selecciona una zona', true);
            return;
        }

        const mesas = (zona.mesas || []).map((m, idx) => ({
            id: m.id,
            pos_x: Number(m.pos_x) || 0,
            pos_y: Number(m.pos_y) || 0,
            orden: idx + 1,
        }));

        try {
            await apiPost({ accion: 'layout_guardar', zona_id: zona.id, mesas });
            showToast('Posiciones guardadas');
        } catch (e) {
            showToast(e.message, true);
        }
    }

    async function crearZona() {
        try {
            const nombre = document.getElementById('modal-zona-nombre').value.trim();
            const ancho = Number(document.getElementById('modal-zona-ancho').value || 1200);
            const alto = Number(document.getElementById('modal-zona-alto').value || 700);
            if (!nombre) {
                showToast('Ingresa el nombre de la zona', true);
                return;
            }
            await apiPost({ accion: 'zona_crear', nombre, ancho, alto });
            closeModal(modalZona);
            document.getElementById('modal-zona-nombre').value = '';
            await cargarLayout();
            showToast('Zona creada');
        } catch (e) {
            showToast(e.message, true);
        }
    }

    async function guardarZona() {
        const zona = getZonaActual();
        if (!zona) {
            showToast('No hay zona seleccionada', true);
            return;
        }

        try {
            await apiPost({
                accion: 'zona_actualizar',
                id: zona.id,
                nombre: zonaNombre.value.trim(),
                ancho: Number(zonaAncho.value || 1200),
                alto: Number(zonaAlto.value || 700),
                activa: 1,
            });
            await cargarLayout();
            zonaActualId = Number(zona.id);
            renderZonas();
            showToast('Zona actualizada');
        } catch (e) {
            showToast(e.message, true);
        }
    }

    async function eliminarZona() {
        const zona = getZonaActual();
        if (!zona) return;
        openConfirmModal({
            text: `¿Eliminar la zona ${zona.nombre}?`,
            extra: 'Esta acción eliminará también todas las mesas asociadas a esa zona.',
            buttonText: 'Eliminar zona',
            onConfirm: async () => {
                try {
                    await apiPost({ accion: 'zona_eliminar', id: zona.id });
                    zonaActualId = 0;
                    closeModal(modalConfirmacion);
                    await cargarLayout();
                    showToast('Zona eliminada');
                } catch (e) {
                    showToast(e.message, true);
                }
            }
        });
    }

    async function crearMesa() {
        const zona = getZonaActual();
        if (!zona) {
            showToast('Primero crea o selecciona una zona', true);
            return;
        }

        try {
            const capacidad = Number(document.getElementById('modal-mesa-capacidad').value || 4);
            const sillas = Number(document.getElementById('modal-mesa-sillas').value || 4);
            const forma = document.getElementById('modal-mesa-forma').value;

            if (mesaCreateMode === 'lote') {
                const prefijo = document.getElementById('modal-mesa-prefijo').value.trim() || 'Mesa';
                const cantidad = Number(document.getElementById('modal-mesa-cantidad').value || 1);
                const inicio = Number(document.getElementById('modal-mesa-inicio').value || 1);
                await apiPost({
                    accion: 'mesa_crear_lote',
                    zona_id: zona.id,
                    prefijo,
                    cantidad,
                    inicio,
                    capacidad,
                    sillas,
                    forma,
                });
            } else {
                const nombre = document.getElementById('modal-mesa-nombre').value.trim() || `Mesa ${(zona.mesas || []).length + 1}`;
                await apiPost({
                    accion: 'mesa_crear',
                    zona_id: zona.id,
                    nombre,
                    capacidad,
                    sillas,
                    forma,
                    pos_x: 80,
                    pos_y: 80,
                });
            }

            closeModal(modalMesa);
            document.getElementById('modal-mesa-nombre').value = '';
            document.getElementById('modal-mesa-prefijo').value = 'Mesa';
            document.getElementById('modal-mesa-cantidad').value = '10';
            document.getElementById('modal-mesa-inicio').value = '1';
            await cargarLayout();
            zonaActualId = Number(zona.id);
            renderZonas();
            showToast(mesaCreateMode === 'lote' ? 'Mesas creadas' : 'Mesa creada');
        } catch (e) {
            showToast(e.message, true);
        }
    }

    async function guardarMesa() {
        const mesa = getMesaActual();
        if (!mesa) {
            showToast('Selecciona una mesa', true);
            return;
        }

        try {
            await apiPost({
                accion: 'mesa_actualizar',
                id: mesa.id,
                nombre: mesaNombre.value.trim(),
                capacidad: Number(mesaCapacidad.value || 4),
                sillas: Number(mesaSillas.value || 4),
                forma: mesaForma.value,
                activa: Number(mesaActiva.value || 1),
            });
            await cargarLayout();
            zonaActualId = Number(zonaSelect.value || 0);
            mesaActualId = Number(mesa.id);
            renderZonas();
            seleccionarMesa(mesaActualId);
            showToast('Mesa actualizada');
        } catch (e) {
            showToast(e.message, true);
        }
    }

    async function eliminarMesa() {
        const mesa = getMesaActual();
        if (!mesa) {
            showToast('Selecciona una mesa', true);
            return;
        }
        openConfirmModal({
            text: `¿Eliminar la mesa ${mesa.nombre}?`,
            extra: 'La mesa se quitará del plano de esta zona.',
            buttonText: 'Eliminar mesa',
            onConfirm: async () => {
                try {
                    await apiPost({ accion: 'mesa_eliminar', id: mesa.id });
                    closeModal(modalConfirmacion);
                    await cargarLayout();
                    zonaActualId = Number(zonaSelect.value || 0);
                    showToast('Mesa eliminada');
                } catch (e) {
                    showToast(e.message, true);
                }
            }
        });
    }

    zonaSelect.addEventListener('change', () => {
        zonaActualId = Number(zonaSelect.value || 0);
        loadZonaForm();
        renderMesas();
    });

    mesaSelect.addEventListener('change', () => {
        seleccionarMesa(Number(mesaSelect.value || 0));
    });

    document.getElementById('btn-guardar-layout').addEventListener('click', guardarPosiciones);
    document.getElementById('btn-zona-nueva').addEventListener('click', () => {
        document.getElementById('modal-zona-nombre').value = '';
        document.getElementById('modal-zona-ancho').value = '1200';
        document.getElementById('modal-zona-alto').value = '700';
        openModal(modalZona);
    });
    document.getElementById('btn-zona-guardar').addEventListener('click', guardarZona);
    document.getElementById('btn-zona-eliminar').addEventListener('click', eliminarZona);
    document.getElementById('btn-mesa-nueva').addEventListener('click', () => {
        const zona = getZonaActual();
        if (!zona) {
            showToast('Primero crea o selecciona una zona', true);
            return;
        }
        setMesaCreateMode('unitaria');
        document.getElementById('modal-mesa-nombre').value = `Mesa ${(zona.mesas || []).length + 1}`;
        document.getElementById('modal-mesa-prefijo').value = 'Mesa';
        document.getElementById('modal-mesa-cantidad').value = '10';
        document.getElementById('modal-mesa-inicio').value = String((zona.mesas || []).length + 1);
        document.getElementById('modal-mesa-capacidad').value = String(mesaCapacidad.value || 4);
        document.getElementById('modal-mesa-sillas').value = String(mesaSillas.value || 4);
        document.getElementById('modal-mesa-forma').value = mesaForma.value || 'rectangular';
        openModal(modalMesa);
    });
    document.getElementById('btn-mesa-guardar').addEventListener('click', guardarMesa);
    document.getElementById('btn-mesa-eliminar').addEventListener('click', eliminarMesa);
    document.getElementById('btn-modal-zona-crear').addEventListener('click', crearZona);
    document.getElementById('btn-modal-mesa-crear').addEventListener('click', crearMesa);
    btnMesaModeUnitaria.addEventListener('click', () => setMesaCreateMode('unitaria'));
    btnMesaModeLote.addEventListener('click', () => setMesaCreateMode('lote'));
    btnConfirmacionAceptar.addEventListener('click', async () => {
        if (typeof confirmAction === 'function') {
            await confirmAction();
            confirmAction = null;
        }
    });

    document.querySelectorAll('[data-close-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
            closeModal(document.getElementById(btn.getAttribute('data-close-modal')));
        });
    });

    [modalZona, modalMesa, modalConfirmacion].forEach((modal) => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });

    cargarLayout().catch((e) => showToast(e.message, true));
})();
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
