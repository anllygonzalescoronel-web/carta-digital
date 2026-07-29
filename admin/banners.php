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

            // Si es un banner nuevo y dejaron el orden en 0 (sin tocarlo),
            // le asignamos automáticamente el siguiente número disponible.
            if ($id <= 0 && $orden === 0) {
                $maxOrden = (int) $db->query('SELECT COALESCE(MAX(orden), 0) m FROM banners')->fetch()['m'];
                $orden = $maxOrden + 1;
            }

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
    <div class="tabla-controles">
        <button type="button" class="btn-scroll-tabla btn-scroll-izq" aria-label="Desplazar tabla a la izquierda"><i class="ti ti-chevron-left"></i></button>
        <button type="button" class="btn-scroll-tabla btn-scroll-der" aria-label="Desplazar tabla a la derecha"><i class="ti ti-chevron-right"></i></button>
    </div>
    <div class="tabla-scroll">
    <table>
        <thead><tr><th>Imagen</th><th>Orden</th><th>Título</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($banners as $b): ?>
            <tr>
                <td><img class="thumb" src="../uploads/banners/<?= limpiar($b['imagen']) ?>"></td>
                <td><?= $b['orden'] ?></td>
                <td><?= limpiar($b['titulo']) ?></td>
                <td><?= $b['activo'] ? '<span class="badge badge-pagado">Activo</span>' : '<span class="badge badge-cancelado">Oculto</span>' ?></td>
                <td>
                    <button type="button" class="btn-icono-accion btn-icono-editar" title="Editar" aria-label="Editar banner" onclick='abrirModalBanner(<?= json_encode($b) ?>)'>
                        <svg class="icono-accion-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 20h4L18.5 9.5a2.121 2.121 0 0 0-3-3L5 17v3z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 6l4 4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <form method="POST" style="display:inline" class="form-eliminar-banner">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                        <button type="submit" class="btn-icono-accion btn-icono-eliminar" title="Eliminar" aria-label="Eliminar banner">
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
        <?php if (empty($banners)): ?><tr><td colspan="5" style="text-align:center;color:#999;">No hay banners todavía.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Modal -->
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
                <label>Orden (menor número = aparece primero)</label>
                <div class="stepper-neu">
                    <button type="button" class="stepper-btn" id="bOrdenMenos" aria-label="Disminuir orden">
                        <i class="ti ti-minus"></i>
                    </button>
                    <input type="number" name="orden" id="bOrden" value="0" class="stepper-input" min="0">
                    <button type="button" class="stepper-btn" id="bOrdenMas" aria-label="Aumentar orden">
                        <i class="ti ti-plus"></i>
                    </button>
                </div>
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

<!-- Modal de confirmación para eliminar -->
<div class="modal-overlay" id="modalConfirmarEliminar">
    <div class="modal-box modal-confirmar">
        <div class="modal-confirmar-icono">
            <i class="ti ti-alert-triangle"></i>
        </div>
        <h3>¿Eliminar este banner?</h3>
        <p>Se eliminará el elemento seleccionado. Esta acción no se puede deshacer.</p>
        <div class="modal-confirmar-botones">
            <button type="button" class="btn btn-secundario" id="btnCancelarEliminar">Cancelar</button>
            <button type="button" class="btn btn-peligro-solido" id="btnConfirmarEliminar">
                <i class="ti ti-trash"></i> Sí, eliminar
            </button>
        </div>
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

// ===== Stepper de "Orden": botones −/+ personalizados =====
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('bOrden');
    const btnMenos = document.getElementById('bOrdenMenos');
    const btnMas = document.getElementById('bOrdenMas');
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

    document.querySelectorAll('.form-eliminar-banner').forEach(function (form) {
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