/**
 * Checkout Multi-Paso con APIPERU Integration
 * 
 * Pasos:
 * 1. Seleccionar tipo de comprobante (Boleta/Factura)
 * 2. Consultar DNI/RUC con APIPERU (auto-llena nombre)
 * 3. Datos de entrega
 * 4. Método de pago (Culqi)
 */

class CheckoutAPIPeru {
    constructor() {
        this.paso = 1;
        this.datos = {
            tipo_comprobante: null,
            tipo_documento: null,
            numero_documento: null,
            cliente_nombre: null,
            cliente_email: null,
            cliente_telefono: null,
            tipo_entrega: null,
            direccion: null,
            referencia: null,
            mesa_id: null,
            zona_id: null,
            mesa_nombre: null,
            metodo_pago: null,
        };
        this.zonasMesas = [];
        this.refreshMesasTimer = null;
        this.consultandoDocumento = false;
        this.procesandoPagoOnline = false;
        this.init();
    }

    init() {
        console.log('CheckoutAPIPeru inicializado');
        // Los listeners se agregan en el modal cuando se abre
    }

        mostrarModal() {
            const modal = document.getElementById('checkout-modal');
            if (modal) {
                if (typeof window.sincronizarClienteCheckout === 'function') {
                    window.sincronizarClienteCheckout();
                }
                modal.classList.add('activo');
                this.paso = 1;
                this.mostrarPaso(1);
                this.setupEventListeners();
                this.configurarTiposEntrega();
                this.cargarMesasDisponibles(true);
                this.iniciarAutoRefreshMesas();
                // Llenar nombre si se conoce
                const nombre = document.getElementById('cliente-nombre');
                if (nombre && !nombre.value) {
                    nombre.value = this.datos.cliente_nombre || '';
                }
            }
        }

        cerrarModal() {
            const modal = document.getElementById('checkout-modal');
            if (modal) {
                modal.classList.remove('activo');
            }
            if (this.refreshMesasTimer) {
                clearInterval(this.refreshMesasTimer);
                this.refreshMesasTimer = null;
            }
        }

        setupEventListeners() {
            if (this._listenersAdded) return;
            this._listenersAdded = true;

            // Cerrar al hacer click en el overlay (fuera del contenedor)
            document.getElementById('checkout-modal')?.addEventListener('click', (e) => {
                if (e.target === document.getElementById('checkout-modal')) {
                    this.cerrarModal();
                }
            });

            // PASO 1: Seleccionar comprobante
            document.querySelectorAll('.opcion-comprobante').forEach(el => {
                el.onclick = (e) => {
                    document.querySelectorAll('.opcion-comprobante').forEach(o => o.classList.remove('seleccionado'));
                    el.classList.add('seleccionado');
                    this.seleccionarComprobante(el.dataset.comprobante);
                    // Habilitar botón de siguiente
                    const btnSiguiente = document.getElementById('paso-1-siguiente');
                    if (btnSiguiente) btnSiguiente.disabled = false;
                };
            });

            // PASO 1: Botón siguiente
            document.getElementById('paso-1-siguiente')?.addEventListener('click', () => {
                this.mostrarPaso(2);
            });

            // PASO 2: Consultar documento
            document.getElementById('documento-consultar-dni')?.addEventListener('click', () => {
                this.datos.tipo_documento = 'dni';
                this.consultarDocumento();
            });

            document.getElementById('documento-consultar-ruc')?.addEventListener('click', () => {
                this.datos.tipo_documento = 'ruc';
                this.consultarDocumento();
            });

            // PASO 3: Datos de entrega
            document.querySelectorAll('input[name="tipo_entrega"]')?.forEach(el => {
                el.addEventListener('change', () => {
                    this.cambiarTipoEntregaUI(el.value);
                });
            });

            document.getElementById('cliente-zona')?.addEventListener('change', (e) => {
                this.renderMesasPorZona((e.target && e.target.value) ? Number(e.target.value) : 0, 0);
            });

            document.getElementById('cliente-mesa')?.addEventListener('change', (e) => {
                const mesaId = Number((e.target && e.target.value) ? e.target.value : 0);
                this.marcarMesaSeleccionadaEnMapa(mesaId);
                this.sincronizarMesaSeleccionadaDesdeUI();
            });

            document.getElementById('paso-3-siguiente')?.addEventListener('click', () => {
                this.validarDatosEntrega();
            });

            // PASO 4: Método de pago
            document.querySelectorAll('.metodo-pago-card').forEach(el => {
                el.onclick = () => {
                    document.querySelectorAll('.metodo-pago-card').forEach(m => m.classList.remove('seleccionado'));
                    el.classList.add('seleccionado');
                    const metodo = el.dataset.metodo;
                    const btnProcesar = document.getElementById('paso-4-procesar');
                    if (btnProcesar) btnProcesar.disabled = false;
                    this.datos.metodo_pago = metodo;
                };
            });

            document.getElementById('paso-4-procesar')?.addEventListener('click', () => {
                const seleccionado = document.querySelector('.metodo-pago-card.seleccionado');
                if (seleccionado) {
                    this.seleccionarMetodoPago(seleccionado.dataset.metodo);
                }
            });

            // Botones de navegación
            document.getElementById('checkout-cerrar')?.addEventListener('click', () => {
                this.cerrarModal();
            });

            document.getElementById('paso-2-volver')?.addEventListener('click', () => {
                this.mostrarPaso(1);
            });

            document.getElementById('paso-2-manual')?.addEventListener('click', () => {
                this.continuarManualDocumento();
            });

            document.getElementById('paso-3-volver')?.addEventListener('click', () => {
                this.mostrarPaso(2);
            });

            document.getElementById('paso-4-volver')?.addEventListener('click', () => {
                this.mostrarPaso(3);
            });
        }

        validarDatosEntrega() {
            const nombre = document.getElementById('cliente-nombre')?.value || '';
            const email = document.getElementById('cliente-email')?.value || '';
            const telefono = document.getElementById('cliente-telefono')?.value || '';
            const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked')?.value;

            if (!nombre || nombre.trim().length < 2) {
                this.mostrarError('Ingresa un nombre válido');
                return;
            }
            if (!this.esEmailValido(email)) {
                this.mostrarError('Ingresa un email válido');
                return;
            }
            if (!telefono || !/^\d{6,20}$/.test(telefono.replace(/\D/g, ''))) {
                this.mostrarError('Ingresa un teléfono válido');
                return;
            }
            if (!tipoEntrega) {
                this.mostrarError('Selecciona recojo, delivery o comer aqui');
                return;
            }

            this.datos.cliente_nombre = nombre;
            this.datos.cliente_email = email;
            this.datos.cliente_telefono = telefono;
            this.datos.tipo_entrega = tipoEntrega;

            if (tipoEntrega === 'delivery') {
                const direccion = document.getElementById('cliente-direccion')?.value || '';
                if (!direccion || direccion.length < 5) {
                    this.mostrarError('Ingresa una dirección válida');
                    return;
                }
                this.datos.direccion = direccion;
                this.datos.referencia = document.getElementById('cliente-referencia')?.value || null;
                this.datos.mesa_id = null;
                this.datos.zona_id = null;
                this.datos.mesa_nombre = null;
            } else if (tipoEntrega === 'comer_aqui') {
                const zonaId = Number(document.getElementById('cliente-zona')?.value || 0);
                const mesaSelect = document.getElementById('cliente-mesa');
                const mesaId = Number(mesaSelect?.value || 0);
                if (zonaId <= 0) {
                    this.mostrarError('Selecciona una zona para comer aqui');
                    return;
                }
                if (mesaId <= 0) {
                    this.mostrarError('Selecciona una mesa disponible');
                    return;
                }

                const opcion = mesaSelect.options[mesaSelect.selectedIndex];
                this.datos.zona_id = zonaId;
                this.datos.mesa_id = mesaId;
                this.datos.mesa_nombre = opcion ? opcion.textContent : null;
                this.datos.direccion = null;
                this.datos.referencia = null;
            } else {
                this.datos.direccion = null;
                this.datos.referencia = null;
                this.datos.mesa_id = null;
                this.datos.zona_id = null;
                this.datos.mesa_nombre = null;
            }

            this.mostrarPaso(4);
        }

    /**
     * PASO 1: Seleccionar tipo de comprobante
     */
    seleccionarComprobante(tipo) {
        this.datos.tipo_comprobante = tipo;
        this.datos.tipo_documento = tipo === 'factura' ? 'ruc' : 'dni';

        const seccionDni = document.getElementById('seccion-dni');
        const seccionRuc = document.getElementById('seccion-ruc');
        if (seccionDni) seccionDni.style.display = this.datos.tipo_documento === 'dni' ? 'block' : 'none';
        if (seccionRuc) seccionRuc.style.display = this.datos.tipo_documento === 'ruc' ? 'block' : 'none';
    }

    configurarTiposEntrega() {
        const cfg = window.APP_CONFIG || {};
        const opciones = [
            { id: 'entrega-recojo', activo: cfg.recojoActivo !== false },
            { id: 'entrega-comer-aqui', activo: cfg.comerAquiActivo !== false },
            { id: 'entrega-delivery', activo: cfg.deliveryActivo !== false },
        ];

        let primeraActiva = null;
        opciones.forEach((op) => {
            const input = document.getElementById(op.id);
            if (!input) return;

            const bloque = input.closest('.form-opcion');
            input.disabled = !op.activo;
            if (bloque) {
                bloque.style.display = op.activo ? '' : 'none';
            }
            if (op.activo && !primeraActiva) {
                primeraActiva = input;
            }
        });

        const actual = document.querySelector('input[name="tipo_entrega"]:checked');
        if (!actual || actual.disabled) {
            if (primeraActiva) {
                primeraActiva.checked = true;
            }
        }

        const entrega = document.querySelector('input[name="tipo_entrega"]:checked')?.value || 'recojo';
        this.cambiarTipoEntregaUI(entrega);
    }

    cambiarTipoEntregaUI(entrega) {
        const seccionDireccion = document.getElementById('seccion-direccion');
        const seccionMesa = document.getElementById('seccion-mesa');
        if (seccionDireccion) {
            seccionDireccion.style.display = entrega === 'delivery' ? 'block' : 'none';
        }
        if (seccionMesa) {
            seccionMesa.style.display = entrega === 'comer_aqui' ? 'block' : 'none';
        }
    }

    async cargarMesasDisponibles(conservarSeleccion = true) {
        try {
            const zonaSeleccionada = Number(document.getElementById('cliente-zona')?.value || this.datos.zona_id || 0);
            const mesaSeleccionada = Number(document.getElementById('cliente-mesa')?.value || this.datos.mesa_id || 0);

            const response = await fetch('api/mesas_disponibles.php', { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!data.ok) {
                this.desactivarComerAquiPorDisponibilidad();
                return;
            }

            this.zonasMesas = Array.isArray(data.zonas) ? data.zonas : [];
            const selectZona = document.getElementById('cliente-zona');
            if (!selectZona) {
                return;
            }

            const opcionesZona = ['<option value="">Selecciona una zona</option>'];
            this.zonasMesas.forEach((z) => {
                opcionesZona.push(`<option value="${Number(z.id)}">${z.nombre}</option>`);
            });
            selectZona.innerHTML = opcionesZona.join('');

            if (this.zonasMesas.length > 0) {
                let zonaObjetivo = Number(this.zonasMesas[0].id);
                if (conservarSeleccion && zonaSeleccionada > 0 && this.zonasMesas.some((z) => Number(z.id) === zonaSeleccionada)) {
                    zonaObjetivo = zonaSeleccionada;
                }

                selectZona.value = String(zonaObjetivo);
                this.renderMesasPorZona(zonaObjetivo, conservarSeleccion ? mesaSeleccionada : 0);
            } else {
                this.renderMesasPorZona(0, 0);
                this.desactivarComerAquiPorDisponibilidad();
            }
        } catch (error) {
            console.error('No se pudo cargar mesas disponibles:', error);
            this.desactivarComerAquiPorDisponibilidad();
        }
    }

    iniciarAutoRefreshMesas() {
        if (this.refreshMesasTimer) {
            clearInterval(this.refreshMesasTimer);
        }

        this.refreshMesasTimer = setInterval(() => {
            const modal = document.getElementById('checkout-modal');
            if (!modal || !modal.classList.contains('activo')) {
                return;
            }

            const entregaActual = document.querySelector('input[name="tipo_entrega"]:checked')?.value || '';
            if (entregaActual !== 'comer_aqui') {
                return;
            }

            this.cargarMesasDisponibles(true).catch(() => {});
        }, 12000);
    }

    desactivarComerAquiPorDisponibilidad() {
        const radio = document.getElementById('entrega-comer-aqui');
        if (!radio) {
            return;
        }

        radio.disabled = true;
        const bloque = radio.closest('.form-opcion');
        if (bloque) {
            bloque.style.display = 'none';
        }

        if (radio.checked) {
            const recojo = document.getElementById('entrega-recojo');
            const delivery = document.getElementById('entrega-delivery');
            if (recojo && !recojo.disabled) {
                recojo.checked = true;
                this.cambiarTipoEntregaUI('recojo');
            } else if (delivery && !delivery.disabled) {
                delivery.checked = true;
                this.cambiarTipoEntregaUI('delivery');
            }
        }
    }

    renderMesasPorZona(zonaId, mesaSeleccionada = 0) {
        const selectMesa = document.getElementById('cliente-mesa');
        if (!selectMesa) {
            return;
        }

        const zona = this.zonasMesas.find((z) => Number(z.id) === Number(zonaId));
        const opcionesMesa = ['<option value="">Selecciona una mesa</option>'];

        this.renderMapaMesas(zona);

        if (zona && Array.isArray(zona.mesas)) {
            zona.mesas.forEach((m) => {
                if (m.ocupada) {
                    return;
                }
                const texto = `${m.nombre} · ${m.sillas || m.capacidad} sillas`;
                opcionesMesa.push(`<option value="${Number(m.id)}">${texto}</option>`);
            });
        }

        if (opcionesMesa.length === 1) {
            opcionesMesa.push('<option value="" disabled>No hay mesas disponibles en esta zona</option>');
        }

        selectMesa.innerHTML = opcionesMesa.join('');

        if (mesaSeleccionada > 0 && selectMesa.querySelector(`option[value="${mesaSeleccionada}"]`)) {
            selectMesa.value = String(mesaSeleccionada);
            this.marcarMesaSeleccionadaEnMapa(mesaSeleccionada);
        } else {
            selectMesa.value = '';
            this.marcarMesaSeleccionadaEnMapa(0);
        }

        this.sincronizarMesaSeleccionadaDesdeUI();
    }

    renderMapaMesas(zona) {
        const grid = document.getElementById('mesa-mapa-grid');
        if (!grid) {
            return;
        }

        if (!zona || !Array.isArray(zona.mesas) || zona.mesas.length === 0) {
            grid.innerHTML = '<div class="mesa-mapa-empty">No hay mesas registradas en esta zona.</div>';
            return;
        }

        grid.innerHTML = zona.mesas.map((m) => {
            const estado = m.ocupada ? 'Ocupada' : 'Libre';
            const claseOcupada = m.ocupada ? ' ocupada' : '';
            return `
                <button type="button" class="mesa-mapa-item${claseOcupada}" data-mesa-id="${Number(m.id)}" ${m.ocupada ? 'disabled' : ''}>
                    <span class="mesa-titulo">${m.nombre}</span>
                    <span class="mesa-meta">${m.sillas || m.capacidad} sillas · ${estado}</span>
                </button>
            `;
        }).join('');

        grid.querySelectorAll('.mesa-mapa-item').forEach((btn) => {
            btn.addEventListener('click', () => {
                const mesaId = Number(btn.dataset.mesaId || 0);
                if (!mesaId) {
                    return;
                }

                const selectMesa = document.getElementById('cliente-mesa');
                if (selectMesa) {
                    selectMesa.value = String(mesaId);
                }
                this.marcarMesaSeleccionadaEnMapa(mesaId);
                this.sincronizarMesaSeleccionadaDesdeUI();
            });
        });
    }

    marcarMesaSeleccionadaEnMapa(mesaId) {
        const grid = document.getElementById('mesa-mapa-grid');
        if (!grid) {
            return;
        }

        grid.querySelectorAll('.mesa-mapa-item').forEach((el) => {
            const id = Number(el.getAttribute('data-mesa-id') || 0);
            el.classList.toggle('seleccionada', id === Number(mesaId) && mesaId > 0);
        });
    }

    sincronizarMesaSeleccionadaDesdeUI() {
        const tipoEntrega = document.querySelector('input[name="tipo_entrega"]:checked')?.value || this.datos.tipo_entrega;
        if (tipoEntrega !== 'comer_aqui') {
            this.datos.mesa_id = null;
            this.datos.zona_id = null;
            this.datos.mesa_nombre = null;
            return;
        }

        const zonaSelect = document.getElementById('cliente-zona');
        const mesaSelect = document.getElementById('cliente-mesa');

        const zonaId = Number(zonaSelect?.value || 0);
        const mesaId = Number(mesaSelect?.value || 0);

        this.datos.zona_id = zonaId > 0 ? zonaId : null;
        this.datos.mesa_id = mesaId > 0 ? mesaId : null;

        const opcion = mesaSelect && mesaSelect.selectedIndex >= 0 ? mesaSelect.options[mesaSelect.selectedIndex] : null;
        this.datos.mesa_nombre = opcion && mesaId > 0 ? opcion.textContent : null;
    }

    /**
     * PASO 2: Consultar y validar documento
     */
    async consultarDocumento() {
        const numeroInput = document.getElementById(`documento-input-${this.datos.tipo_documento}`);
        const numero = numeroInput ? numeroInput.value.trim() : '';

        if (!numero) {
            this.mostrarError('Por favor ingresa ' + (this.datos.tipo_documento === 'dni' ? 'tu DNI' : 'el RUC'));
            return;
        }

        // Validación básica de formato
        const esValido = this.datos.tipo_documento === 'dni' 
            ? /^\d{8}$/.test(numero)
            : /^\d{11}$/.test(numero);

        if (!esValido) {
            const mensaje = this.datos.tipo_documento === 'dni'
                ? 'El DNI debe tener exactamente 8 dígitos'
                : 'El RUC debe tener exactamente 11 dígitos';
            this.mostrarError(mensaje);
            return;
        }

        this.consultandoDocumento = true;
        this.mostrarCargando(true, 'Validando ' + (this.datos.tipo_documento === 'dni' ? 'DNI' : 'RUC') + '...');

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 12000);

            const response = await fetch(`api/consultar_documento.php?tipo=${this.datos.tipo_documento}&numero=${numero}`, {
                headers: { 'Accept': 'application/json' },
                signal: controller.signal,
            });

            clearTimeout(timeoutId);

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const texto = await response.text();
                const preview = texto.slice(0, 120).replace(/\s+/g, ' ').trim();
                throw new Error('Respuesta inválida del servidor. Verifica la URL de la API.');
            }

            const resultado = await response.json();

            if (!resultado.ok) {
                const msg = String(resultado.mensaje || 'No se pudo validar el documento');
                if (/consulta reniec fallida|consulta ruc fallida|error conectando con apiperu|failed to connect|timed out|timeout/i.test(msg)) {
                    throw new Error('No se pudo consultar RENIEC/SUNAT en este momento. Intenta nuevamente o continúa manualmente.');
                }
                throw new Error(msg);
            }

            const datos = resultado.datos;

            // Guardar datos
            this.datos.numero_documento = numero;
            
            if (this.datos.tipo_documento === 'dni') {
                this.datos.cliente_nombre = datos.nombreCompleto || `${datos.apellidoPaterno} ${datos.apellidoMaterno} ${datos.nombres}`;
            } else {
                this.datos.cliente_nombre = datos.razonSocial || datos.nombreComercial || '';
            }

            // Mostrar información
            this.mostrarDatosConsultados(datos);

            const inputNombre = document.getElementById('cliente-nombre');
            if (inputNombre) {
                inputNombre.value = this.datos.cliente_nombre || '';
            }
            
            // Pasar al paso 3
            this.mostrarPaso(3);

        } catch (error) {
            console.error('Error consultando documento:', error);
            if (error.name === 'AbortError') {
                this.mostrarError('La consulta está tardando demasiado. Intenta nuevamente o continúa manualmente.');
            } else {
                this.mostrarError('Error: ' + error.message);
            }
        } finally {
            this.consultandoDocumento = false;
            this.mostrarCargando(false);
        }
    }

    continuarManualDocumento() {
        const numeroInput = document.getElementById(`documento-input-${this.datos.tipo_documento}`);
        const numero = numeroInput ? numeroInput.value.trim() : '';

        if (!numero) {
            this.mostrarError('Ingresa ' + (this.datos.tipo_documento === 'dni' ? 'tu DNI' : 'el RUC') + ' para continuar.');
            return;
        }

        const esValido = this.datos.tipo_documento === 'dni'
            ? /^\d{8}$/.test(numero)
            : /^\d{11}$/.test(numero);

        if (!esValido) {
            this.mostrarError(this.datos.tipo_documento === 'dni'
                ? 'El DNI debe tener exactamente 8 dígitos'
                : 'El RUC debe tener exactamente 11 dígitos');
            return;
        }

        this.datos.numero_documento = numero;
        // Limpiamos para que el usuario lo escriba manualmente en paso 3.
        this.datos.cliente_nombre = '';

        const inputNombre = document.getElementById('cliente-nombre');
        if (inputNombre) {
            inputNombre.value = '';
            inputNombre.focus();
        }

        const mensaje = this.datos.tipo_documento === 'dni'
            ? 'RENIEC no respondió. Continúa ingresando tu nombre manualmente.'
            : 'SUNAT/RUC no respondió. Continúa ingresando la razón social manualmente.';
        this.mostrarError(mensaje);
        this.mostrarPaso(3);
    }

    /**
     * Mostrar datos consultados de RENIEC/RUC
     */
    mostrarDatosConsultados(datos) {
        const contenedor = document.getElementById('datos-consultados');
        if (!contenedor) return;

        let html = '<div class="datos-consultados-box">';
        html += `<h4>${this.datos.tipo_documento === 'dni' ? 'Datos del DNI' : 'Datos del RUC'}</h4>`;
        
        if (this.datos.tipo_documento === 'dni') {
            html += `<p><strong>Nombre Completo:</strong> ${datos.nombreCompleto}</p>`;
            html += `<p><strong>Estado:</strong> ${datos.estado}</p>`;
            if (datos.fechaNacimiento) {
                html += `<p><strong>Fecha Nacimiento:</strong> ${datos.fechaNacimiento}</p>`;
            }
        } else {
            html += `<p><strong>Razón Social:</strong> ${datos.razonSocial}</p>`;
            if (datos.nombreComercial) {
                html += `<p><strong>Nombre Comercial:</strong> ${datos.nombreComercial}</p>`;
            }
            html += `<p><strong>Estado:</strong> ${datos.estado}</p>`;
            if (datos.direccion) {
                html += `<p><strong>Dirección:</strong> ${datos.direccion}</p>`;
            }
        }
        
        html += '</div>';
        contenedor.innerHTML = html;
    }

    /**
     * PASO 3: Datos de entrega
     */
    completarDatosEntrega() {
        const nombre = document.getElementById('cliente-nombre-paso3')?.value || this.datos.cliente_nombre;
        const email = document.getElementById('cliente-email-paso3')?.value.trim() || '';
        const telefono = document.getElementById('cliente-telefono-paso3')?.value.trim() || '';
        const tipoEntrega = document.querySelector('input[name="tipo-entrega"]:checked')?.value || '';

        // Validaciones
        if (!nombre || nombre.trim().length < 2) {
            this.mostrarError('Ingresa un nombre válido');
            return;
        }
        if (!email || !this.esEmailValido(email)) {
            this.mostrarError('Ingresa un email válido');
            return;
        }
        if (!telefono || !/^\d{6,20}$/.test(telefono.replace(/\D/g, ''))) {
            this.mostrarError('Ingresa un teléfono válido');
            return;
        }
            if (!tipoEntrega) {
                this.mostrarError('Selecciona recojo, delivery o comer aqui');
            return;
        }

        // Guardar datos
        this.datos.cliente_nombre = nombre;
        this.datos.cliente_email = email;
        this.datos.cliente_telefono = telefono;
        this.datos.tipo_entrega = tipoEntrega;

        // Si es delivery, pedir dirección
        if (tipoEntrega === 'delivery') {
            this.mostrarPaso(3.5); // Subruta para dirección
            return;
        }

        // Si es recojo, pasar a pago
        this.mostrarPaso(4);
    }

    /**
     * Dirección de delivery
     */
    completarDireccion() {
        const direccion = document.getElementById('direccion-delivery')?.value.trim() || '';
        const referencia = document.getElementById('referencia-delivery')?.value.trim() || '';

        if (!direccion || direccion.length < 5) {
            this.mostrarError('Ingresa una dirección válida');
            return;
        }

        this.datos.direccion = direccion;
        this.datos.referencia = referencia || null;

        this.mostrarPaso(4);
    }

    /**
     * PASO 4: Método de pago
     */
    seleccionarMetodoPago(metodo) {
        const metodoNormalizado = (metodo === 'yape' || metodo === 'plin') ? 'yape_plin' : metodo;
        this.datos.metodo_pago = metodoNormalizado;

        if (metodoNormalizado === 'efectivo') {
            this.procesarPedido();
        } else {
            this.procesarConCulqi(metodoNormalizado);
        }
    }

    /**
     * Procesar pago con Culqi
     */
    async procesarConCulqi(metodoPago) {
        const monto = this.calcularMonto();
        const nombreNegocio = (window.APP_CONFIG && window.APP_CONFIG.nombreNegocio) || 'Carta Digital';
        const publicKey = (window.APP_CONFIG && window.APP_CONFIG.culqiPublicKey) || '';

        if (!window.Culqi) {
            this.mostrarError('Culqi no está disponible. Recarga la página e intenta de nuevo.');
            return;
        }
        if (!publicKey || publicKey.includes('XXXX')) {
            this.mostrarError('Culqi no está configurado en este momento.');
            return;
        }

        const paymentMethods = this.obtenerMetodosPagoCulqi(metodoPago);

        // Registrar contexto activo para callback global de Culqi.
        window.__checkoutApiperuCtx = this;
        window.__checkoutApiperuFlowActivo = true;
        window.culqi = function () {
            const ctx = window.__checkoutApiperuCtx;
            if (!ctx || !window.Culqi) {
                return;
            }

            if (Culqi.token) {
                if (ctx.procesandoPagoOnline) {
                    return;
                }
                ctx.procesandoPagoOnline = true;
                if (typeof window.cerrarCheckoutCulqi === 'function') {
                    window.cerrarCheckoutCulqi();
                } else if (typeof Culqi.close === 'function') {
                    Culqi.close();
                }
                ctx.datos.culqi_token = Culqi.token.id;
                ctx.procesarPedido();
                return;
            }

            // En algunos métodos (ej. Yape) Culqi puede devolver order en lugar de token.
            if (Culqi.order) {
                if (ctx.procesandoPagoOnline) {
                    return;
                }
                ctx.procesandoPagoOnline = true;
                if (typeof window.cerrarCheckoutCulqi === 'function') {
                    window.cerrarCheckoutCulqi();
                } else if (typeof Culqi.close === 'function') {
                    Culqi.close();
                }
                ctx.datos.culqi_token = Culqi.order.id;
                ctx.procesarPedido();
                return;
            }

            if (Culqi.error) {
                if (typeof window.cerrarCheckoutCulqi === 'function') {
                    window.cerrarCheckoutCulqi();
                } else if (typeof Culqi.close === 'function') {
                    Culqi.close();
                }
                const msg = Culqi.error.user_message || Culqi.error.merchant_message || 'No se pudo procesar el pago.';
                ctx.mostrarError(msg);
            }
        };

        try {
            Culqi.publicKey = publicKey;
            Culqi.settings({
                title: nombreNegocio,
                currency: 'PEN',
                amount: Math.round(monto * 100), // Culqi trabaja en céntimos
            });
            Culqi.options({
                lang: 'auto',
                installments: false,
                paymentMethods,
                paymentMethodsSort: Object.keys(paymentMethods).filter((k) => paymentMethods[k]),
            });
            Culqi.open();
        } catch (e) {
            this.mostrarError('No se pudo abrir Culqi: ' + (e.message || 'error desconocido'));
        }
    }

    obtenerMetodosPagoCulqi(metodoPago) {
        if (metodoPago === 'tarjeta') {
            return {
                tarjeta: true,
                yape: false,
                bancaMovil: false,
                agente: false,
                billetera: false,
                cuotealo: false,
            };
        }

        // Para yape/plin usamos checkout billetera de Culqi.
        return {
            tarjeta: false,
            yape: true,
            bancaMovil: false,
            agente: false,
            billetera: false,
            cuotealo: false,
        };
    }

    /**
     * Procesar pedido final
     */
    async procesarPedido() {
        // Obtener carrito
        const carrito = this.obtenerCarrito();
        if (!carrito || carrito.length === 0) {
            this.mostrarError('Tu carrito está vacío');
            return;
        }

        this.mostrarCargando(true, 'Procesando pedido...');

        try {
            this.sincronizarMesaSeleccionadaDesdeUI();
            if (this.datos.tipo_entrega === 'comer_aqui' && !this.datos.mesa_id) {
                this.mostrarCargando(false);
                this.mostrarError('Selecciona una mesa disponible para comer aqui antes de pagar.');
                this.mostrarPaso(3);
                return;
            }

            const payload = {
                items: carrito,
                cliente_nombre: this.datos.cliente_nombre,
                cliente_telefono: this.datos.cliente_telefono,
                cliente_email: this.datos.cliente_email,
                tipo_comprobante: this.datos.tipo_comprobante,
                tipo_documento: this.datos.tipo_documento,
                numero_documento: this.datos.numero_documento,
                tipo_entrega: this.datos.tipo_entrega,
                direccion: this.datos.tipo_entrega === 'delivery' ? this.datos.direccion : null,
                mesa_id: this.datos.tipo_entrega === 'comer_aqui' ? this.datos.mesa_id : null,
                referencia: this.datos.referencia || null,
                metodo_pago: this.datos.metodo_pago,
                notas: document.getElementById('notas-pedido')?.value || '',
                culqi_token: this.datos.culqi_token || null,
            };

            const response = await fetch('api/pedido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const resultado = await response.json();

            if (!resultado.ok) {
                throw new Error(resultado.mensaje || 'Error procesando pedido');
            }

            // Éxito
            this.mostrarConfirmacion(resultado);

        } catch (error) {
            console.error('Error procesando pedido:', error);
            const msg = error.message || '';
            if (msg.toLowerCase().includes('faltan opciones')) {
                this.mostrarError(msg + ' Por favor, retira ese producto del carrito y agrégalo nuevamente seleccionando todas las opciones requeridas.');
            } else {
                this.mostrarError('Error: ' + msg);
            }
            this.procesandoPagoOnline = false;
        } finally {
            this.mostrarCargando(false);
        }
    }

    /**
     * Mostrar paso específico
     */
    mostrarPaso(numero) {
        // Ocultar todos
        for (let i = 1; i <= 4; i++) {
            const el = document.getElementById(`paso-${i}`);
            if (el) el.style.display = 'none';
        }
        const entre = document.getElementById('paso-3.5');
        if (entre) entre.style.display = 'none';

        // Mostrar actual
        const pasoActual = document.getElementById(`paso-${numero}`);
        if (pasoActual) pasoActual.style.display = 'block';

        this.paso = numero;
        this.actualizarIndicador(numero);
    }

    /**
     * Actualizar indicador de pasos
     */
    actualizarIndicador(numero) {
        const indicador = document.getElementById('pasos-indicador');
        if (!indicador) return;

        const labels = ['Comprobante', 'Documento', 'Entrega', 'Pago'];
        const pasos = [1, 2, 3, 4];

        indicador.innerHTML = pasos.map((p, i) => {
            const claseActiva = p < numero ? 'completado' : p == numero ? 'activo' : '';
            return `<div class="paso-item ${claseActiva}"><span>${p}</span> ${labels[i]}</div>`;
        }).join('');
    }

    /**
     * Utilidades
     */
    mostrarError(msg) {
        const el = document.getElementById('mensaje-error');
        if (el) {
            el.textContent = msg;
            el.style.display = 'block';
            setTimeout(() => el.style.display = 'none', 5000);
        } else {
            alert('Error: ' + msg);
        }
    }

    mostrarCargando(show, mensaje) {
        const el = document.getElementById('cargando-overlay');
        if (el) {
            el.style.display = show ? 'flex' : 'none';
            if (show && mensaje) {
                document.getElementById('cargando-mensaje').textContent = mensaje;
            }
        }
    }

    mostrarConfirmacion(datos) {
        // Mostrar modal de confirmación
        const modal = document.getElementById('confirmacion-pedido-modal');
        if (modal) {
            const numero = document.getElementById('confirmacion-numero');
            const total = document.getElementById('confirmacion-total');
            const metodo = document.getElementById('confirmacion-metodo');
            const entrega = document.getElementById('confirmacion-entrega');
            if (numero) numero.textContent = datos.codigo || '-';
            if (total) total.textContent = this.formatearPrecio(datos.total || 0);
            if (metodo) metodo.textContent = this.datos.metodo_pago === 'yape_plin' ? 'Yape / Plin' : (this.datos.metodo_pago || '-');
            if (entrega) {
                if (this.datos.tipo_entrega === 'delivery') {
                    entrega.textContent = 'Delivery';
                } else if (this.datos.tipo_entrega === 'comer_aqui') {
                    entrega.textContent = 'Comer aqui';
                } else {
                    entrega.textContent = 'Recojo';
                }
            }

            const btnWhatsapp = document.getElementById('btnAvisarWhatsappCheckout');
            const btnSeguimiento = document.getElementById('confirmacion-seguimiento');
            const btnVolver = document.getElementById('confirmacion-volver');
            const bloqueCuentaInvitado = document.getElementById('confirmacion-cuenta-invitado');
            const bloqueCuentaLogueado = document.getElementById('confirmacion-cuenta-logueado');
            const btnCrearCuenta = document.getElementById('confirmacion-crear-cuenta');
            const btnGoogleLogin = document.getElementById('confirmacion-google-login');
            const btnIrDashboard = document.getElementById('confirmacion-ir-dashboard');

            if (window.__checkoutConfirmRedirectTimer) {
                clearTimeout(window.__checkoutConfirmRedirectTimer);
                window.__checkoutConfirmRedirectTimer = null;
            }

            try {
                if (this.datos.cliente_nombre) localStorage.setItem('cliente_nombre', this.datos.cliente_nombre);
                if (this.datos.cliente_telefono) localStorage.setItem('cliente_telefono', this.datos.cliente_telefono);
            } catch (e) {
                // Ignorar errores de almacenamiento local.
            }

            if (datos.fidelizacion && typeof window.renderizarClubFidelidad === 'function') {
                window.renderizarClubFidelidad(datos.fidelizacion);
            } else if (this.datos.cliente_telefono && typeof window.cargarResumenFidelidad === 'function') {
                window.cargarResumenFidelidad(this.datos.cliente_telefono);
            }

            if (btnWhatsapp) {
                const whatsappUrl = datos.whatsapp_url || '#';
                btnWhatsapp.href = whatsappUrl;
                btnWhatsapp.classList.toggle('deshabilitado', !datos.whatsapp_url);
                btnWhatsapp.onclick = (e) => {
                    if (!datos.whatsapp_url) {
                        e.preventDefault();
                        return;
                    }
                    if (window.__checkoutConfirmRedirectTimer) {
                        clearTimeout(window.__checkoutConfirmRedirectTimer);
                        window.__checkoutConfirmRedirectTimer = null;
                    }
                };
            }

            if (btnVolver) {
                btnVolver.onclick = () => {
                    if (window.__checkoutConfirmRedirectTimer) {
                        clearTimeout(window.__checkoutConfirmRedirectTimer);
                        window.__checkoutConfirmRedirectTimer = null;
                    }

                    const clienteLogueado = !!(window.CLIENTE_WEB_ACTUAL && window.CLIENTE_WEB_ACTUAL.id);
                    if (clienteLogueado) {
                        window.location.href = 'cliente-dashboard.php';
                        return;
                    }

                    modal.style.display = 'none';
                    if (typeof window.limpiarCarritoCompleto === 'function') {
                        window.limpiarCarritoCompleto();
                    }
                    const overlayCarrito = document.getElementById('overlayCarrito');
                    if (overlayCarrito) {
                        overlayCarrito.classList.remove('visible');
                    }
                    if (typeof window.irHomeVisual === 'function') {
                        const navHome = document.getElementById('navHome');
                        window.irHomeVisual(navHome);
                    }
                };
            }

            if (btnSeguimiento) {
                const params = new URLSearchParams();
                if (this.datos.cliente_nombre) params.set('nombre', this.datos.cliente_nombre);
                if (this.datos.cliente_telefono) params.set('telefono', this.datos.cliente_telefono);
                btnSeguimiento.href = 'estado-pedido.php' + (params.toString() ? ('?' + params.toString()) : '');
            }

            const cuentasWebActivas = !!(window.APP_CONFIG && window.APP_CONFIG.clientesWebActivo);
            const clienteLogueado = !!(window.CLIENTE_WEB_ACTUAL && window.CLIENTE_WEB_ACTUAL.id);

            if (bloqueCuentaInvitado) bloqueCuentaInvitado.style.display = 'none';
            if (bloqueCuentaLogueado) bloqueCuentaLogueado.style.display = 'none';

            if (cuentasWebActivas) {
                if (clienteLogueado) {
                    if (bloqueCuentaLogueado) bloqueCuentaLogueado.style.display = 'block';
                    if (btnVolver) btnVolver.innerHTML = '<i class="ti ti-layout-dashboard"></i> Ir a mi dashboard';
                    if (btnIrDashboard) {
                        btnIrDashboard.href = 'cliente-dashboard.php';
                    }

                    window.__checkoutConfirmRedirectTimer = setTimeout(() => {
                        window.location.href = 'cliente-dashboard.php';
                    }, 5000);
                } else {
                    if (btnVolver) btnVolver.innerHTML = '<i class="ti ti-home"></i> Volver al Inicio';
                    const paramsLogin = new URLSearchParams();
                    paramsLogin.set('from', 'pedido');
                    if (datos.codigo) paramsLogin.set('codigo', String(datos.codigo));
                    if (this.datos.cliente_telefono) paramsLogin.set('telefono', this.datos.cliente_telefono);
                    if (this.datos.cliente_email) paramsLogin.set('email', this.datos.cliente_email);

                    const loginUrl = 'cliente-login.php?' + paramsLogin.toString();

                    if (bloqueCuentaInvitado) bloqueCuentaInvitado.style.display = 'block';
                    if (btnCrearCuenta) btnCrearCuenta.href = loginUrl;
                    if (btnGoogleLogin) btnGoogleLogin.href = loginUrl;
                }
            } else if (btnVolver) {
                btnVolver.innerHTML = '<i class="ti ti-home"></i> Volver al Inicio';
            }

            modal.style.display = 'flex';
        }

        const modalCheckout = document.getElementById('checkout-modal');
        if (modalCheckout) {
            modalCheckout.classList.remove('activo');
        }

        // Limpiar carrito
        this.limpiarCarrito();
        if (typeof window.actualizarBadgeCarrito === 'function') {
            window.actualizarBadgeCarrito();
        }
        if (typeof window.renderizarCarritoModal === 'function') {
            window.renderizarCarritoModal();
        }

        window.__checkoutApiperuFlowActivo = false;
        window.__checkoutApiperuCtx = null;
        this.procesandoPagoOnline = false;
    }

    esEmailValido(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    generarCodigoPedido() {
        return 'PED-' + Date.now();
    }

    formatearPrecio(precio) {
        return 'S/ ' + parseFloat(precio).toFixed(2);
    }

    calcularMonto() {
        const carrito = this.obtenerCarrito();
        let total = 0;
        carrito.forEach(item => {
            total += item.precio * item.cantidad;
        });
        if (this.datos.tipo_entrega === 'delivery') {
            total += Number((window.APP_CONFIG && window.APP_CONFIG.costoDelivery) || 0);
        }
        return total;
    }

    obtenerCarrito() {
        // Obtener del localStorage
        const carritoJSON = localStorage.getItem('carrito');
        return carritoJSON ? JSON.parse(carritoJSON) : [];
    }

    limpiarCarrito() {
        localStorage.removeItem('carrito');
    }
}

