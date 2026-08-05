<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requerirRol(['admin']);

$tituloPagina = 'Popups Frontend';
$paginaActual = 'popups';

$db = getDB();
asegurarTablaPopupsFrontend();

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'guardar') {
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $titulo = trim($_POST['titulo'] ?? '');
            $tipoContenido = ($_POST['tipo_contenido'] ?? 'texto') === 'html' ? 'html' : 'texto';
            $contenido = trim($_POST['contenido'] ?? '');
            $cssCustom = trim($_POST['css_custom'] ?? '');
            $jsCustom = trim($_POST['js_custom'] ?? '');
            $mostrarUnaVez = isset($_POST['mostrar_una_vez']) ? 1 : 0;
            $orden = (int)($_POST['orden'] ?? 0);
            $activo = isset($_POST['activo']) ? 1 : 0;

            if ($nombre === '') {
                throw new RuntimeException('Debes ingresar un nombre para identificar el popup.');
            }

            if ($contenido === '') {
                throw new RuntimeException('Debes ingresar contenido para el popup.');
            }

            if ($id > 0) {
                $stmt = $db->prepare('UPDATE frontend_popups SET nombre=:nombre, titulo=:titulo, tipo_contenido=:tipo, contenido=:contenido, css_custom=:css_custom, js_custom=:js_custom, mostrar_una_vez=:mostrar_una_vez, orden=:orden, activo=:activo WHERE id=:id');
                $stmt->execute([
                    'nombre' => $nombre,
                    'titulo' => $titulo !== '' ? $titulo : null,
                    'tipo' => $tipoContenido,
                    'contenido' => $contenido,
                    'css_custom' => $cssCustom !== '' ? $cssCustom : null,
                    'js_custom' => $jsCustom !== '' ? $jsCustom : null,
                    'mostrar_una_vez' => $mostrarUnaVez,
                    'orden' => $orden,
                    'activo' => $activo,
                    'id' => $id,
                ]);
            } else {
                $stmt = $db->prepare('INSERT INTO frontend_popups (nombre, titulo, tipo_contenido, contenido, css_custom, js_custom, mostrar_una_vez, orden, activo) VALUES (:nombre, :titulo, :tipo, :contenido, :css_custom, :js_custom, :mostrar_una_vez, :orden, :activo)');
                $stmt->execute([
                    'nombre' => $nombre,
                    'titulo' => $titulo !== '' ? $titulo : null,
                    'tipo' => $tipoContenido,
                    'contenido' => $contenido,
                    'css_custom' => $cssCustom !== '' ? $cssCustom : null,
                    'js_custom' => $jsCustom !== '' ? $jsCustom : null,
                    'mostrar_una_vez' => $mostrarUnaVez,
                    'orden' => $orden,
                    'activo' => $activo,
                ]);
            }

            $mensaje = 'Popup guardado correctamente.';
        } elseif ($accion === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare('DELETE FROM frontend_popups WHERE id = :id')->execute(['id' => $id]);
            $mensaje = 'Popup eliminado correctamente.';
        }
    } catch (Throwable $e) {
        $error = 'Ocurrió un error: ' . $e->getMessage();
    }
}

$popups = $db->query('SELECT * FROM frontend_popups ORDER BY orden ASC, id DESC')->fetchAll();

require __DIR__ . '/_layout_top.php';
?>

<style>
.popup-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
}
.popup-template-card {
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    padding: 16px;
    min-height: 178px;
    color: #fff;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.14);
}
.popup-template-card::after {
    content: '';
    position: absolute;
    inset: auto -30px -30px auto;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    filter: blur(4px);
}
.popup-template-card h4 {
    position: relative;
    z-index: 1;
    margin: 0 0 6px;
    font-size: 16px;
}
.popup-template-card p {
    position: relative;
    z-index: 1;
    margin: 0;
    font-size: 12px;
    line-height: 1.5;
    color: rgba(255,255,255,0.92);
}
.popup-template-badges {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}
.popup-template-badges span {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,0.16);
    font-size: 11px;
    font-weight: 700;
}
.popup-template-actions {
    position: relative;
    z-index: 1;
    margin-top: 14px;
}
.popup-template-actions button {
    width: 100%;
}
.popup-template-card.bienvenida { background: linear-gradient(135deg, #1d4ed8, #06b6d4); }
.popup-template-card.relampago { background: linear-gradient(135deg, #f97316, #ef4444); }
.popup-template-card.delivery { background: linear-gradient(135deg, #0f766e, #14b8a6); }
.popup-template-card.combo { background: linear-gradient(135deg, #7c3aed, #ec4899); }
.popup-template-card.info { background: linear-gradient(135deg, #111827, #334155); }
.popup-template-help {
    margin: -6px 0 12px;
    color: #64748b;
    font-size: 12px;
}
.popup-template-toolbar {
    margin-bottom: 14px;
    padding: 14px;
    border-radius: 14px;
    background: #fff7ed;
    border: 1px solid #fed7aa;
}
.popup-template-toolbar strong {
    display: block;
    margin-bottom: 4px;
    color: #9a3412;
}
body.modo-oscuro .popup-template-toolbar {
    background: rgba(249, 115, 22, 0.08);
    border-color: rgba(249, 115, 22, 0.24);
}
body.modo-oscuro .popup-template-help {
    color: #94a3b8;
}
@media (max-width: 720px) {
    .popup-templates-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php if ($mensaje): ?><div class="alerta-ok"><?= limpiar($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta-error"><?= limpiar($error) ?></div><?php endif; ?>

<button class="btn-nuevo" type="button" onclick="abrirModalPopup()">+ Nuevo popup</button>

<div class="card">
    <h3 style="margin-bottom:8px;">Templates predeterminados</h3>
    <p class="popup-template-help">Carga un diseño bonito y animado con un clic, luego edítalo a tu gusto antes de guardarlo.</p>
    <div class="popup-templates-grid">
        <article class="popup-template-card bienvenida">
            <div>
                <h4>Bienvenida premium</h4>
                <p>Ideal para saludar al cliente, presentar el restaurante y destacar una llamada a la acción elegante.</p>
                <div class="popup-template-badges"><span>Bienvenida</span><span>Animado</span><span>Elegante</span></div>
            </div>
            <div class="popup-template-actions"><button type="button" class="btn btn-secundario" onclick="usarTemplatePopup('bienvenida')">Usar template</button></div>
        </article>
        <article class="popup-template-card relampago">
            <div>
                <h4>Oferta relámpago</h4>
                <p>Perfecto para combos, descuentos agresivos y promociones que necesitan urgencia visual.</p>
                <div class="popup-template-badges"><span>Oferta</span><span>Urgencia</span><span>CTA</span></div>
            </div>
            <div class="popup-template-actions"><button type="button" class="btn btn-secundario" onclick="usarTemplatePopup('relampago')">Usar template</button></div>
        </article>
        <article class="popup-template-card delivery">
            <div>
                <h4>Delivery y recojo</h4>
                <p>Sirve para explicar cobertura, tiempos y ventajas de pedir por delivery o pasar a recoger.</p>
                <div class="popup-template-badges"><span>Entrega</span><span>Informativo</span><span>Claro</span></div>
            </div>
            <div class="popup-template-actions"><button type="button" class="btn btn-secundario" onclick="usarTemplatePopup('delivery')">Usar template</button></div>
        </article>
        <article class="popup-template-card combo">
            <div>
                <h4>Combo estrella</h4>
                <p>Diseñado para empujar un producto fuerte con visual potente, chips de beneficios y mensaje emocional.</p>
                <div class="popup-template-badges"><span>Combo</span><span>Destacado</span><span>Venta</span></div>
            </div>
            <div class="popup-template-actions"><button type="button" class="btn btn-secundario" onclick="usarTemplatePopup('combo')">Usar template</button></div>
        </article>
        <article class="popup-template-card info">
            <div>
                <h4>Información útil</h4>
                <p>Úsalo para horarios, políticas, métodos de pago, novedades o cualquier aviso operativo del negocio.</p>
                <div class="popup-template-badges"><span>Horario</span><span>Pagos</span><span>Importante</span></div>
            </div>
            <div class="popup-template-actions"><button type="button" class="btn btn-secundario" onclick="usarTemplatePopup('info')">Usar template</button></div>
        </article>
    </div>
</div>

<div class="card">
    <div class="tabla-scroll">
        <table>
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Mostrar</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($popups as $popup): ?>
                <tr>
                    <td><?= (int)$popup['orden'] ?></td>
                    <td>
                        <strong><?= limpiar($popup['nombre']) ?></strong>
                        <?php if (!empty($popup['titulo'])): ?>
                            <div style="font-size:12px;color:#64748b;margin-top:4px;"><?= limpiar($popup['titulo']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= $popup['tipo_contenido'] === 'html' ? 'HTML/CSS/JS' : 'Texto plano' ?></td>
                    <td><?= (int)$popup['mostrar_una_vez'] === 1 ? 'Una vez' : 'Siempre' ?></td>
                    <td><?= (int)$popup['activo'] === 1 ? '<span class="badge badge-pagado">Activo</span>' : '<span class="badge badge-cancelado">Oculto</span>' ?></td>
                    <td>
                        <button type="button" class="btn-icono-accion btn-icono-editar btn-editar-popup" title="Editar" aria-label="Editar popup"
                            data-popup="<?= htmlspecialchars(json_encode($popup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>">
                            <svg class="icono-accion-svg" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 20h4L18.5 9.5a2.121 2.121 0 0 0-3-3L5 17v3z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 6l4 4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <form method="POST" style="display:inline" class="form-eliminar-popup">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?= (int)$popup['id'] ?>">
                            <button type="submit" class="btn-icono-accion btn-icono-eliminar" title="Eliminar" aria-label="Eliminar popup">
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
                <?php if (empty($popups)): ?>
                <tr><td colspan="6" style="text-align:center;color:#999;">No hay popups configurados todavía.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="modalPopupFrontend">
    <div class="modal-box" style="max-width:760px;">
        <h3 id="modalPopupFrontendTitulo" style="margin-bottom:14px;">Nuevo popup</h3>
        <form method="POST">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" id="popupId">

            <div class="popup-template-toolbar">
                <strong>Templates rápidos</strong>
                <span style="font-size:12px;color:#7c2d12;">Puedes cargar una plantilla aquí mismo y luego modificar cualquier texto, color o código.</span>
                <div class="popup-template-badges" style="margin-top:10px;">
                    <span style="cursor:pointer;" onclick="usarTemplatePopup('bienvenida')">Bienvenida</span>
                    <span style="cursor:pointer;" onclick="usarTemplatePopup('relampago')">Oferta</span>
                    <span style="cursor:pointer;" onclick="usarTemplatePopup('delivery')">Delivery</span>
                    <span style="cursor:pointer;" onclick="usarTemplatePopup('combo')">Combo</span>
                    <span style="cursor:pointer;" onclick="usarTemplatePopup('info')">Información</span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Nombre interno</label>
                    <input type="text" name="nombre" id="popupNombre" required>
                </div>
                <div class="form-group">
                    <label>Título visible (opcional)</label>
                    <input type="text" name="titulo" id="popupTitulo">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tipo de contenido</label>
                    <select name="tipo_contenido" id="popupTipoContenido">
                        <option value="texto">Texto plano</option>
                        <option value="html">HTML/CSS/JS</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Orden</label>
                    <input type="number" name="orden" id="popupOrden" min="0" value="0">
                </div>
            </div>

            <div class="form-group">
                <label>Contenido</label>
                <textarea name="contenido" id="popupContenido" rows="8" placeholder="Escribe el texto del popup o pega aquí tu HTML si eliges el modo avanzado." required></textarea>
            </div>

            <div class="form-group" id="grupoPopupCss">
                <label>CSS personalizado (opcional)</label>
                <textarea name="css_custom" id="popupCss" rows="5" placeholder=".mi-clase { color: red; }"></textarea>
            </div>

            <div class="form-group" id="grupoPopupJs">
                <label>JS personalizado (opcional)</label>
                <textarea name="js_custom" id="popupJs" rows="5" placeholder="console.log('popup listo');"></textarea>
            </div>

            <div class="form-check">
                <input type="checkbox" name="mostrar_una_vez" id="popupMostrarUnaVez">
                <label for="popupMostrarUnaVez" style="margin:0;">Mostrar solo una vez por navegador</label>
            </div>

            <div class="form-check">
                <input type="checkbox" name="activo" id="popupActivo" checked>
                <label for="popupActivo" style="margin:0;">Popup activo</label>
            </div>

            <button class="btn-principal" type="submit">Guardar popup</button>
            <button class="btn btn-secundario" type="button" style="width:100%;margin-top:8px;" onclick="cerrarModalPopup()">Cancelar</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalConfirmarEliminarPopup">
    <div class="modal-box modal-confirmar">
        <div class="modal-confirmar-icono"><i class="ti ti-alert-triangle"></i></div>
        <h3>¿Eliminar este popup?</h3>
        <p>Esta acción no se puede deshacer.</p>
        <div class="modal-confirmar-botones">
            <button type="button" class="btn btn-secundario" id="btnCancelarEliminarPopup">Cancelar</button>
            <button type="button" class="btn btn-peligro-solido" id="btnConfirmarEliminarPopup"><i class="ti ti-trash"></i> Sí, eliminar</button>
        </div>
    </div>
</div>

<script>
const POPUP_TEMPLATES = {
    bienvenida: {
        nombre: 'Bienvenida premium',
        titulo: 'Bienvenido a nuestra carta digital',
        tipo_contenido: 'html',
        orden: 1,
        mostrar_una_vez: true,
        activo: true,
        contenido: `<div class="tpl-bienvenida-wrap"><div class="tpl-bienvenida-glow"></div><div class="tpl-bienvenida-icon"><svg id="giftSvg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="width:80px;height:80px;"><rect x="15" y="35" width="70" height="50" rx="8" fill="none" stroke="#fff" stroke-width="2"/><line x1="50" y1="35" x2="50" y2="85" stroke="#fff" stroke-width="2"/><line x1="15" y1="52" x2="85" y2="52" stroke="#fff" stroke-width="2"/><circle cx="50" cy="18" r="12" fill="#fff"/><rect x="42" y="25" width="16" height="10" rx="2" fill="#fff"/></svg></div><div class="tpl-bienvenida-chip">Bienvenido</div><h2>Sabores que merecen un primer vistazo inolvidable</h2><p>Descubre nuestros platos más pedidos, promociones exclusivas y la forma más rápida de pedir desde tu celular.</p><div class="tpl-bienvenida-actions"><a href="#cat-1" class="tpl-bienvenida-btn">Ver la carta</a><span class="tpl-bienvenida-mini">Prepara tu pedido en menos de 1 minuto</span></div></div>`,
        css_custom: `.tpl-bienvenida-wrap{position:relative;overflow:hidden;padding:28px 22px;border-radius:24px;background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 45%,#22d3ee 100%);color:#fff;text-align:left}.tpl-bienvenida-icon{position:absolute;top:20px;right:20px;opacity:.2;z-index:0}.tpl-bienvenida-glow{position:absolute;top:-30px;right:-20px;width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,.18);filter:blur(4px);animation:tplBienvenidaFloat 4.8s ease-in-out infinite}.tpl-bienvenida-chip{display:inline-flex;padding:6px 12px;border-radius:999px;background:rgba(255,255,255,.14);font-size:12px;font-weight:800;letter-spacing:.04em;margin-bottom:14px;position:relative;z-index:1}.tpl-bienvenida-wrap h2{font-size:28px;line-height:1.08;margin:0 0 12px;font-weight:900;max-width:420px;position:relative;z-index:1}.tpl-bienvenida-wrap p{font-size:14px;line-height:1.65;max-width:430px;color:rgba(255,255,255,.92);margin:0 0 18px;position:relative;z-index:1}.tpl-bienvenida-actions{display:flex;flex-wrap:wrap;align-items:center;gap:12px;position:relative;z-index:1}.tpl-bienvenida-btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 18px;border-radius:999px;background:#fff;color:#0f172a;font-weight:800;text-decoration:none;box-shadow:0 10px 24px rgba(15,23,42,.28);animation:tplBienvenidaBtn 1.9s ease-in-out infinite}.tpl-bienvenida-mini{font-size:12px;color:rgba(255,255,255,.8)}@keyframes tplBienvenidaFloat{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(12px) scale(1.06)}}@keyframes tplBienvenidaBtn{0%,100%{transform:translateY(0)}50%{transform:translateY(-3px)}}`,
        js_custom: `(function(){let angle=0;const giftSvg=document.getElementById('giftSvg');if(!giftSvg)return;setInterval(function(){angle=(angle+3)%360;giftSvg.style.transform='rotate('+angle+'deg)';},30);})();`
    },
    relampago: {
        nombre: 'Oferta relámpago',
        titulo: 'Promo flash del día',
        tipo_contenido: 'html',
        orden: 2,
        mostrar_una_vez: false,
        activo: true,
        contenido: `<div class="tpl-flash-wrap"><div class="tpl-flash-icon"><svg id="rayoSvg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="width:70px;height:70px;"><polygon points="50,10 65,40 45,40 70,90 30,50 50,50" fill="#fff" stroke="#fff" stroke-width="1.5"/></svg></div><div class="tpl-flash-badge">HOY</div><h2>2x1 en productos seleccionados</h2><p>Activa tu promo antes de que termine. Ideal para hamburguesas, pizzas, broasters o combos del día.</p><div class="tpl-flash-strip"><span>Entrega rápida</span><span>Stock limitado</span><span>Solo online</span></div><div class="tpl-flash-bar"><span></span></div><div class="tpl-flash-footer"><strong>Usa este popup para empujar ventas rápidas.</strong></div></div>`,
        css_custom: `.tpl-flash-wrap{padding:26px 22px;border-radius:24px;background:radial-gradient(circle at top right,#fb923c 0%,#f97316 24%,#ef4444 100%);color:#fff;position:relative;overflow:hidden}.tpl-flash-icon{position:absolute;top:10px;right:15px;opacity:.15;animation:tplFlashShake .1s infinite}@keyframes tplFlashShake{0%,100%{transform:translateX(0)}50%{transform:translateX(2px)}}.tpl-flash-badge{display:inline-flex;padding:6px 12px;border-radius:999px;background:#fff;color:#b91c1c;font-size:11px;font-weight:900;letter-spacing:.08em;animation:tplFlashPulse 1.1s ease-in-out infinite}.tpl-flash-wrap h2{margin:14px 0 10px;font-size:30px;line-height:1.02;font-weight:900}.tpl-flash-wrap p{margin:0 0 14px;font-size:14px;line-height:1.6;color:rgba(255,255,255,.92)}.tpl-flash-strip{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}.tpl-flash-strip span{padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.16);font-size:11px;font-weight:700}.tpl-flash-bar{height:8px;border-radius:999px;background:rgba(255,255,255,.16);overflow:hidden}.tpl-flash-bar span{display:block;height:100%;width:38%;border-radius:999px;background:#fff;animation:tplFlashBar 2.8s ease-in-out infinite}.tpl-flash-footer{margin-top:16px;font-size:12px;color:rgba(255,255,255,.84)}@keyframes tplFlashPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.08)}}@keyframes tplFlashBar{0%{width:18%}50%{width:88%}100%{width:34%}}`,
        js_custom: `(function(){const rayoSvg=document.getElementById('rayoSvg');if(!rayoSvg)return;setInterval(function(){rayoSvg.style.opacity='1';setTimeout(function(){rayoSvg.style.opacity='0.5';},100);setTimeout(function(){rayoSvg.style.opacity='1';},200);},800);})();`
    },
    delivery: {
        nombre: 'Delivery y recojo',
        titulo: 'Cómo recibir tu pedido',
        tipo_contenido: 'html',
        orden: 3,
        mostrar_una_vez: true,
        activo: true,
        contenido: `<div class="tpl-delivery-wrap"><div class="tpl-delivery-head"><div><small>INFORMACIÓN</small><h2>Delivery y recojo sin complicaciones</h2></div><div class="tpl-delivery-icon-wrap"><svg id="scooterSvg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="width:60px;height:60px;"><circle cx="25" cy="65" r="8" fill="none" stroke="#fff" stroke-width="2"/><circle cx="75" cy="65" r="8" fill="none" stroke="#fff" stroke-width="2"/><rect x="30" y="30" width="35" height="20" rx="4" fill="none" stroke="#fff" stroke-width="2"/><line x1="35" y1="50" x2="25" y2="65" stroke="#fff" stroke-width="2"/><line x1="65" y1="50" x2="75" y2="65" stroke="#fff" stroke-width="2"/><polyline points="50,30 55,20 45,20" fill="#fff"/></svg></div></div><div class="tpl-delivery-grid"><div class="tpl-delivery-item"><strong>Delivery</strong><span>Llega rápido a tu zona con seguimiento claro del pedido.</span></div><div class="tpl-delivery-item"><strong>Recojo</strong><span>Pide desde aquí y recoge sin hacer cola en el local.</span></div><div class="tpl-delivery-item"><strong>Pago</strong><span>Acepta efectivo, Yape/Plin o tarjeta según tu configuración.</span></div></div></div>`,
        css_custom: `.tpl-delivery-wrap{padding:24px;border-radius:24px;background:linear-gradient(145deg,#042f2e,#0f766e 42%,#14b8a6 100%);color:#fff;overflow:hidden}.tpl-delivery-head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px}.tpl-delivery-head small{display:block;font-size:11px;font-weight:800;letter-spacing:.08em;opacity:.78;margin-bottom:6px}.tpl-delivery-head h2{margin:0;font-size:27px;line-height:1.08;font-weight:900;max-width:360px}.tpl-delivery-icon-wrap{width:80px;height:80px;border-radius:20px;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center}.tpl-delivery-grid{display:grid;gap:10px}.tpl-delivery-item{padding:14px 16px;border-radius:18px;background:rgba(255,255,255,.12);backdrop-filter:blur(6px)}.tpl-delivery-item strong{display:block;font-size:14px;margin-bottom:4px}.tpl-delivery-item span{font-size:13px;line-height:1.5;color:rgba(255,255,255,.9)}`,
        js_custom: `(function(){let posX=0;const scooterSvg=document.getElementById('scooterSvg');if(!scooterSvg)return;setInterval(function(){posX=(posX+2)%100;scooterSvg.style.transform='translateX('+(posX-50)+'px)';},30);})();`
    },
    combo: {
        nombre: 'Combo estrella',
        titulo: 'Combo recomendado',
        tipo_contenido: 'html',
        orden: 4,
        mostrar_una_vez: false,
        activo: true,
        contenido: `<div class="tpl-combo-wrap"><div class="tpl-combo-plates"><svg id="plateSvg1" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:40px;height:40px;top:10px;left:20px;"><circle cx="25" cy="25" r="20" fill="none" stroke="#fff" stroke-width="1.5"/><ellipse cx="25" cy="20" rx="18" ry="12" fill="none" stroke="#fff" stroke-width="1.5"/></svg><svg id="plateSvg2" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:35px;height:35px;top:40px;right:30px;"><circle cx="25" cy="25" r="20" fill="none" stroke="#fff" stroke-width="1.5"/><ellipse cx="25" cy="20" rx="18" ry="12" fill="none" stroke="#fff" stroke-width="1.5"/></svg><svg id="plateSvg3" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:38px;height:38px;bottom:15px;left:35px;"><circle cx="25" cy="25" r="20" fill="none" stroke="#fff" stroke-width="1.5"/><ellipse cx="25" cy="20" rx="18" ry="12" fill="none" stroke="#fff" stroke-width="1.5"/></svg></div><div class="tpl-combo-ribbon">TOP SELLER</div><h2>El combo que más está pidiendo la gente</h2><p>Usa este template para empujar un plato fuerte, combo familiar o pack de temporada con mensaje emocional y visual premium.</p><div class="tpl-combo-chips"><span>Bebida incluida</span><span>Ideal para 2 o 4 personas</span><span>Más ahorro</span></div><button type="button" class="tpl-combo-btn">Quiero verlo ahora</button></div>`,
        css_custom: `.tpl-combo-wrap{position:relative;padding:26px 22px;border-radius:26px;background:linear-gradient(135deg,#581c87 0%,#7c3aed 35%,#ec4899 100%);color:#fff;overflow:hidden;text-align:left;min-height:280px}.tpl-combo-plates{position:absolute;top:0;right:0;width:200px;height:200px;opacity:.15}#plateSvg1,#plateSvg2,#plateSvg3{transform-origin:center;animation:tplComboPlateFloat 3s ease-in-out infinite}#plateSvg2{animation-delay:.3s}#plateSvg3{animation-delay:.6s}@keyframes tplComboPlateFloat{0%,100%{transform:translateY(0) rotate(0deg)}50%{transform:translateY(-10px) rotate(5deg)}}.tpl-combo-ribbon{display:inline-flex;padding:6px 12px;border-radius:999px;background:rgba(255,255,255,.16);font-size:11px;font-weight:900;letter-spacing:.08em;margin-bottom:14px;position:relative;z-index:2}.tpl-combo-wrap h2{margin:0 0 10px;font-size:29px;line-height:1.04;font-weight:900;max-width:430px;position:relative;z-index:2}.tpl-combo-wrap p{margin:0 0 14px;font-size:14px;line-height:1.62;color:rgba(255,255,255,.92);position:relative;z-index:2}.tpl-combo-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;position:relative;z-index:2}.tpl-combo-chips span{padding:6px 10px;border-radius:999px;background:rgba(255,255,255,.14);font-size:11px;font-weight:700}.tpl-combo-btn{border:none;padding:12px 16px;border-radius:14px;background:#fff;color:#6d28d9;font-weight:900;cursor:pointer;box-shadow:0 12px 28px rgba(88,28,135,.28);animation:tplComboGlow 2s ease-in-out infinite;position:relative;z-index:2}@keyframes tplComboGlow{0%,100%{transform:scale(1);box-shadow:0 12px 28px rgba(88,28,135,.28)}50%{transform:scale(1.03);box-shadow:0 18px 34px rgba(236,72,153,.34)}}`,
        js_custom: `(function(){document.querySelectorAll('.tpl-combo-btn').forEach(function(btn){btn.onclick=function(){this.textContent='Listo para vender';};});})();`
    },
    info: {
        nombre: 'Información útil',
        titulo: 'Aviso importante para clientes',
        tipo_contenido: 'html',
        orden: 5,
        mostrar_una_vez: true,
        activo: true,
        contenido: `<div class="tpl-info-wrap"><div class="tpl-info-icon-wrap"><svg id="infoSvg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" style="width:50px;height:50px;"><circle cx="50" cy="50" r="45" fill="none" stroke="#fbbf24" stroke-width="2"/><line x1="50" y1="30" x2="50" y2="45" stroke="#fbbf24" stroke-width="3" stroke-linecap="round"/><circle cx="50" cy="60" r="4" fill="#fbbf24"/></svg></div><div class="tpl-info-body"><h2>Horario, pagos y tiempos de atención</h2><p>Puedes usar este popup para informar horarios especiales, cobertura de delivery, métodos de pago o cualquier novedad importante.</p><ul><li>Atendemos de 12:00 pm a 11:00 pm</li><li>Pagos por Yape, Plin, tarjeta y efectivo</li><li>Tiempo promedio: 25 a 40 minutos</li></ul></div></div>`,
        css_custom: `.tpl-info-wrap{display:flex;gap:16px;align-items:flex-start;padding:24px;border-radius:24px;background:linear-gradient(135deg,#0f172a,#334155);color:#fff}.tpl-info-icon-wrap{width:70px;height:70px;border-radius:16px;background:#fbbf24;display:flex;align-items:center;justify-content:center;flex:0 0 auto}.tpl-info-body h2{margin:0 0 10px;font-size:26px;line-height:1.08;font-weight:900}.tpl-info-body p{margin:0 0 12px;font-size:14px;line-height:1.6;color:rgba(255,255,255,.9)}.tpl-info-body ul{margin:0;padding-left:18px}.tpl-info-body li{font-size:13px;line-height:1.7;color:rgba(255,255,255,.9)}`,
        js_custom: `(function(){let rotation=0;const infoSvg=document.getElementById('infoSvg');if(!infoSvg)return;setInterval(function(){rotation=(rotation+1.5)%360;infoSvg.style.transform='rotate('+rotation+'deg)';},50);})();`
    }
};

function actualizarModoPopup() {
    const tipo = document.getElementById('popupTipoContenido').value;
    const grupoCss = document.getElementById('grupoPopupCss');
    const grupoJs = document.getElementById('grupoPopupJs');
    const contenido = document.getElementById('popupContenido');

    const avanzado = tipo === 'html';
    grupoCss.style.display = avanzado ? '' : 'none';
    grupoJs.style.display = avanzado ? '' : 'none';
    contenido.placeholder = avanzado
        ? 'Pega aquí tu HTML del modal. También puedes usar CSS y JS en los campos de abajo.'
        : 'Escribe aquí el texto plano que se mostrará en el popup.';
}

function usarTemplatePopup(key) {
    const tpl = POPUP_TEMPLATES[key];
    if (!tpl) {
        return;
    }

    abrirModalPopup();
    document.getElementById('popupNombre').value = tpl.nombre;
    document.getElementById('popupTitulo').value = tpl.titulo;
    document.getElementById('popupTipoContenido').value = tpl.tipo_contenido;
    document.getElementById('popupContenido').value = tpl.contenido;
    document.getElementById('popupCss').value = tpl.css_custom;
    document.getElementById('popupJs').value = tpl.js_custom;
    document.getElementById('popupMostrarUnaVez').checked = !!tpl.mostrar_una_vez;
    document.getElementById('popupOrden').value = tpl.orden;
    document.getElementById('popupActivo').checked = !!tpl.activo;
    actualizarModoPopup();
}

function abrirModalPopup(popup) {
    document.getElementById('modalPopupFrontendTitulo').textContent = popup ? 'Editar popup' : 'Nuevo popup';
    document.getElementById('popupId').value = popup ? popup.id : '';
    document.getElementById('popupNombre').value = popup ? popup.nombre : '';
    document.getElementById('popupTitulo').value = popup ? (popup.titulo || '') : '';
    document.getElementById('popupTipoContenido').value = popup ? popup.tipo_contenido : 'texto';
    document.getElementById('popupContenido').value = popup ? (popup.contenido || '') : '';
    document.getElementById('popupCss').value = popup ? (popup.css_custom || '') : '';
    document.getElementById('popupJs').value = popup ? (popup.js_custom || '') : '';
    document.getElementById('popupMostrarUnaVez').checked = popup ? !!parseInt(popup.mostrar_una_vez) : false;
    document.getElementById('popupOrden').value = popup ? popup.orden : 0;
    document.getElementById('popupActivo').checked = popup ? !!parseInt(popup.activo) : true;
    actualizarModoPopup();
    document.getElementById('modalPopupFrontend').classList.add('visible');
}

function cerrarModalPopup() {
    document.getElementById('modalPopupFrontend').classList.remove('visible');
}

document.addEventListener('DOMContentLoaded', function () {
    const tipo = document.getElementById('popupTipoContenido');
    if (tipo) {
        tipo.addEventListener('change', actualizarModoPopup);
        actualizarModoPopup();
    }

    const modalEliminar = document.getElementById('modalConfirmarEliminarPopup');
    const btnCancelarEliminar = document.getElementById('btnCancelarEliminarPopup');
    const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminarPopup');
    let formPendiente = null;

    document.querySelectorAll('.btn-editar-popup').forEach(function (btn) {
        btn.addEventListener('click', function () {
            try {
                const popup = JSON.parse(this.dataset.popup);
                abrirModalPopup(popup);
            } catch (e) {
                console.error('Error al parsear popup', e);
            }
        });
    });

    document.querySelectorAll('.form-eliminar-popup').forEach(function (form) {
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