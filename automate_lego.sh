#!/bin/bash

PROJECT_ROOT="/var/www/html/SAE_S3_BUT2_INFO"
JAR_PATH="$PROJECT_ROOT/bin/legotools-1.0-SNAPSHOT.jar"

if [ ! -f "$JAR_PATH" ]; then
    echo "erreur : le fichier $JAR_PATH est introuvable."
    exit 1
fi

cd "$PROJECT_ROOT"

echo " --- debut de la maintenance automatique des stocks $(date) ---"

echo "execution de refill..."
java -jar "$JAR_PATH" refill

echo "execution de restock..."
java -jar "$JAR_PATH" restock

echo "execution de l'analyse proactive..."
java -jar "$JAR_PATH" proactive

echo "--- maintenance terminee ---"