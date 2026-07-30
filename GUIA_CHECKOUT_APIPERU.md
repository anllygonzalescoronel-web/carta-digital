# 🎯 Guía Completa: Checkout Multi-Paso con APIPERU

**Última actualización:** 2025  
**Estado:** ✅ 100% operativo  
**Autor:** Sistema de Facturación Híbrida

---

## 📋 Tabla de Contenidos

1. [¿Qué es?](#qué-es)
2. [Características](#características)
3. [Configuración](#configuración)
4. [Flujo del Checkout](#flujo-del-checkout)
5. [Archivos Creados](#archivos-creados)
6. [Solución de Problemas](#solución-de-problemas)
7. [Roadmap](#roadmap)

---

## ❓ ¿Qué es?

El **Checkout Multi-Paso con APIPERU** es un sistema de 4 pasos que permite a los clientes:

1. **Seleccionar tipo de comprobante** (Boleta o Factura)
2. **Ingresar DNI/RUC** → Validación automática via APIPERU (llena nombre + datos)
3. **Confirmar datos de entrega** (nombre, email, teléfono, dirección)
4. **Seleccionar método de pago** (Efectivo, Tarjeta con Culqi, Yape, PLIN)

### 🎁 Integración Completa
- ✅ Sistema de facturación híbrido (SUNAT Native + NubeFacT)
- ✅ APIPERU para validación de documentos
- ✅ Culqi para pagos con tarjeta/Yape/PLIN
- ✅ WhatsApp para notificaciones de pedidos
- ✅ Modal interactivo 100% responsive

---

## ⭐ Características

### Cliente (Frontend)
- **Modal interactivo** con 4 pasos claramente diferenciados
- **Indicador de progreso** visual (pasos completados/actuales)
- **Validación automática RENIEC/RUC** llenando nombre completo
- **Mensajes de error** contextuales en español
- **Cargando overlay** durante consultas/procesamiento
- **Confirmación de pedido** con detalles resumidos

### Sistema (Backend)
- **APIPERU Integration** (`includes/apiperu.php`)
- **AJAX Endpoint** (`api/consultar_documento.php`)
- **Hybrid Invoicing** (SUNAT Native + NubeFacT)
- **Multi-step State Management** (localStorage para carrito)
- **Configuration UI** en admin (`admin/configuracion.php`)

### Seguridad & Performance
- Validación de formato DNI/RUC en cliente ANTES de llamada API
- Rate limiting: máx 10 consultas/minuto por sesión
- Tokens de Culqi NO se guardan (procesamiento seguro en cliente)
- APIPERU token almacenado seguro en base de datos
- HTTPS recomendado en producción

---

## ⚙️ Configuración

### Paso 1: Obtener Token APIPERU

1. Ve a **[https://apiperu.dev/](https://apiperu.dev/)**
2. Crea una cuenta (registro gratuito)
3. Inicia sesión en tu panel
4. Copia tu **API Token** (encontrado en tu perfil/cuenta)
5. Copia el token completo (ej: `qwerty123456789abcdef...`)

### Paso 2: Configurar en el Admin

1. Accede a **Admin → Configuración**
2. Baja hasta la sección **"APIPERU - Validación RENIEC/RUC"**
3. Pega el token en el campo **"Token APIPERU"**
4. Marca la casilla **"Habilitar validación automática en checkout"**
5. Haz clic en **"Guardar configuración"**

### Paso 3: Verificar Instalación

```bash
# En la carpeta raíz del proyecto:
php setup_apiperu.php

# Output esperado:
# ✅ Agregada configuración: apiperu_token
# ✅ Agregada configuración: apiperu_habilitado
# ✅ Configuración APIPERU agregada
```

✅ **¡Listo!** El sistema está operativo.

---

## 🔄 Flujo del Checkout

### 1️⃣ Paso 1: Seleccionar Comprobante

```
┌─────────────────────────────────────┐
│     Tipo de Comprobante             │
├─────────────────────────────────────┤
│                                     │
│  [🧾 Boleta]    [📋 Factura]       │
│   (Con DNI)     (Con RUC)          │
│                                     │
│              [Siguiente →]          │
└─────────────────────────────────────┘
```

**Datos guardados:**
- `tipo_comprobante`: "boleta" o "factura"
- `tipo_documento`: "dni" o "ruc" (automático)

### 2️⃣ Paso 2: Consultar Documento

```
┌─────────────────────────────────────┐
│     Consultar Documento             │
├─────────────────────────────────────┤
│                                     │
│ Número de DNI/RUC:                 │
│ [________] [Consultar]             │
│                                     │
│ ✓ Datos Consultados                │
│ ┌─────────────────────────────┐    │
│ │ Nombre: Juan García López   │    │
│ │ DNI: 12345678              │    │
│ │ Estado: Vigente            │    │
│ └─────────────────────────────┘    │
│                                     │
│ [← Volver]      [Siguiente →]      │
└─────────────────────────────────────┘
```

**Proceso:**
1. Cliente ingresa DNI/RUC
2. Click en "Consultar" → Llamada a `/api/consultar_documento.php`
3. APIPERU retorna datos (nombre, estado, detalles)
4. Campos se auto-llenan
5. Cliente verifica y continúa

**Datos guardados:**
- `numero_documento`: DNI/RUC ingresado
- `cliente_nombre`: Nombre auto-llenado desde APIPERU

### 3️⃣ Paso 3: Datos de Entrega

```
┌─────────────────────────────────────┐
│       Datos de Entrega              │
├─────────────────────────────────────┤
│                                     │
│ Nombre: [Juan García López]        │
│ Email: [juan@gmail.com]            │
│ Teléfono: [987654321]              │
│                                     │
│ Tipo de Entrega:                   │
│ (🏪 Recojo)  (📦 Delivery)        │
│                                     │
│ [Si Delivery...]                   │
│ Dirección: [_______________]       │
│ Referencia: [_______________]      │
│                                     │
│ [← Volver]      [Siguiente →]      │
└─────────────────────────────────────┘
```

**Validaciones:**
- Nombre: mín 2 caracteres
- Email: formato válido (contiene @)
- Teléfono: mín 6 dígitos
- Si Delivery: dirección mín 5 caracteres

**Datos guardados:**
- `cliente_nombre`, `cliente_email`, `cliente_telefono`
- `tipo_entrega`: "recojo" o "delivery"
- `direccion`, `referencia` (si delivery)

### 4️⃣ Paso 4: Método de Pago

```
┌─────────────────────────────────────┐
│      Método de Pago                 │
├─────────────────────────────────────┤
│                                     │
│  [💵 Efectivo] [💳 Tarjeta]       │
│  [📱 Yape]    [📲 PLIN]           │
│                                     │
│  [Si Tarjeta/Yape/PLIN...]        │
│  [Formulario Culqi]                │
│                                     │
│ [← Volver]  [Confirmar Pedido]    │
└─────────────────────────────────────┘
```

**Flujos:**
- **Efectivo:** Procesamiento inmediato
- **Tarjeta/Yape/PLIN:** Abre formulario Culqi, obtiene token, luego procesa

**Datos guardados:**
- `metodo_pago`: "efectivo", "tarjeta", "yape", "plin"
- `culqi_token` (solo si no es efectivo)

### ✅ Confirmación

```
┌─────────────────────────────────────┐
│     ¡Pedido Confirmado!             │
├─────────────────────────────────────┤
│              ✅                      │
│                                     │
│ Número de Pedido: PED-1701234567   │
│ Total: S/ 45.50                    │
│ Método: Tarjeta                    │
│ Entrega: Delivery                  │
│                                     │
│       [Volver al Inicio]           │
└─────────────────────────────────────┘
```

Carrito se vacía automáticamente. Pedido enviado a WhatsApp + facturación.

---

## 📁 Archivos Creados

### Backend

#### `includes/apiperu.php` (NEW)
```php
function consultarDNIReniec(string $dni): array
  ├─ Valida formato
  ├─ Llama API APIPERU
  └─ Retorna: nombreCompleto, estado, fechaNacimiento, etc.

function consultarRUCSunat(string $ruc): array
  ├─ Valida formato
  ├─ Llama API APIPERU
  └─ Retorna: razonSocial, nombreComercial, estado, direccion, etc.

class APIPeruException extends Exception
  └─ Manejo de errores APIPERU
```

#### `api/consultar_documento.php` (NEW)
```php
GET /api/consultar_documento.php?tipo=dni&numero=12345678
  └─ Retorna JSON: { ok: bool, datos: {...} || mensaje: string }

GET /api/consultar_documento.php?tipo=ruc&numero=20123456789
  └─ Retorna JSON: { ok: bool, datos: {...} || mensaje: string }

Incluye:
  - Rate limiting (máx 10/minuto)
  - Error handling
  - CORS headers
```

#### `admin/configuracion.php` (UPDATED)
```php
Nuevos campos:
  - apiperu_token: Storage seguro del token
  - apiperu_habilitado: Habilitar/deshabilitar validación

Nueva sección HTML:
  - Panel APIPERU con instrucciones
  - Status display (configurado/no configurado)
  - Link a https://apiperu.dev/
```

#### `setup_apiperu.php` (NEW - Inicialización)
```bash
php setup_apiperu.php

Agrega a tabla `configuracion`:
  ✓ apiperu_token = ''
  ✓ apiperu_habilitado = '1'
```

### Frontend

#### `assets/css/checkout-apiperu.css` (NEW)
```css
Estilos para:
  - Modal principal (#checkout-modal)
  - Indicador de pasos (#pasos-indicador)
  - Transiciones entre pasos
  - Validación visual (inputs, botones)
  - Responsive design (mobile-first)
  - Overlay de cargando
  - Modal de confirmación

Colores:
  - Usa variables CSS (--color-primario, --color-secundario)
  - Paleta oscura para modal
  - Contraste accesible
```

#### `template frontend/checkout-modal.html` (NEW)
```html
Estructura completa:
  - Header con close button
  - Indicador de pasos (4 pasos)
  - Sección PASO 1: Seleccionar comprobante (2 opciones)
  - Sección PASO 2: Consultar documento (DNI/RUC inputs)
  - Sección PASO 3: Datos de entrega (formulario completo)
  - Sección PASO 4: Método de pago (4 opciones)
  - Overlays: cargando, confirmación
  - Área de mensajes de error
```

#### `assets/js/checkout-apiperu.js` (NEW - 400+ líneas)
```javascript
class CheckoutAPIPeru
  ├─ Propiedades: paso, datos (estado del checkout)
  ├─ init(): Inicialización
  ├─ mostrarModal() / cerrarModal(): Control del modal
  ├─ setupEventListeners(): Attachment de listeners
  │
  ├─ PASO 1: seleccionarComprobante(tipo)
  ├─ PASO 2: consultarDocumento() → Llamada APIPERU
  ├─ PASO 3: validarDatosEntrega() → Guarda datos
  ├─ PASO 4: seleccionarMetodoPago(metodo)
  │          → Si tarjeta/yape/plin: procesarConCulqi()
  │          → Si efectivo: procesarPedido() directo
  │
  ├─ Utilidades:
  │  ├─ mostrarError(msg): Mostrar alerta
  │  ├─ mostrarCargando(show, msg): Overlay
  │  ├─ mostrarConfirmacion(datos): Modal de éxito
  │  ├─ calcularMonto(): Total + delivery
  │  ├─ obtenerCarrito(): Lee localStorage
  │  ├─ limpiarCarrito(): Vacía localStorage
  │  └─ Validadores: esEmailValido(), generarCodigoPedido()
```

#### `assets/js/carrito.js` (UPDATED)
```javascript
Agregadas funciones:
  - irACheckoutAPIPeru(): Abre nuevo modal
  - irACheckout(): Redirige a irACheckoutAPIPeru() (compatibilidad)

Cambios:
  - Botón "Continuar" del carrito ahora abre el nuevo checkout
  - Estado del carrito se mantiene en localStorage
  - Carrito se pasa al checkout via `obtenerCarrito()`
```

### HTML Principal

#### `index.php` (UPDATED)
```html
Cambios:
  ✓ Link: assets/css/checkout-apiperu.css (en <head>)
  ✓ Include: template frontend/checkout-modal.html (antes </body>)
  ✓ Script: assets/js/checkout-apiperu.js (antes carrito.js)
  ✓ Init: window.checkout = new CheckoutAPIPeru()

Orden de carga (importante):
  1. Culqi script (https://checkout.culqi.com/js/v4)
  2. CSS checkout
  3. Modal HTML
  4. JS Checkout
  5. JS Carrito (maneja el botón "Ir al Checkout")
```

---

## 📊 Esquema de Datos

### localStorage (Cliente)
```javascript
// Carrito persistido
localStorage.carrito = JSON.stringify([
  { id: 1, nombre: "Lomo Saltado", precio: 25.00, cantidad: 2 },
  { id: 3, nombre: "Arroz con Leche", precio: 8.00, cantidad: 1 }
])
```

### Base de datos (Servidor)
```sql
-- Nueva tabla (o actualizada)
TABLE configuracion:
  [id] [clave]                      [valor]
  ...
  [N]  'apiperu_token'              'qwerty123456...'
  [N+1] 'apiperu_habilitado'        '1'
  ...

-- Tabla de pedidos (ya existe, se usa)
TABLE pedidos:
  [id] [codigo] [cliente_nombre] [cliente_email] [cliente_telefono]
  [tipo_comprobante] [tipo_documento] [numero_documento]
  [tipo_entrega] [direccion] [referencia]
  [metodo_pago] [culqi_token] [total] [estado] [created_at]
  ...
```

### API APIPERU (Retornos Ejemplo)

#### DNI (RENIEC)
```json
{
  "ok": true,
  "datos": {
    "nombreCompleto": "JUAN GARCÍA LÓPEZ",
    "estado": "Vigente",
    "fechaNacimiento": "1990-05-15",
    "sexo": "M",
    "ubigeo": "150131"
  }
}
```

#### RUC (SUNAT)
```json
{
  "ok": true,
  "datos": {
    "razonSocial": "EMPRESA SAC",
    "nombreComercial": "Mi Restaurante",
    "estado": "Activo",
    "direccion": "Av. Principal 123, Piso 2",
    "telefonos": ["987654321"],
    "correos": ["admin@empresa.com"],
    "tipoContribuyente": "Persona Jurídica"
  }
}
```

---

## 🛠️ Solución de Problemas

### ❌ "El modal no aparece"

**Causa:** Script no inicializado
```javascript
// Verificar en browser console:
console.log(window.checkout); // Debe ser objeto CheckoutAPIPeru

// Si es undefined, revisar:
// 1. ¿Se cargó assets/js/checkout-apiperu.js?
// 2. ¿Está el HTML del modal en el DOM?
// 3. ¿index.html incluye el script de inicialización?
```

### ❌ "La consulta APIPERU falla (error 401)"

**Causa:** Token inválido o no configurado
```bash
# Verificar token en DB:
SELECT * FROM configuracion WHERE clave = 'apiperu_token';

# Obtener nuevo token:
1. https://apiperu.dev/ → Cuenta → API
2. Copiar token exacto (sin espacios)
3. Admin → Configuración → Pegar token
4. Guardar
```

### ❌ "El nombre no se auto-llena después de consultar DNI"

**Causa:** ID de input incorrecto en HTML
```javascript
// El JS busca:
// document.getElementById(`documento-input-${this.datos.tipo_documento}`)
// Debe ser: #documento-input-dni o #documento-input-ruc

// Y busca donde poner el nombre:
// document.getElementById('cliente-nombre')

// Verificar HTML:
// <input id="documento-input-dni" ... />
// <input id="cliente-nombre" ... />
```

### ❌ "Culqi no procesa el pago"

**Causa:** Key pública incorrecta o no cargada
```javascript
// Verificar en console:
console.log(window.APP_CONFIG.culqiPublicKey); // Debe tener valor
console.log(Culqi); // Debe estar definido

// Admin → Configuración → Llave pública de Culqi
// Debe ser pk_test_... o pk_live_... (según ambiente)
```

### ✅ "Quiero ver logs de APIPERU"

```php
// En includes/apiperu.php, habilitar debug:
// (ya incluye error_log en líneas específicas)

// Ver logs en:
// subl /laragon/logs/apache_error.log
// subl /laragon/logs/apache_access.log

// O agregar al código:
error_log('APIPERU Request: ' . json_encode($payload));
error_log('APIPERU Response: ' . $response);
```

---

## 🚀 Roadmap

### ✅ Completado
- Checkout modal 4 pasos
- APIPERU DNI/RUC integration
- Culqi payment processing
- Facturación híbrida (SUNAT + NubeFacT)
- Admin configuration panel
- Responsive design (mobile-first)
- Error handling & user messages

### 🔜 En Progreso / Consideraciones
- [ ] Webhook APIPERU para notificaciones
- [ ] Rate limiting dashboard en admin
- [ ] Historial de consultas APIPERU
- [ ] Multi-idioma (ES/EN)
- [ ] PWA offline support
- [ ] Análisis de conversión checkout (Google Analytics)
- [ ] A/B testing de pasos
- [ ] Social login (Google, Facebook)

### 💭 Futuro
- Integración con más proveedores de facturación
- Sistema de descuentos por métodos de pago
- Programa de puntos/rewards
- Integración con delivery externo (Glovo, Rappi)
- Carrito compartible por link (WhatsApp)

---

## 📞 Soporte

### Para dudas sobre APIPERU
- Documentación: https://docs.apiperu.dev/
- Panel de cuenta: https://apiperu.dev/account
- Email soporte: support@apiperu.dev

### Para dudas sobre Culqi
- Documentación: https://culqi.com/docs/
- Sandbox Keys: pk_test_*/sk_test_*
- Dashboard: https://dashboard.culqi.com

### Para dudas sobre Facturación
- SUNAT: https://www.sunat.gob.pe/
- NubeFacT: https://www.nubefact.com/

---

## 📝 Notas de Desarrollo

### Convenciones de Código
- **PHP:** PSR-2 style (4-space indent)
- **JavaScript:** ES6+, class-based
- **CSS:** BEM con variables CSS (responsivo)
- **HTML:** Semantic, accessible (WCAG 2.1 AA)

### Testing
```bash
# Test unitario de APIPERU
php admin/test_facturacion_hibrida.php

# Resultado esperado: 43/43 tests passing ✅
```

### Deploy a Producción
```bash
# 1. Cambiar modo SUNAT a 'prod':
# Admin → Configuración → Modo SUNAT = "Producción"

# 2. Cambiar Culqi a keys de producción:
# Admin → Configuración → Culqi pk_live_* / sk_live_*

# 3. Validar APIPERU token:
# Admin → Configuración → APIPERU Token (verificar conexión)

# 4. Habilitar HTTPS (recomendado)
# Actualizar URL en Admin si aplica

# 5. Test de flujo completo:
#    Completar un pedido de prueba end-to-end
```

---

## 📄 Archivo de Resumen

Archivo generado: `RESUMEN_CHECKOUT_APIPERU.txt`

Contiene:
- Ubicación de archivos modificados
- Cambios line-by-line importantes
- Configuración inicial rápida
- FAQ técnicas

---

**¡Gracias por usar nuestro sistema de Checkout APIPERU!** 🎉

¿Preguntas? Revisa esta guía o consulta los comentarios en el código.

---

*Última revisión: 2025*  
*Versión: 1.0.0*  
*Estado: Production-Ready ✅*
