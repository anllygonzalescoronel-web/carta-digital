<?php
$tituloPagina = 'Productos';
$paginaActual = 'productos';
require __DIR__ . '/_layout_top.php';

$db = getDB();
$mensaje = ''; $error = '';
$carpetaUploads = __DIR__ . '/../uploads/productos';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'guardar') {
            $id = (int)($_POST['id'] ?? 0);
            $categoriaId = (int)($_POST['categoria_id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = (float)($_POST['precio'] ?? 0);
            $precioOferta = $_POST['precio_oferta'] !== '' ? (float)$_POST['precio_oferta'] : null;
            $disponible = isset($_POST['disponible']) ? 1 : 0;
            $destacado = isset($_POST['destacado']) ? 1 : 0;
            $orden = (int)($_POST['orden'] ?? 0);

            if ($nombre === '' || $categoriaId <= 0 || $precio <= 0) {
                throw new RuntimeException('Completa nombre, categoría y precio válido.');
            }

            $nombreImagen = subirImagen('imagen', $carpetaUploads);

            if ($id > 0) {
                if ($nombreImagen) {
                    $stmt = $db->prepare('UPDATE productos SET categoria_id=:c, nombre=:n, descripcion=:d, precio=:p, precio_oferta=:po, imagen=:img, disponible=:dis, destacado=:des, orden=:o WHERE id=:id');
                    $stmt->execute(['c'=>$categoriaId,'n'=>$nombre,'d'=>$descripcion,'p'=>$precio,'po'=>$precioOferta,'img'=>$nombreImagen,'dis'=>$disponible,'des'=>$destacado,'o'=>$orden,'id'=>$id]);
                } else {
                    $stmt = $db->prepare('UPDATE productos SET categoria_id=:c, nombre=:n, descripcion=:d, precio=:p, precio_oferta=:po, disponible=:dis, destacado=:des, orden=:o WHERE id=:id');
                    $stmt->execute(['c'=>$categoriaId,'n'=>$nombre,'d'=>$descripcion,'p'=>$precio,'po'=>$precioOferta,'dis'=>$disponible,'des'=>$destacado,'o'=>$orden,'id'=>$id]);
                }
            } else {
                $stmt = $db->prepare('INSERT INTO productos (categoria_id, nombre, descripcion, precio, precio_oferta, imagen, disponible, destacado, orden) VALUES (:c,:n,:d,:p,:po,:img,:dis,:des,:o)');
                $stmt->execute(['c'=>$categoriaId,'n'=>$nombre,'d'=>$descripcion,'p'=>$precio,'po'=>$precioOferta,'img'=>$nombreImagen,'dis'=>$disponible,'des'=>$destacado,'o'=>$orden]);
            }
            $mensaje = 'Producto guardado correctamente.';
        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM productos WHERE id = :id')->execute(['id'=>$id]);
            $mensaje = 'Producto eliminado.';
        } elseif ($accion === 'toggle_disponible') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare('UPDATE productos SET disponible = 1 - disponible WHERE id = :id')->execute(['id'=>$id]);
            $mensaje = 'Disponibilidad actualizada.';
        }
    } catch (Throwable $e) {
        $error = 'Ocurrió un error: ' . $e->getMessage();
    }
}

$categorias = $db->query('SELECT * FROM categorias ORDER BY orden ASC')->fetchAll();
$productos = $db->query('SELECT p.*, c.nombre AS categoria_nombre FROM productos p JOIN categorias c ON c.id = p.categoria_id ORDER BY p.categoria_id, p.orden ASC')->fetchAll();

// Ícono de estrella en SVG (para "Destacado"), en vez del emoji ⭐.
// No depende de la fuente de emojis del sistema operativo, así carga
// siempre igual y más rápido.
function iconoEstrella(bool $activa): string
{
    if (!$activa) {
        return '—';
    }
    return '<svg class="icono-estrella" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">'
         . '<path d="M12 2.5l2.95 6.05 6.68.7-4.98 4.6 1.33 6.6L12 16.9l-5.98 3.55 1.33-6.6-4.98-4.6 6.68-.7L12 2.5z" '
         . 'fill="#f2c94c" stroke="#e8a90c" stroke-width="1" stroke-linejoin="round"/>'
         . '</svg>';
}
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<?php if (empty($categorias)): ?>
    <div class="alerta-error">Primero crea al menos una categoría en la sección "Categorías".</div>
<?php else: ?>
<button class="btn-nuevo" onclick="abrirModalProducto()">+ Nuevo producto</button>

<div class="card">
    <div class="tabla-controles">
        <button type="button" class="btn-scroll-tabla btn-scroll-izq" aria-label="Desplazar tabla a la izquierda"><i class="ti ti-chevron-left"></i></button>
        <button type="button" class="btn-scroll-tabla btn-scroll-der" aria-label="Desplazar tabla a la derecha"><i class="ti ti-chevron-right"></i></button>
    </div>
    <div class="tabla-scroll">
    <table>
        <thead><tr><th>Img</th><th>Nombre</th><th>Categoría</th><th>Precio</th><th>Disponible</th><th>Destacado</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($productos as $p): ?>
            <tr>
                <td><img class="thumb" src="<?= $p['imagen'] ? '../uploads/productos/'.limpiar($p['imagen']) : '' ?>" onerror="this.style.opacity=0"></td>
                <td><?= limpiar($p['nombre']) ?></td>
                <td><?= limpiar($p['categoria_nombre']) ?></td>
                <td>
                    <?php if ($p['precio_oferta']): ?>
                        <s style="color:#aaa"><?= formatoPrecio($p['precio']) ?></s> <?= formatoPrecio($p['precio_oferta']) ?>
                    <?php else: ?>
                        <?= formatoPrecio($p['precio']) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="accion" value="toggle_disponible">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn btn-sm <?= $p['disponible'] ? 'btn-exito' : 'btn-peligro' ?>" type="submit">
                            <?= $p['disponible'] ? 'Sí' : 'No' ?>
                        </button>
                    </form>
                </td>
                <td><?= iconoEstrella((bool) $p['destacado']) ?></td>
                <td>
                    <button type="button" class="btn-icono-accion btn-icono-editar" title="Editar" aria-label="Editar producto" onclick='abrirModalProducto(<?= json_encode($p) ?>)'>
                        <svg class="icono-accion-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M4 20h4L18.5 9.5a2.121 2.121 0 0 0-3-3L5 17v3z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M14 6l4 4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <form method="POST" style="display:inline" id="form-eliminar-producto-<?= $p['id'] ?>">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="button" class="btn-icono-accion btn-icono-eliminar" title="Eliminar" aria-label="Eliminar producto" onclick="confirmarEliminarProducto(this)">
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
        <?php if (empty($productos)): ?><tr><td colspan="7" style="text-align:center;color:#999;">No hay productos todavía.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<div class="modal-overlay" id="modalProducto">
    <div class="modal-box">
        <h3 id="modalProductoTitulo" style="margin-bottom:14px;">Nuevo producto</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" id="pId">
            <div class="form-group">
                <label>Categoría</label>
                <div class="dropdown-neu abre-abajo" id="dropdown-categoria">
                    <button type="button" class="dropdown-neu-btn" id="dropdown-categoria-btn">
                        <span id="dropdown-categoria-texto"><?= !empty($categorias) ? limpiar($categorias[0]['nombre']) : 'Selecciona' ?></span>
                        <i class="ti ti-chevron-down"></i>
                    </button>
                    <input type="hidden" name="categoria_id" id="pCategoria" value="<?= !empty($categorias) ? $categorias[0]['id'] : '' ?>" required>
                    <div class="dropdown-neu-lista" id="dropdown-categoria-lista">
                        <?php foreach ($categorias as $i => $c): ?>
                            <div class="dropdown-neu-opcion <?= $i === 0 ? 'activa' : '' ?>" data-valor="<?= $c['id'] ?>" data-texto="<?= limpiar($c['nombre']) ?>">
                                <?= limpiar($c['nombre']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="nombre" id="pNombre" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" id="pDescripcion" rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Precio (S/)</label>
                    <input type="number" step="0.01" name="precio" id="pPrecio" required>
                </div>
                <div class="form-group">
                    <label>Precio oferta (opcional)</label>
                    <input type="number" step="0.01" name="precio_oferta" id="pPrecioOferta">
                </div>
            </div>
            <div class="form-group">
                <label>Imagen (jpg/png/webp, máx 5MB)</label>
                <input type="file" name="imagen" accept="image/*">
            </div>
            <div class="form-group">
                <label>Orden</label>
                <input type="number" name="orden" id="pOrden" value="0">
            </div>
            <div class="form-check">
                <input type="checkbox" name="disponible" id="pDisponible" checked>
                <label for="pDisponible" style="margin:0;">Disponible</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="destacado" id="pDestacado">
                <label for="pDestacado" style="margin:0;display:flex;align-items:center;gap:6px;">
                    Destacado
                    <svg class="icono-estrella" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2.5l2.95 6.05 6.68.7-4.98 4.6 1.33 6.6L12 16.9l-5.98 3.55 1.33-6.6-4.98-4.6 6.68-.7L12 2.5z" fill="#f2c94c" stroke="#e8a90c" stroke-width="1" stroke-linejoin="round"/>
                    </svg>
                </label>
            </div>
            <button class="btn-principal" type="submit">Guardar</button>
            <button class="btn btn-secundario" type="button" style="width:100%;margin-top:8px;" onclick="cerrarModalProducto()">Cancelar</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.getElementById('dropdown-categoria');
    if (!dropdown) return;
    const btn = document.getElementById('dropdown-categoria-btn');
    const texto = document.getElementById('dropdown-categoria-texto');
    const input = document.getElementById('pCategoria');
    const lista = document.getElementById('dropdown-categoria-lista');

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('abierto');
    });

    lista.querySelectorAll('.dropdown-neu-opcion').forEach(function (opcion) {
        opcion.addEventListener('click', function () {
            input.value = opcion.getAttribute('data-valor');
            texto.textContent = opcion.getAttribute('data-texto');
            lista.querySelectorAll('.dropdown-neu-opcion').forEach(function (o) { o.classList.remove('activa'); });
            opcion.classList.add('activa');
            dropdown.classList.remove('abierto');
        });
    });

    document.addEventListener('click', function () {
        dropdown.classList.remove('abierto');
    });
});

function seleccionarCategoriaModal(categoriaId) {
    const input = document.getElementById('pCategoria');
    const texto = document.getElementById('dropdown-categoria-texto');
    const lista = document.getElementById('dropdown-categoria-lista');
    const opciones = lista.querySelectorAll('.dropdown-neu-opcion');

    let seleccionada = null;
    opciones.forEach(function (opcion) {
        const esta = String(opcion.getAttribute('data-valor')) === String(categoriaId);
        opcion.classList.toggle('activa', esta);
        if (esta) seleccionada = opcion;
    });

    if (seleccionada) {
        input.value = seleccionada.getAttribute('data-valor');
        texto.textContent = seleccionada.getAttribute('data-texto');
    } else if (opciones.length) {
        // Si no se encontró coincidencia, deja la primera como respaldo
        input.value = opciones[0].getAttribute('data-valor');
        texto.textContent = opciones[0].getAttribute('data-texto');
        opciones[0].classList.add('activa');
    }
}

function abrirModalProducto(p) {
    document.getElementById('modalProductoTitulo').textContent = p ? 'Editar producto' : 'Nuevo producto';
    document.getElementById('pId').value = p ? p.id : '';
    seleccionarCategoriaModal(p ? p.categoria_id : document.getElementById('pCategoria').value);
    document.getElementById('pNombre').value = p ? p.nombre : '';
    document.getElementById('pDescripcion').value = p ? p.descripcion : '';
    document.getElementById('pPrecio').value = p ? p.precio : '';
    document.getElementById('pPrecioOferta').value = p ? (p.precio_oferta || '') : '';
    document.getElementById('pOrden').value = p ? p.orden : 0;
    document.getElementById('pDisponible').checked = p ? !!parseInt(p.disponible) : true;
    document.getElementById('pDestacado').checked = p ? !!parseInt(p.destacado) : false;
    document.getElementById('modalProducto').classList.add('visible');
}
function cerrarModalProducto() { document.getElementById('modalProducto').classList.remove('visible'); }
</script>

<!-- Modal de confirmación para eliminar producto -->
<div class="modal-overlay" id="modalConfirmarEliminarProducto">
    <div class="modal-box modal-confirmar">
        <div class="modal-confirmar-icono"><i class="ti ti-alert-triangle"></i></div>
        <h3>¿Eliminar este producto?</h3>
        <p>Se eliminará el producto seleccionado. Esta acción no se puede deshacer.</p>
        <div class="modal-confirmar-botones">
            <button type="button" class="btn btn-secundario" onclick="cerrarModalConfirmarEliminarProducto()">Cancelar</button>
            <button type="button" class="btn btn-peligro-solido" id="btnConfirmarEliminarProducto"><i class="ti ti-trash"></i> Sí, eliminar</button>
        </div>
    </div>
</div>

<script>
let formularioProductoAEliminar = null;

function confirmarEliminarProducto(boton) {
    formularioProductoAEliminar = boton.closest('form');
    document.getElementById('modalConfirmarEliminarProducto').classList.add('visible');
}

function cerrarModalConfirmarEliminarProducto() {
    document.getElementById('modalConfirmarEliminarProducto').classList.remove('visible');
    formularioProductoAEliminar = null;
}

document.addEventListener('DOMContentLoaded', function () {
    const btnConfirmar = document.getElementById('btnConfirmarEliminarProducto');
    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function () {
            if (formularioProductoAEliminar) {
                formularioProductoAEliminar.submit();
            }
        });
    }
});
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>