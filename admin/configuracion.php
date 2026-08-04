<?php
$tituloPagina = 'Configuración';
$paginaActual = 'configuracion';
require __DIR__ . '/_layout_top.php';

$mensaje = ''; $error = '';
$carpetaUploads = __DIR__ . '/../uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $campos = [
            'nombre_negocio', 'direccion_local', 'whatsapp_numero', 'costo_delivery',
            'color_primario', 'color_primario_fuerte', 'color_secundario', 'color_texto', 'color_fondo',
            'yape_plin_numero', 'culqi_public_key', 'culqi_secret_key', 'mensaje_bienvenida',
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_secure',
            'smtp_from_email', 'smtp_from_name', 'smtp_timeout',
            'facturacion_driver', 'facturacion_modo_hibrido',
            'sunat_ruc_emisor', 'sunat_razon_social', 'sunat_nombre_comercial',
            'sunat_usuario_sol', 'sunat_clave_sol', 'sunat_certificado_clave',
            'sunat_direccion', 'sunat_ubigeo', 'sunat_modo',
            'sunat_serie_boleta', 'sunat_serie_factura',
            'nubefact_ruta', 'nubefact_token',
            'nubefact_serie_boleta', 'nubefact_serie_factura',
            'apiperu_token',
            'google_client_id',
        ];
        foreach ($campos as $c) {
            if (isset($_POST[$c])) guardarConfig($c, trim($_POST[$c]));
        }

        $checkboxes = ['delivery_activo', 'recojo_activo', 'comer_aqui_activo', 'efectivo_activo', 'yape_plin_activo', 'tarjeta_activo', 'apiperu_habilitado', 'smtp_enabled', 'clientes_web_activo', 'google_login_activo'];
        foreach ($checkboxes as $c) {
            guardarConfig($c, isset($_POST[$c]) ? '1' : '0');
        }

        $logo = subirImagen('logo', $carpetaUploads);
        if ($logo) guardarConfig('logo', $logo);

        $qr = subirImagen('yape_plin_qr', $carpetaUploads);
        if ($qr) guardarConfig('yape_plin_qr', $qr);

        // Manejo de certificado SUNAT
        if (isset($_FILES['sunat_certificado']) && $_FILES['sunat_certificado']['size'] > 0) {
            $file = $_FILES['sunat_certificado'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Error al subir certificado SUNAT.');
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pfx') {
                throw new RuntimeException('El certificado debe ser un archivo .pfx');
            }
            $nombre = 'sunat_' . time() . '.pfx';
            $ruta = $carpetaUploads . '/' . $nombre;
            if (!move_uploaded_file($file['tmp_name'], $ruta)) {
                throw new RuntimeException('No se pudo guardar el certificado.');
            }
            guardarConfig('sunat_certificado_path', 'uploads/' . $nombre);
        }

        $mensaje = 'Configuración guardada correctamente.';
        // Forzar recarga de config en esta petición
        $stmt = getDB()->query('SELECT clave, valor FROM configuracion');
        $GLOBALS['config_override'] = [];
    } catch (Throwable $e) {
        $error = 'Error al guardar: ' . $e->getMessage();
    }
}

// Releer configuración fresca desde BD (por si se acaba de guardar)
$configFresca = [];
foreach (getDB()->query('SELECT clave, valor FROM configuracion') as $row) {
    $configFresca[$row['clave']] = $row['valor'];
}
function c($k, $d = '') { global $configFresca; return $configFresca[$k] ?? $d; }

$colorPrimario = c('color_primario', '#1f6b3a');
$colorPrimarioFuerte = c('color_primario_fuerte', '#154d29');
$colorSecundario = c('color_secundario', '#3ea152');
$colorTexto = c('color_texto', '#1c2b22');
$colorFondo = c('color_fondo', '#f2f6f2');
?>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<style>
.color-config-wrap { display:grid; gap:14px; }
.color-input-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
.color-input-card { border:1px solid #e5e7eb; border-radius:14px; padding:10px; background:#fafafa; }
.color-input-card label { display:block; font-weight:600; margin-bottom:6px; }
.color-input-card .color-row { display:flex; gap:10px; align-items:center; }
.color-input-card input[type="color"] { width:44px; height:36px; border:none; background:transparent; padding:0; cursor:pointer; }
.color-hex { font-family:Consolas, monospace; font-size:12px; color:#374151; background:#fff; border:1px solid #d1d5db; border-radius:8px; padding:6px 8px; min-width:90px; text-align:center; }
.palette-label { font-size:12px; color:#6b7280; margin:2px 0 0; }
.palette-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
.palette-btn { border:1px solid #e5e7eb; border-radius:12px; background:#fff; padding:8px; cursor:pointer; text-align:left; transition:.18s ease; }
.palette-btn:hover { border-color:#cbd5e1; transform:translateY(-1px); box-shadow:0 4px 10px rgba(0,0,0,.06); }
.palette-swatches { display:flex; gap:6px; margin-bottom:6px; }
.palette-swatches span { width:18px; height:18px; border-radius:50%; border:1px solid rgba(0,0,0,.12); }
.palette-name { font-size:12px; font-weight:600; color:#111827; }
.color-preview { border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; background:#fff; }
.color-preview-header { padding:14px; color:#fff; }
.color-preview-title { font-size:14px; font-weight:700; margin:0 0 4px; }
.color-preview-sub { font-size:12px; opacity:.9; margin:0; }
.color-preview-body { padding:14px; }
.color-preview-card { background:#fff; border-radius:12px; padding:12px; box-shadow:0 6px 18px rgba(0,0,0,.08); }
.color-preview-btn { margin-top:10px; border:none; border-radius:10px; padding:8px 12px; color:#fff; font-weight:600; cursor:default; }
@media (max-width: 720px) {
    .color-input-grid { grid-template-columns:1fr; }
    .palette-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
}
</style>

<form method="POST" enctype="multipart/form-data">

<div class="config-tabs" id="config-tabs">
    <button type="button" class="config-tab-btn activo" data-tab="general"><i class="ti ti-settings"></i> General</button>
    <button type="button" class="config-tab-btn" data-tab="pagos"><i class="ti ti-credit-card"></i> Pagos</button>
    <button type="button" class="config-tab-btn" data-tab="smtp"><i class="ti ti-mail"></i> SMTP</button>
    <button type="button" class="config-tab-btn" data-tab="facturacion"><i class="ti ti-file-invoice"></i> Facturación</button>
    <button type="button" class="config-tab-btn" data-tab="apiperu"><i class="ti ti-api"></i> APIPERU</button>
    <button type="button" class="config-tab-btn" data-tab="clientes"><i class="ti ti-user-circle"></i> Clientes</button>
</div>

<div class="grid-dos-cards">
    <div class="card config-card" data-tab="general">
<h3><i class="ti ti-building-store"></i> Datos del negocio</h3>        <div class="form-group">
            <label>Nombre del negocio</label>
            <input type="text" name="nombre_negocio" value="<?= limpiar(c('nombre_negocio')) ?>" required>
        </div>
        <div class="form-group">
            <label>Dirección</label>
            <input type="text" name="direccion_local" value="<?= limpiar(c('direccion_local')) ?>">
        </div>
        <div class="form-group">
            <label>Logo (opcional)</label>
            <input type="file" name="logo" accept="image/*">
            <?php if (c('logo')): ?><img src="../uploads/<?= limpiar(c('logo')) ?>" class="thumb" style="margin-top:8px;"><?php endif; ?>
        </div>
        <div class="form-group">
            <label>Mensaje de bienvenida (uso futuro)</label>
            <input type="text" name="mensaje_bienvenida" value="<?= limpiar(c('mensaje_bienvenida')) ?>">
        </div>
    </div>

    <div class="card config-card" data-tab="general">
        <h3><i class="ti ti-palette"></i> Colores de la carta</h3>
        <p class="palette-label">Elige una paleta predefinida o ajusta cada color manualmente. Verás la vista previa al instante.</p>

        <div class="color-config-wrap">
            <div class="palette-grid" id="paletteGrid">
                <button type="button" class="palette-btn" data-p="#1f6b3a" data-pf="#154d29" data-s="#3ea152" data-t="#1c2b22" data-f="#f2f6f2">
                    <div class="palette-swatches"><span style="background:#1f6b3a"></span><span style="background:#3ea152"></span><span style="background:#1c2b22"></span><span style="background:#f2f6f2"></span></div>
                    <div class="palette-name">Verde Clásico</div>
                </button>
                <button type="button" class="palette-btn" data-p="#c2410c" data-pf="#7c2d12" data-s="#ea580c" data-t="#1f2937" data-f="#fff7ed">
                    <div class="palette-swatches"><span style="background:#c2410c"></span><span style="background:#ea580c"></span><span style="background:#1f2937"></span><span style="background:#fff7ed"></span></div>
                    <div class="palette-name">Naranja Brasa</div>
                </button>
                <button type="button" class="palette-btn" data-p="#1d4ed8" data-pf="#1e3a8a" data-s="#0ea5e9" data-t="#0f172a" data-f="#eff6ff">
                    <div class="palette-swatches"><span style="background:#1d4ed8"></span><span style="background:#0ea5e9"></span><span style="background:#0f172a"></span><span style="background:#eff6ff"></span></div>
                    <div class="palette-name">Azul Fresco</div>
                </button>
                <button type="button" class="palette-btn" data-p="#7c2d12" data-pf="#431407" data-s="#b45309" data-t="#292524" data-f="#fef7ed">
                    <div class="palette-swatches"><span style="background:#7c2d12"></span><span style="background:#b45309"></span><span style="background:#292524"></span><span style="background:#fef7ed"></span></div>
                    <div class="palette-name">Café Parrilla</div>
                </button>
                <button type="button" class="palette-btn" data-p="#be123c" data-pf="#881337" data-s="#e11d48" data-t="#1f2937" data-f="#fff1f2">
                    <div class="palette-swatches"><span style="background:#be123c"></span><span style="background:#e11d48"></span><span style="background:#1f2937"></span><span style="background:#fff1f2"></span></div>
                    <div class="palette-name">Rojo Promoción</div>
                </button>
                <button type="button" class="palette-btn" data-p="#334155" data-pf="#0f172a" data-s="#64748b" data-t="#0f172a" data-f="#f8fafc">
                    <div class="palette-swatches"><span style="background:#334155"></span><span style="background:#64748b"></span><span style="background:#0f172a"></span><span style="background:#f8fafc"></span></div>
                    <div class="palette-name">Minimal Neutro</div>
                </button>
            </div>

            <div class="color-input-grid">
                <div class="color-input-card">
                    <label>Color primario</label>
                    <div class="color-row">
                        <input type="color" name="color_primario" id="color_primario" value="<?= limpiar($colorPrimario) ?>">
                        <span class="color-hex" id="hex_color_primario"><?= limpiar($colorPrimario) ?></span>
                    </div>
                </div>
                <div class="color-input-card">
                    <label>Color gradiente superior</label>
                    <div class="color-row">
                        <input type="color" name="color_primario_fuerte" id="color_primario_fuerte" value="<?= limpiar($colorPrimarioFuerte) ?>">
                        <span class="color-hex" id="hex_color_primario_fuerte"><?= limpiar($colorPrimarioFuerte) ?></span>
                    </div>
                </div>
                <div class="color-input-card">
                    <label>Color secundario</label>
                    <div class="color-row">
                        <input type="color" name="color_secundario" id="color_secundario" value="<?= limpiar($colorSecundario) ?>">
                        <span class="color-hex" id="hex_color_secundario"><?= limpiar($colorSecundario) ?></span>
                    </div>
                </div>
                <div class="color-input-card">
                    <label>Color de texto</label>
                    <div class="color-row">
                        <input type="color" name="color_texto" id="color_texto" value="<?= limpiar($colorTexto) ?>">
                        <span class="color-hex" id="hex_color_texto"><?= limpiar($colorTexto) ?></span>
                    </div>
                </div>
                <div class="color-input-card">
                    <label>Color de fondo</label>
                    <div class="color-row">
                        <input type="color" name="color_fondo" id="color_fondo" value="<?= limpiar($colorFondo) ?>">
                        <span class="color-hex" id="hex_color_fondo"><?= limpiar($colorFondo) ?></span>
                    </div>
                </div>
            </div>

            <div class="color-preview" id="colorPreview">
                <div class="color-preview-header" id="previewHeader">
                    <p class="color-preview-title">Vista previa de la carta</p>
                    <p class="color-preview-sub">Así se verá el encabezado y botones</p>
                </div>
                <div class="color-preview-body" id="previewBody">
                    <div class="color-preview-card">
                        <div style="font-weight:700; margin-bottom:6px;" id="previewText">Pollo a la brasa + papas</div>
                        <div style="font-size:12px; opacity:.8;">Sabor recomendado por la casa</div>
                        <button type="button" class="color-preview-btn" id="previewBtn">Agregar al carrito</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




<div class="grid-dos-cards">

    <div class="card config-card" data-tab="general">
<h3><i class="ti ti-brand-whatsapp"></i> WhatsApp para recibir pedidos</h3>        <div class="form-group">
            <label>Número de WhatsApp (con código de país, sin +)</label>
            <input type="text" name="whatsapp_numero" value="<?= limpiar(c('whatsapp_numero')) ?>" placeholder="51987654321" required>
        </div>
    </div>

    <div class="card config-card" data-tab="general">
<h3><i class="ti ti-truck-delivery"></i> Entrega</h3>        <div class="form-check">
            <input type="checkbox" name="recojo_activo" id="recojo_activo" <?= c('recojo_activo')==='1'?'checked':'' ?>>
            <label for="recojo_activo" style="margin:0;">Permitir recojo en local</label>
        </div>
        <div class="form-check">
            <input type="checkbox" name="delivery_activo" id="delivery_activo" <?= c('delivery_activo')==='1'?'checked':'' ?>>
            <label for="delivery_activo" style="margin:0;">Permitir delivery</label>
        </div>
        <div class="form-check">
            <input type="checkbox" name="comer_aqui_activo" id="comer_aqui_activo" <?= c('comer_aqui_activo','1')==='1'?'checked':'' ?>>
            <label for="comer_aqui_activo" style="margin:0;">Permitir comer aqui</label>
        </div>
        <div class="form-group">
            <label>Costo de delivery (S/)</label>
            <input type="number" step="0.01" name="costo_delivery" value="<?= limpiar(c('costo_delivery')) ?>">
        </div>
    </div>



    
    <div class="card config-card" data-tab="pagos">
<h3><i class="ti ti-credit-card"></i> Métodos de pago</h3>
        <div class="form-check">
            <input type="checkbox" name="efectivo_activo" id="efectivo_activo" <?= c('efectivo_activo')==='1'?'checked':'' ?>>
            <label for="efectivo_activo" style="margin:0;">Efectivo al recibir</label>
        </div>

        <div class="form-check">
            <input type="checkbox" name="yape_plin_activo" id="yape_plin_activo" <?= c('yape_plin_activo')==='1'?'checked':'' ?>>
            <label for="yape_plin_activo" style="margin:0;">Yape / Plin</label>
        </div>
        <div class="form-group">
            <label>Número Yape/Plin</label>
            <input type="text" name="yape_plin_numero" value="<?= limpiar(c('yape_plin_numero')) ?>">
        </div>
        <div class="form-group">
            <label>Imagen QR de Yape/Plin</label>
            <input type="file" name="yape_plin_qr" accept="image/*">
            <?php if (c('yape_plin_qr')): ?><img src="../uploads/<?= limpiar(c('yape_plin_qr')) ?>" class="thumb" style="margin-top:8px;width:70px;height:70px;"><?php endif; ?>
        </div>

        <div class="form-check">
            <input type="checkbox" name="tarjeta_activo" id="tarjeta_activo" <?= c('tarjeta_activo')==='1'?'checked':'' ?>>
            <label for="tarjeta_activo" style="margin:0;">Tarjeta (Culqi)</label>
        </div>
        <div class="form-group">
            <label>Llave pública de Culqi (pk_...)</label>
            <input type="text" name="culqi_public_key" value="<?= limpiar(c('culqi_public_key')) ?>">
        </div>
        <div class="form-group">
            <label>Llave secreta de Culqi (sk_...)</label>
            <input type="text" name="culqi_secret_key" value="<?= limpiar(c('culqi_secret_key')) ?>">
            <small style="color:#888;">La encuentras en tu panel de Culqi &rarr; Llaves API. Usa las de <b>prueba</b> (pk_test/sk_test) hasta que actives tu cuenta.</small>
        </div>
    </div>

    <div class="card config-card" data-tab="smtp">
        <h3><i class="ti ti-mail"></i> Correo SMTP</h3>
        <p style="color:#666;margin-top:-6px;">Envía correo automático al cliente luego de la compra con adjuntos del comprobante (PDF/XML/CDR).</p>

        <div class="form-check">
            <input type="checkbox" name="smtp_enabled" id="smtp_enabled" value="1" <?= c('smtp_enabled')==='1'?'checked':'' ?>>
            <label for="smtp_enabled" style="margin:0;">Activar envío de correo al cliente</label>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Servidor SMTP</label>
                <input type="text" name="smtp_host" value="<?= limpiar(c('smtp_host')) ?>" placeholder="smtp.gmail.com">
            </div>
            <div class="form-group">
                <label>Puerto SMTP</label>
                <input type="number" name="smtp_port" value="<?= limpiar(c('smtp_port', '587')) ?>" min="1" max="65535" placeholder="587">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Usuario SMTP</label>
                <input type="text" name="smtp_username" value="<?= limpiar(c('smtp_username')) ?>" placeholder="usuario@dominio.com">
            </div>
            <div class="form-group">
                <label>Contraseña SMTP</label>
                <input type="password" name="smtp_password" value="<?= limpiar(c('smtp_password')) ?>" placeholder="••••••••">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Seguridad</label>
                <select name="smtp_secure">
                    <option value="tls" <?= c('smtp_secure', 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                    <option value="ssl" <?= c('smtp_secure') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                    <option value="none" <?= c('smtp_secure') === 'none' ? 'selected' : '' ?>>Sin cifrado</option>
                </select>
            </div>
            <div class="form-group">
                <label>Timeout (segundos)</label>
                <input type="number" name="smtp_timeout" value="<?= limpiar(c('smtp_timeout', '15')) ?>" min="5" max="120">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Email remitente</label>
                <input type="email" name="smtp_from_email" value="<?= limpiar(c('smtp_from_email')) ?>" placeholder="ventas@tuempresa.com">
            </div>
            <div class="form-group">
                <label>Nombre remitente</label>
                <input type="text" name="smtp_from_name" value="<?= limpiar(c('smtp_from_name', c('nombre_negocio', 'Carta Digital'))) ?>" placeholder="Carta Digital">
            </div>
        </div>

        <?php if (c('smtp_enabled') === '1' && c('smtp_host') && c('smtp_username')): ?>
            <div class="alerta-ok" style="margin-top:10px;">
                <i class="ti ti-check"></i> SMTP habilitado. Se intentará enviar correo al cliente al confirmar la compra.
            </div>
        <?php else: ?>
            <div class="alerta-error" style="margin-top:10px;">
                <i class="ti ti-alert-triangle"></i> Configura SMTP para activar el envío automático de comprobantes por email.
            </div>
        <?php endif; ?>
    </div>

    <div class="card config-card" data-tab="facturacion">
        <h3><i class="ti ti-receipt-2"></i> Facturación Electrónica Híbrida</h3>
        <p style="color:#666;margin-top:-6px;">Selecciona el sistema de facturación a usar: SUNAT Nativo o NubeFacT.</p>
        
        <div class="form-group">
            <label>Driver de Facturación Activo</label>
            <select name="facturacion_driver" required>
                <option value="native" <?= c('facturacion_driver', 'native') === 'native' ? 'selected' : '' ?>>
                    🏛️ SUNAT Nativo (Certificado Digital + SOL)
                </option>
                <option value="nubefact" <?= c('facturacion_driver') === 'nubefact' ? 'selected' : '' ?>>
                    ☁️ NubeFacT (API Cloud)
                </option>
            </select>
            <small style="color:#666;display:block;margin-top:6px;">
                • <b>SUNAT Nativo</b>: Requiere certificado digital (.pfx) y credenciales SOL (usuario + contraseña).<br>
                • <b>NubeFacT</b>: Requiere RUTA y TOKEN de tu cuenta en NubeFacT (más rápido, sin certificado).
            </small>
        </div>

        <div class="form-check">
            <input type="checkbox" name="facturacion_modo_hibrido" id="facturacion_modo_hibrido" value="1" <?= c('facturacion_modo_hibrido')==='1'?'checked':'' ?>>
            <label for="facturacion_modo_hibrido" style="margin:0;">
                Modo híbrido: Permitir ambos en paralelo (experimental - requiere ambos configurados)
            </label>
        </div>
        <small style="color:#666;display:block;margin-top:6px;">
            Si está activado, los pedidos pueden elegir qué tipo de facturación usar. Si está desactivado, 
            se usa solo el driver seleccionado arriba.
        </small>
    </div>

    <div class="card config-card" data-tab="facturacion">
        <h3><i class="ti ti-certificate"></i> SUNAT Nativo</h3>
        <p style="color:#666;margin-top:-6px;">Configuración para emisión directa a SUNAT usando certificado digital.</p>
        
        <div class="form-group">
            <label>RUC del Emisor (11 dígitos)</label>
            <input type="text" name="sunat_ruc_emisor" value="<?= limpiar(c('sunat_ruc_emisor')) ?>" placeholder="20123456789">
            <small style="color:#666;display:block;margin-top:6px;">Tu RUC como empresa. Debe estar registrado en SUNAT.</small>
        </div>

        <div class="form-group">
            <label>Razón Social</label>
            <input type="text" name="sunat_razon_social" value="<?= limpiar(c('sunat_razon_social')) ?>" placeholder="EMPRESA SAC">
        </div>

        <div class="form-group">
            <label>Nombre Comercial</label>
            <input type="text" name="sunat_nombre_comercial" value="<?= limpiar(c('sunat_nombre_comercial')) ?>" placeholder="Mi Restaurante">
        </div>

        <div class="form-group">
            <label>Usuario SOL (SUNAT)</label>
            <input type="text" name="sunat_usuario_sol" value="<?= limpiar(c('sunat_usuario_sol')) ?>" placeholder="usuario_sol">
            <small style="color:#666;display:block;margin-top:6px;">Usuario de acceso al Portal de SUNAT.</small>
        </div>

        <div class="form-group">
            <label>Contraseña SOL (SUNAT)</label>
            <input type="password" name="sunat_clave_sol" value="<?= limpiar(c('sunat_clave_sol')) ?>" placeholder="tu_clave_sol">
            <small style="color:#666;display:block;margin-top:6px;">Contraseña de acceso al Portal de SUNAT. Se guardará de forma segura.</small>
        </div>

        <div class="form-group">
            <label>Certificado Digital (.pfx)</label>
            <input type="file" name="sunat_certificado" accept=".pfx">
            <?php if (c('sunat_certificado_path')): ?>
                <small style="color:green;display:block;margin-top:6px;">✓ Certificado cargado: <?= limpiar(basename(c('sunat_certificado_path'))) ?></small>
            <?php else: ?>
                <small style="color:orange;display:block;margin-top:6px;">⚠ No hay certificado. Sube tu archivo .pfx descargado desde SUNAT.</small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Contraseña del Certificado</label>
            <input type="password" name="sunat_certificado_clave" value="<?= limpiar(c('sunat_certificado_clave')) ?>" placeholder="contraseña_cert">
            <small style="color:#666;display:block;margin-top:6px;">Contraseña que protegetue archivo .pfx. Se guardará de forma segura.</small>
        </div>

        <div class="form-group">
            <label>Dirección Fiscal</label>
            <input type="text" name="sunat_direccion" value="<?= limpiar(c('sunat_direccion')) ?>" placeholder="Av. Principal 123, Piso 2">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>UBIGEO</label>
                <input type="text" name="sunat_ubigeo" value="<?= limpiar(c('sunat_ubigeo', '150131')) ?>" maxlength="6" placeholder="150131">
                <small style="color:#666;display:block;margin-top:6px;">Código UBIGEO de 6 dígitos (INEI).</small>
            </div>
            <div class="form-group">
                <label>Modo SUNAT</label>
                <select name="sunat_modo">
                    <option value="demo" <?= c('sunat_modo') === 'demo' ? 'selected' : '' ?>>Demo (Pruebas)</option>
                    <option value="beta" <?= c('sunat_modo') === 'beta' ? 'selected' : '' ?>>Beta</option>
                    <option value="prod" <?= c('sunat_modo') === 'prod' ? 'selected' : '' ?>>Producción</option>
                </select>
                <small style="color:#666;display:block;margin-top:6px;">Demo = sin validación SUNAT real.</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Serie Boleta</label>
                <input type="text" name="sunat_serie_boleta" value="<?= limpiar(c('sunat_serie_boleta', 'B001')) ?>" maxlength="4" placeholder="B001">
            </div>
            <div class="form-group">
                <label>Serie Factura</label>
                <input type="text" name="sunat_serie_factura" value="<?= limpiar(c('sunat_serie_factura', 'F001')) ?>" maxlength="4" placeholder="F001">
            </div>
        </div>
    </div>

    <div class="card config-card" data-tab="facturacion">
        <h3><i class="ti ti-cloud"></i> NubeFacT API</h3>
        <p style="color:#666;margin-top:-6px;">Configuración para emisión a través del servicio en la nube NubeFacT.</p>
        
        <div class="form-group">
            <label>RUTA de API (URL)</label>
            <input type="text" name="nubefact_ruta" value="<?= limpiar(c('nubefact_ruta')) ?>" placeholder="https://api.nubefact.com/api/v1/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
            <small style="color:#666;display:block;margin-top:6px;">La encuentras en tu panel NubeFacT → API - Integración → Botón "Copiar RUTA".</small>
        </div>

        <div class="form-group">
            <label>TOKEN (Autenticación)</label>
            <input type="password" name="nubefact_token" value="<?= limpiar(c('nubefact_token')) ?>" placeholder="tu_token_largo_aqui">
            <small style="color:#666;display:block;margin-top:6px;">Token de acceso a tu cuenta. Se guardará de forma segura.</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Serie Boleta (NubeFacT)</label>
                <input type="text" name="nubefact_serie_boleta" value="<?= limpiar(c('nubefact_serie_boleta', 'BBB1')) ?>" maxlength="4" placeholder="BBB1">
                <small style="color:#666;display:block;margin-top:6px;">Debe existir en tu cuenta NubeFacT.</small>
            </div>
            <div class="form-group">
                <label>Serie Factura (NubeFacT, opcional)</label>
                <input type="text" name="nubefact_serie_factura" value="<?= limpiar(c('nubefact_serie_factura', 'FFF1')) ?>" maxlength="4" placeholder="FFF1">
                <small style="color:#666;display:block;margin-top:6px;">Déjalo en blanco si no emites facturas por NubeFacT.</small>
            </div>
        </div>

        <?php if (c('nubefact_ruta') && c('nubefact_token')): ?>
            <div class="alerta-ok" style="margin-top:10px;">
                <i class="ti ti-check"></i> NubeFacT está configurado. La API está lista.
            </div>
        <?php else: ?>
            <div class="alerta-error" style="margin-top:10px;">
                <i class="ti ti-alert-triangle"></i> NubeFacT aún no está completamente configurado (faltan RUTA y/o TOKEN).
            </div>
        <?php endif; ?>
    </div>

    <div class="card config-card" data-tab="apiperu">
        <h3><i class="ti ti-api"></i> APIPERU - Validación RENIEC/RUC</h3>
        <p style="color:#666;margin-top:-6px;">Consulta automática de DNI y RUC en el checkout para auto-llenar datos del cliente.</p>
    
        <div class="form-group">
            <label>Token APIPERU</label>
            <input type="password" name="apiperu_token" value="<?= limpiar(c('apiperu_token')) ?>" placeholder="qwerty123456">
            <small style="color:#666;display:block;margin-top:6px;">
                Obtén un token gratis en: <a href="https://apiperu.dev/account" target="_blank" style="color:#0066cc;">https://apiperu.dev/</a><br>
                Soporta: Validación de DNI (RENIEC) y RUC (SUNAT)
            </small>
        </div>

        <div class="form-check">
            <input type="checkbox" name="apiperu_habilitado" id="apiperu_habilitado" value="1" <?= c('apiperu_habilitado')==='1'?'checked':'' ?>>
            <label for="apiperu_habilitado" style="margin:0;">
                Habilitar validación automática en checkout
            </label>
        </div>
        <small style="color:#666;display:block;margin-top:6px;">
            Si está habilitado, los clientes podrán ver la validación y auto-llenado de datos al ingresar su DNI/RUC.
        </small>

        <?php if (c('apiperu_token')): ?>
            <div class="alerta-ok" style="margin-top:10px;">
                <i class="ti ti-check"></i> Token APIPERU configurado. Validación RENIEC/RUC disponible.
            </div>
        <?php else: ?>
            <div class="alerta-error" style="margin-top:10px;">
                <i class="ti ti-alert-triangle"></i> Token APIPERU no configurado. Ingresa un token para activar la validación.
            </div>
        <?php endif; ?>
    </div>

    <div class="card config-card" data-tab="clientes">
        <h3><i class="ti ti-users-group"></i> Cuentas de clientes</h3>
        <p style="color:#666;margin-top:-6px;">Permite que tus clientes creen cuenta, consulten su historial, fidelización y entren con Google.</p>

        <div class="form-check">
            <input type="checkbox" name="clientes_web_activo" id="clientes_web_activo" value="1" <?= c('clientes_web_activo', '1')==='1'?'checked':'' ?>>
            <label for="clientes_web_activo" style="margin:0;">Habilitar cuentas de clientes en la web</label>
        </div>

        <div class="form-check">
            <input type="checkbox" name="google_login_activo" id="google_login_activo" value="1" <?= c('google_login_activo', '0')==='1'?'checked':'' ?>>
            <label for="google_login_activo" style="margin:0;">Permitir acceso con Google</label>
        </div>

        <div class="form-group">
            <label>Google Client ID</label>
            <input type="text" name="google_client_id" value="<?= limpiar(c('google_client_id')) ?>" placeholder="1234567890-abcxyz.apps.googleusercontent.com">
            <small style="color:#666;display:block;margin-top:6px;">Crea una credencial OAuth Web en Google Cloud y pega aquí el Client ID. El login social funcionará cuando este valor exista y la opción esté activada.</small>
        </div>

        <?php if (c('google_login_activo') === '1' && c('google_client_id')): ?>
            <div class="alerta-ok" style="margin-top:10px;">
                <i class="ti ti-check"></i> Google Login listo para mostrarse en la web.
            </div>
        <?php else: ?>
            <div class="alerta-error" style="margin-top:10px;">
                <i class="ti ti-alert-triangle"></i> Si quieres acceso con Google, activa la opción y configura tu Client ID.
            </div>
        <?php endif; ?>
    </div>

    <button class="btn-principal" type="submit">Guardar configuración</button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const botones = Array.from(document.querySelectorAll('.config-tab-btn'));
    const cards = Array.from(document.querySelectorAll('.config-card'));

    function activar(tab) {
        botones.forEach(btn => btn.classList.toggle('activo', btn.dataset.tab === tab));
        cards.forEach(card => {
            card.classList.toggle('oculto-tab', card.dataset.tab !== tab);
        });
    }

    botones.forEach(btn => {
        btn.addEventListener('click', () => activar(btn.dataset.tab));
    });

    activar('general');

    const inpP = document.getElementById('color_primario');
    const inpPF = document.getElementById('color_primario_fuerte');
    const inpS = document.getElementById('color_secundario');
    const inpT = document.getElementById('color_texto');
    const inpF = document.getElementById('color_fondo');

    const hexP = document.getElementById('hex_color_primario');
    const hexPF = document.getElementById('hex_color_primario_fuerte');
    const hexS = document.getElementById('hex_color_secundario');
    const hexT = document.getElementById('hex_color_texto');
    const hexF = document.getElementById('hex_color_fondo');

    const previewHeader = document.getElementById('previewHeader');
    const previewBody = document.getElementById('previewBody');
    const previewText = document.getElementById('previewText');
    const previewBtn = document.getElementById('previewBtn');

    function normalizarColor(v, def) {
        const val = String(v || '').trim();
        return /^#[0-9A-Fa-f]{6}$/.test(val) ? val : def;
    }

    function aplicarPreview() {
        if (!inpP || !inpPF || !inpS || !inpT || !inpF) return;
        inpP.value = normalizarColor(inpP.value, '#1f6b3a');
        inpPF.value = normalizarColor(inpPF.value, '#154d29');
        inpS.value = normalizarColor(inpS.value, '#3ea152');
        inpT.value = normalizarColor(inpT.value, '#1c2b22');
        inpF.value = normalizarColor(inpF.value, '#f2f6f2');

        if (hexP) hexP.textContent = inpP.value;
        if (hexPF) hexPF.textContent = inpPF.value;
        if (hexS) hexS.textContent = inpS.value;
        if (hexT) hexT.textContent = inpT.value;
        if (hexF) hexF.textContent = inpF.value;

        if (previewHeader) {
            previewHeader.style.background = `linear-gradient(160deg, ${inpPF.value} 0%, ${inpP.value} 55%, ${inpS.value} 100%)`;
        }
        if (previewBody) previewBody.style.background = inpF.value;
        if (previewText) previewText.style.color = inpT.value;
        if (previewBtn) previewBtn.style.background = inpP.value;
    }

    [inpP, inpPF, inpS, inpT, inpF].forEach(inp => inp && inp.addEventListener('input', aplicarPreview));

    document.querySelectorAll('.palette-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!inpP || !inpPF || !inpS || !inpT || !inpF) return;
            inpP.value = this.dataset.p || inpP.value;
            inpPF.value = this.dataset.pf || inpPF.value;
            inpS.value = this.dataset.s || inpS.value;
            inpT.value = this.dataset.t || inpT.value;
            inpF.value = this.dataset.f || inpF.value;
            aplicarPreview();
        });
    });

    aplicarPreview();
});
</script>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
