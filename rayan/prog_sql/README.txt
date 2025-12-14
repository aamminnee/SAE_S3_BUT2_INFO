Les tables et les champs nécessaires aux modules Front-End et Java ont été mises en places (le tout en anglais comme demandé dans le module Front-End) :
- Table users regroupant les informations des utilisateurs (login, adresse mail, mot de passe haché...),
- Table images permettant de stocker les images uploadés en format BLOB,
- Table pieces conservant les informations des types de pièces utilisés ainsi que leur nombre en stock,
- Table orders liée aux tables users, orders, et pieces qui conserve les informations d'une commande passée par un utilisateur,
- Table orders_pieces permettant de faire un lien entre les tables orders et pieces
- Table invoices stockant les factures (pas encore totalement intégrée)
(A noter, l'import de la base de données créera des tables doublons des vues créés, mais c'est quelque chose qui a été ajouté par l'export de la base par phpMyAdmin)
- Vues pour extraires les différentes parties du numéros de séries (compte de jours depuis la fabrication, l'heure de fabrication dans le jour en nombre de millisecondes écoulés depuis minuit, et les octets aléatoires restants)
- Triggers pour empêcher la modification d'une commande validée
- Trigger pour mettre à jour la somme totale de la commande en fonction des pièces utilisés pour le pavage sélectionné.

On notera qu'il faudra à l'avenir prévoir un jeu de données complet pour pouvoir tester la base de manière plus optimale. Les données présents sont de simples exemples assez simples.

Pour importer la base, il suffit tout simplement d'exécuter le contenu du fichier SQL.
Attention, dans un phpMyAdmin, il faut d'abord s'assurer d'avoir les droits nécessaires pour créer les vues.