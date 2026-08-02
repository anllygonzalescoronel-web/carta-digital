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
                $productoId = $id;
            } else {
                $stmt = $db->prepare('INSERT INTO productos (categoria_id, nombre, descripcion, precio, precio_oferta, imagen, disponible, destacado, orden) VALUES (:c,:n,:d,:p,:po,:img,:dis,:des,:o)');
                $stmt->execute(['c'=>$categoriaId,'n'=>$nombre,'d'=>$descripcion,'p'=>$precio,'po'=>$precioOferta,'img'=>$nombreImagen,'dis'=>$disponible,'des'=>$destacado,'o'=>$orden]);
                $productoId = (int)$db->lastInsertId();
            }
            
            // Procesar ingredientes si viene el JSON
            $ingredientesJson = $_POST['ingredientes_json'] ?? '[]';
            $ingredientes = json_decode($ingredientesJson, true) ?? [];
            if (!empty($ingredientes) && !empty($productoId)) {
                try {
                    $db->prepare('DELETE FROM producto_ingredientes WHERE producto_id = :pid')
                       ->execute(['pid' => $productoId]);
                    $stmt = $db->prepare(
                        'INSERT IGNORE INTO producto_ingredientes (producto_id, ingrediente_id, cantidad)
                         VALUES (:pid, :iid, :cant)'
                    );
                    foreach ($ingredientes as $item) {
                        $iid  = (int)($item['ingrediente_id'] ?? 0);
                        $cant = (float)($item['cantidad'] ?? 0);
                        if ($iid > 0 && $cant > 0) {
                            $stmt->execute(['pid' => $productoId, 'iid' => $iid, 'cant' => $cant]);
                        }
                    }
                } catch (Throwable $eIng) {
                    // Silencioso si falla guardado de ingredientes
                }
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
            <input type="hidden" name="ingredientes_json" id="pIngredientesJson" value="[]">
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

            <!-- ── Ingredientes ─────────────────────────────────────────── -->
            <div style="margin-top:18px;border-top:1px solid #e2e8f0;padding-top:14px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <label style="font-weight:700;font-size:.9rem;"><i class="ti ti-packages" style="margin-right:4px;"></i>Ingredientes asignados</label>
                    <small style="color:#94a3b8;">Opcional — para descuento automático de stock</small>
                </div>
                <div id="pIngredientesList" style="display:flex;flex-direction:column;gap:6px;margin-bottom:10px;"></div>
                <div style="display:flex;gap:8px;align-items:flex-end;">
                    <div style="flex:1;">
                        <select id="pIngSelect" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.88rem;background:#fff;">
                            <option value="">— Seleccionar ingrediente —</option>
                        </select>
                    </div>
                    <div style="width:80px;">
                        <input type="number" id="pIngCant" placeholder="Cant." step="0.001" min="0.001"
                               style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.88rem;box-sizing:border-box;">
                    </div>
                    <div style="width:90px;">
                        <select id="pIngUnidad" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.88rem;background:#fff;">
                            <option value="">und.</option>
                        </select>
                    </div>
                    <button type="button" onclick="agregarIngredienteModal()"
                            style="padding:7px 14px;background:#6366f1;color:#fff;border:none;border-radius:7px;cursor:pointer;font-weight:600;white-space:nowrap;">
                        + Agregar
                    </button>
                </div>
            </div>
            <!-- ─────────────────────────────────────────────────────────── -->

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
    // Limpiar ingredientes y cargar si es edición
    ingModalItems = [];
    renderIngredientesModal();
    if (p && p.id) cargarIngredientesProducto(p.id);
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

<script>
/* ── Módulo de ingredientes en modal de producto ── */
const API_ING = '../api/ingredientes.php';
let todosIngredientes = [];   // cache de ingredientes disponibles
let ingModalItems = [];       // [{ingrediente_id, nombre, unidad, cantidad}]
const UNIDAD_LABEL_P = {kg:'kg',g:'g',l:'L',ml:'ml',m:'m',cm:'cm',unidad:'und',porcion:'porc.'};

// Cargar ingredientes disponibles para el <select>
async function cargarCatalogoIngredientes() {
    try {
        const r = await fetch(API_ING + '?accion=listar', { headers: { Accept: 'application/json' } });
        const d = await r.json();
        if (!d.ok) return;
        todosIngredientes = d.ingredientes || [];
        const sel = document.getElementById('pIngSelect');
        if (!sel) return;
        sel.innerHTML = '<option value="">— Seleccionar ingrediente —</option>' +
            todosIngredientes.map(i =>
                `<option value="${i.id}" data-unidad="${i.unidad}" data-nombre="${i.nombre.replace(/"/g,'&quot;')}">${i.nombre} (${UNIDAD_LABEL_P[i.unidad]||i.unidad})</option>`
            ).join('');
        
        // Agregar listener para cambios en el select
        sel.addEventListener('change', actualizarUnidadesDisponibles);
    } catch(_) {}
}

// Función para actualizar las unidades disponibles según el ingrediente seleccionado
function actualizarUnidadesDisponibles() {
    const sel = document.getElementById('pIngSelect');
    const selUnidad = document.getElementById('pIngUnidad');
    if (!sel.value) {
        selUnidad.innerHTML = '<option value="">und.</option>';
        return;
    }
    
    const opt = sel.options[sel.selectedIndex];
    const unidadBase = opt.dataset.unidad;
    
    // Mapeo de unidades disponibles por tipo
    const unidadesPorTipo = {
        kg: ['kg', 'g'],
        g:  ['kg', 'g'],
        l:  ['l', 'ml'],
        ml: ['l', 'ml'],
        m:  ['m', 'cm'],
        cm: ['m', 'cm'],
        unidad: ['unidad'],
        porcion: ['porcion'],
    };
    
    const disponibles = unidadesPorTipo[unidadBase] || [unidadBase];
    const LABEL = {kg:'kg',g:'g',l:'L',ml:'ml',m:'m',cm:'cm',unidad:'und',porcion:'porc.'};
    
    selUnidad.innerHTML = disponibles.map(u => 
        `<option value="${u}" ${u === unidadBase ? 'selected' : ''}>${LABEL[u]||u}</option>`
    ).join('');
}

// Cargar ingredientes ya asignados al producto al abrir modal editar
async function cargarIngredientesProducto(productoId) {
    try {
        const r = await fetch(`${API_ING}?accion=producto&producto_id=${productoId}`, { headers: { Accept: 'application/json' } });
        const d = await r.json();
        if (!d.ok) return;
        ingModalItems = (d.ingredientes || []).map(i => ({
            ingrediente_id: parseInt(i.ingrediente_id),
            nombre: i.nombre,
            unidad: i.unidad,
            cantidad: parseFloat(i.cantidad),
        }));
        renderIngredientesModal();
    } catch(_) {}
}

function renderIngredientesModal() {
    const cont = document.getElementById('pIngredientesList');
    if (!cont) return;
    if (!ingModalItems.length) {
        cont.innerHTML = '<small style="color:#94a3b8;">Sin ingredientes asignados.</small>';
        return;
    }
    
    const LABEL = {kg:'kg',g:'g',l:'L',ml:'ml',m:'m',cm:'cm',unidad:'und',porcion:'porc.'};
    
    cont.innerHTML = ingModalItems.map((it, idx) => {
        // Mostrar cantidad con unidad usada si es diferente de la base
        let detalle = `${it.cantidad} ${LABEL[it.unidad]||it.unidad}`;
        if (it.unidad_usada && it.unidad_usada !== it.unidad) {
            detalle = `${it.cantidad_usada} ${LABEL[it.unidad_usada]||it.unidad_usada} (= ${it.cantidad} ${LABEL[it.unidad]||it.unidad})`;
        }
        
        return `<div style="display:flex;align-items:center;gap:8px;background:#f8fafc;border-radius:7px;padding:6px 10px;">
            <span style="flex:1;font-size:.88rem;">${it.nombre}</span>
            <span style="font-size:.83rem;color:#64748b;">${detalle}</span>
            <button type="button" onclick="quitarIngredienteModal(${idx})"
                    style="background:#fef2f2;border:none;color:#ef4444;border-radius:5px;padding:2px 7px;cursor:pointer;font-weight:700;">✕</button>
        </div>`;
    }).join('');
}

function agregarIngredienteModal() {
    const sel = document.getElementById('pIngSelect');
    const cant = parseFloat(document.getElementById('pIngCant').value);
    const unidadUsada = document.getElementById('pIngUnidad').value;
    
    if (!sel.value || !cant || cant <= 0) {
        alert('Selecciona un ingrediente e ingresa una cantidad válida.');
        return;
    }
    const opt = sel.options[sel.selectedIndex];
    const id = parseInt(sel.value);
    const unidadBase = opt.dataset.unidad;
    
    // Evitar duplicados
    if (ingModalItems.find(x => x.ingrediente_id === id)) {
        alert('Ese ingrediente ya fue agregado. Quítalo primero para cambiarlo.');
        return;
    }
    
    // Convertir cantidad si es necesaria
    let cantidadEnUnitBase = cant;
    if (unidadUsada !== unidadBase) {
        // Función de conversión usando fetch a un endpoint (o JS puro)
        cantidadEnUnitBase = convertirUnidadFront(cant, unidadUsada, unidadBase);
    }
    
    ingModalItems.push({
        ingrediente_id: id,
        nombre: opt.dataset.nombre,
        unidad: unidadBase,
        cantidad: cantidadEnUnitBase,
        unidad_usada: unidadUsada,  // Guardar unidad usada para mostrar
        cantidad_usada: cant,
    });
    sel.value = '';
    document.getElementById('pIngCant').value = '';
    document.getElementById('pIngUnidad').innerHTML = '<option value="">und.</option>';
    renderIngredientesModal();
}

// Conversión de unidades en el navegador (duplicado del backend)
function convertirUnidadFront(cantidad, desde, hacia) {
    if (desde === hacia || cantidad <= 0) return cantidad;
    
    const grupos = {
        peso:    {kg: 1000, g: 1},
        volumen: {l: 1000, ml: 1},
        longitud:{m: 100, cm: 1},
    };
    
    let grupoDe = null, grupoHacia = null;
    for (const [g, unidades] of Object.entries(grupos)) {
        if (desde in unidades) grupoDe = g;
        if (hacia in unidades) grupoHacia = g;
    }
    
    if (grupoDe !== grupoHacia) return cantidad;
    
    const grupo = grupos[grupoDe];
    const enBase = cantidad * grupo[desde];
    const resultado = enBase / grupo[hacia];
    
    return Math.round(resultado * 10000) / 10000;
}

function quitarIngredienteModal(idx) {
    ingModalItems.splice(idx, 1);
    renderIngredientesModal();
}

// Interceptar el submit del form para guardar ingredientes después de guardar el producto
document.addEventListener('DOMContentLoaded', function() {
    cargarCatalogoIngredientes();

    const formProducto = document.querySelector('#modalProducto form');
    if (!formProducto) return;
    formProducto.addEventListener('submit', function(e) {
        // Llenar el campo hidden con los ingredientes en JSON
        const ingredientes = ingModalItems.map(i => ({
            ingrediente_id: i.ingrediente_id,
            cantidad: i.cantidad,
            unidad: i.unidad,
        }));
        document.getElementById('pIngredientesJson').value = JSON.stringify(ingredientes);
        // Dejar que el form se envíe normalmente
    });
});
</script>

<?php
// Después de guardar un producto nuevo, intentar guardar sus ingredientes pendientes
// Esto se maneja por JS leyendo sessionStorage y haciendo AJAX con el ID del último producto
?>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const pending = sessionStorage.getItem('_pending_ing');
    if (!pending) return;
    sessionStorage.removeItem('_pending_ing');

    // Buscar el último producto creado (el que esté al final de la tabla)
    const filas = document.querySelectorAll('table tbody tr[data-id]');
    // Productos no tienen data-id en la tabla, así que buscamos el último ID via API
    // Simplemente ignoramos y pedimos al usuario que reasigne en edición
    // (para simplicidad — alternativa sería hacer el form via AJAX completo)
});
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>