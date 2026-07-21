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
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<title><?= limpiar($nombreNegocio) ?> - Carta Digital</title>
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
    <?php if ($logo): ?><img src="uploads/<?= limpiar($logo) ?>" class="logo" alt="logo"><?php endif; ?>
    <div>
        <h1><?= limpiar($nombreNegocio) ?></h1>
        <p><?= limpiar(cfg('direccion_local', '')) ?></p>
    </div>
</header>

<?php if (!empty($banners)): ?>
<div class="banner-slider" id="bannerSlider">
    <div class="banner-track" id="bannerTrack">
        <?php foreach ($banners as $b): ?>
        <div class="banner-slide">
            <img src="uploads/<?= limpiar($b['imagen']) ?>" alt="<?= limpiar($b['titulo']) ?>">
            <?php if ($b['titulo'] || $b['subtitulo']): ?>
            <div class="banner-caption">
                <h2><?= limpiar($b['titulo']) ?></h2>
                <p><?= limpiar($b['subtitulo']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="banner-dots" id="bannerDots"></div>
</div>
<?php endif; ?>

<nav class="categorias-nav" id="categoriasNav">
    <?php foreach ($categorias as $i => $cat): ?>
    <button class="cat-btn <?= $i === 0 ? 'activo' : '' ?>" data-target="cat-<?= $cat['id'] ?>">
        <?= limpiar($cat['nombre']) ?>
    </button>
    <?php endforeach; ?>
</nav>

<main>
    <?php foreach ($categorias as $cat): ?>
    <section class="seccion-categoria" id="cat-<?= $cat['id'] ?>">
        <h3><?= limpiar($cat['nombre']) ?></h3>
        <?php foreach ($productosPorCategoria[$cat['id']] as $p): ?>
        <div class="producto-card <?= !$p['disponible'] ? 'no-disponible' : '' ?>">
            <img class="producto-img" src="<?= $p['imagen'] ? 'uploads/' . limpiar($p['imagen']) : 'assets/img/placeholder.png' ?>" alt="<?= limpiar($p['nombre']) ?>" onerror="this.style.visibility='hidden'">
            <div class="producto-info">
                <?php if ($p['destacado']): ?><span class="badge-destacado">★ Destacado</span><?php endif; ?>
                <h4><?= limpiar($p['nombre']) ?></h4>
                <p class="desc"><?= limpiar($p['descripcion']) ?></p>
                <div class="producto-precios">
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
                        <button class="btn-agregar" onclick="agregarProducto(this)">Agregar</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
</main>

<footer class="footer-public">Hecho con ❤ - Carta Digital</footer>

<!-- Botón flotante del carrito -->
<button class="carrito-flotante" id="btnCarrito" onclick="abrirCarrito()">
    <span>🛒 Ver mi pedido</span>
    <span class="badge-count" id="carritoContador">0</span>
</button>

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
