#!/usr/bin/env bash

# DIGITAL TRANSPORT - INSTALACIÓN DE CERTIFICADO SSL/TLS (LET'S ENCRYPT / CERTBOT)

echo "====================================================="
echo " Aprovisionamiento de Certificado SSL/TLS Gratuito "
echo "====================================================="

if [ -z "$1" ]; then
    echo "Uso: ./setup_ssl_certbot.sh tu-dominio.com [email-administrador]"
    echo "Ejemplo: ./setup_ssl_certbot.sh mi-transporte-digital.com admin@transporte.com"
    exit 1
fi

DOMAIN=$1
EMAIL=${2:-"admin@$DOMAIN"}

echo "[1/3] Verificando instalación de Certbot..."
if ! command -v certbot &> /dev/null; then
    echo "Instalando Certbot..."
    if command -v apt-get &> /dev/null; then
        sudo apt-get update && sudo apt-get install -y certbot python3-certbot-apache
    elif command -v dnf &> /dev/null; then
        sudo dnf install -y certbot python3-certbot-apache
    elif command -v pacman &> /dev/null; then
        sudo pacman -Sy --noconfirm certbot certbot-apache
    fi
fi

echo "[2/3] Solicitando e instalando certificado SSL Let's Encrypt para $DOMAIN..."
sudo certbot --apache --non-interactive --agree-tos -m "$EMAIL" -d "$DOMAIN" --redirect

echo "[3/3] Configurando renovación automática del certificado SSL..."
sudo systemctl enable certbot.timer &> /dev/null || true

echo "====================================================="
echo " ¡Certificado SSL/TLS (HTTPS) Activado con Éxito!"
echo " Su sitio web ahora está protegido en: https://$DOMAIN"
echo "====================================================="
