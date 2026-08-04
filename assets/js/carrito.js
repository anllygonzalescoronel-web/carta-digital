/* ==========================================================
   Carta Digital - Carrito, checkout y pagos (Culqi + WhatsApp)
   ========================================================== */

// ---------- Estado del carrito (persistido en localStorage) ----------
let carrito = JSON.parse(localStorage.getItem('carrito') || '[]');
let favoritos = JSON.parse(localStorage.getItem('favoritos') || '[]');
let entregaSeleccionada = 'recojo';
let pagoSeleccionado = 'efectivo';
let comprobanteSeleccionado = 'boleta';
let culqiTokenActual = null;
let terminoBusqueda = '';
let categoriaActiva = null;
const carruselesProductos = [];

function guardarCarrito() {
    localStorage.setItem('carrito', JSON.stringify(carrito));
    actualizarBadgeCarrito();
}

function guardarFavoritos() {
    localStorage.setItem('favoritos', JSON.stringify(favoritos));
}

function escaparHtml(txt) {
    return String(txt || '').replace(/[&<>"']/g, (m) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[m]));
}

function obtenerDatosProductoDesdeCard(card) {
    if (!card) return null;
    const cont = card.querySelector('.control-cantidad');
    if (!cont) return null;

    const id = parseInt(cont.dataset.id || '0', 10);
    if (!id) return null;

    const nombre = cont.dataset.nombre || card.querySelector('h4')?.textContent?.trim() || 'Producto';
    const descripcion = card.querySelector('.desc')?.textContent?.trim() || '';
    const precio = parseFloat(cont.dataset.precio || '0');
    const imgEl = card.querySelector('.producto-img');
    const imagen = imgEl ? (imgEl.currentSrc || imgEl.src || '') : '';

    return { id, nombre, descripcion, precio, imagen };
}

function renderizarFavoritosModal() {
    const lista = document.getElementById('listaFavoritos');
    if (!lista) return;

    if (!favoritos.length) {
        lista.innerHTML = '<div class="vacio-msg">Aun no tienes favoritos guardados.</div>';
        return;
    }

    lista.innerHTML = favoritos.map((f) => `
        <div class="favorito-item">
            ${f.imagen ? `<img src="${escaparHtml(f.imagen)}" alt="${escaparHtml(f.nombre)}" class="favorito-img">` : '<div class="favorito-img favorito-img-placeholder"><i class="fa-solid fa-burger"></i></div>'}
            <div class="favorito-info">
                <h5>${escaparHtml(f.nombre)}</h5>
                ${f.descripcion ? `<p>${escaparHtml(f.descripcion)}</p>` : ''}
                <div class="favorito-precio">S/ ${Number(f.precio || 0).toFixed(2)}</div>
            </div>
            <button class="btn-quitar-fav" type="button" onclick="quitarFavorito(${f.id})">Quitar</button>
        </div>
    `).join('');
}

function sincronizarBotonesFavoritos() {
    const idsFavoritos = new Set(favoritos.map((f) => Number(f.id)));
    document.querySelectorAll('.producto-card .btn-fav').forEach((btn) => {
        const card = btn.closest('.producto-card, .item-card');
        const data = obtenerDatosProductoDesdeCard(card);
        if (!data) return;
        const activo = idsFavoritos.has(data.id);
        btn.classList.toggle('activo', activo);
        const icono = btn.querySelector('i');
        if (icono) {
            icono.classList.toggle('fa-solid', activo);
            icono.classList.toggle('fa-regular', !activo);
        }
    });
}

function animarCorazonAFavoritos(btnOrigen) {
    const destino = document.querySelector('#navFav i') || document.getElementById('navFav');
    if (!btnOrigen || !destino) return;

    const origenRect = btnOrigen.getBoundingClientRect();
    const destinoRect = destino.getBoundingClientRect();
    if (!origenRect.width || !destinoRect.width) return;

    const heart = document.createElement('i');
    heart.className = 'fa-solid fa-heart corazon-fly';
    heart.style.left = (origenRect.left + origenRect.width / 2 - 22) + 'px';
    heart.style.top = (origenRect.top + origenRect.height / 2 - 22) + 'px';
    document.body.appendChild(heart);

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            heart.style.left = (destinoRect.left + destinoRect.width / 2 - 12) + 'px';
            heart.style.top = (destinoRect.top + destinoRect.height / 2 - 12) + 'px';
            heart.style.fontSize = '24px';
            heart.style.opacity = '0.32';
            heart.style.transform = 'rotate(24deg) scale(0.55)';
        });
    });

    heart.addEventListener('transitionend', () => {
        heart.remove();
        const iconoFav = document.querySelector('#navFav i');
        if (!iconoFav) return;
        iconoFav.classList.remove('latido-fav');
        void iconoFav.offsetWidth;
        iconoFav.classList.add('latido-fav');
    }, { once: true });
}

function quitarFavorito(id) {
    const idNum = Number(id);
    favoritos = favoritos.filter((f) => Number(f.id) !== idNum);
    guardarFavoritos();
    sincronizarBotonesFavoritos();
    renderizarFavoritosModal();
}

function actualizarBadgeCarrito() {
    const totalItems = carrito.reduce((acc, i) => acc + i.cantidad, 0);
    const btn = document.getElementById('btnCarrito');
    document.getElementById('carritoContador').textContent = totalItems;
    btn.classList.toggle('visible', totalItems > 0);
}

function limpiarCarritoCompleto() {
    carrito = [];
    localStorage.removeItem('carrito');
    actualizarBadgeCarrito();

    const overlayCarrito = document.getElementById('overlayCarrito');
    if (overlayCarrito && overlayCarrito.classList.contains('visible')) {
        renderizarCarritoModal();
    }

    document.querySelectorAll('.control-cantidad').forEach((cont) => {
        renderizarStepper(cont, parseInt(cont.dataset.id, 10));
    });
}

// ---------- Agregar / quitar productos ----------
// ---------- Agregar / quitar productos ----------

// Estado del modal de opciones
let _mopCont = null;   // elemento .control-cantidad actual
let _mopSeleccionadas = {};  // {grupoId: [opcionId, ...]}
let _detalleCont = null; // elemento .control-cantidad del modal detalle

function agregarProducto(btnEl) {
    const cont = btnEl.closest('.control-cantidad');
    const id = parseInt(cont.dataset.id, 10);
    const tieneOpciones = cont.dataset.tieneOpciones === '1';
    const grupos = (window.OPCIONES_PRODUCTOS || {})[id];

    if (tieneOpciones && grupos && grupos.length > 0) {
        abrirModalOpciones(cont, grupos);
        return;
    }
    _agregarAlCarritoDirecto(cont);
}

function _agregarAlCarritoDirecto(cont, opcionesSeleccionadas) {
    const imgEl = obtenerImagenDeControl(cont);
    const imagen = cont.dataset.imagen || imgEl?.currentSrc || imgEl?.src || '';
    const id = parseInt(cont.dataset.id, 10);
    const nombre = cont.dataset.nombre;
    const precioBase = parseFloat(cont.dataset.precio);

    if (opcionesSeleccionadas && opcionesSeleccionadas.length > 0) {
        const extraTotal = opcionesSeleccionadas.reduce((s, o) => s + o.precio_extra, 0);
        const precioTotal = precioBase + extraTotal;
        const key = id + '_' + opcionesSeleccionadas.map(o => o.opcion_id).sort().join('_');
        let item = carrito.find(i => i.key === key);
        if (item) {
            item.cantidad++;
        } else {
            carrito.push({ id, key, nombre, precio: precioTotal, precioBase, imagen, opciones: opcionesSeleccionadas, cantidad: 1 });
        }
    } else {
        let item = carrito.find(i => i.id === id && !i.key);
        if (item) {
            item.cantidad++;
        } else {
            carrito.push({ id, nombre, precio: precioBase, imagen, cantidad: 1 });
        }
    }
    guardarCarrito();
    volarAlCarrito(imgEl);
    renderizarStepper(cont, id);
}

// ── Modal de opciones ──────────────────────────────────
function abrirModalOpciones(cont, grupos) {
    _mopCont = cont;
    _mopSeleccionadas = {};

    const id     = parseInt(cont.dataset.id, 10);
    const nombre = cont.dataset.nombre;
    const precio = parseFloat(cont.dataset.precio);
    const imagen = cont.dataset.imagen || '';

    // Info producto
    document.getElementById('mopProductoInfo').innerHTML = `
        ${imagen ? `<img src="${imagen}" alt="${nombre}">` : ''}
        <div>
            <p class="mop-nombre">${nombre}</p>
            <p class="mop-precio">S/ ${precio.toFixed(2)}</p>
        </div>`;

    // Grupos
    const container = document.getElementById('mopGrupos');
    container.innerHTML = '';
    grupos.forEach(g => {
        const div = document.createElement('div');
        div.className = 'mop-grupo';
        div.dataset.grupoId = g.id;
        div.dataset.tipo = g.tipo;
        div.dataset.requerido = g.requerido ? '1' : '0';
        div.dataset.max = g.max;
        div.innerHTML = `<p class="mop-grupo-titulo">
            ${g.nombre}
            <span class="mop-badge">${g.tipo === 'checkbox' ? 'Elige varios' : 'Elige uno'}</span>
            ${g.requerido ? '<span class="mop-badge mop-badge-req">Obligatorio</span>' : ''}
        </p>`;
        g.opciones.forEach(op => {
            const label = document.createElement('label');
            label.className = 'mop-opcion';
            label.innerHTML = `
                <input type="${g.tipo}" name="grupo_${g.id}" value="${op.id}"
                    data-precio-extra="${op.precio_extra}"
                    data-opcion-nombre="${op.nombre.replace(/"/g,'&quot;')}"
                    data-grupo-nombre="${g.nombre.replace(/"/g,'&quot;')}"
                    data-grupo-id="${g.id}">
                <span class="mop-opcion-nombre">${op.nombre}</span>
                <span class="mop-opcion-precio">${op.precio_extra > 0 ? '+S/ '+op.precio_extra.toFixed(2) : 'Gratis'}</span>`;
            label.querySelector('input').addEventListener('change', _mopActualizarTotal);
            label.addEventListener('click', function() {
                // Marcar visualmente
                div.querySelectorAll('.mop-opcion').forEach(l => l.classList.remove('seleccionada'));
                if (g.tipo === 'radio') {
                    label.classList.add('seleccionada');
                } else {
                    if (label.querySelector('input').checked) label.classList.add('seleccionada');
                    else label.classList.remove('seleccionada');
                }
            });
            div.appendChild(label);
        });
        container.appendChild(div);
    });

    _mopActualizarTotal();
    document.getElementById('modalOpciones').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarModalOpciones() {
    document.getElementById('modalOpciones').style.display = 'none';
    document.body.style.overflow = '';
    _mopCont = null;
}

function abrirModalDetalleProducto(card) {
    const data = obtenerDatosProductoDesdeCard(card);
    const cont = card?.querySelector('.control-cantidad');
    if (!data || !cont) return;

    _detalleCont = cont;

    const img = document.getElementById('detalleProductoImagen');
    const nombre = document.getElementById('detalleProductoNombre');
    const desc = document.getElementById('detalleProductoDescripcion');
    const precio = document.getElementById('detalleProductoPrecio');
    const tipo = document.getElementById('detalleProductoTipo');
    const btnAgregar = document.getElementById('detalleProductoAgregar');

    if (img) {
        img.src = data.imagen || '';
        img.alt = data.nombre || 'Producto';
    }
    if (nombre) nombre.textContent = data.nombre || 'Producto';
    if (desc) desc.textContent = data.descripcion || 'Sin descripcion disponible.';
    if (precio) precio.textContent = `S/ ${Number(data.precio || 0).toFixed(2)}`;

    const tieneOpciones = cont.dataset.tieneOpciones === '1';
    if (tipo) tipo.textContent = tieneOpciones ? 'Personalizable' : 'Listo para agregar';
    if (btnAgregar) btnAgregar.textContent = tieneOpciones ? 'Personalizar y agregar' : 'Agregar al carrito';

    abrirModal('overlayProductoDetalle');
}

function cerrarModalDetalleProducto() {
    cerrarModal('overlayProductoDetalle');
    _detalleCont = null;
}

function agregarDesdeDetalleProducto() {
    if (!_detalleCont) return;

    const cont = _detalleCont;
    const id = parseInt(cont.dataset.id, 10);
    const tieneOpciones = cont.dataset.tieneOpciones === '1';
    const grupos = (window.OPCIONES_PRODUCTOS || {})[id];

    cerrarModalDetalleProducto();

    if (tieneOpciones && grupos && grupos.length > 0) {
        abrirModalOpciones(cont, grupos);
        return;
    }

    _agregarAlCarritoDirecto(cont);
}

function _mopActualizarTotal() {
    if (!_mopCont) return;
    const precioBase = parseFloat(_mopCont.dataset.precio);
    let extra = 0;
    document.querySelectorAll('#mopGrupos input:checked').forEach(inp => {
        extra += parseFloat(inp.dataset.precioExtra || 0);
    });
    document.getElementById('mopTotalTexto').textContent = 'S/ ' + (precioBase + extra).toFixed(2);
}

function confirmarOpciones() {
    if (!_mopCont) return;
    const contActual = _mopCont;
    const grupos = document.querySelectorAll('#mopGrupos .mop-grupo');
    let valido = true;
    const opcionesSeleccionadas = [];

    grupos.forEach(div => {
        const requerido = div.dataset.requerido === '1';
        const marcados = div.querySelectorAll('input:checked');
        if (requerido && marcados.length === 0) {
            valido = false;
            div.style.outline = '2px solid #e53e3e';
            div.style.borderRadius = '12px';
        } else {
            div.style.outline = '';
        }
        marcados.forEach(inp => {
            opcionesSeleccionadas.push({
                grupo_id:      parseInt(inp.dataset.grupoId),
                grupo_nombre:  inp.dataset.grupoNombre,
                opcion_id:     parseInt(inp.value),
                opcion_nombre: inp.dataset.opcionNombre,
                precio_extra:  parseFloat(inp.dataset.precioExtra || 0),
            });
        });
    });

    if (!valido) return;
    _agregarAlCarritoDirecto(contActual, opcionesSeleccionadas);
    cerrarModalOpciones();
}

// Cerrar al click en overlay
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('modalOpciones');
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) cerrarModalOpciones();
        });
    }

    const overlayDetalle = document.getElementById('overlayProductoDetalle');
    if (overlayDetalle) {
        overlayDetalle.addEventListener('click', function(e) {
            if (e.target === overlayDetalle) cerrarModalDetalleProducto();
        });
    }
});

function renderizarStepper(cont, id) {
    const item = carrito.find(i => i.id === id);
    const cantidad = item ? item.cantidad : 0;

    if (cantidad === 0) {
        cont.innerHTML = `<button class="btn-agregar" onclick="agregarProducto(this)" aria-label="Agregar al carrito">Agregar</button>`;
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

function quitarDelCarritoKey(key, id) {
    if (key !== null) {
        carrito = carrito.filter(i => i.key !== key);
    } else {
        carrito = carrito.filter(i => i.id !== id || i.key);
    }
    guardarCarrito();
    renderizarCarritoModal();
    const cont = document.querySelector(`.control-cantidad[data-id="${id}"]`);
    if (cont) renderizarStepper(cont, id);
}

function quitarDelCarritoIdx(idx) {
    const item = carrito[idx];
    if (!item) return;
    const id = item.id;
    carrito.splice(idx, 1);
    guardarCarrito();
    renderizarCarritoModal();
    const cont = document.querySelector(`.control-cantidad[data-id="${id}"]`);
    if (cont) renderizarStepper(cont, id);
}

// ---------- Modales genéricos ----------
function abrirModal(id) { document.getElementById(id).classList.add('visible'); }
function cerrarModal(id) { document.getElementById(id).classList.remove('visible'); }

function resetearCheckout() {
    const ids = [
        'inputNombre',
        'inputNumeroDocumento',
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
    comprobanteSeleccionado = 'boleta';
    culqiTokenActual = null;

    document.querySelectorAll('#opcionesComprobante .opcion-toggle').forEach((el) => {
        el.classList.toggle('activo', el.dataset.comprobante === 'boleta');
    });

    const tipoDocEl = document.getElementById('inputTipoDocumento');
    if (tipoDocEl) {
        tipoDocEl.value = 'dni';
        tipoDocEl.disabled = false;
    }

    const numeroDocEl = document.getElementById('inputNumeroDocumento');
    if (numeroDocEl) {
        numeroDocEl.maxLength = 8;
        numeroDocEl.placeholder = 'Ej. 12345678';
    }

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

    lista.innerHTML = carrito.map((i, idx) => `
        <div class="carrito-item">
            ${i.imagen ? `<img src="${escaparHtml(i.imagen)}" class="carrito-item-img" alt="${escaparHtml(i.nombre)}" onerror="this.style.display='none'">` : '<div class="carrito-item-img carrito-item-img-ph"><i class="fa-solid fa-utensils"></i></div>'}
            <div class="info">
                <h5>${escaparHtml(i.nombre)}</h5>
                ${i.opciones && i.opciones.length > 0 ? '<ul class="carrito-opciones">' + i.opciones.map(o => `<li>${escaparHtml(o.grupo_nombre)}: <strong>${escaparHtml(o.opcion_nombre)}</strong>${o.precio_extra > 0 ? ' +S/ '+o.precio_extra.toFixed(2) : ''}</li>`).join('') + '</ul>' : ''}
                <div class="p-unit">S/ ${i.precio.toFixed(2)} c/u</div>
                <div class="carrito-item-controles">
                    <button class="carrito-btn-menos" onclick="cambiarCantidadCarrito(${idx}, -1)">−</button>
                    <span class="carrito-cantidad">${i.cantidad}</span>
                    <button class="carrito-btn-mas" onclick="cambiarCantidadCarrito(${idx}, 1)">+</button>
                </div>
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

function cambiarCantidadCarrito(idx, delta) {
    const item = carrito[idx];
    if (!item) return;
    item.cantidad += delta;
    if (item.cantidad <= 0) {
        carrito.splice(idx, 1);
        // Sincronizar stepper en la carta
        const cont = document.querySelector(`.control-cantidad[data-id="${item.id}"]`);
        if (cont) renderizarStepper(cont, item.id);
    }
    guardarCarrito();
    renderizarCarritoModal();
}

    // Nueva versión: Abrir checkout con APIPERU
    function irACheckoutAPIPeru() {
        cerrarModal('overlayCarrito');
    
        // Inicializar checkout y mostrar modal
        if (window.checkout) {
            window.checkout.mostrarModal();
        } else {
            console.error('Checkout no inicializado');
        }
    }

    // Mantener irACheckout por compatibilidad pero redirigir a nuevo sistema
    function irACheckout() {
        irACheckoutAPIPeru();
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

function seleccionarComprobante(el) {
    document.querySelectorAll('#opcionesComprobante .opcion-toggle').forEach((e) => e.classList.remove('activo'));
    el.classList.add('activo');
    comprobanteSeleccionado = el.dataset.comprobante;
    actualizarTipoDocumentoSegunComprobante();
}

function actualizarTipoDocumentoSegunComprobante() {
    const tipoDocEl = document.getElementById('inputTipoDocumento');
    const numeroDocEl = document.getElementById('inputNumeroDocumento');
    if (!tipoDocEl || !numeroDocEl) return;

    if (comprobanteSeleccionado === 'factura') {
        tipoDocEl.value = 'ruc';
        tipoDocEl.disabled = true;
        numeroDocEl.maxLength = 11;
        numeroDocEl.placeholder = 'Ej. 20123456789';
    } else {
        tipoDocEl.disabled = false;
        if (tipoDocEl.value !== 'dni' && tipoDocEl.value !== 'ruc') {
            tipoDocEl.value = 'dni';
        }
        if (tipoDocEl.value === 'ruc') {
            numeroDocEl.maxLength = 11;
            numeroDocEl.placeholder = 'Ej. 20123456789';
        } else {
            numeroDocEl.maxLength = 8;
            numeroDocEl.placeholder = 'Ej. 12345678';
        }
    }
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
    const tipoDoc = document.getElementById('inputTipoDocumento').value;
    const numeroDoc = (document.getElementById('inputNumeroDocumento').value || '').replace(/\D/g, '');

    if (nombre.length < 2) { mostrarErrorCheckout('Ingresa tu nombre completo.'); return false; }
    if (!/^[0-9+ ]{6,20}$/.test(telefono)) { mostrarErrorCheckout('Ingresa un teléfono válido.'); return false; }

    if (comprobanteSeleccionado === 'factura' && tipoDoc !== 'ruc') {
        mostrarErrorCheckout('La factura requiere RUC.');
        return false;
    }

    if (tipoDoc === 'dni' && !/^\d{8}$/.test(numeroDoc)) {
        mostrarErrorCheckout('Para DNI, ingresa 8 dígitos.');
        return false;
    }

    if (tipoDoc === 'ruc' && !/^\d{11}$/.test(numeroDoc)) {
        mostrarErrorCheckout('Para RUC, ingresa 11 dígitos.');
        return false;
    }
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
        tipo_comprobante: comprobanteSeleccionado,
        tipo_documento: document.getElementById('inputTipoDocumento').value,
        numero_documento: (document.getElementById('inputNumeroDocumento').value || '').replace(/\D/g, ''),
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
        try {
            const nombre = document.getElementById('inputNombre').value.trim();
            const telefono = document.getElementById('inputTelefono').value.trim();
            if (nombre) localStorage.setItem('cliente_nombre', nombre);
            if (telefono) localStorage.setItem('cliente_telefono', telefono);
        } catch (e) {
            // Ignorar errores de localStorage.
        }

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

// ---------- Auto-scroll de quickcats ----------
(function initQuickCatsAutoScroll() {
    const nav = document.getElementById('quickCats');
    if (!nav) return;
    let pausado = false;
    let intervalo;

    function scrollPaso() {
        if (pausado) return;
        const maxScroll = nav.scrollWidth - nav.clientWidth;
        if (maxScroll <= 0) return;
        const siguiente = nav.scrollLeft + 90;
        if (siguiente >= maxScroll) {
            nav.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            nav.scrollBy({ left: 90, behavior: 'smooth' });
        }
    }

    intervalo = setInterval(scrollPaso, 2000);

    // Pausa al tocar/arrastrar
    nav.addEventListener('pointerdown', () => { pausado = true; clearInterval(intervalo); });
    nav.addEventListener('pointerup', () => {
        setTimeout(() => {
            pausado = false;
            intervalo = setInterval(scrollPaso, 2000);
        }, 3000);
    });
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

(function initCarruselesProductos() {
    const tracks = Array.from(document.querySelectorAll('.grid-items'));
    if (!tracks.length) return;

    tracks.forEach((track) => {
        let autoplayTimer = null;
        let resumeTimer = null;

        const obtenerCardsVisibles = () =>
            Array.from(track.querySelectorAll('.producto-card')).filter((card) => card.style.display !== 'none');

        const obtenerIndiceActual = (cards) => {
            if (!cards.length) return 0;
            const referencia = track.scrollLeft + (track.clientWidth * 0.25);
            let mejorIndice = 0;
            let menorDistancia = Number.POSITIVE_INFINITY;

            cards.forEach((card, idx) => {
                const left = card.offsetLeft - track.offsetLeft;
                const distancia = Math.abs(left - referencia);
                if (distancia < menorDistancia) {
                    menorDistancia = distancia;
                    mejorIndice = idx;
                }
            });

            return mejorIndice;
        };

        const deslizarAlIndice = (cards, index) => {
            const card = cards[index];
            if (!card) return;
            const left = card.offsetLeft - track.offsetLeft;
            track.scrollTo({ left, behavior: 'smooth' });
        };

        const avanzar = () => {
            const cards = obtenerCardsVisibles();
            if (cards.length <= 1) return;
            const indiceActual = obtenerIndiceActual(cards);
            const siguienteIndice = (indiceActual + 1) % cards.length;
            deslizarAlIndice(cards, siguienteIndice);
        };

        const detenerAutoplay = () => {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        };

        const iniciarAutoplay = () => {
            detenerAutoplay();
            if (obtenerCardsVisibles().length > 1) {
                autoplayTimer = setInterval(avanzar, 3000);
            }
        };

        const pausarAutoplay = () => {
            detenerAutoplay();
            if (resumeTimer) {
                clearTimeout(resumeTimer);
                resumeTimer = null;
            }
        };

        const reanudarConEspera = () => {
            if (resumeTimer) clearTimeout(resumeTimer);
            resumeTimer = setTimeout(() => {
                iniciarAutoplay();
            }, 3000);
        };

        track.addEventListener('pointerdown', pausarAutoplay, { passive: true });
        track.addEventListener('pointerup', reanudarConEspera, { passive: true });
        track.addEventListener('pointercancel', reanudarConEspera, { passive: true });
        track.addEventListener('mouseleave', reanudarConEspera, { passive: true });
        track.addEventListener('wheel', () => {
            pausarAutoplay();
            reanudarConEspera();
        }, { passive: true });

        iniciarAutoplay();
        carruselesProductos.push({ track, iniciarAutoplay, pausarAutoplay });
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

    document.querySelectorAll('.producto-card, .item-card').forEach((card) => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('.btn-fav, .control-cantidad, .stepper, button, a, input, label')) return;
            abrirModalDetalleProducto(card);
        });
    });

    const inputBuscar = document.getElementById('inputBuscar');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', (e) => filtrarProductos(e.target.value));
    }

    const navHome = document.getElementById('navHome');
    if (navHome) navHome.addEventListener('click', () => marcarNavActivo(navHome));

    actualizarTipoDocumentoSegunComprobante();

    const inputNumeroDocumento = document.getElementById('inputNumeroDocumento');
    if (inputNumeroDocumento) {
        inputNumeroDocumento.addEventListener('input', () => {
            inputNumeroDocumento.value = inputNumeroDocumento.value.replace(/\D/g, '');
        });
    }

    renderizarFavoritosModal();
    sincronizarBotonesFavoritos();
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

    carruselesProductos.forEach((c) => {
        if (!document.body.contains(c.track)) return;
        c.iniciarAutoplay();
    });
}

function toggleFavoritoVisual(btn) {
    const card = btn.closest('.producto-card, .item-card');
    const data = obtenerDatosProductoDesdeCard(card);
    if (!data) return;

    const icono = btn.querySelector('i');
    const idx = favoritos.findIndex((f) => Number(f.id) === Number(data.id));
    const activo = idx === -1;

    if (activo) {
        favoritos.unshift(data);
        animarCorazonAFavoritos(btn);
    } else {
        favoritos.splice(idx, 1);
    }

    guardarFavoritos();
    btn.classList.toggle('activo', activo);
    if (icono) {
        icono.classList.toggle('fa-solid', activo);
        icono.classList.toggle('fa-regular', !activo);
    }

    renderizarFavoritosModal();
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
    renderizarFavoritosModal();
    abrirModal('overlayFavoritos');
}

function abrirPerfilVisual(el) {
    marcarNavActivo(el);
    abrirModal('overlayPerfil');
}

/* ================================================================
   OFERTAS WEB - solo aplican en delivery y recojo (no comer_aqui)
   ================================================================ */

// Mapa: producto_id → descuento calculado { original, descuento, nuevo }
let descuentosActivos = {};

function calcularDescuentoProducto(precioOriginal, oferta) {
    if (oferta.tipo_descuento === 'porcentaje') {
        const d = precioOriginal * oferta.valor_descuento / 100;
        return Math.min(d, precioOriginal);
    }
    return Math.min(oferta.valor_descuento, precioOriginal);
}

function aplicarOfertasWeb() {
    const ofertas = (typeof OFERTAS_WEB !== 'undefined') ? OFERTAS_WEB : [];
    const esWebDescuentable = (entregaSeleccionada === 'delivery' || entregaSeleccionada === 'recojo');

    descuentosActivos = {};

    if (esWebDescuentable && ofertas.length) {
        ofertas.forEach(oferta => {
            oferta.productos.forEach(pid => {
                // Solo registramos el primer descuento que aplique (mayor primero si varios)
                if (!descuentosActivos[pid]) {
                    descuentosActivos[pid] = {
                        tipo: oferta.tipo_descuento,
                        valor: oferta.valor_descuento,
                        ofertaId: oferta.id,
                    };
                }
            });
        });
    }

    // Actualizar badges visuales y precio en las cards
    document.querySelectorAll('.producto-card, .item-card').forEach(card => {
        const cont = card.querySelector('.control-cantidad');
        if (!cont) return;
        const pid = parseInt(cont.dataset.id || '0', 10);
        const precioBase = parseFloat(cont.dataset.precioBase || cont.dataset.precio || '0');

        // Guardar precio base original
        if (!cont.dataset.precioBase) cont.dataset.precioBase = precioBase;

        const desc = descuentosActivos[pid];
        const wrap = card.querySelector('.item-img-wrap');
        let badge = card.querySelector('.badge-oferta-web');

        const precioFinal = card.querySelector('.precio');
        let spanOriginal = card.querySelector('.precio-original-tachado');

        if (desc && precioBase > 0) {
            const montoDesc = calcularDescuentoProducto(precioBase, { tipo_descuento: desc.tipo, valor_descuento: desc.valor });
            const precioNuevo = Math.max(0, precioBase - montoDesc);

            // Actualizar el precio del dataset para que el carrito use el precio con descuento
            cont.dataset.precio = precioNuevo.toFixed(2);

            // Badge visual
            if (!badge && wrap) {
                badge = document.createElement('span');
                badge.className = 'badge-oferta-web';
                wrap.appendChild(badge);
            }
            if (badge) {
                badge.textContent = desc.tipo === 'porcentaje'
                    ? `-${desc.valor}%`
                    : `-S/${desc.valor.toFixed(2)}`;
            }

            // Precio tachado
            if (precioFinal) {
                if (!spanOriginal) {
                    spanOriginal = document.createElement('span');
                    spanOriginal.className = 'precio-original-tachado';
                    precioFinal.parentNode.insertBefore(spanOriginal, precioFinal);
                }
                spanOriginal.textContent = `S/${precioBase.toFixed(2)}`;
                precioFinal.textContent = `S/${precioNuevo.toFixed(2)}`;
            }
        } else {
            // Sin descuento: restaurar
            cont.dataset.precio = precioBase;
            if (badge) badge.remove();
            if (spanOriginal) spanOriginal.remove();
            if (precioFinal) precioFinal.textContent = `S/${precioBase.toFixed(2)}`;
        }
    });

    // Renderizar strips de ofertas
    renderizarSeccionOfertasWeb(esWebDescuentable);
}

function renderizarSeccionOfertasWeb(visible) {
    const sec = document.getElementById('seccionOfertasWeb');
    if (!sec) return;
    const ofertas = (typeof OFERTAS_WEB !== 'undefined') ? OFERTAS_WEB : [];
    const productos = (typeof PRODUCTOS_MAP !== 'undefined') ? PRODUCTOS_MAP : {};

    if (!visible || !ofertas.length) {
        sec.style.display = 'none';
        return;
    }

    const itemsMarquesina = [];
    const vistosIds = new Set();
    ofertas.forEach(oferta => {
        oferta.productos.forEach(pid => {
            if (vistosIds.has(pid)) return;
            const prod = productos[pid];
            if (!prod || !prod.disponible) return;
            vistosIds.add(pid);
            const precioBase = prod.precio;
            const montoDesc = oferta.tipo_descuento === 'porcentaje'
                ? precioBase * oferta.valor_descuento / 100
                : Math.min(oferta.valor_descuento, precioBase);
            const precioNuevo = Math.max(0, precioBase - montoDesc);
            const labelDesc = oferta.tipo_descuento === 'porcentaje'
                ? `-${oferta.valor_descuento}%`
                : `-S/${oferta.valor_descuento.toFixed(2)}`;
            itemsMarquesina.push({ prod, precioNuevo, labelDesc });
        });
    });

    if (!itemsMarquesina.length) { sec.style.display = 'none'; return; }

    const colorFondo = ofertas[0].color_fondo;
    const tituloSeccion = ofertas.length === 1 ? ofertas[0].titulo : '⚡ Ofertas especiales';

    function cardHtml({ prod, precioNuevo, labelDesc }) {
        const imgHtml = prod.imagen
            ? `<img class="oferta-card-img" src="${escaparHtml(prod.imagen)}" alt="${escaparHtml(prod.nombre)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
            : '';
        const phStyle = prod.imagen ? 'display:none' : '';
        return `<div class="oferta-card">
            ${imgHtml}
            <div class="oferta-card-img-placeholder" style="${phStyle}"><i class="fa-solid fa-utensils"></i></div>
            <span class="oferta-desc-badge">${labelDesc}</span>
            <div class="oferta-card-info">
                <div class="oferta-card-nombre">${escaparHtml(prod.nombre)}</div>
                <div class="oferta-card-precios">
                    <span class="oferta-card-precio-antes">S/${prod.precio.toFixed(2)}</span>
                    <span class="oferta-card-precio-ahora">S/${precioNuevo.toFixed(2)}</span>
                </div>
                <button class="oferta-card-btn"
                    data-id="${prod.id}"
                    data-nombre="${escaparHtml(prod.nombre)}"
                    data-precio="${precioNuevo.toFixed(2)}"
                    data-imagen="${escaparHtml(prod.imagen || '')}"
                    data-tiene-opciones="${prod.tiene_opciones}"
                    onclick="agregarDesdeOferta(this)">
                    <i class="fa-solid fa-cart-plus"></i> Agregar
                </button>
            </div>
        </div>`;
    }

    // Duplicar para loop seamless
    const tarjetas = itemsMarquesina.map(cardHtml).join('');
    const duracion = Math.max(14, itemsMarquesina.length * 5);

    sec.style.display = 'block';
    sec.style.background = colorFondo;
    sec.innerHTML = `
        <div class="ofertas-web-header">
            <span class="owh-icono"><i class="fa-solid fa-bolt"></i></span>
            <h3>${escaparHtml(tituloSeccion)}</h3>
            <span class="owh-sub">${itemsMarquesina.length} promo${itemsMarquesina.length !== 1 ? 's' : ''}</span>
        </div>
        <div class="ofertas-marquesina-wrap" id="ofertasWrap">
            <div class="ofertas-marquesina" id="ofertasMarquesina">
                ${tarjetas}${tarjetas}
            </div>
        </div>`;

    _initDragScroll(document.getElementById('ofertasWrap'), document.getElementById('ofertasMarquesina'));
}

function _initDragScroll(wrap, track) {
    if (!wrap || !track) return;

    const mitad = () => track.scrollWidth / 2;

    // --- Auto-scroll continuo con RAF ---
    let rafId = null;
    let velocidad = 0.55; // px por frame
    let pausado = false;

    function tick() {
        if (!pausado) {
            wrap.scrollLeft += velocidad;
            // Loop seamless: cuando llega a la mitad, vuelve al inicio
            if (wrap.scrollLeft >= mitad()) {
                wrap.scrollLeft -= mitad();
            }
        }
        rafId = requestAnimationFrame(tick);
    }
    rafId = requestAnimationFrame(tick);

    // Pausar en hover (desktop)
    wrap.addEventListener('mouseenter', () => { pausado = true; });
    wrap.addEventListener('mouseleave', () => { pausado = false; });

    // Pausar mientras el usuario arrastra / toca
    let touching = false;
    wrap.addEventListener('pointerdown', () => { touching = true; pausado = true; }, { passive: true });
    wrap.addEventListener('pointerup',   () => { touching = false; setTimeout(() => { if (!touching) pausado = false; }, 800); });
    wrap.addEventListener('pointercancel', () => { touching = false; setTimeout(() => { pausado = false; }, 800); });
}

// Hook: cuando cambia tipo de entrega, recalcular ofertas
const _seleccionarEntregaOriginal = seleccionarEntrega;
seleccionarEntrega = function(el) {
    _seleccionarEntregaOriginal(el);
    aplicarOfertasWeb();
};

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(aplicarOfertasWeb, 200);
});

function agregarDesdeOferta(btn) {
    const id       = parseInt(btn.dataset.id, 10);
    const nombre   = btn.dataset.nombre;
    const precio   = parseFloat(btn.dataset.precio);
    const imagen   = btn.dataset.imagen || '';
    const tieneOpc = parseInt(btn.dataset.tieneOpciones || '0', 10);

    if (tieneOpc) {
        const cardReal  = document.querySelector(`.control-cantidad[data-id="${id}"]`);
        if (cardReal) {
            const btnAgregar = cardReal.closest('article')?.querySelector('.btn-agregar');
            if (btnAgregar) { agregarProducto(btnAgregar); return; }
        }
    }

    // Animación: volar imagen al carrito
    const imgEl = btn.closest('.oferta-card')?.querySelector('.oferta-card-img');
    if (imgEl) {
        volarAlCarrito(imgEl);
    } else if (imagen) {
        // Crear imagen temporal para la animación
        const tmpImg = document.createElement('img');
        tmpImg.src = imagen;
        tmpImg.style.cssText = 'position:fixed;opacity:0;pointer-events:none;width:1px;height:1px';
        document.body.appendChild(tmpImg);
        const rect = btn.getBoundingClientRect();
        tmpImg.style.left = rect.left + 'px';
        tmpImg.style.top  = rect.top  + 'px';
        tmpImg.style.width  = '40px';
        tmpImg.style.height = '40px';
        tmpImg.style.opacity = '1';
        volarAlCarrito(tmpImg);
        setTimeout(() => tmpImg.remove(), 900);
    }

    carrito.push({ id, nombre, precio, imagen, cantidad: 1, opciones: [] });
    guardarCarrito();
    actualizarBadgeCarrito();

    // Feedback visual en el botón
    const textoOriginal = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Agregado';
    btn.style.background = '#22c55e';
    setTimeout(() => {
        btn.innerHTML = textoOriginal;
        btn.style.background = '';
    }, 1200);
}
