# 🎯 Instalador Carta Digital v2.0

Bienvenido al instalador profesional de **Carta Digital**. Esta guía te mostrará cómo usar el instalador paso a paso.

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de que tu servidor cumple con:

- **PHP 8.0+** (recomendado: 8.1 o superior)
- **MySQL 5.7+ o MariaDB 10.3+**
- **Extensiones PHP requeridas:**
  - PDO + PDO MySQL
  - cURL
  - OpenSSL
  - GD (para procesamiento de imágenes)
  - Multibyte String (mbstring)
  - XML
  - JSON
  - ZIP

## 🚀 Cómo Usar el Instalador

### Paso 1: Acceder al Instalador

Abre tu navegador y ve a:

```
http://localhost/carta-digital/installer/
```

O si está en servidor remoto:

```
https://tu-dominio.com/carta-digital/installer/
```

### Paso 2: Verificación de Requisitos

El instalador verificará automáticamente todos los requisitos del servidor:

- ✅ Versión de PHP
- ✅ Extensiones PHP necesarias
- ✅ Soporte para MySQL
- ✅ Permisos de directorios
- ✅ Configuración del servidor web

**Señales:**
- 🟢 **Verde**: Requisito cumplido
- 🟡 **Amarillo**: Advertencia (funciona pero no es óptimo)
- 🔴 **Rojo**: Error crítico (debe solucionarse)

Si hay errores rojos, haz clic en **"Verificar de Nuevo"** después de resolver los problemas.

### Paso 3: Configuración de Base de Datos

Configure los parámetros de conexión a MySQL:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Servidor** | Host de MySQL | `localhost` o `127.0.0.1` |
| **Usuario** | Usuario de MySQL | `root` |
| **Contraseña** | Contraseña (opcional si no la hay) | `micontraseña123` |
| **Base de Datos** | Nombre de la BD (se creará si no existe) | `carta_digital` |
| **Puerto** | Puerto de MySQL | `3306` |

**Ejemplo XAMPP (por defecto):**
```
Servidor: localhost
Usuario: root
Contraseña: (dejar vacío)
Base de Datos: carta_digital
Puerto: 3306
```

**Ejemplo Hosting Compartido:**
```
Servidor: localhost (o tu host específico)
Usuario: (tu usuario de hosting)
Contraseña: (tu contraseña de hosting)
Base de Datos: (nombre dado por hosting)
Puerto: 3306
```

Luego haz clic en **"Probar Conexión"** para verificar que funciona.

### Paso 4: Instalación en Progreso

El instalador ejecutará automáticamente:

1. ✅ **Crear base de datos** - Crea la BD si no existe
2. ✅ **Importar esquema** - Carga la estructura de tablas
3. ✅ **Instalar dependencias** - Descarga librerías Composer
4. ✅ **Crear directorios** - Genera carpetas de uploads
5. ✅ **Configurar sistema** - Guarda la configuración

**Nota:** Este proceso puede tomar 2-5 minutos. ⏳ No cierres la ventana.

### Paso 5: ¡Finalizado!

Cuando veas la pantalla de éxito, tu sistema está listo. Te mostrará:

- 👤 **Usuario admin:** `admin`
- 🔐 **Contraseña:** `admin123`
- 🔗 **Enlaces rápidos** a la carta y panel admin

## 🔐 Primeros Pasos Después de la Instalación

1. **Cambia tu contraseña inmediatamente:**
   - Accede a `http://localhost/carta-digital/admin/`
   - Inicia sesión con: `admin / admin123`
   - Ve a **Configuración** → **Cambiar Contraseña**

2. **Configura tu negocio:**
   - Nombre y descripción
   - Logo (imagen del negocio)
   - Colores personalizados
   - Número de WhatsApp para recibir pedidos

3. **Añade productos y categorías:**
   - Crea categorías (Pizzas, Pastas, Bebidas, etc.)
   - Sube productos con imágenes

4. **Configura métodos de pago (opcional):**
   - Culqi para tarjetas
   - APIPERU para validación DNI/RUC
   - Otros métodos según necesites

5. **Prueba el sistema completamente:**
   - Accede a `http://localhost/carta-digital/`
   - Realiza un pedido de prueba
   - Verifica que recibas el mensaje por WhatsApp

## ⚠️ Solución de Problemas

### Error: "No se puede conectar a la base de datos"

1. Verifica que MySQL está corriendo
2. Confirma usuario/contraseña de MySQL
3. Intenta conectar desde línea de comandos:
   ```bash
   mysql -h localhost -u root -p
   ```

### Error: "Permisos insuficientes en directorios"

En **Linux/Mac:**
```bash
chmod -R 755 uploads/
```

En **XAMPP (Windows):** Ejecuta como administrador.

### Error: "Composer no encontrado"

El instalador puede continuar. Después, ejecuta manualmente:
```bash
composer install
```

### Las imágenes no aparecen después de instalar

Sigue estos pasos en Windows:

```powershell
# Eliminar symlink si existe
Remove-Item public/storage -Force -Recurse -ErrorAction Ignore

# Crear symlink nuevo
cmd /c mklink /D "c:\ruta\carta-digital\public\storage" "c:\ruta\carta-digital\storage\app\public"
```

En Linux/Mac:
```bash
php artisan storage:link
```

## 📞 Información Técnica

### Estructura de Base de Datos

Automáticamente se crean:
- Tablas de configuración
- Tablas de productos y categorías
- Tablas de pedidos
- Tablas de usuarios
- Usuario admin por defecto
- Datos de ejemplo

### Archivos de Configuración

- **includes/db.php** - Credenciales MySQL
- **.env** - Variables de entorno
- **admin/** - Panel de administración
- **api/** - APIs REST
- **uploads/** - Almacenamiento de imágenes

### Dependencias Composer

El sistema usa:
- `greenter/lite` - Facturación SUNAT
- `greenter/report` - Reportes de facturación
- `dompdf/dompdf` - Generación de PDF
- `phpmailer/phpmailer` - Envío de correos

## 🔒 Seguridad

### Cambiar contraseña admin

```sql
UPDATE usuarios SET
  password = SHA2('tuNuevaContraseña', 256),
  updated_at = NOW()
WHERE username = 'admin';
```

### Crear nuevo usuario admin

```sql
INSERT INTO usuarios (full_name, username, password, is_admin, status, created_at)
VALUES (
  'Nuevo Admin',
  'newadmin',
  SHA2('contraseña123', 256),
  1,
  'activo',
  NOW()
);
```

### Variables de entorno críticas

En `.env` configura después:
- `CULQI_PUBLIC_KEY` - Desde tu cuenta Culqi
- `CULQI_SECRET_KEY` - Desde tu cuenta Culqi
- `APIPERU_TOKEN` - Desde APIPERU Dev
- `WHATSAPP_NUMBER` - Tu número de WhatsApp

## 🆘 Soporte

Si encuentras problemas:

1. Revisa los logs en `includes/` o error log del servidor
2. Verifica que PHP 8.0+ está instalado
3. Comprueba que MySQL está corriendo
4. Intenta ejecutar el instalador de nuevo
5. Consulta la documentación de Carta Digital

---

**Versión:** 2.0.0  
**Última actualización:** 2024  
**Soporte:** [Documentación Oficial](https://github.com/tu-repo)
