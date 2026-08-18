#!/bin/bash
set -e

echo "=========================================="
echo "LuiTechStream - Iniciando contenedor..."
echo "=========================================="

# Esperar a que la base de datos esté disponible
echo "Esperando conexión a la base de datos..."
MAX_RETRIES=30
RETRY_COUNT=0

until php -r "
\$host = getenv('DB_HOST') ?: 'luitechstream-luitechstream-05puer';
\$port = getenv('DB_PORT') ?: '3306';
\$user = getenv('DB_USERNAME') ?: 'luitechStream';
\$pass = getenv('DB_PASSWORD') ?: 'Castro161219@';
try {
    new PDO('mysql:host=' . \$host . ';port=' . \$port, \$user, \$pass);
    exit(0);
} catch (PDOException \$e) {
    exit(1);
}
" 2>/dev/null; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        echo "ERROR: No se pudo conectar a la base de datos después de $MAX_RETRIES intentos."
        echo "Verifica las variables de entorno DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD, DB_DATABASE"
        exit 1
    fi
    echo "Intento $RETRY_COUNT/$MAX_RETRIES - Base de datos no disponible, reintentando en 2 segundos..."
    sleep 2
done

echo "✅ Base de datos disponible. Inicializando datos..."

# Inicializar la base de datos
php /var/www/html/init-db.php

echo "✅ Inicialización completada."

# Iniciar Apache en primer plano
echo "Iniciando Apache..."
exec apache2-foreground