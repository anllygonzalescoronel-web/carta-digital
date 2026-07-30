<?php
$tituloPagina = 'Categorías';
$paginaActual = 'categorias';
require __DIR__ . '/_layout_top.php';

$db = getDB();
$mensaje = ''; $error = '';

try {
    $db->exec("ALTER TABLE categorias ADD COLUMN IF NOT EXISTS imagen VARCHAR(255) DEFAULT NULL AFTER nombre");
} catch (Throwable $e) {
    // Si la columna ya existe o el motor no soporta IF NOT EXISTS, seguimos.
}

// ---------- Procesar acciones ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'guardar') {
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $orden = (int)($_POST['orden'] ?? 0);
            $activo = isset($_POST['activo']) ? 1 : 0;
            $imagenActual = trim($_POST['imagen_actual'] ?? '');
            if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');

            // Si es una categoría nueva y dejaron el orden en 0 (sin tocarlo),
            // le asignamos automáticamente el siguiente número disponible.
            if ($id <= 0 && $orden === 0) {
                $maxOrden = (int) $db->query('SELECT COALESCE(MAX(orden), 0) m FROM categorias')->fetch()['m'];
                $orden = $maxOrden + 1;
            }

            if ($id > 0) {
                $imagen = $imagenActual;
                if (isset($_FILES['imagen']) && $_FILES['imagen']['size'] > 0) {
                    $file = $_FILES['imagen'];
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Error al subir la imagen de la categoría.');
                    }
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    if (!in_array($ext, $permitidas, true)) {
                        throw new RuntimeException('La imagen debe ser jpg, jpeg, png, webp o gif.');
                    }
                    $dirUploads = __DIR__ . '/../uploads/categorias';
                    if (!is_dir($dirUploads)) {
                        mkdir($dirUploads, 0775, true);
                    }
                    $nombreArchivo = 'cat_' . time() . '_' . $id . '.' . $ext;
                    $rutaDestino = $dirUploads . '/' . $nombreArchivo;
                    if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
                        throw new RuntimeException('No se pudo guardar la imagen de la categoría.');
                    }
                    $imagen = 'uploads/categorias/' . $nombreArchivo;
                }
                $stmt = $db->prepare('UPDATE categorias SET nombre=:n, imagen=:i, orden=:o, activo=:a WHERE id=:id');
                $stmt->execute(['n'=>$nombre,'i'=>$imagen ?: null,'o'=>$orden,'a'=>$activo,'id'=>$id]);
            } else {
                $imagen = null;
                if (isset($_FILES['imagen']) && $_FILES['imagen']['size'] > 0) {
                    $file = $_FILES['imagen'];
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        throw new RuntimeException('Error al subir la imagen de la categoría.');
                    }
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    if (!in_array($ext, $permitidas, true)) {
                        throw new RuntimeException('La imagen debe ser jpg, jpeg, png, webp o gif.');
                    }
                    $dirUploads = __DIR__ . '/../uploads/categorias';
                    if (!is_dir($dirUploads)) {
                        mkdir($dirUploads, 0775, true);
                    }
                    $nombreArchivo = 'cat_' . time() . '.' . $ext;
                    $rutaDestino = $dirUploads . '/' . $nombreArchivo;
                    if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
                        throw new RuntimeException('No se pudo guardar la imagen de la categoría.');
                    }
                    $imagen = 'uploads/categorias/' . $nombreArchivo;
                }
                $stmt = $db->prepare('INSERT INTO categorias (nombre, imagen, orden, activo) VALUES (:n,:i,:o,:a)');
                $stmt->execute(['n'=>$nombre,'i'=>$imagen,'o'=>$orden,'a'=>$activo]);
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

function rutaImagenCategoria(?string $imagen): string {
    $imagen = trim((string)$imagen);
    if ($imagen === '') {
        return 'assets/img/placeholder.png';
    }
    if (strpos($imagen, 'uploads/') === 0) {
        return $imagen;
    }
    return 'uploads/categorias/' . $imagen;
}
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<button class="btn-nuevo" onclick="abrirModalCategoria()">+ Nueva categoría</button>

<div class="card">
    <div class="tabla-controles">
        <button type="button" class="btn-scroll-tabla btn-scroll-izq" aria-label="Desplazar tabla a la izquierda"><i class="ti ti-chevron-left"></i></button>
        <button type="button" class="btn-scroll-tabla btn-scroll-der" aria-label="Desplazar tabla a la derecha"><i class="ti ti-chevron-right"></i></button>
    </div>
    <div class="tabla-scroll">
    <table>
        <thead><tr><th>Orden</th><th>Imagen</th><th>Nombre</th><th>Productos</th><th>Estado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($categorias as $c): ?>
            <tr>
                <td><?= $c['orden'] ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <img src="../<?= limpiar(rutaImagenCategoria($c['imagen'] ?? '')) ?>" alt="<?= limpiar($c['nombre']) ?>" class="thumb" style="object-fit:cover;width:54px;height:54px;border-radius:14px;box-shadow:0 6px 16px rgba(0,0,0,.12);">
                    </div>
                </td>
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
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalCategoria">
    <div class="modal-box">
        <h3 id="modalCategoriaTitulo" style="margin-bottom:14px;">Nueva categoría</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" id="catId">
            <input type="hidden" name="imagen_actual" id="catImagenActual">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" id="catNombre" required>
            </div>
            <div class="form-group">
                <label>Imagen de categoría</label>
                <input type="file" name="imagen" id="catImagen" accept="image/*">
                <small style="color:#666;display:block;margin-top:6px;">Se mostrará en la carta pública.</small>
                <div id="catImagenPreviewWrap" style="display:none;margin-top:10px;">
                    <img id="catImagenPreview" src="" alt="Vista previa" style="width:100%;max-height:180px;object-fit:cover;border-radius:16px;border:1px solid #ddd;box-shadow:0 10px 24px rgba(0,0,0,.10);">
                </div>
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
function abrirModalCategoria(c) {
    document.getElementById('modalCategoriaTitulo').textContent = c ? 'Editar categoría' : 'Nueva categoría';
    document.getElementById('catId').value = c ? c.id : '';
    document.getElementById('catNombre').value = c ? c.nombre : '';
    document.getElementById('catOrden').value = c ? c.orden : 0;
    document.getElementById('catActivo').checked = c ? !!parseInt(c.activo) : true;
    document.getElementById('catImagenActual').value = c && c.imagen ? c.imagen : '';
    const previewWrap = document.getElementById('catImagenPreviewWrap');
    const preview = document.getElementById('catImagenPreview');
    if (previewWrap && preview) {
        if (c && c.imagen) {
            preview.src = '../' + c.imagen;
            previewWrap.style.display = 'block';
        } else {
            preview.src = '';
            previewWrap.style.display = 'none';
        }
    }
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