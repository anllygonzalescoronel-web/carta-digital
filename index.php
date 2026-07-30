<?php
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$banners = $db->query('SELECT * FROM banners WHERE activo = 1 ORDER BY orden ASC')->fetchAll();
$categorias = $db->query('SELECT * FROM categorias WHERE activo = 1 ORDER BY orden ASC')->fetchAll();

$stmtProductos = $db->prepare('SELECT * FROM productos WHERE categoria_id = :cat ORDER BY orden ASC, id ASC');
$productosPorCategoria = [];
foreach ($categorias as $cat) {
    $stmtProductos->execute(['cat' => $cat['id']]);
    $productosPorCategoria[$cat['id']] = $stmtProductos->fetchAll();
}

$nombreNegocio = cfg('nombre_negocio', 'Mi Restaurante');
$logo = cfg('logo');
$culqiPublicKey = cfg('culqi_public_key');
$iconosCategoria = ['fa-pizza-slice', 'fa-burger', 'fa-mug-hot', 'fa-ice-cream', 'fa-drumstick-bite', 'fa-fish', 'fa-lemon', 'fa-cheese'];

// Cargar opciones/toppings de todos los productos
$opcionesProductos = [];
try {
    $allGrupos = $db->query('SELECT g.*, GROUP_CONCAT(o.id,"|",o.nombre,"|",o.precio_extra,"|",o.disponible ORDER BY o.orden SEPARATOR ";;") AS opciones_raw FROM producto_grupos g LEFT JOIN producto_opciones o ON o.grupo_id = g.id GROUP BY g.id ORDER BY g.orden')->fetchAll();
    foreach ($allGrupos as $g) {
        $opciones = [];
        if ($g['opciones_raw']) {
            foreach (explode(';;', $g['opciones_raw']) as $row) {
                $parts = explode('|', $row);
                if (count($parts) === 4 && $parts[3] == 1) {
                    $opciones[] = ['id' => (int)$parts[0], 'nombre' => $parts[1], 'precio_extra' => (float)$parts[2]];
                }
            }
        }
        if (empty($opciones)) continue;
        $opcionesProductos[$g['producto_id']][] = [
            'id'         => $g['id'],
            'nombre'     => $g['nombre'],
            'tipo'       => $g['tipo'],
            'requerido'  => (bool)$g['requerido'],
            'max'        => (int)$g['max_opciones'],
            'opciones'   => $opciones,
        ];
    }
} catch (Throwable $e) { /* tablas aún no existen */ }

function rutaImagenCategoria(?string $imagen): string {
    $nombre = trim((string)$imagen);
    if ($nombre === '') {
        return 'assets/img/placeholder.png';
    }
    if (strpos($nombre, 'uploads/') === 0) {
        return $nombre;
    }
    return 'uploads/categorias/' . $nombre;
}

function rutaImagenProducto(?string $imagen): string {
    $nombre = trim((string)$imagen);
    if ($nombre === '') {
        return 'assets/img/placeholder.png';
    }

    // Compatibilidad con valores antiguos: "archivo.jpg", "productos/archivo.jpg" o "uploads/productos/archivo.jpg".
    if (strpos($nombre, 'uploads/') === 0) {
        return $nombre;
    }
    if (strpos($nombre, 'productos/') === 0) {
        return 'uploads/' . $nombre;
    }

    return 'uploads/productos/' . $nombre;
}

function rutaImagenBanner(?string $imagen): string {
    $nombre = trim((string)$imagen);
    if ($nombre === '') {
        return '';
    }

    // Compatibilidad con valores: "archivo.jpg", "banners/archivo.jpg" o "uploads/banners/archivo.jpg".
    if (strpos($nombre, 'uploads/') === 0) {
        return $nombre;
    }
    if (strpos($nombre, 'banners/') === 0) {
        return 'uploads/' . $nombre;
    }

    return 'uploads/banners/' . $nombre;
}

$slidesBanner = [];
if (!empty($banners)) {
    foreach ($banners as $b) {
        $slidesBanner[] = [
            'imagen' => rutaImagenBanner($b['imagen'] ?? ''),
            'titulo' => (string)($b['titulo'] ?? ''),
            'subtitulo' => (string)($b['subtitulo'] ?? ''),
            'demo' => false,
        ];
    }
} else {
    $slidesBanner = [
        [
            'imagen' => 'https://placehold.co/1200x600/2e7d46/ffffff?text=Promo+1',
            'titulo' => 'Ofertas que no te puedes perder',
            'subtitulo' => '2x1 en platos seleccionados hasta las 8pm',
            'demo' => true,
        ],
        [
            'imagen' => 'https://placehold.co/1200x600/3ea152/ffffff?text=Promo+2',
            'titulo' => 'Delivery disponible',
            'subtitulo' => 'Recibe tu pedido donde estes',
            'demo' => true,
        ],
        [
            'imagen' => 'https://placehold.co/1200x600/1f6b3a/ffffff?text=Promo+3',
            'titulo' => 'Nuevos en la carta',
            'subtitulo' => 'Descubre los productos destacados de hoy',
            'demo' => true,
        ],
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<title><?= limpiar($nombreNegocio) ?> - Carta Digital</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/checkout-apiperu.css">
<script src="https://checkout.culqi.com/js/v4"></script>
<style>
:root {
    --color-primario: <?= limpiar(cfg('color_primario', '#E8590C')) ?>;
    --color-primario-fuerte: <?= limpiar(cfg('color_primario_fuerte', cfg('color_primario', '#E8590C'))) ?>;
    --color-secundario: <?= limpiar(cfg('color_secundario', '#FFC107')) ?>;
    --color-texto: <?= limpiar(cfg('color_texto', '#212121')) ?>;
    --color-fondo: <?= limpiar(cfg('color_fondo', '#FFF8F0')) ?>;
}
</style>
</head>
<body>

<header class="header">
    <div class="header-top">
        <div class="header-location">
            <span class="loc-label"><i class="fa-solid fa-location-dot"></i> Ubicacion actual</span>
            <h1><?= limpiar(cfg('direccion_local', $nombreNegocio)) ?></h1>
            <p><?= limpiar(cfg('direccion_local', '')) ?></p>
        </div>
        <?php if ($logo): ?>
            <img src="uploads/<?= limpiar($logo) ?>" class="logo" alt="logo">
        <?php else: ?>
            <div class="logo logo-fallback"><i class="fa-solid fa-utensils"></i></div>
        <?php endif; ?>
    </div>
    <div class="search-bar">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="inputBuscar" placeholder="Buscar en la carta...">
    </div>
</header>

<main class="main-content">
    <div class="banner-slider" id="bannerSlider">
        <div class="banner-track" id="bannerTrack">
            <?php foreach ($slidesBanner as $slide): ?>
            <div class="banner-slide">
                <img src="<?= limpiar($slide['imagen']) ?>" alt="<?= limpiar($slide['titulo']) ?>">
                <?php if ($slide['titulo'] || $slide['subtitulo']): ?>
                <div class="banner-caption">
                    <h2><?= limpiar($slide['titulo']) ?></h2>
                    <p><?= limpiar($slide['subtitulo']) ?></p>
                    <?php if (!empty($slide['demo'])): ?>
                    <button type="button" class="banner-cta" onclick="irHomeVisual(document.getElementById('navHome'))">Pedir ahora</button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="banner-dots" id="bannerDots"></div>
    </div>

    <nav class="quickcats" id="quickCats">
        <button class="quickcat-item activo" type="button" data-target="all-products">
            <span class="quickcat-circle"><i class="fa-solid fa-layer-group"></i></span>
            <span>Todos</span>
        </button>
        <?php foreach ($categorias as $i => $cat): ?>
        <button class="quickcat-item" type="button" data-target="cat-<?= $cat['id'] ?>">
            <span class="quickcat-circle">
                <?php if (!empty($cat['imagen'])): ?>
                    <img src="<?= limpiar(rutaImagenCategoria($cat['imagen'])) ?>" alt="<?= limpiar($cat['nombre']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <i class="fa-solid <?= $iconosCategoria[$i % count($iconosCategoria)] ?>"></i>
                <?php endif; ?>
            </span>
            <span><?= limpiar($cat['nombre']) ?></span>
        </button>
        <?php endforeach; ?>
    </nav>

    <?php foreach ($categorias as $cat): ?>
    <section class="seccion seccion-categoria" id="cat-<?= $cat['id'] ?>">
        <div class="seccion-header">
            <div class="categoria-hero" style="display:flex;align-items:center;gap:12px;">
                <img src="<?= limpiar(rutaImagenCategoria($cat['imagen'] ?? '')) ?>" alt="<?= limpiar($cat['nombre']) ?>" style="width:72px;height:72px;border-radius:20px;object-fit:cover;flex:0 0 auto;box-shadow:0 12px 28px rgba(0,0,0,.14);border:2px solid rgba(255,255,255,.75);">
                <div>
                    <h3 style="margin:0;font-size:18px;"><?= limpiar($cat['nombre']) ?></h3>
                    <p style="margin:4px 0 0;color:#6b7a70;font-size:12px;">Selecciona tus favoritos de esta categoría</p>
                </div>
            </div>
        </div>
        <div class="grid-items">
        <?php foreach ($productosPorCategoria[$cat['id']] as $p): ?>
        <article class="item-card producto-card <?= !$p['disponible'] ? 'no-disponible' : '' ?>">
            <div class="item-img-wrap">
                <img class="producto-img" src="<?= limpiar(rutaImagenProducto($p['imagen'] ?? '')) ?>" alt="<?= limpiar($p['nombre']) ?>" onerror="this.style.visibility='hidden'">
                <button class="btn-fav" type="button" onclick="toggleFavoritoVisual(this); event.stopPropagation();">
                    <i class="fa-regular fa-heart"></i>
                </button>
                <?php if ($p['destacado']): ?><span class="badge-destacado">★ Destacado</span><?php endif; ?>
                <?php if (!$p['disponible']): ?><span class="badge-agotado">Agotado</span><?php endif; ?>
            </div>
            <div class="producto-info">
                <h4><?= limpiar($p['nombre']) ?></h4>
                <p class="desc"><?= limpiar($p['descripcion']) ?></p>
                <div class="item-footer producto-precios">
                    <div>
                        <?php if ($p['precio_oferta']): ?>
                            <span class="precio-oferta-tachado"><?= formatoPrecio($p['precio']) ?></span>
                            <span class="precio"><?= formatoPrecio($p['precio_oferta']) ?></span>
                        <?php else: ?>
                            <span class="precio"><?= formatoPrecio($p['precio']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="control-cantidad" data-id="<?= $p['id'] ?>"
                         data-nombre="<?= limpiar($p['nombre']) ?>"
                         data-precio="<?= $p['precio_oferta'] ?: $p['precio'] ?>"
                         data-tiene-opciones="<?= isset($opcionesProductos[$p['id']]) ? '1' : '0' ?>"
                         data-imagen="<?= limpiar(rutaImagenProducto($p['imagen'] ?? '')) ?>">
                        <button class="btn-agregar" onclick="agregarProducto(this)" aria-label="Agregar al carrito">Agregar</button>
                    </div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</main>

<footer class="footer-public">Carta Digital</footer>

<!-- JSON de opciones de productos -->
<script>
window.OPCIONES_PRODUCTOS = <?= json_encode($opcionesProductos, JSON_UNESCAPED_UNICODE) ?>;
</script>

<!-- Modal detalle de producto -->
<div class="overlay" id="overlayProductoDetalle">
    <div class="modal modal-producto-detalle">
        <div class="modal-header">
            <h3>Detalle del producto</h3>
            <button type="button" onclick="cerrarModalDetalleProducto()" aria-label="Cerrar">&times;</button>
        </div>
        <div class="detalle-producto-wrap">
            <img id="detalleProductoImagen" class="detalle-producto-img" src="" alt="Producto">
            <div class="detalle-producto-body">
                <h4 id="detalleProductoNombre"></h4>
                <p id="detalleProductoDescripcion"></p>
                <div class="detalle-producto-footer">
                    <strong id="detalleProductoPrecio" class="detalle-producto-precio"></strong>
                    <span id="detalleProductoTipo" class="detalle-producto-tipo"></span>
                </div>
            </div>
            <button type="button" class="btn-principal" id="detalleProductoAgregar" onclick="agregarDesdeDetalleProducto()">Agregar al carrito</button>
        </div>
    </div>
</div>

<!-- Modal de personalización de producto -->
<div id="modalOpciones" class="modal-opciones-overlay" style="display:none;">
    <div class="modal-opciones-box">
        <button class="modal-opciones-cerrar" onclick="cerrarModalOpciones()" aria-label="Cerrar">&times;</button>
        <div class="modal-opciones-producto" id="mopProductoInfo"></div>
        <div id="mopGrupos"></div>
        <div class="modal-opciones-footer">
            <div class="modal-opciones-total">
                Total: <strong id="mopTotalTexto">S/ 0.00</strong>
            </div>
            <button class="btn-agregar-opciones" id="btnAgregarConOpciones" onclick="confirmarOpciones()">
                <i class="fa-solid fa-plus"></i> Agregar al carrito
            </button>
        </div>
    </div>
</div>

<!-- Barra inferior -->
<nav class="bottom-nav">
    <button class="nav-item activo" id="navHome" type="button" onclick="irHomeVisual(this)">
        <i class="fa-solid fa-house"></i>
        <span>Inicio</span>
    </button>
    <button class="nav-item" id="navFav" type="button" onclick="abrirFavoritosVisual(this)">
        <i class="fa-regular fa-heart"></i>
        <span>Favoritos</span>
    </button>
    <button class="nav-item nav-carrito" id="btnCarrito" type="button" onclick="abrirCarrito()">
        <span class="nav-carrito-icon-wrap">
            <i class="fa-solid fa-bag-shopping"></i>
            <span class="badge-count" id="carritoContador">0</span>
        </span>
        <span>Pedido</span>
    </button>
    <button class="nav-item" id="navPerfil" type="button" onclick="abrirPerfilVisual(this)">
        <i class="fa-regular fa-user"></i>
        <span>Perfil</span>
    </button>
    <button class="nav-item" id="navEstado" type="button" onclick="window.location.href='estado-pedido.php'">
        <i class="fa-solid fa-route"></i>
        <span>Mi estado</span>
    </button>
</nav>

<!-- Modal carrito -->
<div class="overlay" id="overlayCarrito">
    <div class="modal">
        <div class="modal-header">
            <h3>Mi pedido</h3>
            <button onclick="cerrarModal('overlayCarrito')">&times;</button>
        </div>
        <div id="listaCarrito"></div>
        <div id="resumenCarrito"></div>
        <button class="btn-principal" id="btnIrCheckout" onclick="irACheckout()">Continuar</button>
    </div>
</div>

<!-- Modal favoritos -->
<div class="overlay" id="overlayFavoritos">
    <div class="modal">
        <div class="modal-header">
            <h3>Mis favoritos</h3>
            <button onclick="cerrarModal('overlayFavoritos')">&times;</button>
        </div>
        <div id="listaFavoritos"></div>
    </div>
</div>

<!-- Modal perfil -->
<div class="overlay" id="overlayPerfil">
    <div class="modal">
        <div class="modal-header">
            <h3>Perfil</h3>
            <button onclick="cerrarModal('overlayPerfil')">&times;</button>
        </div>
        <div class="perfil-card">
            <div class="perfil-avatar"><i class="fa-solid fa-user"></i></div>
            <h4>Invitado</h4>
            <p>Pronto podras guardar tus direcciones y revisar tu historial de pedidos.</p>
        </div>
    </div>
</div>

<!-- Modal checkout -->
<div class="overlay" id="overlayCheckout">
    <div class="modal">
        <div class="modal-header">
            <h3>Finalizar pedido</h3>
            <button onclick="cerrarModal('overlayCheckout')">&times;</button>
        </div>
        <div id="checkoutError"></div>

        <div class="form-group">
            <label>Nombre completo</label>
            <input type="text" id="inputNombre" placeholder="Ej. Juan Pérez">
        </div>
        <div class="form-group">
            <label>Tipo de comprobante</label>
            <div class="opciones-toggle" id="opcionesComprobante">
                <div class="opcion-toggle activo" data-comprobante="boleta" onclick="seleccionarComprobante(this)">Boleta<small>B001</small></div>
                <div class="opcion-toggle" data-comprobante="factura" onclick="seleccionarComprobante(this)">Factura<small>F001</small></div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Tipo de documento</label>
                <select id="inputTipoDocumento" onchange="actualizarTipoDocumentoSegunComprobante()">
                    <option value="dni">DNI</option>
                    <option value="ruc">RUC</option>
                </select>
            </div>
            <div class="form-group">
                <label>Número de documento</label>
                <input type="text" id="inputNumeroDocumento" placeholder="Ej. 12345678" inputmode="numeric" maxlength="11">
            </div>
        </div>
        <div class="form-group">
            <label>Teléfono (WhatsApp)</label>
            <input type="tel" id="inputTelefono" placeholder="Ej. 987654321">
        </div>

        <div class="form-group">
            <label>¿Cómo prefieres recibir tu pedido?</label>
            <div class="opciones-toggle">
                <?php if (cfg('recojo_activo','1') === '1'): ?>
                <div class="opcion-toggle activo" data-entrega="recojo" onclick="seleccionarEntrega(this)">🏠 Recojo<small>En el local</small></div>
                <?php endif; ?>
                <?php if (cfg('delivery_activo','1') === '1'): ?>
                <div class="opcion-toggle" data-entrega="delivery" onclick="seleccionarEntrega(this)">🛵 Delivery<small>+ <?= formatoPrecio(cfg('costo_delivery','0')) ?></small></div>
                <?php endif; ?>
            </div>
        </div>

        <div id="camposDelivery" style="display:none;">
            <div class="form-group">
                <label>Dirección de entrega</label>
                <input type="text" id="inputDireccion" placeholder="Av., calle, número, distrito">
            </div>
            <div class="form-group">
                <label>Referencia (opcional)</label>
                <input type="text" id="inputReferencia" placeholder="Ej. frente al parque, portón azul">
            </div>
        </div>

        <div class="form-group">
            <label>Método de pago</label>
            <?php if (cfg('efectivo_activo','1') === '1'): ?>
            <div class="metodo-pago-card activo" data-pago="efectivo" onclick="seleccionarPago(this)">
                <span class="icono">💵</span>
                <div class="txt"><strong>Efectivo</strong><span>Pagas al recibir tu pedido</span></div>
            </div>
            <?php endif; ?>
            <?php if (cfg('yape_plin_activo','1') === '1'): ?>
            <div class="metodo-pago-card" data-pago="yape_plin" onclick="seleccionarPago(this)">
                <span class="icono">📲</span>
                <div class="txt"><strong>Yape / Plin</strong><span>Yape se cobra con Culqi; Plin sigue como QR manual</span></div>
            </div>
            <?php endif; ?>
            <?php if (cfg('tarjeta_activo','1') === '1'): ?>
            <div class="metodo-pago-card" data-pago="tarjeta" onclick="seleccionarPago(this)">
                <span class="icono">💳</span>
                <div class="txt"><strong>Tarjeta</strong><span>Pago seguro con Culqi</span></div>
            </div>
            <?php endif; ?>
        </div>

        <div id="bloqueYape" style="display:none;">
            <div class="qr-box">
                <?php if (cfg('yape_plin_qr')): ?>
                    <img src="uploads/<?= limpiar(cfg('yape_plin_qr')) ?>" alt="QR Yape/Plin">
                <?php endif; ?>
                <p>Yape se procesa con Culqi. Si usas Plin manual, paga al número <strong><?= limpiar(cfg('yape_plin_numero')) ?></strong></p>
                <p style="font-size:11px;color:#888;margin-top:4px;">Al elegir Yape se abrirá el checkout seguro de Culqi.</p>
            </div>
        </div>

        <div id="bloqueTarjeta" style="display:none;">
            <div class="form-group">
                <label>Email (para tu comprobante)</label>
                <input type="email" id="inputEmail" placeholder="tucorreo@ejemplo.com">
            </div>
            <div id="culqi-form-tarjeta"></div>
        </div>

        <div class="form-group">
            <label>Notas adicionales (opcional)</label>
            <textarea id="inputNotas" rows="2" placeholder="Ej. sin cebolla, tocar timbre 2 veces..."></textarea>
        </div>

        <button class="btn-principal" id="btnConfirmarPedido" onclick="confirmarPedido()">
            Confirmar pedido
        </button>
    </div>
</div>

<!-- Modal confirmación de venta -->
<div class="overlay" id="overlayConfirmacion">
    <div class="modal modal-confirmacion">
        <div class="confetti-layer" id="confettiLayer"></div>
        <div class="confirmacion-wrap">
            <div class="confirmacion-icono"><i class="fa-solid fa-check"></i></div>
            <h3>Pedido confirmado</h3>
            <p>Tu pedido fue procesado correctamente. Elige como deseas continuar.</p>
            <div class="confirmacion-codigo">Codigo de pedido: <strong id="confirmacionCodigo">-</strong></div>

            <div class="acciones-confirmacion">
                <a class="btn-principal btn-whatsapp" id="btnAvisarWhatsapp" href="#" target="_self" rel="noopener noreferrer">
                    <i class="fa-brands fa-whatsapp"></i> Avisar por WhatsApp
                </a>
                <button class="btn-secundario" id="btnVolverCarta" type="button">Volver a la carta</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.APP_CONFIG = {
        culqiPublicKey: <?= json_encode($culqiPublicKey) ?>,
        costoDelivery: <?= (float) cfg('costo_delivery', '0') ?>,
        nombreNegocio: <?= json_encode($nombreNegocio) ?>
    };
</script>

    <!-- Modal de Checkout Multi-Paso -->
    <?php include 'template frontend/checkout-modal.html'; ?>

    <!-- Scripts de Checkout -->
    <script src="assets/js/checkout-apiperu.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar checkout cuando el DOM esté listo
        window.checkout = new CheckoutAPIPeru();
    });
    </script>

<script src="assets/js/carrito.js"></script>
</body>
</html>
