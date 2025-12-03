# SAE Lego Mosaic

## Description
Ce projet permet de transformer une image source en une image LEGO en suivant plusieurs étapes :
1. Conversion de l'image en une taille d'exemple donnée.
2. Génération d'un fichier texte représentant les couleurs et les positions.
3. Pavage 1x1 en utilisant un programme C.
4. Génération de l'image LEGO finale.

## Organisation
- `src/` : code source Java
- `c/` : code source C pour le pavage
- `images_conversion/` : images converties
- `images_customer/` : images des clients
- `images_results/` : images en mosaic Lego
- `matching/` : fichiers texte générés et le stock des briques de LEGO
- `paving/` : fichiers de pavage générés
- `bin/` : classes compilées Java

## Utilisation
Pour tout exécuter et tester le programme d'un coup :
./run_pipeline.sh images_customer/image.jpeg 512x512

Sinon tester étape par étape en exécutant le main dans chaque package
