#ifndef STRUCTURES_H
#define STRUCTURES_H

#define UNMATCHED -1

// Structure pour représenter un pixel
typedef struct {
    unsigned char rouge;
    unsigned char vert;
    unsigned char bleu;
} Pixel;

// Structure pour représenter une piece de Lego
typedef struct {
    int id;
    int largeur;
    int hauteur;
    Pixel couleur;
    float prix;
    int stock;
} Brique;

// Structure pour représenter une image composée de pixels
typedef struct {
    int largeur;
    int hauteur;
    Pixel *pixels;
} Image;

// Structure pour stocker l'ensemble des paires associées
typedef struct {
    int *paires;
    int taille;
} Matching;

// Structure pour représenter les résultats d'un pavage
typedef struct {
    int nb_poses;
    float prix_total;
    int somme_erreurs;
    int nb_rupture;
    char **lignes;
} ResultatPavage;

// Fonctions de conversion
int hexadecimal_en_decimal(char c);
Pixel hexadecimal_en_pixel(const char *hex);
void pixel_en_hexadecimal(Pixel p, char* hex);

// Calcul de la différence entre deux pixels
int difference_pixel(Pixel p1, Pixel p2);

// Lecture des fichiers d'entrée
int lire_image(const char *nomfichier, Image *sortie);
int lire_briques(const char *nomfichier, Brique **briques);
int charger_image_et_briques(const char *dossier, Image *img, Brique **briques, int *nb_briques);

// Ecriture des fichiers de sortie
void ecrire_resultat(const char* dossier, const char* nom_fichier, ResultatPavage *R, int largeur, int hauteur);

// Libère la mémoire allouée pour le résultat
void liberer_resultat(ResultatPavage *R);

// Cherche les briques les plus proche en couleur
int trouver_brique_proche(Pixel pixel, Brique *briques, int nb_briques);
int trouver_brique_2x1_proche(Pixel p1, Pixel p2, Brique *briques, int nb_briques);
int trouver_brique_alternative(Pixel couleur, Brique *briques, int nb_briques, int largeur_max, int hauteur_max);

// Fait une copie des briques
Brique* copier_briques(Brique *src, int nb_briques);

// Fonctions pour le matching 2x1
int getIndex(int x, int y, Image *I);
void initMatching(Matching *M, int n);
void freeMatching(Matching *M);
int getMatch(Matching *M, int u);
void setMatch(Matching *M, int u, int v);
int isFree(const Matching *M, int u);
void greedyInsert(Matching *M, int u, Image *I);
void greedyMatching(Matching *M, Image *I);

// Algorithme
ResultatPavage algo1x1(Image *img, Brique *briques, int nb_briques);
ResultatPavage algoGreedyMatching(Image *img, Brique *briques, int nb_briques);
ResultatPavage algoStockAmeliore(Image *img, Brique *briques, int nb_briques);
ResultatPavage algoStockForme(Image *img, Brique *briques, int nb_briques);
ResultatPavage algo2x2(Image *img, Brique *briques, int nb_briques);
ResultatPavage algo4x2(Image *img, Brique *briques, int nb_briques);

#endif