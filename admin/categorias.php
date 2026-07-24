<?php
$tituloPagina = 'Categorías';
$paginaActual = 'categorias';
require __DIR__ . '/_layout_top.php';

$db = getDB();
$mensaje = ''; $error = '';

// ---------- Procesar acciones ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'guardar') {
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $orden = (int)($_POST['orden'] ?? 0);
            $activo = isset($_POST['activo']) ? 1 : 0;
            if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE categorias SET nombre=:n, orden=:o, activo=:a WHERE id=:id');
                $stmt->execute(['n'=>$nombre,'o'=>$orden,'a'=>$activo,'id'=>$id]);
            } else {
                $stmt = $db->prepare('INSERT INTO categorias (nombre, orden, activo) VALUES (:n,:o,:a)');
                $stmt->execute(['n'=>$nombre,'o'=>$orden,'a'=>$activo]);
            }
            $mensaje = 'Categoría guardada correctamente.';
        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM categorias WHERE id = :id')->execute(['id'=>$id]);
            $mensaje = 'Categoría eliminada.';
        }
    } catch (Throwable $e) {
        $error = 'Ocurrió un error: ' . $e->getMessage();
    }
}

$categorias = $db->query('SELECT c.*, (SELECT COUNT(*) FROM productos p WHERE p.categoria_id=c.id) AS total_productos FROM categorias c ORDER BY orden ASC')->fetchAll();
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<button class="btn-nuevo" onclick="abrirModalCategoria()">+ Nueva categoría</button>

<div class="card">
    <table>
        <thead><tr><th>Orden</th><th>Nombre</th><th>Productos</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($categorias as $c): ?>
            <tr>
                <td><?= $c['orden'] ?></td>
                <td><?= limpiar($c['nombre']) ?></td>
                <td><?= $c['total_productos'] ?></td>
                <td><?= $c['activo'] ? '<span class="badge badge-pagado">Activa</span>' : '<span class="badge badge-cancelado">Oculta</span>' ?></td>
                <td>
                    <button type="button" class="btn-icono-accion btn-icono-editar" title="Editar" aria-label="Editar categoría" onclick='abrirModalCategoria(<?= json_encode($c) ?>)'>
                        <svg class="icono-accion-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 20h4L18.5 9.5a2.121 2.121 0 0 0-3-3L5 17v3z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 6l4 4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <form method="POST" style="display:inline" class="form-eliminar-categoria">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn-icono-accion btn-icono-eliminar" title="Eliminar" aria-label="Eliminar categoría">
                            <svg class="icono-accion-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5 7h14" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                                <path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 7l1 13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1l1-13" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 11v6M14 11v6" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($categorias)): ?><tr><td colspan="5" style="text-align:center;color:#999;">No hay categorías todavía.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalCategoria">
    <div class="modal-box">
        <h3 id="modalCategoriaTitulo" style="margin-bottom:14px;">Nueva categoría</h3>
        <form method="POST">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" id="catId">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" id="catNombre" required>
            </div>
            <div class="form-group">
                <label>Orden (menor número = aparece primero)</label>
                <div class="stepper-neu">
                    <button type="button" class="stepper-btn" id="catOrdenMenos" aria-label="Disminuir orden">
                        <i class="ti ti-minus"></i>
                    </button>
                    <input type="number" name="orden" id="catOrden" value="0" class="stepper-input" min="0">
                    <button type="button" class="stepper-btn" id="catOrdenMas" aria-label="Aumentar orden">
                        <i class="ti ti-plus"></i>
                    </button>
                </div>
            </div>
            <div class="form-check">
                <input type="checkbox" name="activo" id="catActivo" checked>
                <label for="catActivo" style="margin:0;">Visible en la carta pública</label>
            </div>
            <button class="btn-principal" type="submit">Guardar</button>
            <button class="btn btn-secundario" type="button" style="width:100%;margin-top:8px;" onclick="cerrarModalCategoria()">Cancelar</button>
        </form>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal-overlay" id="modalConfirmarEliminar">
    <div class="modal-box modal-confirmar">
        <div class="modal-confirmar-icono">
            <i class="ti ti-alert-triangle"></i>
        </div>
        <h3>¿Eliminar esta categoría?</h3>
        <p>Se eliminará junto con sus productos. Esta acción no se puede deshacer.</p>
        <div class="modal-confirmar-botones">
            <button type="button" class="btn btn-secundario" id="btnCancelarEliminar">Cancelar</button>
            <button type="button" class="btn btn-peligro-solido" id="btnConfirmarEliminar">
                <i class="ti ti-trash"></i> Sí, eliminar
            </button>
        </div>
    </div>
</div>

<script>
function abrirModalCategoria(c) {
    document.getElementById('modalCategoriaTitulo').textContent = c ? 'Editar categoría' : 'Nueva categoría';
    document.getElementById('catId').value = c ? c.id : '';
    document.getElementById('catNombre').value = c ? c.nombre : '';
    document.getElementById('catOrden').value = c ? c.orden : 0;
    document.getElementById('catActivo').checked = c ? !!parseInt(c.activo) : true;
    document.getElementById('modalCategoria').classList.add('visible');
}
function cerrarModalCategoria() { document.getElementById('modalCategoria').classList.remove('visible'); }

// ===== Stepper de "Orden": botones −/+ personalizados =====
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('catOrden');
    const btnMenos = document.getElementById('catOrdenMenos');
    const btnMas = document.getElementById('catOrdenMas');
    if (input && btnMenos && btnMas) {
        btnMenos.addEventListener('click', function () {
            const actual = parseInt(input.value, 10) || 0;
            input.value = Math.max(actual - 1, 0);
        });
        btnMas.addEventListener('click', function () {
            const actual = parseInt(input.value, 10) || 0;
            input.value = actual + 1;
        });
    }

    // ===== Modal de confirmación de eliminar (reemplaza el confirm() nativo) =====
    const modalEliminar = document.getElementById('modalConfirmarEliminar');
    const btnCancelarEliminar = document.getElementById('btnCancelarEliminar');
    const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminar');
    let formPendiente = null;

    document.querySelectorAll('.form-eliminar-categoria').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            formPendiente = form;
            modalEliminar.classList.add('visible');
        });
    });

    btnCancelarEliminar.addEventListener('click', function () {
        formPendiente = null;
        modalEliminar.classList.remove('visible');
    });

    btnConfirmarEliminar.addEventListener('click', function () {
        if (formPendiente) {
            formPendiente.submit();
        }
    });
});
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>