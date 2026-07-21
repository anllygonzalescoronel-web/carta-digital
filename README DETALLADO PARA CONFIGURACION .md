#  Carta Digital — MVP funcional

Carta digital para pollería/restaurante, optimizada para móviles, con panel de
administración y pedidos que se envían por WhatsApp. Incluye pago **efectivo**,
**Yape** vía Culqi, **Plin** manual con QR y **tarjeta con Culqi** (cobro real
online).

---

## 1. Requisitos

- XAMPP (Apache + PHP 8.x + MySQL/MariaDB) — https://www.apachefriends.org/
- Una cuenta en [Culqi](https://culqi.com) (puedes usar el modo de pruebas gratis)

No necesitas Composer ni Node.js para esta primera versión: todo funciona con
PHP puro + cURL para hablar con la API de Culqi.

---

## 2. Instalación

1. Copia la carpeta `carta-digital` dentro de `htdocs` de tu XAMPP:
   ```
   C:\xampp\htdocs\carta-digital        (Windows)
   /Applications/XAMPP/htdocs/carta-digital   (Mac)
   ```
2. Abre **phpMyAdmin** (o la consola de MySQL) y crea la base de datos importando
   el archivo `sql/schema.sql`. Esto crea la base `carta_digital`, sus tablas,
   un usuario administrador y datos de ejemplo (categorías, productos, config).

   Con phpMyAdmin: pestaña **Importar** → selecciona `sql/schema.sql` → Ejecutar.

   Por consola:
   ```bash
   mysql -u root --default-character-set=utf8mb4 < sql/schema.sql
   ```
   > Importante: usa `--default-character-set=utf8mb4` (o el equivalente en tu
   > cliente) para que las tildes (á, é, í...) no se guarden mal.

3. Revisa `includes/db.php` y ajusta usuario/clave de MySQL si no usas el root
   sin contraseña por defecto de XAMPP.

4. Da permisos de escritura a la carpeta `uploads/` (y sus subcarpetas
   `uploads/productos` y `uploads/banners`) para que el admin pueda subir imágenes.

5. Abre `http://localhost/carta-digital/` → deberías ver la carta pública
   funcionando con los productos de ejemplo.

6. Entra al panel admin en `http://localhost/carta-digital/admin/`
   - Usuario: `admin`
   - Clave: `admin123`
   - **Cámbiala apenas puedas** (ver sección 5).

---

## 3. Configura tu negocio

Dentro del panel admin → **Configuración**:

- Nombre, dirección y logo del negocio.
- Colores de la carta (se aplican al instante en la carta pública).
- **Número de WhatsApp** donde llegarán los pedidos (formato: código de país +
  número, sin espacios ni `+`. Ej: `51987654321`).
- Activar/desactivar Recojo y Delivery, y el costo del delivery.
- Métodos de pago: Efectivo, Yape vía Culqi, y Tarjeta (Culqi).

Luego en **Categorías**, **Productos** y **Banners** puedes crear todo el
contenido de tu carta con imágenes.

---

## 4. Configurar Culqi (pagos con tarjeta)

1. Crea una cuenta en https://culqi.com y entra a tu panel.
2. Ve a **Llaves API** y copia tus llaves de **pruebas**:
   - `pk_test_...` (pública)
   - `sk_test_...` (secreta)
3. Pégalas en el panel admin → Configuración → sección "Tarjeta (Culqi)".
4. Prueba un pedido con tarjeta usando una [tarjeta de pruebas de Culqi](https://docs.culqi.com/es/documentacion/pruebas/tarjetas-de-prueba/)
   (por ejemplo `4111 1111 1111 1111`, CVV `123`, cualquier fecha futura).
5. Cuando tu cuenta esté aprobada por Culqi, reemplaza las llaves por las de
   **producción** (`pk_live_...` / `sk_live_...`) y ¡ya cobras de verdad!

### Sobre el repo `culqi-php` que ya tienes clonado

Esta primera versión llama directamente a la API REST de Culqi (`includes/culqi.php`)
usando cURL, sin depender de Composer, para que puedas probar todo de inmediato
en XAMPP. Si prefieres usar el SDK oficial `culqi/culqi-php`, dentro de
`includes/culqi.php` dejé comentado exactamente cómo reemplazar la función
`crearCargoCulqi()` para usar el SDK en vez de la llamada directa. Es un cambio
de unas pocas líneas cuando quieras dar ese salto.

---

## 5. Cambiar la contraseña del admin

Por seguridad, cambia la contraseña del usuario `admin` cuanto antes. La forma
más simple: genera un nuevo hash con PHP y actualízalo en la tabla `admin_usuarios`.

```php
<?php echo password_hash('TU_NUEVA_CLAVE', PASSWORD_BCRYPT);
```//
Ejecuta ese script (por ejemplo desde la consola: `php -r "echo password_hash('TU_NUEVA_CLAVE', PASSWORD_BCRYPT);"`)
y copia el resultado en:

```sql
UPDATE admin_usuarios SET password_hash = 'EL_HASH_GENERADO' WHERE usuario = 'admin';
```

---

## 6. Cómo funciona el flujo de pedido

1. El cliente arma su carrito (persistido en `localStorage` del navegador).
2. Completa nombre, teléfono, tipo de entrega (recojo/delivery) y método de pago.
3. **Efectivo**: se guarda el pedido como `pendiente` y se abre WhatsApp con el
   detalle listo para enviar.
4. **Yape**: se abre el checkout de Culqi, se crea el token del pago en el
   navegador, tu backend cobra con ese token vía la API de Culqi, y si el cobro
   es exitoso el pedido se guarda como `pagado` y también se abre WhatsApp con
   el detalle.
5. **Tarjeta**: se abre el Checkout de Culqi, se tokeniza la tarjeta en el
   navegador (los datos de tarjeta nunca tocan tu servidor), tu backend cobra
   con ese token vía la API de Culqi, y si el cobro es exitoso el pedido se
   guarda como `pagado` y también se abre WhatsApp con el detalle (marcado
   como pagado).
6. Todos los precios se **recalculan en el servidor** contra la base de datos
   antes de guardar el pedido o cobrar, para que nadie pueda manipular precios
   desde el navegador.
7. En el panel admin → **Pedidos** puedes ver el detalle completo y cambiar el
   estado (pendiente → pagado → en preparación → en camino → entregado).

---

## 7. Estructura del proyecto

```
carta-digital/
├── index.php                 # Carta pública (banners, categorías, productos, carrito)
├── api/
│   └── pedido.php            # Crea el pedido, valida, cobra con Culqi, genera link WhatsApp
├── includes/
│   ├── db.php                # Conexión PDO a MySQL
│   ├── functions.php         # Helpers (config, precios, subir imágenes...)
│   ├── auth.php               # Sesión / login del admin
│   └── culqi.php             # Integración con la API de Culqi
├── admin/
│   ├── login.php / logout.php
│   ├── index.php             # Dashboard
│   ├── categorias.php        # CRUD categorías
│   ├── productos.php         # CRUD productos (con imagen)
│   ├── banners.php           # CRUD banners deslizantes
│   ├── pedidos.php           # Listado + detalle + cambio de estado
│   └── configuracion.php     # Colores, negocio, WhatsApp, pagos, Culqi
├── assets/
│   ├── css/style.css         # Estilos públicos (mobile-first)
│   └── js/carrito.js         # Carrito, checkout, Culqi Checkout, envío a WhatsApp
├── uploads/                  # Imágenes subidas (productos, banners, logo, QR)
└── sql/schema.sql            # Esquema + datos de ejemplo
```

---

## 8. Próximos pasos sugeridos (cuando quieras ampliar)

- WhatsApp Business API real (en vez de abrir `wa.me`) para automatizar
  confirmaciones y notificaciones de estado.
- Verificación automática de pagos Yape con Culqi (Plin puede mantenerse como
   flujo manual con QR si lo necesitas).
- Multi-sucursal, cupones de descuento, programa de puntos.
- Notificaciones push o por email cuando cambia el estado del pedido.

¡Éxitos con tu pollería! 🍗🔥
