#!/bin/bash
# CARTA DIGITAL - INSTALADOR CLI
# Para Linux/Mac

set -e

echo "╔═══════════════════════════════════════════╗"
echo "║   🍕 CARTA DIGITAL - INSTALADOR v2.0     ║"
echo "║        Sistema de Instalación             ║"
echo "╚═══════════════════════════════════════════╝"
echo ""

# Detectar directorio actual
INSTALLER_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
ROOT_DIR="$(dirname "$INSTALLER_DIR")"

echo "📍 Directorio raíz: $ROOT_DIR"
echo ""

# Verificaciones iniciales
echo "🔍 Verificando requisitos básicos..."

# Verificar PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP no encontrado. Por favor instala PHP 8.0+"
    exit 1
fi

PHP_VERSION=$(php -v | grep -oP 'PHP \K[0-9.]+')
echo "✅ PHP $PHP_VERSION encontrado"

# Verificar extensiones
echo ""
echo "🔍 Verificando extensiones PHP..."

EXTENSIONS=("pdo" "pdo_mysql" "curl" "openssl" "gd" "mbstring" "xml" "json")

for ext in "${EXTENSIONS[@]}"
do
    if php -m | grep -q $ext; then
        echo "  ✅ $ext"
    else
        echo "  ❌ Falta extensión: $ext"
    fi
done

echo ""
echo "🗄️  Verificando base de datos..."

# Solicitar credenciales
read -p "Host MySQL [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "Usuario MySQL [root]: " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Contraseña MySQL (enter si no la hay): " DB_PASS
echo ""

read -p "Nombre BD [carta_digital]: " DB_NAME
DB_NAME=${DB_NAME:-carta_digital}

read -p "Puerto MySQL [3306]: " DB_PORT
DB_PORT=${DB_PORT:-3306}

# Probar conexión
echo ""
echo "🧪 Probando conexión a MySQL..."

if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" &> /dev/null; then
    echo "✅ Conexión exitosa"
else
    echo "❌ No se pudo conectar a MySQL"
    exit 1
fi

# Crear base de datos
echo ""
echo "📦 Creando base de datos..."

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" <<EOF
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF

echo "✅ Base de datos lista"

# Importar schema
echo ""
echo "📥 Importando esquema..."

SCHEMA_FILE="$ROOT_DIR/sql/schema.sql"
if [ ! -f "$SCHEMA_FILE" ]; then
    echo "❌ Archivo sql/schema.sql no encontrado"
    exit 1
fi

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SCHEMA_FILE"
echo "✅ Esquema importado"

# Crear directorios
echo ""
echo "📂 Creando directorios..."

DIRS=(
    "uploads"
    "uploads/productos"
    "uploads/banners"
    "uploads/categorias"
    "uploads/sunat"
    "uploads/sunat/cdr"
    "uploads/sunat/certificados"
    "uploads/sunat/pdf"
    "uploads/sunat/xml"
    "uploads/iconos"
)

for dir in "${DIRS[@]}"
do
    mkdir -p "$ROOT_DIR/$dir"
    chmod 755 "$ROOT_DIR/$dir"
    echo "  ✅ $dir"
done

# Configurar archivo db.php
echo ""
echo "⚙️  Configurando db.php..."

DB_CONFIG_FILE="$ROOT_DIR/includes/db.php"

if [ -f "$DB_CONFIG_FILE" ]; then
    # Realizar copias de seguridad
    cp "$DB_CONFIG_FILE" "$DB_CONFIG_FILE.backup.$(date +%Y%m%d_%H%M%S)"
    
    # Reemplazar valores (de forma segura)
    sed -i "s|define('DB_HOST', '[^']*')|define('DB_HOST', '$DB_HOST')|g" "$DB_CONFIG_FILE"
    sed -i "s|define('DB_NAME', '[^']*')|define('DB_NAME', '$DB_NAME')|g" "$DB_CONFIG_FILE"
    sed -i "s|define('DB_USER', '[^']*')|define('DB_USER', '$DB_USER')|g" "$DB_CONFIG_FILE"
    sed -i "s|define('DB_PASS', '[^']*')|define('DB_PASS', '$DB_PASS')|g" "$DB_CONFIG_FILE"
    
    echo "✅ Configuración guardada"
fi

# Instalar dependencias Composer
echo ""
echo "📚 Instalando dependencias Composer..."

if command -v composer &> /dev/null; then
    cd "$ROOT_DIR"
    composer install --no-dev -q
    echo "✅ Dependencias instaladas"
else
    echo "⚠️  Composer no encontrado. Ejecuta manualmente:"
    echo "   cd $ROOT_DIR && composer install"
fi

# Crear archivo .env
echo ""
echo "🔧 Creando archivo .env..."

ENV_FILE="$ROOT_DIR/.env"
cat > "$ENV_FILE" << EOF
; Carta Digital - Configuración del Sistema
; Generado por instalador automático

DB_HOST=$DB_HOST
DB_PORT=$DB_PORT
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASS=$DB_PASS

APP_NAME="Carta Digital"
APP_DEBUG=false
APP_ENV=production

SESSION_NAME=carta_digital_session
CHARSET=utf8mb4
TIMEZONE=America/Lima

; APIs (configurar después)
CULQI_PUBLIC_KEY=
CULQI_SECRET_KEY=
APIPERU_TOKEN=
WHATSAPP_NUMBER=

GENERATED_AT=$(date)
EOF

chmod 644 "$ENV_FILE"
echo "✅ Archivo .env creado"

# Finales
echo ""
echo "╔═══════════════════════════════════════════╗"
echo "║     ✅ ¡INSTALACIÓN COMPLETADA!           ║"
echo "╚═══════════════════════════════════════════╝"
echo ""
echo "📊 Información:"
echo "  Base de Datos: $DB_NAME"
echo "  Host: $DB_HOST"
echo "  Usuario Admin: admin"
echo "  Contraseña Admin: admin123"
echo ""
echo "🔗 Enlaces rápidos:"
echo "  Carta Digital: http://localhost/carta-digital/"
echo "  Panel Admin: http://localhost/carta-digital/admin/"
echo "  Diagnóstico: http://localhost/carta-digital/installer/diagnostico.php"
echo ""
echo "🔐 Recuerda:"
echo "  1. Cambia la contraseña admin inmediatamente"
echo "  2. Configura datos de tu negocio"
echo "  3. Prueba el sistema completamente"
echo ""
echo "Para más información, consulta:"
echo "  📖 $INSTALLER_DIR/README.md"
echo "  ⚡ $INSTALLER_DIR/QUICK_START.md"
echo ""
