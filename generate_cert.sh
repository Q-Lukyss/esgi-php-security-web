#!/bin/bash
set -e

SSL_DIR="./nginx/ssl"
CERT_FILE="$SSL_DIR/dev.crt"
KEY_FILE="$SSL_DIR/dev.key"

mkdir -p "$SSL_DIR"

if [[ -f "$CERT_FILE" && -f "$KEY_FILE" ]]; then
    echo "Le certificat SSL existe déjà"
    exit 0
fi

echo "Génération du certificat SSL..."

openssl req -x509 -nodes -days 365 \
    -subj "/C=FR/ST=Grand Est/L=Reims/O=Dev/CN=localhost" \
    -newkey rsa:2048 \
    -keyout "$KEY_FILE" \
    -out "$CERT_FILE"

chmod 600 "$KEY_FILE"
chmod 644 "$CERT_FILE"

echo "Certificat généré."
echo "  Certificat: $CERT_FILE"
echo "  Clé:        $KEY_FILE"