#include "structures.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <limits.h>

// Convertit un caractère hexadécimal en entier
int hexadecimal_en_decimal(char c){
    if (c >= '0' && c <= '9'){
        return c - '0';
    }
    else if (c >= 'A' && c <= 'F'){
        return c - 'A' + 10;
    }
    else if (c >= 'a' && c <= 'f'){
        return c - 'a' + 10;
    }
    return -1;
}

// Convertit une chaîne "RRGGBB" en pixel
Pixel hexadecimal_en_pixel(const char *hex){
    Pixel pixel;
    pixel.rouge = hexadecimal_en_decimal(hex[0]) * 16 + hexadecimal_en_decimal(hex[1]);
    pixel.vert = hexadecimal_en_decimal(hex[2]) * 16 + hexadecimal_en_decimal(hex[3]);
    pixel.bleu = hexadecimal_en_decimal(hex[4]) * 16 + hexadecimal_en_decimal(hex[5]);
    return pixel;
}

// Convertit un pixel en chaîne hexadécimale
void pixel_en_hexadecimal(Pixel p, char* hex){
    snprintf(hex, 7, "%02x%02x%02x", p.rouge, p.vert, p.bleu);
}

// Calcule la différence entre deux pixels
int difference_pixel(Pixel p1, Pixel p2){
    int diffRouge = (int)p1.rouge - (int)p2.rouge;
    int diffVert = (int)p1.vert - (int)p2.vert;
    int diffBleu = (int)p1.bleu - (int)p2.bleu;
    return diffRouge * diffRouge + diffVert * diffVert + diffBleu * diffBleu;
}

// Lecture d'une image depuis un fichier texte
int lire_image(const char *nomfichier, Image *sortie) {
    FILE *fichier = fopen(nomfichier, "r");
    if (!fichier) {
        perror("Erreur lors de l'ouverture du fichier image");
        return 0;
    }

    int largeur, hauteur;
    if (fscanf(fichier, "%d %d", &largeur, &hauteur) != 2){
        fprintf(stderr, "Erreur, les dimensions de l'image sont invalides\n");
        fclose(fichier);
        return 0;
    }

    Pixel *pixels = malloc((size_t)largeur * hauteur * sizeof(Pixel));
    if (!pixels){
        perror("Erreur lors de l'allocation mémoire de l'image");
        fclose(fichier);
        return 0;
    }

    char hex[7];
    hex[6] = '\0';
    for (int y = 0; y < hauteur; y++){
        for (int x = 0; x < largeur; x++){
            if (fscanf(fichier, " %6s", hex) != 1){
                fprintf(stderr, "Erreur, pixel invalide à (%d,%d)\n", x, y);
                free(pixels);
                fclose(fichier);
                return 0;
            }
            pixels[x + y * largeur] = hexadecimal_en_pixel(hex);
        }
    }

    fclose(fichier);
    sortie->largeur = largeur;
    sortie->hauteur = hauteur;
    sortie->pixels = pixels;
    return 1;
}

// Lecture des briques depuis un fichier texte
int lire_briques(const char *nomfichier, Brique **briques){
    FILE *fichier = fopen(nomfichier, "r");
    if (!fichier) {
        perror("Erreur lors de l'ouverture du fichier pièces");
        return 0;
    }

    // Lecture de la première ligne du fichier
    int nb_formes, nb_couleurs, nb_briques;
    if (fscanf(fichier, "%d %d %d", &nb_formes, &nb_couleurs, &nb_briques) != 3){
        printf("Erreur sur le format du fichier pièces");
        fclose(fichier);
        return 0;
    }

    // Lecture des formes 
    int *formes_largeur = malloc(nb_formes * sizeof(int));
    int *formes_hauteur = malloc(nb_formes * sizeof(int));
    char buffer[20];

    for (int i = 0; i < nb_formes; i++) {
        if (fscanf(fichier, "%19s", buffer) != 1) {
            fprintf(stderr, "Erreur lors de la lecture du forme %d\n", i);
            fclose(fichier);
            free(formes_largeur);
            free(formes_hauteur);
            return 0;
        }

        int largeur, hauteur;
        if(sscanf(buffer, "%d-%d", &largeur, &hauteur) != 2){
            fprintf(stderr, "Erreur, format invalide pour la forme %d\n", i);
            fclose(fichier);
            free(formes_largeur);
            free(formes_hauteur);
            return 0;
        }
        formes_largeur[i] = largeur;
        formes_hauteur[i] = hauteur;
    }

    // Lecture des couleurs
    Pixel *couleurs = malloc(nb_couleurs * sizeof(Pixel));
    char hex[7];
    for (int i = 0; i < nb_couleurs; i++) {
        if (fscanf(fichier, "%6s", hex) != 1){
            fprintf(stderr, "Erreur lors de la lecture de la couleur %d\n", i);
            fclose(fichier);
            free(formes_largeur);
            free(formes_hauteur);
            free(couleurs);
            return 0;
        }
        couleurs[i] = hexadecimal_en_pixel(hex);
    }

    // Lecture des pieces
    Brique *liste = malloc(nb_briques * sizeof(Brique));
    for (int i = 0; i < nb_briques; i++) {
        int id_forme, id_couleur;
        float prix;
        int stock;

        if (fscanf(fichier, "%d/%d %f %d", &id_forme, &id_couleur, &prix, &stock) != 4){
            fprintf(stderr, "Erreur, format invalide pour la brique %d\n", i);
            fclose(fichier);
            free(formes_largeur);
            free(formes_hauteur);
            free(couleurs);
            free(liste);
            return 0;
        }

        if (id_forme < 0 || id_forme >= nb_formes || id_couleur < 0 || id_couleur >= nb_couleurs) {
            printf("Erreur, forme/couleur invalide pour la brique %d\n", i);
            fclose(fichier);
            free(formes_largeur);
            free(formes_hauteur);
            free(couleurs);
            free(liste);
            return 0;
        }

        liste[i].id = i;
        liste[i].largeur = formes_largeur[id_forme];
        liste[i].hauteur = formes_hauteur[id_forme];
        liste[i].couleur = couleurs[id_couleur];
        liste[i].prix = prix;
        liste[i].stock = stock;
    }

    free(formes_largeur);
    free(formes_hauteur);
    free(couleurs);
    fclose(fichier);

    *briques = liste;
    return nb_briques;
}

// Charge l'image et les briques depuis un dossier
int charger_image_et_briques(const char* dossier, Image* img, Brique** briques, int* nb_briques) {
    char image_fichier[512], pieces_fichier[512];
    snprintf(image_fichier, sizeof(image_fichier), "%s/image.txt", dossier);
    snprintf(pieces_fichier, sizeof(pieces_fichier), "%s/pieces.txt", dossier);

    if (!lire_image(image_fichier, img)) {
        return 0;
    }

    *nb_briques = lire_briques(pieces_fichier, briques);
    if (*nb_briques == 0) {
        free(img->pixels);
        img->pixels = NULL;
        return 0;
    }

    return 1;
}

// Ecriture des fichiers de sortie
void ecrire_resultat(const char* dossier, const char* nom_fichier, ResultatPavage *R, int largeur, int hauteur) {
    char sortie_fichier[512];
    snprintf(sortie_fichier, sizeof(sortie_fichier), "%s/%s", dossier, nom_fichier);

    FILE* fichier_sortie = fopen(sortie_fichier, "w");
    if (!fichier_sortie) {
        perror("Erreur lors de l'ouverture du fichier de sortie");
        return;
    }

    fprintf(fichier_sortie, "%d %.0f %d %d\n", R->nb_poses, R->prix_total, R->somme_erreurs, R->nb_rupture);

    int total_cases;
    if (largeur > 0 && hauteur > 0) {
        total_cases = largeur * hauteur;
        for (int i = 0; i < total_cases; i++) {
            if (R->lignes[i]) {
                fprintf(fichier_sortie, "%s\n", R->lignes[i]);
            }
        }
    } else {
        // on itère sur les nb_poses (utile pour algo1x1 et algoStockAmeliore si elles remplissent compact)
        for (int i = 0; i < R->nb_poses; i++) {
            if (R->lignes[i]) {
                fprintf(fichier_sortie, "%s\n", R->lignes[i]);
            }
        }
    }

    fclose(fichier_sortie);

    printf("%s %d %.0f %d %d\n", sortie_fichier, R->nb_poses, R->prix_total, R->somme_erreurs, R->nb_rupture);
}

// Libère la mémoire allouée pour le résultat
void liberer_resultat(ResultatPavage* R){ 
    if (!R || !R->lignes) { 
        return; 
    } 
    for (int i = 0; i < R->nb_poses; i++) { 
        if(R->lignes[i]) { 
            free(R->lignes[i]); 
        } 
    } 
    free(R->lignes); 
    R->lignes = NULL; 
    R->nb_poses = 0; 
    R->prix_total = 0; 
    R->somme_erreurs = 0; 
    R->nb_rupture = 0; 
}

// Cherche la brique 1x1 la plus proche en couleur
int trouver_brique_proche(Pixel pixel, Brique *briques, int nb_briques){
    int min_index = 0;
    int min_diff = difference_pixel(pixel, briques[0].couleur);

    for (int i = 1; i < nb_briques; i++) {
        int diff = difference_pixel(pixel, briques[i].couleur);
        if (diff < min_diff) {
            min_diff = diff;
            min_index = i;
        }
    }
    return min_index;
}

// Cherche la brique 2x1 la plus proche en couleur
int trouver_brique_2x1_proche(Pixel p1, Pixel p2, Brique *briques, int nb_briques) {
    int min_index = -1;
    int min_diff = -1;

    for (int i = 0; i < nb_briques; i++) {
        if (briques[i].largeur == 2 && briques[i].hauteur == 1) {
            int diff = difference_pixel(p1, briques[i].couleur) + difference_pixel(p2, briques[i].couleur);

            if (min_index == -1 || diff < min_diff) {
                min_index = i;
                min_diff = diff;
            }
        }
    }
    return min_index;
}

// Cherche une brique à remplacer si le stock est à 0
int trouver_brique_alternative(Pixel couleur, Brique *briques, int nb_briques, int largeur_max, int hauteur_max){
    int min_diff = -1;
    int min_index = -1;
    for (int i = 0; i < nb_briques; i++) {
        if (briques[i].stock > 0 &&
            briques[i].couleur.rouge == couleur.rouge &&
            briques[i].couleur.vert  == couleur.vert &&
            briques[i].couleur.bleu  == couleur.bleu &&
            briques[i].largeur <= largeur_max &&
            briques[i].hauteur <= hauteur_max) {

            int diff = difference_pixel(couleur, briques[i].couleur);
            if (min_index == -1 || diff < min_diff) {
                min_index = i;
                min_diff = diff;
            }
        }
    }
    return min_index;
}

// Fait une copie des briques
Brique* copier_briques(Brique *src, int nb_briques) {
    Brique *copy = malloc(sizeof(Brique) * nb_briques);
    if (!copy) {
        return NULL;
    }
    for (int i = 0; i < nb_briques; i++) {
        copy[i] = src[i];
    }
    return copy;
}

// Renvoie le numéro de case aux coordonées (x, y)
int getIndex(int x, int y, Image* I){
    if (x < 0 || x >= I->largeur || y < 0 || y >= I->hauteur) {
        return -1;
    }
    return x + I->largeur * y;
}

// Initialise la structure
void initMatching(Matching *M, int nb_cases){
    M->taille = nb_cases;
    M->paires = malloc(nb_cases * sizeof(int));

    for (int i = 0; i < nb_cases; i++) {
        M->paires[i] = UNMATCHED;
    }
}

// Retrouve le voisin de u si il est matchée
int getMatch(Matching *M, int u) {
    if (u < 0 || u >= M->taille) {
        return UNMATCHED;
    }
    return M->paires[u];
}

// Libére le matching
void freeMatching(Matching *M) {
    if (M->paires) {
        free(M->paires);
    }
    M->paires = NULL;
    M->taille = 0;
}

// Associe u et v
void setMatch(Matching *M, int u, int v){
    M->paires[u] = v;
    if (v != UNMATCHED) {
        M->paires[v] = u;
    }
}

// Vérifie si u est libre
int isFree(const Matching *M, int u) {
    return M->paires[u] == UNMATCHED;
}

// Insère u et v si ils sont de même couleur
void greedyInsert(Matching *M, int u, Image *I) {
    if (!isFree(M, u)) {
        return;
    }

    int x = u % I->largeur;
    int y = u / I->largeur;

    Pixel *pixel_u = &I->pixels[u];

    // Horizontal
    int v_horizantale = getIndex(x + 1, y, I);
    if (v_horizantale != -1 && isFree(M, v_horizantale)) {
        Pixel *pixel_v = &I->pixels[v_horizantale];
        if (pixel_u->rouge == pixel_v->rouge && pixel_u->vert == pixel_v->vert && pixel_u->bleu == pixel_v->bleu) {
            setMatch(M, u, v_horizantale);
            return;
        }
    }

    // Vertical
    int v_vertical = getIndex(x, y + 1, I);
    if (v_vertical != -1 && isFree(M, v_vertical)) {
        Pixel *pixel_v = &I->pixels[v_vertical];
        if (pixel_u->rouge == pixel_v->rouge && pixel_u->vert == pixel_v->vert && pixel_u->bleu == pixel_v->bleu) {
            setMatch(M, u, v_vertical);
            return;
        }
    }
}

// Chaque pixel fussione avec un voisin de même couleur
void greedyMatching(Matching *M, Image *I) {
    int total = I->largeur * I->hauteur;
    initMatching(M, total);

    for (int y = 0; y < I->hauteur; y++) {
        for (int x = 0; x < I->largeur; x++) {
            int u = getIndex(x, y, I);
            if (isFree(M, u)) {
                greedyInsert(M, u, I);
            }
        }
    }
}

