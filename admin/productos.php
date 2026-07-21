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
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<?php if (empty($categorias)): ?>
    <div class="alerta-error">Primero crea al menos una categoría en la sección "Categorías".</div>
<?php else: ?>
<button class="btn-nuevo" onclick="abrirModalProducto()">+ Nuevo producto</button>

<div class="card">
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
                <td><?= $p['destacado'] ? '⭐' : '—' ?></td>
                <td>
                    <button class="btn btn-secundario btn-sm" onclick='abrirModalProducto(<?= json_encode($p) ?>)'>Editar</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este producto?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn btn-peligro btn-sm" type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($productos)): ?><tr><td colspan="7" style="text-align:center;color:#999;">No hay productos todavía.</td></tr><?php endif; ?>
        </tbody>
    </table>
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
                <select name="categoria_id" id="pCategoria" required>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= limpiar($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
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
                <label for="pDestacado" style="margin:0;">Destacado ⭐</label>
            </div>
            <button class="btn-principal" type="submit">Guardar</button>
            <button class="btn btn-secundario" type="button" style="width:100%;margin-top:8px;" onclick="cerrarModalProducto()">Cancelar</button>
        </form>
    </div>
</div>

<script>
function abrirModalProducto(p) {
    document.getElementById('modalProductoTitulo').textContent = p ? 'Editar producto' : 'Nuevo producto';
    document.getElementById('pId').value = p ? p.id : '';
    document.getElementById('pCategoria').value = p ? p.categoria_id : document.getElementById('pCategoria').value;
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

<?php require __DIR__ . '/_layout_bottom.php'; ?>
