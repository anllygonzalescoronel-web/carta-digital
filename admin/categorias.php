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
                    <button class="btn btn-secundario btn-sm" onclick='abrirModalCategoria(<?= json_encode($c) ?>)'>Editar</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar esta categoría y sus productos?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button class="btn btn-peligro btn-sm" type="submit">Eliminar</button>
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
                <input type="number" name="orden" id="catOrden" value="0">
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
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
