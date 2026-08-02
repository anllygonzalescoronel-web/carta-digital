# 🚀 GUÍA RÁPIDA - INSTALADOR CARTA DIGITAL v2.0

## 📌 Pasos Principales

### 1️⃣ Acceso al Instalador
```
Abre tu navegador en:
http://localhost/carta-digital/installer/
```

### 2️⃣ Verificación Automática
El sistema verificará automáticamente:
- ✅ Versión PHP (requerida: 8.0+)
- ✅ Extensiones necesarias (PDO, cURL, GD, etc.)
- ✅ Permisos de directorios
- ✅ Configuración del servidor

**Si hay errores en rojo**, soluciónalos y haz click en **"Verificar de Nuevo"**

### 3️⃣ Configuración de Base de Datos

Ingresa tus datos de MySQL:

#### 📋 Para XAMPP (por defecto):
```
Servidor:      localhost
Usuario:       root
Contraseña:    (vacío)
Base de Datos: carta_digital
Puerto:        3306
```

#### 📋 Para Hosting Compartido (Ejemplo):
```
Servidor:      localhost o tu.servidor.com
Usuario:       usuario_hosting
Contraseña:    tu_contraseña
Base de Datos: nombrebd_digital
Puerto:        3306
```

**Después, haz click en "🧪 Probar Conexión"**

Si funciona → aparecerá un mensaje verde ✅

### 4️⃣ Instalación
El sistema ejecutará automáticamente:
1. Crear base de datos
2. Importar tablas y datos
3. Instalar dependencias (Composer)
4. Crear directorios necesarios
5. Configurar el sistema

⏳ **Espera 2-5 minutos. NO cierres la ventana.**

### 5️⃣ ¡Completado!
Verás la pantalla de éxito con:
- 👤 Usuario: **admin**
- 🔐 Contraseña: **admin123**
- 🔗 Enlaces a tu carta y panel admin

---

## 🔐 Próximos Pasos CRÍTICOS

### Cambiar Contraseña Admin (Inmediatamente)
1. Entra a: `http://localhost/carta-digital/admin/`
2. Usuario: `admin` / Contraseña: `admin123`
3. Ve a **Configuración** → **Cambiar Contraseña**
4. Ingresa una contraseña segura (mínimo 8 caracteres)

### Configurar tu Negocio
1. Panel Admin → **Configuración**
2. Completa:
   - Nombre del negocio
   - Dirección
   - Teléfono/WhatsApp (✨ IMPORTANTE para recibir pedidos)
   - Logo
   - Colores personalizados

### Crear Productos
1. Panel Admin → **Productos**
2. Haz click en **"+ Agregar Producto"**
3. Llena los datos y sube imagen
4. Repite para cada producto

### Crear Categorías
1. Panel Admin → **Categorías**
2. Haz click en **"+ Agregar Categoría"**
3. Asigna productos a cada categoría

---

## ⚠️ ERRORES COMUNES Y SOLUCIONES

### ❌ "Extensión PDO no encontrada"
**Solución XAMPP:**
1. Abre: `C:\xampp\php\php.ini`
2. Busca: `;extension=pdo_mysql`
3. Quita el `;` → `extension=pdo_mysql`
4. Guarda y reinicia Apache

**Solución Linux:**
```bash
sudo apt-get install php8.1-mysql
sudo systemctl restart apache2
```

### ❌ "No se puede conectar a la BD"
1. Verifica que MySQL está corriendo
2. Comprueba usuario/contraseña
3. Intenta en terminal:
   ```bash
   mysql -h localhost -u root -p
   ```

### ❌ "Permisos insuficientes en uploads"
**Windows (XAMPP):**
- Clic derecho en carpeta `uploads` → Propiedades
- Seguridad → Editar → Permisos
- Permitir Control Total para EVERYONE

**Linux/Mac:**
```bash
chmod -R 755 uploads/
chmod -R 755 storage/ (si existe)
```

### ❌ "Base de datos ya existe"
Es normal. El instalador continuará sin problema.

---

## 🔧 HERRAMIENTA DE DIAGNÓSTICO

Si algo no funciona, usa:
```
http://localhost/carta-digital/installer/diagnostico.php
```

Esto te mostrará:
- ✅ Estado de PHP y extensiones
- ✅ Conexión a base de datos
- ✅ Permisos de directorios
- ✅ Soluciones automáticas

---

## 🌐 DESPUÉS DE LA INSTALACIÓN

### Para Ver la Carta Digital:
```
http://localhost/carta-digital/
```

### Para Administrar:
```
http://localhost/carta-digital/admin/
```

### Para Hacer Cambios Rápidos:
```
http://localhost/carta-digital/installer/diagnostico.php
```

---

## 🚀 PRÓXIMAS CONFIGURACIONES (Opcionales)

### Pagos con Tarjeta (Culqi)
1. Crea cuenta en: https://culqi.com
2. Obtén llaves de prueba
3. Panel Admin → Configuración → Culqi
4. Pega tus llaves

### Validación de DNI/RUC (APIPERU)
1. Regístrate en: https://apiperu.dev/
2. Obtén token gratuito
3. Panel Admin → Configuración → APIPERU
4. Pega tu token

### Facturación Electrónica
1. Obtén certificado digital SUNAT
2. Panel Admin → Configuración → SUNAT
3. Sube certificado y contraseña

---

## 📞 INFORMACIÓN IMPORTANTE

- **Versión PHP Mínima:** 8.0
- **Versión MySQL:** 5.7+ (MariaDB 10.3+)
- **Navegador:** Chrome, Firefox, Safari, Edge (últimas versiones)
- **Almacenamiento:** ~50MB para instalación completa
- **Memoria Requerida:** Mínimo 128MB

---

## ✨ CONSEJOS

1. **Respalda base de datos regularmente** antes de actualizar
2. **Cambia contraseña admin regularmente**
3. **Descarga backups del stock de productos**
4. **Usa certificado SSL en producción** (HTTPS)
5. **Mantén PHP y MySQL actualizados**

---

**¿Problemas?** Revisa el diagnóstico en:
```
http://tu-dominio.com/carta-digital/installer/diagnostico.php
```

**Soporte:** Consulta la documentación oficial o contacta al equipo técnico.

Versión: 2.0.0 | Última actualización: 2024
