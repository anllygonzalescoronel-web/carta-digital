/* ==========================================================
   Carta Digital - Carrito, checkout y pagos (Culqi + WhatsApp)
   ========================================================== */

// ---------- Estado del carrito (persistido en localStorage) ----------
let carrito = JSON.parse(localStorage.getItem('carrito') || '[]');
let entregaSeleccionada = 'recojo';
let pagoSeleccionado = 'efectivo';
let culqiTokenActual = null;
let terminoBusqueda = '';
let categoriaActiva = null;

function guardarCarrito() {
    localStorage.setItem('carrito', JSON.stringify(carrito));
    actualizarBadgeCarrito();
}

function actualizarBadgeCarrito() {
    const totalItems = carrito.reduce((acc, i) => acc + i.cantidad, 0);
    const btn = document.getElementById('btnCarrito');
    document.getElementById('carritoContador').textContent = totalItems;
    btn.classList.toggle('visible', totalItems > 0);
}

// ---------- Agregar / quitar productos ----------
function agregarProducto(btnEl) {
    const cont = btnEl.closest('.control-cantidad');
    const imgProducto = obtenerImagenDeControl(cont);
    const id = parseInt(cont.dataset.id, 10);
    const nombre = cont.dataset.nombre;
    const precio = parseFloat(cont.dataset.precio);

    let item = carrito.find(i => i.id === id);
    if (item) {
        item.cantidad++;
    } else {
        carrito.push({ id, nombre, precio, cantidad: 1 });
    }
    guardarCarrito();
    volarAlCarrito(imgProducto);
    renderizarStepper(cont, id);
}

function renderizarStepper(cont, id) {
    const item = carrito.find(i => i.id === id);
    const cantidad = item ? item.cantidad : 0;

    if (cantidad === 0) {
        cont.innerHTML = `<button class="btn-agregar" onclick="agregarProducto(this)">Agregar</button>`;
        return;
    }
    cont.innerHTML = `
        <div class="stepper">
            <button onclick="cambiarCantidad(${id}, -1, this)">−</button>
            <span>${cantidad}</span>
            <button onclick="cambiarCantidad(${id}, 1, this)">+</button>
        </div>`;
}

function cambiarCantidad(id, delta, btnEl) {
    const item = carrito.find(i => i.id === id);
    if (!item) return;
    const cont = btnEl.closest('.control-cantidad');
    const imgProducto = delta > 0 ? obtenerImagenDeControl(cont) : null;
    item.cantidad += delta;
    if (item.cantidad <= 0) {
        carrito = carrito.filter(i => i.id !== id);
    }
    guardarCarrito();
    if (delta > 0) volarAlCarrito(imgProducto);
    renderizarStepper(cont, id);
    if (document.getElementById('overlayCarrito').classList.contains('visible')) {
        renderizarCarritoModal();
    }
}

function obtenerImagenDeControl(cont) {
    if (!cont) return null;
    const card = cont.closest('.producto-card, .item-card');
    if (!card) return null;
    return card.querySelector('.producto-img');
}

function volarAlCarrito(imgEl) {
    if (!imgEl) return;

    const destino = document.querySelector('.nav-carrito-icon-wrap');
    if (!destino) return;

    const origenRect = imgEl.getBoundingClientRect();
    const destinoRect = destino.getBoundingClientRect();
    if (!origenRect.width || !destinoRect.width) return;

    const clone = document.createElement('img');
    clone.src = imgEl.currentSrc || imgEl.src;
    clone.style.position = 'fixed';
    clone.style.left = origenRect.left + 'px';
    clone.style.top = origenRect.top + 'px';
    clone.style.width = origenRect.width + 'px';
    clone.style.height = origenRect.height + 'px';
    clone.style.borderRadius = '50%';
    clone.style.objectFit = 'cover';
    clone.style.zIndex = '9999';
    clone.style.pointerEvents = 'none';
    clone.style.transition = 'left .7s cubic-bezier(.4,-0.2,.6,1), top .7s cubic-bezier(.4,-0.2,.6,1), width .7s ease, height .7s ease, opacity .7s ease';
    document.body.appendChild(clone);

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            clone.style.left = (destinoRect.left + destinoRect.width / 2 - 10) + 'px';
            clone.style.top = (destinoRect.top + destinoRect.height / 2 - 10) + 'px';
            clone.style.width = '20px';
            clone.style.height = '20px';
            clone.style.opacity = '0.35';
        });
    });

    clone.addEventListener('transitionend', () => {
        clone.remove();
        rebotarCarrito();
    }, { once: true });
}

function rebotarCarrito() {
    const el = document.querySelector('.nav-carrito-icon-wrap');
    if (!el) return;
    el.classList.remove('rebote');
    void el.offsetWidth;
    el.classList.add('rebote');
}

function quitarDelCarrito(id) {
    carrito = carrito.filter(i => i.id !== id);
    guardarCarrito();
    renderizarCarritoModal();
    // Sincroniza el stepper visible en la carta si existe
    const cont = document.querySelector(`.control-cantidad[data-id="${id}"]`);
    if (cont) renderizarStepper(cont, id);
}

// ---------- Modales genéricos ----------
function abrirModal(id) { document.getElementById(id).classList.add('visible'); }
function cerrarModal(id) { document.getElementById(id).classList.remove('visible'); }

function resetearCheckout() {
    const ids = [
        'inputNombre',
        'inputTelefono',
        'inputDireccion',
        'inputReferencia',
        'inputNotas',
        'inputEmail'
    ];

    ids.forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });

    entregaSeleccionada = 'recojo';
    pagoSeleccionado = 'efectivo';
    culqiTokenActual = null;

    document.querySelectorAll('.opcion-toggle').forEach((el) => {
        el.classList.toggle('activo', el.dataset.entrega === 'recojo');
    });

    const camposDelivery = document.getElementById('camposDelivery');
    if (camposDelivery) camposDelivery.style.display = 'none';

    document.querySelectorAll('.metodo-pago-card').forEach((el) => {
        el.classList.toggle('activo', el.dataset.pago === 'efectivo');
    });

    const bloqueYape = document.getElementById('bloqueYape');
    const bloqueTarjeta = document.getElementById('bloqueTarjeta');
    if (bloqueYape) bloqueYape.style.display = 'none';
    if (bloqueTarjeta) bloqueTarjeta.style.display = 'none';

    limpiarErrorCheckout();
}

function mostrarConfirmacionVenta(codigoPedido, whatsappUrl) {
    const codigoEl = document.getElementById('confirmacionCodigo');
    const btnWhatsapp = document.getElementById('btnAvisarWhatsapp');
    const btnVolver = document.getElementById('btnVolverCarta');

    if (codigoEl) {
        codigoEl.textContent = codigoPedido || '-';
    }

    if (btnWhatsapp) {
        btnWhatsapp.href = whatsappUrl || '#';
        btnWhatsapp.classList.toggle('deshabilitado', !whatsappUrl);
        btnWhatsapp.setAttribute('aria-disabled', whatsappUrl ? 'false' : 'true');
        btnWhatsapp.onclick = (e) => {
            if (!whatsappUrl) {
                e.preventDefault();
                return;
            }
            cerrarModal('overlayConfirmacion');
        };
    }

    if (btnVolver) {
        btnVolver.onclick = () => {
            cerrarModal('overlayConfirmacion');
            irHomeVisual(document.getElementById('navHome'));
        };
    }

    abrirModal('overlayConfirmacion');
    lanzarConfettiConfirmacion();
}

function lanzarConfettiConfirmacion() {
    const capa = document.getElementById('confettiLayer');
    if (!capa) return;

    capa.innerHTML = '';
    const colores = ['#22c55e', '#f59e0b', '#0ea5e9', '#ef4444', '#a855f7', '#14b8a6'];
    const total = window.innerWidth < 760 ? 75 : 120;

    for (let i = 0; i < total; i++) {
        const pieza = document.createElement('span');
        const size = (Math.random() * 8) + 5;
        pieza.className = 'confetti-piece';
        pieza.style.left = `${Math.random() * 100}%`;
        pieza.style.width = `${size}px`;
        pieza.style.height = `${(Math.random() * 5) + 4}px`;
        pieza.style.background = colores[Math.floor(Math.random() * colores.length)];
        pieza.style.animationDelay = `${Math.random() * 0.55}s`;
        pieza.style.animationDuration = `${2.1 + Math.random() * 1.4}s`;
        pieza.style.transform = `rotate(${Math.random() * 360}deg)`;
        capa.appendChild(pieza);
    }

    setTimeout(() => {
        capa.innerHTML = '';
    }, 3800);
}

function abrirCarrito() {
    renderizarCarritoModal();
    abrirModal('overlayCarrito');
}

function calcularSubtotal() {
    return carrito.reduce((acc, i) => acc + i.precio * i.cantidad, 0);
}

function renderizarCarritoModal() {
    const lista = document.getElementById('listaCarrito');
    const resumen = document.getElementById('resumenCarrito');
    const btnContinuar = document.getElementById('btnIrCheckout');

    if (carrito.length === 0) {
        lista.innerHTML = '<p class="vacio-msg">Tu carrito está vacío. ¡Agrega algo delicioso! 🍗</p>';
        resumen.innerHTML = '';
        btnContinuar.style.display = 'none';
        return;
    }
    btnContinuar.style.display = 'block';

    lista.innerHTML = carrito.map(i => `
        <div class="carrito-item">
            <div class="info">
                <h5>${i.cantidad}x ${i.nombre}</h5>
                <div class="p-unit">S/ ${i.precio.toFixed(2)} c/u</div>
                <button class="btn-quitar" onclick="quitarDelCarrito(${i.id})">Quitar</button>
            </div>
            <div class="subtotal-item">S/ ${(i.precio * i.cantidad).toFixed(2)}</div>
        </div>
    `).join('');

    const subtotal = calcularSubtotal();
    resumen.innerHTML = `
        <div class="resumen-total"><span>Subtotal</span><span>S/ ${subtotal.toFixed(2)}</span></div>
        <div class="resumen-total total-final"><span>Total</span><span>S/ ${subtotal.toFixed(2)}</span></div>
    `;
}

function irACheckout() {
    cerrarModal('overlayCarrito');
    abrirModal('overlayCheckout');
}

// ---------- Selección entrega / pago ----------
function seleccionarEntrega(el) {
    document.querySelectorAll('.opcion-toggle').forEach(e => e.classList.remove('activo'));
    el.classList.add('activo');
    entregaSeleccionada = el.dataset.entrega;
    document.getElementById('camposDelivery').style.display = entregaSeleccionada === 'delivery' ? 'block' : 'none';
}

function seleccionarPago(el) {
    document.querySelectorAll('.metodo-pago-card').forEach(e => e.classList.remove('activo'));
    el.classList.add('activo');
    pagoSeleccionado = el.dataset.pago;
    document.getElementById('bloqueYape').style.display = pagoSeleccionado === 'yape_plin' ? 'block' : 'none';
    document.getElementById('bloqueTarjeta').style.display = (pagoSeleccionado === 'tarjeta' || pagoSeleccionado === 'yape_plin') ? 'block' : 'none';
    culqiTokenActual = null;
}

// ---------- Validación y envío del pedido ----------
function mostrarErrorCheckout(msg) {
    document.getElementById('checkoutError').innerHTML = `<div class="alerta-error">${msg}</div>`;
}
function limpiarErrorCheckout() {
    document.getElementById('checkoutError').innerHTML = '';
}

function validarFormulario() {
    const nombre = document.getElementById('inputNombre').value.trim();
    const telefono = document.getElementById('inputTelefono').value.trim();

    if (nombre.length < 2) { mostrarErrorCheckout('Ingresa tu nombre completo.'); return false; }
    if (!/^[0-9+ ]{6,20}$/.test(telefono)) { mostrarErrorCheckout('Ingresa un teléfono válido.'); return false; }
    if (entregaSeleccionada === 'delivery') {
        const dir = document.getElementById('inputDireccion').value.trim();
        if (dir.length < 5) { mostrarErrorCheckout('Ingresa tu dirección de entrega.'); return false; }
    }
    if (pagoSeleccionado === 'tarjeta' || pagoSeleccionado === 'yape_plin') {
        const email = document.getElementById('inputEmail').value.trim();
        if (!email.includes('@')) { mostrarErrorCheckout('Ingresa un email válido para tu comprobante.'); return false; }
    }
    return true;
}

function confirmarPedido() {
    limpiarErrorCheckout();
    if (carrito.length === 0) { mostrarErrorCheckout('Tu carrito está vacío.'); return; }
    if (!validarFormulario()) return;

    if ((pagoSeleccionado === 'tarjeta' || pagoSeleccionado === 'yape_plin') && !culqiTokenActual) {
        abrirCulqiCheckout();
        return;
    }
    enviarPedidoAlServidor();
}

function obtenerMetodosPagoCulqi() {
    if (pagoSeleccionado === 'yape_plin') {
        return { tarjeta: false, yape: true, bancaMovil: false, agente: false, billetera: false, cuotealo: false };
    }

    return { tarjeta: true, yape: false, bancaMovil: false, agente: false, billetera: false, cuotealo: false };
}

// ---------- Integración Culqi (Checkout emergente) ----------
function abrirCulqiCheckout() {
    if (!window.APP_CONFIG.culqiPublicKey || window.APP_CONFIG.culqiPublicKey.includes('XXXX')) {
        mostrarErrorCheckout('El pago con Culqi aún no está configurado. Prueba con efectivo.');
        return;
    }
    const total = calcularSubtotal() + (entregaSeleccionada === 'delivery' ? window.APP_CONFIG.costoDelivery : 0);

    Culqi.publicKey = window.APP_CONFIG.culqiPublicKey;
    Culqi.settings({
        title: window.APP_CONFIG.nombreNegocio,
        currency: 'PEN',
        amount: Math.round(total * 100), // Culqi espera céntimos
    });
    const paymentMethods = obtenerMetodosPagoCulqi();
    Culqi.options({
        lang: 'auto',
        installments: false,
        paymentMethods,
        paymentMethodsSort: Object.keys(paymentMethods).filter(method => paymentMethods[method]),
    });
    Culqi.open();
}

function cerrarCheckoutCulqi() {
    if (!window.Culqi) return;

    const limpiarRestosCulqi = () => {
        const selectores = [
            '[id*="culqi" i]',
            '[class*="culqi" i]',
            'iframe[src*="culqi" i]',
            'iframe[name*="culqi" i]',
            'iframe[title*="culqi" i]'
        ];

        const elementos = document.querySelectorAll(selectores.join(','));
        elementos.forEach((el) => {
            if (el === document.documentElement || el === document.body) return;
            if (el.closest && el.closest('#overlayCheckout, #overlayConfirmacion, #overlayCarrito, #overlayFavoritos, #overlayPerfil')) return;
            el.remove();
        });

        [document.body, document.documentElement].forEach((root) => {
            if (!root) return;
            root.style.overflow = '';
            root.style.position = '';
            root.style.touchAction = '';
            [...root.classList].forEach((cls) => {
                if (/culqi/i.test(cls)) root.classList.remove(cls);
            });
        });
    };

    try {
        if (typeof Culqi.close === 'function') {
            Culqi.close();
        }
    } catch (e) {
        // Ignora errores de cierre para no romper el flujo de compra.
    }

    try {
        if (typeof Culqi.closeCheckout === 'function') {
            Culqi.closeCheckout();
        }
    } catch (e) {
        // Algunas versiones no exponen este método.
    }

    limpiarRestosCulqi();
    setTimeout(limpiarRestosCulqi, 120);
    setTimeout(limpiarRestosCulqi, 450);
    setTimeout(limpiarRestosCulqi, 900);
}

// Culqi.js llama a esta función global cuando el usuario termina el checkout
window.culqi = function () {
    if (Culqi.token) {
        cerrarCheckoutCulqi();
        culqiTokenActual = Culqi.token.id;
        enviarPedidoAlServidor();
    } else if (Culqi.order) {
        // No usado en este flujo simple (pagos con Yape vía Culqi Order), reservado para el futuro.
        cerrarCheckoutCulqi();
    } else if (Culqi.error) {
        cerrarCheckoutCulqi();
        mostrarErrorCheckout(Culqi.error.user_message || 'No se pudo procesar tu tarjeta. Intenta nuevamente.');
    }
};

// ---------- Envío final al backend ----------
async function enviarPedidoAlServidor() {
    const btn = document.getElementById('btnConfirmarPedido');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Procesando...';

    const payload = {
        items: carrito.map(i => ({ id: i.id, cantidad: i.cantidad })),
        cliente_nombre: document.getElementById('inputNombre').value.trim(),
        cliente_telefono: document.getElementById('inputTelefono').value.trim(),
        cliente_email: (pagoSeleccionado === 'tarjeta' || pagoSeleccionado === 'yape_plin') ? document.getElementById('inputEmail').value.trim() : '',
        tipo_entrega: entregaSeleccionada,
        direccion: entregaSeleccionada === 'delivery' ? document.getElementById('inputDireccion').value.trim() : '',
        referencia: entregaSeleccionada === 'delivery' ? document.getElementById('inputReferencia').value.trim() : '',
        metodo_pago: pagoSeleccionado,
        notas: document.getElementById('inputNotas').value.trim(),
        culqi_token: culqiTokenActual,
    };

    try {
        const resp = await fetch('api/pedido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await resp.json();

        if (!data.ok) {
            mostrarErrorCheckout(data.mensaje || 'No se pudo procesar tu pedido.');
            btn.disabled = false;
            btn.textContent = 'Confirmar pedido';
            culqiTokenActual = null;
            return;
        }

        // Éxito: limpiar carrito y mostrar confirmación visual
        carrito = [];
        guardarCarrito();
        document.querySelectorAll('.control-cantidad').forEach(cont => {
            renderizarStepper(cont, parseInt(cont.dataset.id, 10));
        });

        cerrarModal('overlayCheckout');
        limpiarErrorCheckout();

        btn.disabled = false;
        btn.textContent = 'Confirmar pedido';

        cerrarCheckoutCulqi();
        resetearCheckout();
        mostrarConfirmacionVenta(data.codigo, data.whatsapp_url);

    } catch (err) {
        cerrarCheckoutCulqi();
        mostrarErrorCheckout('Error de conexión. Intenta nuevamente.');
        btn.disabled = false;
        btn.textContent = 'Confirmar pedido';
    }
}

// ---------- Banner slider automático ----------
(function initBannerSlider() {
    const track = document.getElementById('bannerTrack');
    if (!track) return;
    const slides = track.children.length;
    const dotsCont = document.getElementById('bannerDots');
    let actual = 0;

    for (let i = 0; i < slides; i++) {
        const dot = document.createElement('span');
        if (i === 0) dot.classList.add('activo');
        dot.addEventListener('click', () => irASlide(i));
        dotsCont.appendChild(dot);
    }

    function irASlide(i) {
        actual = i;
        track.style.transform = `translateX(-${i * 100}%)`;
        [...dotsCont.children].forEach((d, idx) => d.classList.toggle('activo', idx === i));
    }

    if (slides > 1) {
        setInterval(() => irASlide((actual + 1) % slides), 4000);
    }
})();

// ---------- Navegación de categorías (filtro en el mismo bloque) ----------
(function initCategoriasNav() {
    const botones = document.querySelectorAll('.cat-btn');
    const quicks = document.querySelectorAll('.quickcat-item');

    function activarCategoria(targetId) {
        botones.forEach(b => b.classList.toggle('activo', b.dataset.target === targetId));
        quicks.forEach(q => q.classList.toggle('activo', q.dataset.target === targetId));
    }

    const primeraCategoria = botones[0]?.dataset.target || quicks[0]?.dataset.target || null;
    if (primeraCategoria) {
        categoriaActiva = primeraCategoria;
        activarCategoria(primeraCategoria);
    }

    botones.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            activarCategoria(targetId);
            categoriaActiva = targetId;
            aplicarFiltrosCatalogo();
        });
    });

    quicks.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            activarCategoria(targetId);
            categoriaActiva = targetId;
            aplicarFiltrosCatalogo();
        });
    });
})();

// ---------- Al cargar: pintar cantidades ya guardadas en localStorage ----------
document.addEventListener('DOMContentLoaded', () => {
    actualizarBadgeCarrito();
    document.querySelectorAll('.control-cantidad').forEach(cont => {
        const id = parseInt(cont.dataset.id, 10);
        if (carrito.find(i => i.id === id)) {
            renderizarStepper(cont, id);
        }
    });

    const inputBuscar = document.getElementById('inputBuscar');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', (e) => filtrarProductos(e.target.value));
    }

    const navHome = document.getElementById('navHome');
    if (navHome) navHome.addEventListener('click', () => marcarNavActivo(navHome));

    aplicarFiltrosCatalogo();
});

function filtrarProductos(texto) {
    terminoBusqueda = (texto || '').trim().toLowerCase();
    aplicarFiltrosCatalogo();
}

function aplicarFiltrosCatalogo() {
    const secciones = document.querySelectorAll('.seccion-categoria');

    secciones.forEach(seccion => {
        const cards = seccion.querySelectorAll('.producto-card');
        const coincideCategoria = !categoriaActiva || categoriaActiva === 'all-products' || seccion.id === categoriaActiva;
        let visibles = 0;

        cards.forEach(card => {
            const tituloEl = card.querySelector('h4');
            const descEl = card.querySelector('.desc');
            const textoCard = ((tituloEl ? tituloEl.textContent : '') + ' ' + (descEl ? descEl.textContent : '')).toLowerCase();
            const coincide = terminoBusqueda === '' || textoCard.includes(terminoBusqueda);
            card.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });

        seccion.style.display = coincideCategoria && visibles > 0 ? '' : 'none';
    });
}

function toggleFavoritoVisual(btn) {
    const icono = btn.querySelector('i');
    const activo = btn.classList.toggle('activo');
    if (icono) {
        icono.classList.toggle('fa-solid', activo);
        icono.classList.toggle('fa-regular', !activo);
    }
}

function marcarNavActivo(el) {
    document.querySelectorAll('.bottom-nav .nav-item').forEach(item => item.classList.remove('activo'));
    if (el) el.classList.add('activo');
}

function irHomeVisual(el) {
    marcarNavActivo(el);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function abrirFavoritosVisual(el) {
    marcarNavActivo(el);
    abrirModal('overlayFavoritos');
}

function abrirPerfilVisual(el) {
    marcarNavActivo(el);
    abrirModal('overlayPerfil');
}
