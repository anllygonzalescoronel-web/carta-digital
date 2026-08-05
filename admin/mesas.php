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
    background: transparent;
    border: none;
    box-shadow: none;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 0;
    cursor: grab;
    user-select: none;
    overflow: visible;
}

.mesa-item.redonda {
    border-radius: 999px;
}

.mesa-item.selected {
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.22), 0 12px 22px rgba(15, 23, 42, 0.14);
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

.mesa-tablero {
    position: absolute;
    inset: 0;
    border-radius: 8px;
    border: 2px solid #334155;
    background:
      repeating-linear-gradient(0deg, rgba(71, 85, 105, 0.22) 0 1px, rgba(255,255,255,0.95) 1px 6px),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
    z-index: 2;
}

.mesa-item.redonda .mesa-tablero {
    border-radius: 999px;
}

.mesa-item.rectangular .mesa-tablero {
    border-radius: 6px;
}

.mesa-sillas-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 3;
}

.mesa-silla {
    position: absolute;
    width: 15px;
    height: 9px;
    border-radius: 4px;
    border: 2px solid #334155;
    background: #ffffff;
    box-shadow: none;
    transform-origin: center center;
}

.mesa-silla::after {
    content: '';
    position: absolute;
    width: 12px;
    height: 9px;
    left: 50%;
    top: -10px;
    transform: translateX(-50%);
    border-radius: 8px 8px 0 0;
    border: 2px solid #334155;
    border-bottom: none;
    background: transparent;
}

.mesa-silla::before {
    content: none;
}

.mesa-item.redonda .mesa-silla {
    width: 12px;
    height: 12px;
    border-radius: 999px;
}

.mesa-item.redonda .mesa-silla::after {
    width: 10px;
    height: 4px;
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

.mesa-size-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.mesa-elementos-box {
    border: 1px solid #dbe3ef;
    border-radius: 14px;
    padding: 10px;
    background: #f8fbff;
}

.mesa-elementos-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
}

.mesa-elementos-head span {
    font-size: 12px;
    color: #64748b;
    font-weight: 700;
}

.mesa-elementos-lista {
    display: grid;
    gap: 8px;
}

.mesa-elemento-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid #dbe3ef;
}

.mesa-elemento-miniatura {
    width: 58px;
    height: 58px;
    border-radius: 14px;
    border: 1px solid #dbe3ef;
    background: #ffffff;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    overflow: hidden;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.7);
}

.mesa-elemento-miniatura img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mesa-elemento-miniatura .mini-texto {
    padding: 6px;
    font-size: 11px;
    font-weight: 900;
    text-align: center;
    line-height: 1.1;
    color: #0f172a;
}

.mesa-elemento-miniatura .mini-icono {
    font-size: 22px;
    line-height: 1;
}

.mesa-elemento-item strong {
    display: block;
    color: #0f172a;
    font-size: 13px;
}

.mesa-elemento-item small {
    display: block;
    color: #64748b;
    font-size: 11px;
    margin-top: 2px;
}

.mesa-elemento-actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
}

.mesa-elemento-btn {
    border: none;
    border-radius: 10px;
    width: 32px;
    height: 32px;
    cursor: pointer;
    background: #e2e8f0;
    color: #0f172a;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.mesa-elemento-btn.danger {
    background: #fee2e2;
    color: #b91c1c;
}

.mesa-overlay-layer {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.mesa-overlay-element {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: auto;
    cursor: move;
    user-select: none;
}

.mesa-overlay-element .overlay-inner {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.mesa-overlay-element .overlay-texto {
    font-weight: 800;
    text-align: center;
    line-height: 1.1;
}

.mesa-overlay-element .overlay-icono {
    font-size: inherit;
    line-height: 1;
}

.mesa-overlay-element img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.mesa-overlay-element.selected {
    outline: 2px solid rgba(15, 23, 42, 0.55);
    outline-offset: 2px;
}

.mesa-zona-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 30;
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
    background: transparent;
}

body.modo-oscuro .mesa-tablero {
        border-color: #cbd5e1;
    background:
            repeating-linear-gradient(0deg, rgba(148, 163, 184, 0.2) 0 1px, rgba(30, 41, 59, 0.94) 1px 6px),
      linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        box-shadow: 0 2px 6px rgba(2, 6, 23, 0.45);
}

body.modo-oscuro .mesa-silla {
        background: #1e293b;
    border-color: #cbd5e1;
}

body.modo-oscuro .mesa-silla::after {
    border-color: #cbd5e1;
    background: transparent;
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

body.modo-oscuro .mesa-elementos-box,
body.modo-oscuro .mesa-elemento-item {
    background: #0f172a;
    border-color: #334155;
}

body.modo-oscuro .mesa-elemento-item strong,
body.modo-oscuro .mesa-elementos-head span {
    color: #e2e8f0;
}

body.modo-oscuro .mesa-elemento-item small {
    color: #94a3b8;
}

body.modo-oscuro .mesa-elemento-miniatura {
    background: #0f172a;
    border-color: #334155;
}

body.modo-oscuro .mesa-elemento-miniatura .mini-texto {
    color: #e2e8f0;
}

body.modo-oscuro .mesa-elemento-btn {
    background: #334155;
    color: #e2e8f0;
}

body.modo-oscuro .mesa-elemento-btn.danger {
    background: rgba(127, 29, 29, 0.32);
    color: #fecaca;
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
                <input type="number" id="zona-ancho" min="800" max="2400" value="1000">
            </div>
            <div class="mesas-field">
                <label>Alto canvas (px)</label>
                <input type="number" id="zona-alto" min="500" max="1600" value="620">
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

        <div class="mesa-size-grid">
            <div class="mesas-field">
                <label>Ancho mesa (px)</label>
                <input type="number" id="mesa-ancho" min="80" max="420" value="120">
            </div>
            <div class="mesas-field">
                <label>Alto mesa (px)</label>
                <input type="number" id="mesa-alto" min="60" max="320" value="74">
            </div>
        </div>

        <div class="mesas-field">
            <label>Decoraciones</label>
            <div class="mesa-elementos-box">
                <div class="mesa-elementos-head">
                    <button type="button" class="mesas-btn mesas-btn-soft" id="btn-mesa-elemento-agregar">Agregar elemento</button>
                    <span>Texto, iconos o imágenes dentro de la mesa</span>
                </div>
                <div class="mesa-elementos-lista" id="mesa-elementos-lista"></div>
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
                    <input type="number" id="modal-zona-ancho" min="800" max="2400" value="1000">
                </div>
                <div class="mesas-field">
                    <label>Alto canvas (px)</label>
                    <input type="number" id="modal-zona-alto" min="500" max="1600" value="620">
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

<div class="mesas-modal-backdrop" id="modal-elemento">
    <div class="mesas-modal" role="dialog" aria-modal="true" aria-labelledby="modal-elemento-title">
        <div class="mesas-modal-head">
            <h4 id="modal-elemento-title"><i class="ti ti-shape"></i> Elemento de mesa</h4>
            <button type="button" class="mesas-modal-close" data-close-modal="modal-elemento"><i class="ti ti-x"></i></button>
        </div>
        <div class="mesas-modal-body">
            <p>Sube imágenes y luego ajusta su tamaño, posición, rotación y capa dentro del canvas de la zona.</p>
            <input type="hidden" id="modal-elemento-id">
            <div class="mesas-row">
                <div class="mesas-field">
                    <label>Tipo</label>
                    <select id="modal-elemento-tipo" disabled>
                        <option value="imagen">Imagen</option>
                    </select>
                </div>
                <div class="mesas-field">
                    <label>Color</label>
                    <input type="color" id="modal-elemento-color" value="#0f172a">
                </div>
            </div>
            <div class="mesas-field">
                <label>Contenido (interno)</label>
                <input type="text" id="modal-elemento-contenido" placeholder="Ej: VIP, ti ti-star, https://...">
            </div>
            <div class="mesas-field" id="modal-elemento-archivo-wrap" style="display:none;">
                <label>Imagen desde archivo</label>
                <div id="modal-elemento-dropzone" style="border:1px dashed #94a3b8;border-radius:14px;padding:14px;text-align:center;background:#f8fafc;cursor:pointer;">
                    <strong style="display:block;font-size:13px;color:#0f172a;">Arrastra y suelta una imagen aquí</strong>
                    <span style="display:block;margin-top:4px;font-size:12px;color:#64748b;">o haz clic para elegir un archivo</span>
                    <input type="file" id="modal-elemento-archivo" accept="image/*" style="display:none;">
                </div>
                <small style="display:block;margin-top:6px;color:#64748b;font-weight:700;" id="modal-elemento-archivo-actual"></small>
            </div>
            <div class="mesas-row">
                <div class="mesas-field">
                    <label>Fondo</label>
                    <input type="color" id="modal-elemento-fondo" value="#ffffff">
                </div>
                <div class="mesas-field">
                    <label>Tamaño fuente</label>
                    <input type="number" id="modal-elemento-fuente" min="10" max="48" value="18">
                </div>
            </div>
            <div class="mesas-row">
                <div class="mesas-field">
                    <label>Ancho</label>
                    <input type="number" id="modal-elemento-ancho" min="20" max="260" value="48">
                </div>
                <div class="mesas-field">
                    <label>Alto</label>
                    <input type="number" id="modal-elemento-alto" min="20" max="260" value="48">
                </div>
            </div>
            <div class="mesas-row">
                <div class="mesas-field">
                    <label>Rotación (grados)</label>
                    <input type="number" id="modal-elemento-rotacion" min="-180" max="180" value="0">
                    <input type="range" id="modal-elemento-rotacion-slider" min="-180" max="180" value="0" style="width:100%;margin-top:8px;">
                    <small id="modal-elemento-rotacion-valor" style="display:block;margin-top:4px;color:#64748b;font-weight:700;">0°</small>
                </div>
                <div class="mesas-field">
                    <label>Capa</label>
                    <input type="number" id="modal-elemento-capa" min="0" max="9999" value="1">
                </div>
            </div>
            <div class="mesas-row">
                <div class="mesas-field">
                    <label>X</label>
                    <input type="number" id="modal-elemento-x" min="0" max="500" value="10">
                </div>
                <div class="mesas-field">
                    <label>Y</label>
                    <input type="number" id="modal-elemento-y" min="0" max="500" value="10">
                </div>
            </div>
            <div class="mesas-modal-actions">
                <button type="button" class="mesas-btn mesas-btn-danger" id="btn-elemento-eliminar" style="margin-right:auto; display:none;">Eliminar</button>
                <button type="button" class="mesas-btn mesas-btn-soft" id="btn-elemento-duplicar" style="display:none;">Duplicar</button>
                <button type="button" class="mesas-btn mesas-btn-soft" data-close-modal="modal-elemento">Cancelar</button>
                <button type="button" class="mesas-btn mesas-btn-primary" id="btn-elemento-guardar">Guardar elemento</button>
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
    const mesaAncho = document.getElementById('mesa-ancho');
    const mesaAlto = document.getElementById('mesa-alto');
    const mesaElementosLista = document.getElementById('mesa-elementos-lista');

    const modalElemento = document.getElementById('modal-elemento');
    const modalElementoId = document.getElementById('modal-elemento-id');
    const modalElementoTipo = document.getElementById('modal-elemento-tipo');
    const modalElementoContenido = document.getElementById('modal-elemento-contenido');
    const modalElementoColor = document.getElementById('modal-elemento-color');
    const modalElementoFondo = document.getElementById('modal-elemento-fondo');
    const modalElementoFuente = document.getElementById('modal-elemento-fuente');
    const modalElementoAncho = document.getElementById('modal-elemento-ancho');
    const modalElementoAlto = document.getElementById('modal-elemento-alto');
    const modalElementoRotacion = document.getElementById('modal-elemento-rotacion');
    const modalElementoCapa = document.getElementById('modal-elemento-capa');
    const modalElementoX = document.getElementById('modal-elemento-x');
    const modalElementoY = document.getElementById('modal-elemento-y');
    const modalElementoArchivo = document.getElementById('modal-elemento-archivo');
    const modalElementoArchivoWrap = document.getElementById('modal-elemento-archivo-wrap');
    const modalElementoArchivoActual = document.getElementById('modal-elemento-archivo-actual');
    const modalElementoDropzone = document.getElementById('modal-elemento-dropzone');
    const modalElementoRotacionSlider = document.getElementById('modal-elemento-rotacion-slider');
    const modalElementoRotacionValor = document.getElementById('modal-elemento-rotacion-valor');
    const btnElementoGuardar = document.getElementById('btn-elemento-guardar');
    const btnElementoEliminar = document.getElementById('btn-elemento-eliminar');
    const btnElementoDuplicar = document.getElementById('btn-elemento-duplicar');
    let archivoElementoPendiente = null;

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
    let elementoEditandoId = '';

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

    function parseDecoraciones(value) {
        if (Array.isArray(value)) {
            return value;
        }

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

    function getDecoracionesMesa(mesa) {
        const decoraciones = parseDecoraciones(mesa?.decoraciones);
        mesa.decoraciones = decoraciones;
        return decoraciones;
    }

    function generarIdElemento() {
        return `elem_${Date.now()}_${Math.random().toString(16).slice(2, 8)}`;
    }

    function sanitizeIconClass(value) {
        return String(value || '')
            .replace(/[^a-zA-Z0-9\-\s]/g, '')
            .trim() || 'ti ti-star';
    }

    function normalizarRutaImagen(value) {
        const ruta = String(value || '').trim();
        if (!ruta) return '';
        if (/^(https?:)?\/\//i.test(ruta) || ruta.startsWith('data:')) {
            return ruta;
        }
        const limpia = ruta.replace(/^\.\//, '').replace(/^\/+/, '');
        return `/${limpia}`;
    }

    function crearMiniaturaElemento(elemento) {
        const tipo = ['texto', 'icono', 'imagen'].includes(elemento?.tipo) ? elemento.tipo : 'texto';
        const contenidoRaw = elemento?.contenido
            ?? (tipo === 'imagen' ? elemento?.imagen : tipo === 'icono' ? elemento?.icono : elemento?.texto)
            ?? '';
        const item = {
            tipo,
            contenido: String(contenidoRaw),
            color: String(elemento?.color || '#0f172a'),
            fondo: String(elemento?.fondo || '#ffffff'),
            fuente: Math.max(10, Math.min(18, Number(elemento?.fuente || 14))),
            rotacion: Math.max(-180, Math.min(180, Number(elemento?.rotacion || 0))),
            capa: Math.max(0, Math.min(9999, Number(elemento?.capa || 1))),
        };

        return {
            tipo: item.tipo,
            contenido: item.contenido,
            color: item.color,
            fondo: item.fondo,
            fuente: item.fuente,
            rotacion: item.rotacion,
            capa: item.capa,
            imagen: item.tipo === 'imagen' ? normalizarRutaImagen(item.contenido) : null,
            icono: item.tipo === 'icono' ? sanitizeIconClass(item.contenido) : null,
            texto: item.tipo === 'texto' ? item.contenido.slice(0, 24) : item.tipo === 'icono' ? item.contenido.slice(0, 24) : 'Imagen',
        };
    }

    function normalizarElemento(elemento) {
        const normalizado = {
            id: String(elemento?.id || generarIdElemento()),
            tipo: ['texto', 'icono', 'imagen'].includes(elemento?.tipo) ? elemento.tipo : 'texto',
            contenido: String(elemento?.contenido ?? ''),
            color: String(elemento?.color || '#0f172a'),
            fondo: String(elemento?.fondo || 'transparent'),
            fuente: Math.max(10, Math.min(48, Number(elemento?.fuente || 18))),
            ancho: Math.max(20, Math.min(260, Number(elemento?.ancho || 48))),
            alto: Math.max(20, Math.min(260, Number(elemento?.alto || 48))),
            x: Math.max(0, Number(elemento?.x || 10)),
            y: Math.max(0, Number(elemento?.y || 10)),
            rotacion: Math.max(-180, Math.min(180, Number(elemento?.rotacion || 0))),
            capa: Math.max(0, Math.min(9999, Number(elemento?.capa || 1))),
            borde: String(elemento?.borde || '0'),
            redondeo: Math.max(0, Math.min(999, Number(elemento?.redondeo || 12))),
            scope: String(elemento?.scope || 'mesa') === 'zona' ? 'zona' : 'mesa',
        };

        if (normalizado.tipo === 'imagen') {
            normalizado.contenido = normalizarRutaImagen(normalizado.contenido);
        }

        normalizado.miniatura = crearMiniaturaElemento(elemento?.miniatura || normalizado);
        return normalizado;
    }

    function obtenerPosicionElementoEnZona(mesa, elemento) {
        const item = normalizarElemento(elemento);
        if (item.scope === 'zona') {
            return {
                x: Math.max(0, Number(item.x) || 0),
                y: Math.max(0, Number(item.y) || 0),
            };
        }

        return {
            x: Math.max(0, Number(mesa?.pos_x || 0) + (Number(item.x) || 0)),
            y: Math.max(0, Number(mesa?.pos_y || 0) + (Number(item.y) || 0)),
        };
    }

    function renderElementoNode(mesa, elemento) {
        const item = normalizarElemento(elemento);
        const posicion = obtenerPosicionElementoEnZona(mesa, item);
        const node = document.createElement('div');
        node.className = 'mesa-overlay-element';
        node.dataset.elementoId = item.id;
        node.dataset.mesaId = String(mesa?.id || '');
        node.style.left = `${posicion.x}px`;
        node.style.top = `${posicion.y}px`;
        node.style.width = `${item.ancho}px`;
        node.style.height = `${item.alto}px`;
        node.style.zIndex = String(300 + item.capa);
        node.style.borderRadius = `${item.redondeo}px`;
        node.style.background = item.fondo && item.fondo !== 'transparent' ? item.fondo : 'transparent';
        node.style.color = item.color;
        node.style.transform = `rotate(${item.rotacion}deg)`;
        node.style.transformOrigin = 'center center';

        const inner = document.createElement('div');
        inner.className = 'overlay-inner';

        if (item.tipo === 'imagen') {
            const img = document.createElement('img');
            img.src = normalizarRutaImagen(item.contenido);
            img.alt = 'Elemento de mesa';
            img.draggable = false;
            img.addEventListener('dragstart', (ev) => ev.preventDefault());
            inner.appendChild(img);
        } else if (item.tipo === 'icono') {
            const icon = document.createElement('i');
            icon.className = sanitizeIconClass(item.contenido);
            icon.style.fontSize = `${Math.max(14, item.fuente)}px`;
            icon.classList.add('overlay-icono');
            inner.appendChild(icon);
        } else {
            const texto = document.createElement('span');
            texto.className = 'overlay-texto';
            texto.textContent = item.contenido || 'Texto';
            texto.style.fontSize = `${Math.max(10, item.fuente)}px`;
            inner.appendChild(texto);
        }

        node.appendChild(inner);
        node.addEventListener('dblclick', (ev) => {
            ev.stopPropagation();
            seleccionarMesa(mesa.id);
            abrirModalElemento(mesa, item.id);
        });
        node.addEventListener('dragstart', (ev) => ev.preventDefault());
        node.addEventListener('mousedown', (ev) => iniciarArrastreElemento(ev, mesa, item, node));
        node.addEventListener('touchstart', (ev) => iniciarArrastreElemento(ev, mesa, item, node), { passive: true });
        return node;
    }

    function renderElementosZona(zona) {
        const overlayZona = document.createElement('div');
        overlayZona.className = 'mesa-zona-overlay';

        const mesasZona = Array.isArray(zona?.mesas) ? zona.mesas : [];
        mesasZona.forEach((mesa) => {
            const decoraciones = getDecoracionesMesa(mesa);
            decoraciones.forEach((item) => {
                overlayZona.appendChild(renderElementoNode(mesa, item));
            });
        });

        board.appendChild(overlayZona);
    }

    function renderElementosMesaPanel() {
        const mesa = getMesaActual();
        const elementos = mesa ? getDecoracionesMesa(mesa) : [];
        if (!elementos.length) {
            mesaElementosLista.innerHTML = '<div class="mesa-elemento-item"><div><strong>Sin elementos</strong><small>Agrega imágenes desde archivo</small></div></div>';
            return;
        }

        mesaElementosLista.innerHTML = '';
        elementos.sort((a, b) => Number(a.capa || 0) - Number(b.capa || 0)).forEach((elemento, index) => {
            const item = normalizarElemento(elemento);
            const posicion = obtenerPosicionElementoEnZona(mesa, item);
            const row = document.createElement('div');
            row.className = 'mesa-elemento-item';
            const thumb = document.createElement('div');
            thumb.className = 'mesa-elemento-miniatura';
            const mini = item.miniatura || crearMiniaturaElemento(item);
            if (mini.imagen) {
                const img = document.createElement('img');
                img.src = mini.imagen;
                img.alt = 'Miniatura';
                thumb.appendChild(img);
            } else if (mini.icono) {
                const icon = document.createElement('i');
                icon.className = mini.icono;
                icon.classList.add('mini-icono');
                icon.style.color = mini.color;
                thumb.style.background = mini.fondo || '#ffffff';
                thumb.appendChild(icon);
            } else {
                const text = document.createElement('div');
                text.className = 'mini-texto';
                text.style.color = mini.color;
                text.style.background = mini.fondo || '#ffffff';
                text.textContent = mini.texto || 'Texto';
                thumb.appendChild(text);
            }

            const info = document.createElement('div');
            const title = document.createElement('strong');
            title.textContent = `${item.tipo.toUpperCase()} ${index + 1}`;
            const detail = document.createElement('small');
            detail.textContent = `${item.contenido ? item.contenido.slice(0, 40) : 'Sin contenido'} · ${item.ancho}x${item.alto} · x:${Math.round(posicion.x)} y:${Math.round(posicion.y)} · capa ${item.capa}`;
            info.appendChild(title);
            info.appendChild(detail);
            row.appendChild(thumb);
            row.appendChild(info);

            const actions = document.createElement('div');
            actions.className = 'mesa-elemento-actions';

            const editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'mesa-elemento-btn';
            editBtn.innerHTML = '<i class="ti ti-pencil"></i>';
            editBtn.addEventListener('click', () => abrirModalElemento(mesa, item.id));

            const downBtn = document.createElement('button');
            downBtn.type = 'button';
            downBtn.className = 'mesa-elemento-btn';
            downBtn.innerHTML = '<i class="ti ti-arrow-down"></i>';
            downBtn.title = 'Bajar capa';
            downBtn.addEventListener('click', () => moverCapaElemento(item.id, -1));

            const upBtn = document.createElement('button');
            upBtn.type = 'button';
            upBtn.className = 'mesa-elemento-btn';
            upBtn.innerHTML = '<i class="ti ti-arrow-up"></i>';
            upBtn.title = 'Subir capa';
            upBtn.addEventListener('click', () => moverCapaElemento(item.id, 1));

            const backBtn = document.createElement('button');
            backBtn.type = 'button';
            backBtn.className = 'mesa-elemento-btn';
            backBtn.innerHTML = '<i class="ti ti-chevrons-down"></i>';
            backBtn.title = 'Enviar atrás';
            backBtn.addEventListener('click', () => moverCapaElemento(item.id, 'back'));

            const frontBtn = document.createElement('button');
            frontBtn.type = 'button';
            frontBtn.className = 'mesa-elemento-btn';
            frontBtn.innerHTML = '<i class="ti ti-chevrons-up"></i>';
            frontBtn.title = 'Traer al frente';
            frontBtn.addEventListener('click', () => moverCapaElemento(item.id, 'front'));

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'mesa-elemento-btn danger';
            deleteBtn.innerHTML = '<i class="ti ti-trash"></i>';
            deleteBtn.addEventListener('click', () => eliminarElementoMesa(item.id));

            actions.appendChild(editBtn);
            actions.appendChild(backBtn);
            actions.appendChild(downBtn);
            actions.appendChild(upBtn);
            actions.appendChild(frontBtn);
            actions.appendChild(deleteBtn);
            row.appendChild(actions);
            mesaElementosLista.appendChild(row);
        });
    }

    function abrirModalElemento(mesa, idElemento = '') {
        if (!mesa) {
            showToast('Selecciona una mesa primero', true);
            return;
        }

        const elementos = getDecoracionesMesa(mesa);
        const elemento = idElemento ? elementos.find((item) => String(item.id) === String(idElemento)) : null;
        elementoEditandoId = elemento ? String(elemento.id) : '';

        const defaultsNuevo = {
            tipo: 'imagen',
            contenido: '',
            color: '#0f172a',
            fondo: '#ffffff',
            fuente: 18,
            ancho: 56,
            alto: 32,
            x: Math.max(0, Number(mesa?.pos_x || 0) + 18),
            y: Math.max(0, Number(mesa?.pos_y || 0) + 18),
            scope: 'zona',
        };
        const actual = normalizarElemento(elemento || defaultsNuevo);
        const posicion = obtenerPosicionElementoEnZona(mesa, actual);

        modalElementoId.value = actual.id;
        modalElementoTipo.value = actual.tipo;
        modalElementoContenido.value = actual.contenido;
        modalElementoColor.value = actual.color;
        modalElementoFondo.value = actual.fondo && actual.fondo !== 'transparent' ? actual.fondo : '#ffffff';
        modalElementoFuente.value = String(actual.fuente);
        modalElementoAncho.value = String(actual.ancho);
        modalElementoAlto.value = String(actual.alto);
        modalElementoRotacion.value = String(actual.rotacion);
        modalElementoCapa.value = String(actual.capa);
        modalElementoX.value = String(Math.round(posicion.x));
        modalElementoY.value = String(Math.round(posicion.y));
        modalElementoArchivo.value = '';
        archivoElementoPendiente = null;
        modalElementoArchivoActual.textContent = actual.tipo === 'imagen' && actual.contenido ? `Imagen actual: ${actual.contenido}` : '';
        modalElementoArchivoWrap.style.display = actual.tipo === 'imagen' ? 'block' : 'none';
        btnElementoEliminar.style.display = elemento ? 'inline-flex' : 'none';
        btnElementoDuplicar.style.display = elemento ? 'inline-flex' : 'none';
        actualizarUIElementoModal();
        openModal(modalElemento);
    }

    function cerrarModalElemento() {
        closeModal(modalElemento);
        elementoEditandoId = '';
    }

    function obtenerMesaParaEdicion() {
        const mesa = getMesaActual();
        if (!mesa) {
            showToast('Selecciona una mesa', true);
            return null;
        }
        return mesa;
    }

    async function guardarElementoMesa() {
        const mesa = obtenerMesaParaEdicion();
        if (!mesa) return;

        const tipo = 'imagen';
        const contenido = modalElementoContenido.value.trim();

        if (tipo !== 'imagen' && !contenido) {
            showToast('Ingresa el contenido del elemento', true);
            return;
        }

        const elementos = getDecoracionesMesa(mesa);
        const idElemento = String(modalElementoId.value || generarIdElemento());
        const index = elementos.findIndex((item) => String(item.id) === String(elementoEditandoId || idElemento));

        const base = index >= 0 ? elementos[index] : { id: idElemento };
        let contenidoFinal = contenido;

        if (tipo === 'imagen') {
            const archivo = archivoElementoPendiente || (modalElementoArchivo.files && modalElementoArchivo.files[0] ? modalElementoArchivo.files[0] : null);
            if (archivo) {
                contenidoFinal = normalizarRutaImagen(await subirImagenElementoMesa(archivo));
            } else if (!base.contenido) {
                showToast('Selecciona una imagen desde archivo', true);
                return;
            }
        }

        const nuevo = normalizarElemento({
            ...base,
            id: idElemento,
            tipo,
            contenido: contenidoFinal,
            color: modalElementoColor.value,
            fondo: modalElementoFondo.value,
            fuente: Number(modalElementoFuente.value || 18),
            ancho: Number(modalElementoAncho.value || 48),
            alto: Number(modalElementoAlto.value || 48),
            rotacion: Number(modalElementoRotacion.value || 0),
            capa: Number(modalElementoCapa.value || 1),
            x: Number(modalElementoX.value || 10),
            y: Number(modalElementoY.value || 10),
            scope: 'zona',
        });
        nuevo.miniatura = crearMiniaturaElemento(nuevo);

        if (index >= 0) {
            elementos[index] = nuevo;
        } else {
            elementos.push(nuevo);
        }

        mesa.decoraciones = elementos;
        renderMesas();
        seleccionarMesa(mesa.id);
        cerrarModalElemento();
        showToast('Elemento guardado');
    }

    function eliminarElementoMesa(idElemento) {
        const mesa = obtenerMesaParaEdicion();
        if (!mesa) return;

        const elementos = getDecoracionesMesa(mesa).filter((item) => String(item.id) !== String(idElemento));
        mesa.decoraciones = elementos;
        renderMesas();
        seleccionarMesa(mesa.id);
        showToast('Elemento eliminado');
    }

    function duplicarElementoMesa(idElemento) {
        const mesa = obtenerMesaParaEdicion();
        if (!mesa) return;

        const elementos = getDecoracionesMesa(mesa);
        const original = elementos.find((item) => String(item.id) === String(idElemento));
        if (!original) {
            showToast('No se pudo duplicar el elemento', true);
            return;
        }

        const copia = normalizarElemento({
            ...original,
            id: generarIdElemento(),
            x: Number(original.x || 0) + 18,
            y: Number(original.y || 0) + 18,
            capa: Number(original.capa || 1) + 1,
        });
        copia.miniatura = crearMiniaturaElemento(copia);

        elementos.push(copia);
        mesa.decoraciones = elementos;
        renderMesas();
        seleccionarMesa(mesa.id);
        abrirModalElemento(mesa, copia.id);
        showToast('Elemento duplicado');
    }

    async function subirImagenElementoMesa(archivo) {
        const formData = new FormData();
        formData.append('accion', 'elemento_subir_imagen');
        formData.append('archivo', archivo);

        const r = await fetch(API, {
            method: 'POST',
            body: formData,
        });
        const data = await r.json();
        if (!data.ok) {
            throw new Error(data.mensaje || 'No se pudo subir la imagen');
        }
        return data.ruta || '';
    }

    function moverCapaElemento(idElemento, movimiento) {
        const mesa = getMesaActual();
        if (!mesa) {
            showToast('Selecciona una mesa', true);
            return;
        }

        const elementos = getDecoracionesMesa(mesa);
        const actual = elementos.find((item) => String(item.id) === String(idElemento));
        if (!actual) return;

        const ordenados = [...elementos].sort((a, b) => Number(a.capa || 0) - Number(b.capa || 0));
        const idx = ordenados.findIndex((item) => String(item.id) === String(idElemento));
        if (idx < 0) return;

        if (movimiento === 'front') {
            const maxCapa = Math.max(...ordenados.map((item) => Number(item.capa || 0)));
            actual.capa = maxCapa + 1;
        } else if (movimiento === 'back') {
            const minCapa = Math.min(...ordenados.map((item) => Number(item.capa || 0)));
            actual.capa = Math.max(0, minCapa - 1);
        } else {
            const direccion = Number(movimiento) || 0;
            actual.capa = Math.max(0, Number(actual.capa || 0) + direccion);
        }

        let capaBase = 1;
        ordenados
            .sort((a, b) => Number(a.capa || 0) - Number(b.capa || 0))
            .forEach((item) => {
                item.capa = capaBase++;
            });

        mesa.decoraciones = ordenados;
        renderMesas();
        seleccionarMesa(mesa.id);
    }

    function actualizarUIElementoModal() {
        modalElementoTipo.value = 'imagen';
        modalElementoArchivoWrap.style.display = 'block';
        modalElementoContenido.closest('.mesas-field').style.display = 'none';
        modalElementoColor.disabled = true;
        modalElementoFondo.disabled = true;
        modalElementoFuente.disabled = true;
        const rotacionActual = Number(modalElementoRotacion.value || 0);
        modalElementoRotacionSlider.value = String(rotacionActual);
        modalElementoRotacionValor.textContent = `${rotacionActual}°`;
        modalElementoFondo.value = '#ffffff';
        modalElementoContenido.placeholder = 'Se guardará la ruta de la imagen subida';
    }

    modalElementoTipo.addEventListener('change', actualizarUIElementoModal);
    modalElementoRotacion.addEventListener('input', () => {
        modalElementoRotacionSlider.value = modalElementoRotacion.value;
        modalElementoRotacionValor.textContent = `${modalElementoRotacion.value || 0}°`;
    });
    modalElementoRotacionSlider.addEventListener('input', () => {
        modalElementoRotacion.value = modalElementoRotacionSlider.value;
        modalElementoRotacionValor.textContent = `${modalElementoRotacionSlider.value || 0}°`;
    });

    modalElementoDropzone.addEventListener('click', () => modalElementoArchivo.click());
    modalElementoDropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        modalElementoDropzone.style.borderColor = '#0f172a';
        modalElementoDropzone.style.background = '#eef2ff';
    });
    modalElementoDropzone.addEventListener('dragleave', () => {
        modalElementoDropzone.style.borderColor = '#94a3b8';
        modalElementoDropzone.style.background = '#f8fafc';
    });
    modalElementoDropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        modalElementoDropzone.style.borderColor = '#94a3b8';
        modalElementoDropzone.style.background = '#f8fafc';
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
            archivoElementoPendiente = e.dataTransfer.files[0];
            modalElementoArchivoActual.textContent = `Archivo listo: ${e.dataTransfer.files[0].name}`;
        }
    });
    modalElementoArchivo.addEventListener('change', () => {
        archivoElementoPendiente = modalElementoArchivo.files && modalElementoArchivo.files[0]
            ? modalElementoArchivo.files[0]
            : null;
        modalElementoArchivoActual.textContent = archivoElementoPendiente
            ? `Archivo listo: ${archivoElementoPendiente.name}`
            : '';
    });

    function iniciarArrastreElemento(ev, mesa, elemento, node) {
        if (ev.button !== undefined && ev.button !== 0) return;
        ev.stopPropagation();

        let dragging = true;
        const start = ev.touches ? ev.touches[0] : ev;
        const startX = start.clientX;
        const startY = start.clientY;
        const originX = Number(node.style.left.replace('px', '')) || 0;
        const originY = Number(node.style.top.replace('px', '')) || 0;

        const move = (moveEv) => {
            if (!dragging) return;
            const current = moveEv.touches ? moveEv.touches[0] : moveEv;
            const dx = current.clientX - startX;
            const dy = current.clientY - startY;
            const maxX = Math.max(0, board.offsetWidth - node.offsetWidth);
            const maxY = Math.max(0, board.offsetHeight - node.offsetHeight);
            const x = Math.max(0, Math.min(maxX, originX + dx));
            const y = Math.max(0, Math.min(maxY, originY + dy));
            node.style.left = `${Math.round(x)}px`;
            node.style.top = `${Math.round(y)}px`;
        };

        const end = () => {
            if (!dragging) return;
            dragging = false;
            const elementos = getDecoracionesMesa(mesa);
            const idx = elementos.findIndex((item) => String(item.id) === String(elemento.id));
            if (idx >= 0) {
                elementos[idx].x = Number(node.style.left.replace('px', '')) || 0;
                elementos[idx].y = Number(node.style.top.replace('px', '')) || 0;
                elementos[idx].scope = 'zona';
            }
            mesa.decoraciones = elementos;
            renderMesas();
            seleccionarMesa(mesa.id);
            window.removeEventListener('mousemove', move);
            window.removeEventListener('mouseup', end);
            window.removeEventListener('touchmove', move);
            window.removeEventListener('touchend', end);
        };

        window.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);
        window.addEventListener('touchmove', move, { passive: true });
        window.addEventListener('touchend', end);
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

    function resolverMesaObjetivo(autoseleccionar = true) {
        let mesa = getMesaActual();
        const zona = getZonaActual();
        if (!zona) return null;

        if (!mesa) {
            const desdeSelector = Number(mesaSelect.value || 0);
            if (desdeSelector > 0) {
                mesa = (zona.mesas || []).find((m) => Number(m.id) === desdeSelector) || null;
            }
        }

        if (!mesa && autoseleccionar && Array.isArray(zona.mesas) && zona.mesas.length > 0) {
            mesa = zona.mesas[0];
            seleccionarMesa(mesa.id);
        }

        return mesa;
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
        zonaAncho.value = '1000';
        zonaAlto.value = '620';
        zonaInfo.textContent = 'Zona: -';
        board.innerHTML = '';
        board.style.width = '1000px';
        board.style.height = '620px';
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

    function calcularPuntoPerimetroRectangular(distancia, ancho, alto, separacion) {
        const perimetro = (2 * ancho) + (2 * alto);
        const d = ((distancia % perimetro) + perimetro) % perimetro;

        if (d < ancho) {
            return { x: d, y: -separacion, rot: 0 };
        }
        if (d < (ancho + alto)) {
            return { x: ancho + separacion, y: d - ancho, rot: 90 };
        }
        if (d < (2 * ancho + alto)) {
            return { x: ancho - (d - ancho - alto), y: alto + separacion, rot: 180 };
        }
        return { x: -separacion, y: alto - (d - (2 * ancho + alto)), rot: 270 };
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

    function crearSillasMesa(mesa, mesaNode) {
        const totalSillas = Math.max(0, Math.min(20, Number(mesa?.sillas || 0)));
        if (totalSillas <= 0) return;

        const anchoMesa = Math.max(80, Number(mesa?.ancho) || 120);
        const altoMesa = Math.max(60, Number(mesa?.alto) || 74);
        const redonda = mesa?.forma === 'redonda';
        const capa = document.createElement('div');
        capa.className = 'mesa-sillas-layer';

        const sizeBase = redonda
            ? Math.max(11, Math.min(16, Math.round(Math.min(anchoMesa, altoMesa) * 0.13)))
            : Math.max(10, Math.min(16, Math.round(Math.min(anchoMesa, altoMesa) * 0.12)));
        const separacion = Math.max(12, Math.round(Math.min(anchoMesa, altoMesa) * 0.22));
        const plantilla = redonda
            ? calcularPlantillaSillasRedonda(totalSillas, anchoMesa, altoMesa, separacion)
            : calcularPlantillaSillasRectangular(totalSillas, anchoMesa, altoMesa, separacion);

        const puntos = Array.isArray(plantilla)
            ? plantilla
            : (() => {
                if (redonda) {
                    const radioX = (anchoMesa / 2) + separacion;
                    const radioY = (altoMesa / 2) + separacion;
                    return Array.from({ length: totalSillas }, (_, i) => {
                        const angulo = (-Math.PI / 2) + ((Math.PI * 2) * i / totalSillas);
                        return {
                            x: (anchoMesa / 2) + Math.cos(angulo) * radioX,
                            y: (altoMesa / 2) + Math.sin(angulo) * radioY,
                            rot: (angulo * 180 / Math.PI) + 90,
                        };
                    });
                }

                const perimetro = (2 * anchoMesa) + (2 * altoMesa);
                return Array.from({ length: totalSillas }, (_, i) => {
                    const distancia = ((i + 0.5) * perimetro) / totalSillas;
                    return calcularPuntoPerimetroRectangular(distancia, anchoMesa, altoMesa, separacion);
                });
            })();

        puntos.forEach((punto) => {
            const silla = document.createElement('div');
            silla.className = 'mesa-silla';
            silla.style.width = `${sizeBase}px`;
            silla.style.height = redonda
                ? `${sizeBase}px`
                : `${Math.max(8, Math.round(sizeBase * 0.72))}px`;
            silla.style.left = `${punto.x}px`;
            silla.style.top = `${punto.y}px`;
            silla.style.transform = `translate(-50%, -50%) rotate(${Number(punto.rot) || 0}deg)`;
            capa.appendChild(silla);
        });

        mesaNode.appendChild(capa);
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
            el.style.width = `${Math.max(80, Number(m.ancho) || 120)}px`;
            el.style.height = `${Math.max(60, Number(m.alto) || 74)}px`;
            el.style.left = `${Number(m.pos_x)}px`;
            el.style.top = `${Number(m.pos_y)}px`;

            const tablero = document.createElement('div');
            tablero.className = 'mesa-tablero';
            el.appendChild(tablero);

            const content = document.createElement('div');
            content.style.position = 'relative';
            content.style.zIndex = '4';
            content.style.pointerEvents = 'none';
            const name = document.createElement('span');
            name.className = 'name';
            name.textContent = m.nombre;
            const meta = document.createElement('span');
            meta.className = 'meta';
            meta.textContent = `${m.sillas} sillas`;
            content.appendChild(name);
            content.appendChild(meta);
            el.appendChild(content);
            crearSillasMesa(m, el);

            makeDraggable(el, m, zona);

            el.addEventListener('click', () => {
                seleccionarMesa(m.id);
            });

            board.appendChild(el);
        });

        renderElementosZona(zona);
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
            mesaAncho.value = 120;
            mesaAlto.value = 74;
            mesaElementosLista.innerHTML = '<div class="mesa-elemento-item"><div><strong>Sin mesa seleccionada</strong><small>Selecciona una mesa para editar sus elementos</small></div></div>';
            return;
        }

        mesaSelect.value = String(mesa.id);
        mesaNombre.value = mesa.nombre;
        mesaCapacidad.value = mesa.capacidad;
        mesaSillas.value = mesa.sillas;
        mesaForma.value = mesa.forma;
        mesaActiva.value = String(mesa.activa);
        mesaAncho.value = Math.max(80, Number(mesa.ancho) || 120);
        mesaAlto.value = Math.max(60, Number(mesa.alto) || 74);
        renderElementosMesaPanel();
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
            ancho: Number(m.ancho) || 120,
            alto: Number(m.alto) || 74,
            orden: idx + 1,
            decoraciones: parseDecoraciones(m.decoraciones),
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
            const ancho = Number(document.getElementById('modal-zona-ancho').value || 1000);
            const alto = Number(document.getElementById('modal-zona-alto').value || 620);
            if (!nombre) {
                showToast('Ingresa el nombre de la zona', true);
                return;
            }
            const data = await apiPost({ accion: 'zona_crear', nombre, ancho, alto });
            closeModal(modalZona);
            document.getElementById('modal-zona-nombre').value = '';
            if (data && data.zona_id) {
                zonaActualId = Number(data.zona_id);
                const zonaNueva = {
                    id: Number(data.zona_id),
                    nombre,
                    ancho,
                    alto,
                    orden: zonas.length + 1,
                    activa: 1,
                    mesas: [],
                };
                if (!zonas.some((z) => Number(z.id) === zonaNueva.id)) {
                    zonas.push(zonaNueva);
                }
            }
            await cargarLayout();
            renderZonas();
            zonaSelect.value = String(zonaActualId || zonaSelect.value || '');
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
                ancho: Number(zonaAncho.value || 1000),
                alto: Number(zonaAlto.value || 620),
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
            const ancho = Number(mesaAncho.value || 120);
            const alto = Number(mesaAlto.value || 74);
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
                    ancho,
                    alto,
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
                    ancho,
                    alto,
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
                ancho: Number(mesaAncho.value || 120),
                alto: Number(mesaAlto.value || 74),
                forma: mesaForma.value,
                activa: Number(mesaActiva.value || 1),
                decoraciones: parseDecoraciones(mesa.decoraciones),
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

    function abrirModalElementoMesa() {
        const mesa = resolverMesaObjetivo(true);
        if (!mesa) {
            showToast('Primero crea al menos una mesa en esta zona', true);
            return;
        }
        abrirModalElemento(mesa);
    }

    function reiniciarModalElemento() {
        elementoEditandoId = '';
        modalElementoId.value = '';
        modalElementoTipo.value = 'imagen';
        modalElementoContenido.value = '';
        modalElementoColor.value = '#0f172a';
        modalElementoFondo.value = '#ffffff';
        modalElementoFuente.value = '18';
        modalElementoAncho.value = '56';
        modalElementoAlto.value = '32';
        modalElementoX.value = '10';
        modalElementoY.value = '10';
        modalElementoArchivo.value = '';
        modalElementoArchivoActual.textContent = '';
        archivoElementoPendiente = null;
        btnElementoEliminar.style.display = 'none';
        btnElementoDuplicar.style.display = 'none';
        actualizarUIElementoModal();
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
        document.getElementById('modal-zona-ancho').value = '1000';
        document.getElementById('modal-zona-alto').value = '620';
        openModal(modalZona);
    });
    document.getElementById('btn-zona-guardar').addEventListener('click', guardarZona);
    document.getElementById('btn-zona-eliminar').addEventListener('click', eliminarZona);
    document.getElementById('btn-mesa-elemento-agregar').addEventListener('click', abrirModalElementoMesa);
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
    btnElementoGuardar.addEventListener('click', guardarElementoMesa);
    btnElementoEliminar.addEventListener('click', () => {
        if (!elementoEditandoId) return;
        eliminarElementoMesa(elementoEditandoId);
        cerrarModalElemento();
    });
    btnElementoDuplicar.addEventListener('click', () => {
        if (!elementoEditandoId) return;
        duplicarElementoMesa(elementoEditandoId);
    });
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

    [modalZona, modalMesa, modalElemento, modalConfirmacion].forEach((modal) => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal);
                if (modal === modalElemento) {
                    reiniciarModalElemento();
                }
            }
        });
    });

    document.querySelectorAll('[data-close-modal="modal-elemento"]').forEach((btn) => {
        btn.addEventListener('click', () => {
            cerrarModalElemento();
            reiniciarModalElemento();
        });
    });

    cargarLayout().catch((e) => showToast(e.message, true));
})();
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
