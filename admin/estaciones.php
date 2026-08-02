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
.est-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; margin-bottom: 20px; }

.est-card {
    background: #fff;
    border: 1px solid #e8edf5;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15,23,42,.05);
    display: flex;
    flex-direction: column;
    transition: box-shadow .2s;
}
.est-card:hover { box-shadow: 0 6px 20px rgba(15,23,42,.10); }
.est-card-header {
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    color: #fff;
}
.est-card-header .est-icon {
    width: 44px; height: 44px;
    background: rgba(255,255,255,.15);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.est-card-header h3 { margin: 0; font-size: 15px; font-weight: 800; }
.est-card-header p  { margin: 2px 0 0; font-size: 11.5px; opacity: .8; }
.est-card-header .est-badge-activa {
    font-size: 10px; font-weight: 800; padding: 3px 9px; border-radius: 999px;
    background: rgba(255,255,255,.18); white-space: nowrap;
}
.est-card-body { padding: 14px 18px; flex: 1; }
.est-tag-group { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.est-tag {
    font-size: 11px; font-weight: 700; padding: 3px 9px;
    border-radius: 6px; background: #f1f5f9; color: #334155;
    display: inline-flex; align-items: center; gap: 4px;
}
.est-tag.cat { background: #dbeafe; color: #1e40af; }
.est-tag.usr { background: #dcfce7; color: #166534; }
.est-tag.empty { background: #fef3c7; color: #92400e; font-style: italic; }
.est-card-footer {
    padding: 10px 18px;
    border-top: 1px solid #f1f5f9;
    display: flex; gap: 8px;
}

/* ── Botón nueva estación ── */
.btn-nueva-est {
    display: inline-flex; align-items: center; gap: 8px;
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #fff; border: none; border-radius: 12px;
    padding: 11px 20px; font-size: 14px; font-weight: 700;
    cursor: pointer; margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(15,23,42,.18);
    transition: opacity .15s;
}
.btn-nueva-est:hover { opacity: .88; }

/* ── Botones card ── */
.btn-est {
    border: none; border-radius: 9px; padding: 7px 14px;
    font-weight: 700; font-size: 12px; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px;
    transition: opacity .15s;
}
.btn-est:hover { opacity: .85; }
.btn-est-edit { background: #f1f5f9; color: #334155; }
.btn-est-del  { background: #fee2e2; color: #b91c1c; }

/* ── Modal ── */
.est-modal-backdrop {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.55);
    backdrop-filter: blur(3px);
    display: none; align-items: center; justify-content: center; padding: 20px;
    z-index: 1200;
}
.est-modal-backdrop.show { display: flex; }
.est-modal {
    width: 100%; max-width: 680px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 28px 70px rgba(15,23,42,.22);
    overflow: hidden;
    max-height: 90vh;
    display: flex; flex-direction: column;
}
.est-modal-head {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    color: #fff; padding: 18px 22px;
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0;
}
.est-modal-head h4 { margin: 0; font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
.est-modal-close { border: none; background: rgba(255,255,255,.12); color: #fff; width: 34px; height: 34px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; }
.est-modal-body { padding: 22px; overflow-y: auto; flex: 1; }
.est-modal-footer { padding: 14px 22px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; flex-shrink: 0; }

/* ── Form ── */
.ef-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 560px) { .ef-row { grid-template-columns: 1fr; } }
.ef-field { margin-bottom: 14px; }
.ef-field label { display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 5px; }
.ef-field input, .ef-field select, .ef-field textarea {
    width: 100%; border: 1px solid #cbd5e1; border-radius: 10px;
    padding: 9px 11px; font-size: 13px; box-sizing: border-box; color: #0f172a; background: #fff;
    transition: border-color .15s, box-shadow .15s;
}
.ef-field input:focus, .ef-field select:focus, .ef-field textarea:focus {
    outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12);
}

/* ── Checkboxes de categorías y usuarios ── */
.est-check-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 8px; max-height: 200px; overflow-y: auto; padding: 2px; }
.est-check-item {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 10px; border-radius: 10px; border: 1px solid #e2e8f0;
    cursor: pointer; transition: border-color .15s, background .15s;
}
.est-check-item:hover { border-color: #6366f1; background: #f5f3ff; }
.est-check-item.checked { border-color: #6366f1; background: #ede9fe; }
.est-check-item input[type=checkbox] { accent-color: #6366f1; width: 15px; height: 15px; flex-shrink: 0; }
.est-check-item span { font-size: 12px; font-weight: 600; color: #334155; }

/* ── Color picker preview ── */
.color-row { display: flex; align-items: center; gap: 10px; }
.color-preview { width: 36px; height: 36px; border-radius: 10px; border: 2px solid #e2e8f0; flex-shrink: 0; }

/* ── Iconos sugeridos ── */
.icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(44px, 1fr)); gap: 6px; }
.icon-opt {
    border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f8fafc;
    width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 20px; transition: border-color .15s, background .15s;
}
.icon-opt:hover { border-color: #6366f1; background: #ede9fe; }
.icon-opt.selected { border-color: #6366f1; background: #ede9fe; }

/* ── Modal confirmación ── */
.est-confirm-backdrop {
    position: fixed; inset: 0; background: rgba(15,23,42,.55); backdrop-filter: blur(3px);
    display: none; align-items: center; justify-content: center; padding: 20px; z-index: 1300;
}
.est-confirm-backdrop.show { display: flex; }
.est-confirm-box { background: #fff; border-radius: 18px; padding: 28px; max-width: 420px; width: 100%; box-shadow: 0 20px 50px rgba(15,23,42,.2); text-align: center; }
.est-confirm-box h4 { font-size: 17px; margin: 0 0 8px; }
.est-confirm-box p { color: #64748b; font-size: 13px; margin: 0 0 20px; }

/* ── Estado vacío ── */
.est-empty { text-align: center; padding: 56px 20px; color: #94a3b8; }
.est-empty i { font-size: 52px; display: block; margin-bottom: 14px; opacity: .4; }
.est-empty h3 { font-size: 16px; color: #475569; margin: 0 0 6px; }
.est-empty p { font-size: 13px; }

/* ── Msg ── */
.est-msg { font-size: 13px; font-weight: 700; min-height: 18px; }
.est-msg.ok { color: #166534; }
.est-msg.err { color: #b91c1c; }
</style>

<div class="est-wrap">

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <div>
            <h2 style="margin:0;font-size:20px;font-weight:800;color:#0f172a;">Estaciones de Producción</h2>
            <p style="margin:4px 0 0;color:#64748b;font-size:13px;">Define los paneles de cocina: Cocina, Barra, Pollos, Sopas, etc. Asigna categorías de productos y usuarios responsables.</p>
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
            <button type="button" id="btnCancelarEst" style="border:none;border-radius:10px;padding:10px 18px;background:#f1f5f9;color:#334155;font-weight:700;cursor:pointer;">Cancelar</button>
            <button type="button" id="btnGuardarEst" style="border:none;border-radius:10px;padding:10px 22px;background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;">
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
        <p id="confirmEstNombre" style="font-weight:700;color:#0f172a;margin-bottom:6px;"></p>
        <p>Se eliminarán también las asignaciones de categorías y usuarios. Los pedidos no se verán afectados.</p>
        <div style="display:flex;gap:10px;justify-content:center;">
            <button type="button" id="btnCancelarConfirmEst" style="border:none;border-radius:10px;padding:10px 20px;background:#f1f5f9;color:#334155;font-weight:700;cursor:pointer;">Cancelar</button>
            <button type="button" id="btnConfirmarElimEst" style="border:none;border-radius:10px;padding:10px 20px;background:linear-gradient(135deg,#b91c1c,#dc2626);color:#fff;font-weight:700;cursor:pointer;">Eliminar</button>
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
        grid.innerHTML = datos.estaciones.map(e => {
            const cats = e.categorias.length
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
                    <p style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin:0 0 6px;">Categorías asignadas</p>
                    <div class="est-tag-group">${cats}</div>
                    <p style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin:8px 0 6px;">Encargados</p>
                    <div class="est-tag-group">${usrs}</div>
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
