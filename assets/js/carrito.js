/* ==========================================================
   Carta Digital - Carrito, checkout y pagos (Culqi + WhatsApp)
   ========================================================== */

// ---------- Estado del carrito (persistido en localStorage) ----------
let carrito = JSON.parse(localStorage.getItem('carrito') || '[]');
let entregaSeleccionada = 'recojo';
let pagoSeleccionado = 'efectivo';
let culqiTokenActual = null;

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
    item.cantidad += delta;
    const cont = btnEl.closest('.control-cantidad');
    if (item.cantidad <= 0) {
        carrito = carrito.filter(i => i.id !== id);
    }
    guardarCarrito();
    renderizarStepper(cont, id);
    if (document.getElementById('overlayCarrito').classList.contains('visible')) {
        renderizarCarritoModal();
    }
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

// Culqi.js llama a esta función global cuando el usuario termina el checkout
window.culqi = function () {
    if (Culqi.token) {
        culqiTokenActual = Culqi.token.id;
        enviarPedidoAlServidor();
    } else if (Culqi.order) {
        // No usado en este flujo simple (pagos con Yape vía Culqi Order), reservado para el futuro.
    } else if (Culqi.error) {
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

        // Éxito: limpiar carrito y redirigir a WhatsApp
        carrito = [];
        guardarCarrito();
        document.querySelectorAll('.control-cantidad').forEach(cont => {
            renderizarStepper(cont, parseInt(cont.dataset.id, 10));
        });

        document.getElementById('checkoutError').innerHTML =
            `<div class="alerta-ok">¡Pedido ${data.codigo} registrado! Te llevamos a WhatsApp para confirmar...</div>`;

        setTimeout(() => { window.location.href = data.whatsapp_url; }, 900);

    } catch (err) {
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

// ---------- Navegación de categorías (tabs + scroll spy) ----------
(function initCategoriasNav() {
    const botones = document.querySelectorAll('.cat-btn');
    botones.forEach(btn => {
        btn.addEventListener('click', () => {
            botones.forEach(b => b.classList.remove('activo'));
            btn.classList.add('activo');
            const target = document.getElementById(btn.dataset.target);
            if (target) {
                const y = target.getBoundingClientRect().top + window.scrollY - 115;
                window.scrollTo({ top: y, behavior: 'smooth' });
            }
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
});
