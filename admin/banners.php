<?php
$tituloPagina = 'Banners';
$paginaActual = 'banners';
require __DIR__ . '/_layout_top.php';

$db = getDB();
$mensaje = ''; $error = '';
$carpetaUploads = __DIR__ . '/../uploads/banners';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'guardar') {
            $id = (int)($_POST['id'] ?? 0);
            $titulo = trim($_POST['titulo'] ?? '');
            $subtitulo = trim($_POST['subtitulo'] ?? '');
            $orden = (int)($_POST['orden'] ?? 0);
            $activo = isset($_POST['activo']) ? 1 : 0;
            $nombreImagen = subirImagen('imagen', $carpetaUploads);

            if ($id > 0) {
                if ($nombreImagen) {
                    $stmt = $db->prepare('UPDATE banners SET imagen=:img, titulo=:t, subtitulo=:s, orden=:o, activo=:a WHERE id=:id');
                    $stmt->execute(['img'=>$nombreImagen,'t'=>$titulo,'s'=>$subtitulo,'o'=>$orden,'a'=>$activo,'id'=>$id]);
                } else {
                    $stmt = $db->prepare('UPDATE banners SET titulo=:t, subtitulo=:s, orden=:o, activo=:a WHERE id=:id');
                    $stmt->execute(['t'=>$titulo,'s'=>$subtitulo,'o'=>$orden,'a'=>$activo,'id'=>$id]);
                }
            } else {
                if (!$nombreImagen) throw new RuntimeException('Selecciona una imagen para el banner.');
                $stmt = $db->prepare('INSERT INTO banners (imagen, titulo, subtitulo, orden, activo) VALUES (:img,:t,:s,:o,:a)');
                $stmt->execute(['img'=>$nombreImagen,'t'=>$titulo,'s'=>$subtitulo,'o'=>$orden,'a'=>$activo]);
            }
            $mensaje = 'Banner guardado correctamente.';
        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM banners WHERE id = :id')->execute(['id'=>$id]);
            $mensaje = 'Banner eliminado.';
        }
    } catch (Throwable $e) {
        $error = 'Ocurrió un error: ' . $e->getMessage();
    }
}

$banners = $db->query('SELECT * FROM banners ORDER BY orden ASC')->fetchAll();
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<button class="btn-nuevo" onclick="abrirModalBanner()">+ Nuevo banner</button>

<div class="card">
    <table>
        <thead><tr><th>Imagen</th><th>Título</th><th>Orden</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($banners as $b): ?>
            <tr>
                <td><img class="thumb" src="../uploads/banners/<?= limpiar($b['imagen']) ?>"></td>
                <td><?= limpiar($b['titulo']) ?></td>
                <td><?= $b['orden'] ?></td>
                <td><?= $b['activo'] ? '<span class="badge badge-pagado">Activo</span>' : '<span class="badge badge-cancelado">Oculto</span>' ?></td>
                <td>
                    <button class="btn btn-secundario btn-sm" onclick='abrirModalBanner(<?= json_encode($b) ?>)'>Editar</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este banner?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <button class="btn btn-peligro btn-sm" type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($banners)): ?><tr><td colspan="5" style="text-align:center;color:#999;">No hay banners todavía.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="modalBanner">
    <div class="modal-box">
        <h3 id="modalBannerTitulo" style="margin-bottom:14px;">Nuevo banner</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" id="bId">
            <div class="form-group">
                <label>Imagen (recomendado 1200x600px)</label>
                <input type="file" name="imagen" accept="image/*">
            </div>
            <div class="form-group">
                <label>Título (opcional)</label>
                <input type="text" name="titulo" id="bTitulo">
            </div>
            <div class="form-group">
                <label>Subtítulo (opcional)</label>
                <input type="text" name="subtitulo" id="bSubtitulo">
            </div>
            <div class="form-group">
                <label>Orden</label>
                <input type="number" name="orden" id="bOrden" value="0">
            </div>
            <div class="form-check">
                <input type="checkbox" name="activo" id="bActivo" checked>
                <label for="bActivo" style="margin:0;">Visible en la carta</label>
            </div>
            <button class="btn-principal" type="submit">Guardar</button>
            <button class="btn btn-secundario" type="button" style="width:100%;margin-top:8px;" onclick="cerrarModalBanner()">Cancelar</button>
        </form>
    </div>
</div>

<script>
function abrirModalBanner(b) {
    document.getElementById('modalBannerTitulo').textContent = b ? 'Editar banner' : 'Nuevo banner';
    document.getElementById('bId').value = b ? b.id : '';
    document.getElementById('bTitulo').value = b ? b.titulo : '';
    document.getElementById('bSubtitulo').value = b ? b.subtitulo : '';
    document.getElementById('bOrden').value = b ? b.orden : 0;
    document.getElementById('bActivo').checked = b ? !!parseInt(b.activo) : true;
    document.getElementById('modalBanner').classList.add('visible');
}
function cerrarModalBanner() { document.getElementById('modalBanner').classList.remove('visible'); }
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
