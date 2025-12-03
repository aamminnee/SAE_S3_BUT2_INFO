#!/bin/bash

# Vérifier les arguments
if [ $# -lt 2 ]; then
    echo "Usage: $0 <image_source> <taille_exemple: 128x128>"
    exit 1
fi

IMAGE_SOURCE=$1
TAILLE=$2

# Étape 1 : exécuter ImageConversionTest.java
echo "=== Étape 1 : Conversion de l'image ==="

javac -d bin src/main/java/fr/univ_eiffel/lego/image/conversion/*.java
echo "en cours..."
java -cp bin fr.univ_eiffel.lego.image.conversion.ImageConversionTest "$IMAGE_SOURCE" "$TAILLE"

# Extraire le nom de base sans extension
BASE_NAME=$(basename "$IMAGE_SOURCE")
EXT="${BASE_NAME##*.}" 
BASE_NAME="${BASE_NAME%.*}" 

# Construire le chemin de l'image convertie
IMG_CONV="images_conversion/${BASE_NAME}_conversion_1.${EXT}"

# Étape 2 : exécuter ImageToText.java
echo "=== Étape 2 : Génération du fichier texte ==="

# Compiler tous les fichiers Java dans le package text
javac -d bin src/main/java/fr/univ_eiffel/lego/image/text/*.java

# Exécuter ImageToText avec le classpath bin
java -cp bin fr.univ_eiffel.lego.image.text.ImageToText "$IMG_CONV"

# Récupérer dynamiquement le nom du fichier texte généré
BASE_NAME_CONV=$(basename "$IMG_CONV")
BASE_NAME_CONV="${BASE_NAME_CONV%.*}" 

# Le fichier texte se trouve maintenant dans matching/
TXT_FILE="matching/${BASE_NAME_CONV}.txt"

# Étape 3 : exécuter pavage.c
echo "=== Étape 3 : Pavage 1x1 avec C ==="

# Appeler Java qui s’occupe lui-même d’appeler pavage.c
javac -d bin src/main/java/fr/univ_eiffel/lego/paving/runner/*.java
java -cp bin fr.univ_eiffel.lego.paving.runner.PavingRunner "$TXT_FILE" matching/pieces.txt

# Le fichier out1x1.txt est maintenant dans pavages/
OUT_FILE="paving/out1x1.txt"


# Étape 4 : exécuter LegoPavingTest.java pour générer l'image LEGO
echo "=== Étape 4 : Génération de l'image LEGO ==="

# Compiler tous les fichiers Java dans le package legoPaving
javac -d bin src/main/java/fr/univ_eiffel/lego/image/legoPaving/*.java

# Exécuter le programme avec classpath bin et l'image source en argument
java -cp bin fr.univ_eiffel.lego.image.legoPaving.LegoPavingTest "$IMG_CONV"

echo "=== Pipeline terminé ! L'image LEGO est générée ==="


