PROJET : SAE Lego, Composant Java

-------
1. Description du projet

Ce projet vise à créer un tableau LEGO personnalisable à partir d'une image téléversée par un utilisateur.
Le composant Java agit comme intermédiaire entre le frontend PHP, le solveur de pavage C, la base de données, et l'API REST de l'usine de fabrication LEGO.

-------
2. Fonctionnalités actuellement implémentés

A. Traitement d'image
   - Conversion d'image (PNG/JPEG) en matrice de pixels de résolution réduite.
   - Implémentation du Pattern Strategy pour le redimensionnement :
     - 'NearestNeighborStrategy' (Plus Proche Voisin)
     - 'BilinearStrategy' (Interpolation Bilinéaire)
   - Implémentation du Pattern Composite (`MultiPhasesStrategy`) pour le chaînage des étapes de réduction.
   - Manquant : Interpolation bicubique.

B. Gestion du stock et des commandes à l'usine
   - Pattern Adapter ('FactoryConfig') pour communiquer avec l'API REST de l'usine (https://legofactory.plade.org).
   - Utilisation de la bibliothèque Gson pour la sérialisation/désérialisation JSON.
   - Pattern Strategy pour le Paiement ('PaymentService') : Implémentation de la logique de résolution du challenge SHA-256 pour recharger le compte.
   - Logique de Stockage et Commande ('StockService') : Détermination des briques manquantes en comparant les besoins clients au stock local ('Inventory').

-------
3. Dépendences Techniques (Maven)

Le projet nécessite l'ajout de la dépendance suivante dans le 'pom.xml' :
- Gson (com.google.code.gson) : Nécessaire pour les communications JSON avec l'API de l'usine.

-------
4. Utilisation actuelle

Le composant est piloté par le 'StockService' et 'ImageService'. Mais il manque un vrai main pour donner un vrai exemple d'utilisation

Flux de commande (simplifié) :
1. Frontend PHP reçoit l'upload et appelle le service Java.
2. ImageService utilise une `ScalingStrategy` pour réduire l'image.
3. [À implémenter] L'image réduite est quantifiée pour correspondre aux briques disponibles (en formes et en couleurs).
4. [À implémenter] Le résultat est envoyé au solveur C pour le pavage.
5. StockService est appelé avec les briques requises par le solveur C.
6. Puis celui-ci appelle 'determineMissingBricks()'.
7. Si besoin de commander : 'PaymentService' est utilisé pour recharger le compte si 'getBalance()' est insuffisant.
8. Commande à l'usine avec 'requestQuote' et 'placeOrder'.