#!/bin/bash
set -e

# 1. S'assurer que les dossiers de l'application appartiennent bien à Apache
echo "Configuration des permissions pour www-data..."
chown -R www-data:www-data /var/www/html

# 2. Donner les droits d'écriture spécifiques sur le dossier d'upload si nécessaire
if [ -d "/var/www/html/chatbot_kh/uploads" ]; then
    chmod -R 775 /var/www/html/chatbot_kh/uploads
fi

echo "Démarrage d'Apache..."
# 3. Exécuter la commande principale de l'image officielle PHP/Apache (passée via CMD)
exec apache2-foreground