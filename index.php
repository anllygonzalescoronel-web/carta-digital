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
<script src="https://checkout.culqi.com/js/v4"></script>
<style>
:root {
    --color-primario: <?= limpiar(cfg('color_primario', '#E8590C')) ?>;
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

<nav class="categorias-nav" id="categoriasNav">
    <button class="cat-btn activo" data-target="all-products">Todos</button>
    <?php foreach ($categorias as $cat): ?>
    <button class="cat-btn" data-target="cat-<?= $cat['id'] ?>">
        <?= limpiar($cat['nombre']) ?>
    </button>
    <?php endforeach; ?>
</nav>

<nav class="quickcats" id="quickCats">
    <button class="quickcat-item activo" type="button" data-target="all-products">
        <span class="quickcat-circle"><i class="fa-solid fa-layer-group"></i></span>
        <span>Todos</span>
    </button>
    <?php foreach ($categorias as $i => $cat): ?>
    <button class="quickcat-item" type="button" data-target="cat-<?= $cat['id'] ?>">
        <span class="quickcat-circle"><i class="fa-solid <?= $iconosCategoria[$i % count($iconosCategoria)] ?>"></i></span>
        <span><?= limpiar($cat['nombre']) ?></span>
    </button>
    <?php endforeach; ?>
</nav>

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

    <?php foreach ($categorias as $cat): ?>
    <section class="seccion seccion-categoria" id="cat-<?= $cat['id'] ?>">
        <div class="seccion-header">
            <h3><?= limpiar($cat['nombre']) ?></h3>
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
                         data-precio="<?= $p['precio_oferta'] ?: $p['precio'] ?>">
                        <button class="btn-agregar" onclick="agregarProducto(this)"><i class="fa-solid fa-plus"></i></button>
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
        <div class="vacio-msg">Aun no tienes favoritos guardados.</div>
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
<script src="assets/js/carrito.js"></script>
</body>
</html>
