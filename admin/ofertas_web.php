<?php
$tituloPagina = 'Ofertas Web';
$paginaActual = 'ofertas_web';
require __DIR__ . '/_layout_top.php';

$db = getDB();
$mensaje = '';
$error = '';

/* ------------------------------------------------------------------ */
/* POST - Acciones                                                       */
/* ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'guardar') {
            $id             = (int)($_POST['id'] ?? 0);
            $titulo         = trim($_POST['titulo'] ?? '');
            $color_fondo    = trim($_POST['color_fondo'] ?? 'rgba(255,80,0,0.18)');
            $tipo_descuento = in_array($_POST['tipo_descuento'] ?? '', ['porcentaje', 'plano']) ? $_POST['tipo_descuento'] : 'porcentaje';
            $valor          = max(0, (float)($_POST['valor_descuento'] ?? 0));
            $activo         = isset($_POST['activo']) ? 1 : 0;
            $orden          = (int)($_POST['orden'] ?? 0);
            $productos_ids  = array_filter(array_map('intval', (array)($_POST['productos'] ?? [])));

            if ($titulo === '') throw new RuntimeException('El título es obligatorio.');

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE ofertas_web SET titulo=:t, color_fondo=:c, tipo_descuento=:td, valor_descuento=:v, activo=:a, orden=:o WHERE id=:id');
                $stmt->execute(['t'=>$titulo,'c'=>$color_fondo,'td'=>$tipo_descuento,'v'=>$valor,'a'=>$activo,'o'=>$orden,'id'=>$id]);
            } else {
                if ($orden === 0) {
                    $maxOrden = (int)$db->query('SELECT COALESCE(MAX(orden),0) m FROM ofertas_web')->fetch()['m'];
                    $orden = $maxOrden + 1;
                }
                $stmt = $db->prepare('INSERT INTO ofertas_web (titulo, color_fondo, tipo_descuento, valor_descuento, activo, orden) VALUES (:t,:c,:td,:v,:a,:o)');
                $stmt->execute(['t'=>$titulo,'c'=>$color_fondo,'td'=>$tipo_descuento,'v'=>$valor,'a'=>$activo,'o'=>$orden]);
                $id = (int)$db->lastInsertId();
            }

            // Sincronizar productos
            $db->prepare('DELETE FROM oferta_web_productos WHERE oferta_id = :oid')->execute(['oid'=>$id]);
            if (!empty($productos_ids)) {
                $ins = $db->prepare('INSERT IGNORE INTO oferta_web_productos (oferta_id, producto_id) VALUES (:oid, :pid)');
                foreach ($productos_ids as $pid) {
                    $ins->execute(['oid'=>$id, 'pid'=>$pid]);
                }
            }
            $mensaje = 'Oferta guardada correctamente.';

        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM oferta_web_productos WHERE oferta_id = :id')->execute(['id'=>$id]);
            $db->prepare('DELETE FROM ofertas_web WHERE id = :id')->execute(['id'=>$id]);
            $mensaje = 'Oferta eliminada.';

        } elseif ($accion === 'toggle_activo') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare('UPDATE ofertas_web SET activo = 1 - activo WHERE id = :id')->execute(['id'=>$id]);
            $mensaje = 'Estado actualizado.';
        }
    } catch (Throwable $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

/* ------------------------------------------------------------------ */
/* Datos                                                                 */
/* ------------------------------------------------------------------ */
$ofertas = $db->query('SELECT * FROM ofertas_web ORDER BY orden ASC, id DESC')->fetchAll();

// Productos por oferta
$productosOferta = [];
$rowsPO = $db->query('SELECT oferta_id, producto_id FROM oferta_web_productos')->fetchAll();
foreach ($rowsPO as $r) {
    $productosOferta[$r['oferta_id']][] = $r['producto_id'];
}

// Todos los productos para el selector
$todosProductos = $db->query('SELECT p.id, p.nombre, c.nombre AS categoria FROM productos p LEFT JOIN categorias c ON c.id = p.categoria_id ORDER BY c.nombre, p.nombre')->fetchAll();
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<button class="btn-nuevo" onclick="abrirModal()">+ Nueva oferta web</button>

<div class="card">
    <div class="tabla-controles">
        <button type="button" class="btn-scroll-tabla btn-scroll-izq" aria-label="Izquierda"><i class="ti ti-chevron-left"></i></button>
        <button type="button" class="btn-scroll-tabla btn-scroll-der" aria-label="Derecha"><i class="ti ti-chevron-right"></i></button>
    </div>
    <div class="tabla-scroll">
    <table>
        <thead>
            <tr>
                <th>Color</th>
                <th>Título</th>
                <th>Descuento</th>
                <th>Productos</th>
                <th>Orden</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($ofertas)): ?>
            <tr><td colspan="7" style="text-align:center;color:#888;padding:24px">No hay ofertas creadas todavía.</td></tr>
        <?php endif; ?>
        <?php foreach ($ofertas as $o):
            $pids = $productosOferta[$o['id']] ?? [];
        ?>
            <tr>
                <td>
                    <span style="display:inline-block;width:32px;height:20px;border-radius:6px;background:<?= limpiar($o['color_fondo']) ?>;border:1px solid rgba(0,0,0,0.1)"></span>
                </td>
                <td><?= limpiar($o['titulo']) ?></td>
                <td>
                    <?php if ($o['tipo_descuento'] === 'porcentaje'): ?>
                        <span class="badge" style="background:#6366f1;color:#fff"><?= number_format($o['valor_descuento'],0) ?>%</span>
                    <?php else: ?>
                        <span class="badge" style="background:#0ea5e9;color:#fff">S/ <?= number_format($o['valor_descuento'],2) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= count($pids) ?> producto<?= count($pids) !== 1 ? 's' : '' ?></td>
                <td><?= $o['orden'] ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="accion" value="toggle_activo">
                        <input type="hidden" name="id" value="<?= $o['id'] ?>">
                        <button type="submit" class="badge <?= $o['activo'] ? 'badge-pagado' : 'badge-cancelado' ?>" style="border:none;cursor:pointer">
                            <?= $o['activo'] ? 'Activa' : 'Inactiva' ?>
                        </button>
                    </form>
                </td>
                <td>
                    <button type="button" class="btn-icono-accion btn-icono-editar" title="Editar"
                        onclick='abrirModal(<?= json_encode([
                            "id"             => $o["id"],
                            "titulo"         => $o["titulo"],
                            "color_fondo"    => $o["color_fondo"],
                            "tipo_descuento" => $o["tipo_descuento"],
                            "valor_descuento"=> $o["valor_descuento"],
                            "activo"         => $o["activo"],
                            "orden"          => $o["orden"],
                            "productos"      => $pids,
                        ]) ?>)'>
                        <svg class="icono-accion-svg" viewBox="0 0 24 24" fill="none"><path d="M4 20h4L18.5 9.5a2.121 2.121 0 0 0-3-3L5 17v3z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 6l4 4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <form method="post" style="display:inline" onsubmit="return confirm('¿Eliminar esta oferta?')">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= $o['id'] ?>">
                        <button type="submit" class="btn-icono-accion btn-icono-eliminar" title="Eliminar">
                            <svg class="icono-accion-svg" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- ===== MODAL ===== -->
<div id="modalOferta" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:600px;width:96%">
        <div class="modal-header">
            <h3 id="modalTitulo">Nueva oferta web</h3>
            <button type="button" class="modal-close" onclick="cerrarModal()">✕</button>
        </div>
        <form method="post" id="formOferta">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" id="f_id" value="0">

            <div class="form-group">
                <label>Título de la oferta *</label>
                <input type="text" name="titulo" id="f_titulo" class="form-control" required placeholder="Ej: Descuento fin de semana">
            </div>

            <div class="form-group" style="display:flex;gap:16px;flex-wrap:wrap">
                <div style="flex:1;min-width:160px">
                    <label>Color de fondo (neon)</label>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:6px" id="colorPresets">
                        <?php
                        $neons = [
                            'rgba(255,60,0,0.22)'   => '#ff3c00',
                            'rgba(255,200,0,0.22)'  => '#ffc800',
                            'rgba(0,220,130,0.22)'  => '#00dc82',
                            'rgba(80,100,255,0.22)' => '#5064ff',
                            'rgba(220,0,255,0.22)'  => '#dc00ff',
                            'rgba(0,200,255,0.22)'  => '#00c8ff',
                            'rgba(255,0,140,0.22)'  => '#ff008c',
                        ];
                        foreach ($neons as $rgba => $hex): ?>
                        <button type="button" class="color-preset-btn"
                            data-rgba="<?= $rgba ?>"
                            onclick="seleccionarColor(this)"
                            style="width:28px;height:28px;border-radius:50%;background:<?= $hex ?>;border:3px solid transparent;cursor:pointer;transition:.15s">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="color_fondo" id="f_color_fondo" value="rgba(255,60,0,0.22)">
                    <div id="colorPreview" style="margin-top:10px;padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;background:rgba(255,60,0,0.22);border:1px solid rgba(255,60,0,0.4);color:#222">
                        Vista previa de la oferta
                    </div>
                </div>
            </div>

            <div class="form-group" style="display:flex;gap:16px;flex-wrap:wrap">
                <div style="flex:1;min-width:160px">
                    <label>Tipo de descuento</label>
                    <select name="tipo_descuento" id="f_tipo_descuento" class="form-control">
                        <option value="porcentaje">Porcentaje (%)</option>
                        <option value="plano">Monto fijo (S/)</option>
                    </select>
                </div>
                <div style="flex:1;min-width:120px">
                    <label>Valor del descuento</label>
                    <input type="number" name="valor_descuento" id="f_valor" class="form-control" min="0" step="0.01" value="0" placeholder="Ej: 10">
                </div>
                <div style="flex:0 0 90px">
                    <label>Orden</label>
                    <input type="number" name="orden" id="f_orden" class="form-control" min="0" value="0">
                </div>
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px">
                    <input type="checkbox" name="activo" id="f_activo" value="1" checked> Oferta activa (visible en carta digital)
                </label>
            </div>

            <div class="form-group">
                <label>Productos en oferta</label>
                <div style="margin-bottom:8px;display:flex;gap:8px;flex-wrap:wrap">
                    <input type="text" id="buscadorProductos" class="form-control" placeholder="Buscar producto..." style="flex:1;min-width:180px" oninput="filtrarProductos(this.value)">
                    <button type="button" class="btn-sm" onclick="seleccionarTodos(true)">Todos</button>
                    <button type="button" class="btn-sm btn-outline" onclick="seleccionarTodos(false)">Ninguno</button>
                </div>
                <div id="listaProductos" style="max-height:260px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:10px;padding:8px 10px;background:#f8fafc;display:flex;flex-direction:column;gap:4px">
                <?php
                $catActual = '';
                foreach ($todosProductos as $p):
                    if ($p['categoria'] !== $catActual) {
                        $catActual = $p['categoria'];
                        echo '<div class="producto-cat-label" style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-top:6px;margin-bottom:2px;padding-left:2px">' . limpiar($catActual ?: 'Sin categoría') . '</div>';
                    }
                ?>
                    <label class="prod-check-item" data-nombre="<?= strtolower(limpiar($p['nombre'])) ?>" style="display:flex;align-items:center;gap:8px;padding:5px 6px;border-radius:7px;cursor:pointer;transition:.1s;font-size:13px">
                        <input type="checkbox" name="productos[]" value="<?= $p['id'] ?>" style="accent-color:var(--color-primario, #E8590C)">
                        <?= limpiar($p['nombre']) ?>
                    </label>
                <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn-outline" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn-primary">Guardar oferta</button>
            </div>
        </form>
    </div>
</div>

<style>
.color-preset-btn.selected { border-color: #334155 !important; transform: scale(1.18); }
.btn-sm { padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600; border:none; background:var(--color-primario, #E8590C); color:#fff; cursor:pointer; }
.btn-outline { padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600; background:#f1f5f9; border:1px solid #cbd5e1; cursor:pointer; }
.prod-check-item:hover { background:#e2e8f0; }
</style>

<script>
const todosLosProductos = <?= json_encode(array_map(fn($p) => ['id'=>(int)$p['id'],'nombre'=>$p['nombre'],'categoria'=>$p['categoria']], $todosProductos)) ?>;

function abrirModal(data = null) {
    document.getElementById('modalOferta').style.display = 'flex';
    const f = document.getElementById('formOferta');

    if (data) {
        document.getElementById('modalTitulo').textContent = 'Editar oferta web';
        document.getElementById('f_id').value = data.id;
        document.getElementById('f_titulo').value = data.titulo;
        document.getElementById('f_tipo_descuento').value = data.tipo_descuento;
        document.getElementById('f_valor').value = data.valor_descuento;
        document.getElementById('f_activo').checked = !!parseInt(data.activo);
        document.getElementById('f_orden').value = data.orden;
        aplicarColorFondo(data.color_fondo);

        // Marcar productos
        const ids = new Set((data.productos || []).map(Number));
        f.querySelectorAll('input[name="productos[]"]').forEach(cb => {
            cb.checked = ids.has(parseInt(cb.value));
        });
    } else {
        document.getElementById('modalTitulo').textContent = 'Nueva oferta web';
        f.reset();
        document.getElementById('f_id').value = 0;
        document.getElementById('f_activo').checked = true;
        aplicarColorFondo('rgba(255,60,0,0.22)');
    }
}

function cerrarModal() {
    document.getElementById('modalOferta').style.display = 'none';
}

function seleccionarColor(btn) {
    document.querySelectorAll('.color-preset-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    aplicarColorFondo(btn.dataset.rgba);
}

function aplicarColorFondo(rgba) {
    document.getElementById('f_color_fondo').value = rgba;
    const preview = document.getElementById('colorPreview');
    preview.style.background = rgba;
    // Detectar color de borde más saturado quitando alpha
    preview.style.borderColor = rgba.replace(/[\d.]+\)$/, '0.5)');

    // Marcar preset activo
    document.querySelectorAll('.color-preset-btn').forEach(b => {
        b.classList.toggle('selected', b.dataset.rgba === rgba);
    });
}

function filtrarProductos(termino) {
    const t = termino.toLowerCase().trim();
    document.querySelectorAll('#listaProductos .prod-check-item').forEach(item => {
        const visible = !t || item.dataset.nombre.includes(t);
        item.style.display = visible ? '' : 'none';
    });
}

function seleccionarTodos(marcar) {
    document.querySelectorAll('#listaProductos input[type="checkbox"]').forEach(cb => {
        if (cb.closest('.prod-check-item').style.display !== 'none') cb.checked = marcar;
    });
}

// Cerrar modal al hacer click fuera
document.getElementById('modalOferta').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
