#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#define UNMATCHED -1

// Structure pour représenter un pixel RGB
typedef struct {
    unsigned char rouge;
    unsigned char vert;
    unsigned char bleu;
} Pixel;

// Structure pour représenter une brique LEGO
typedef struct {
    int id;
    int largeur;
    int hauteur;
    Pixel couleur;
    float prix;
    int stock;
} Brique;

typedef struct {
    int largeur;
    int hauteur;
    Pixel *pixels;
} Image;

typedef struct {
    int nb_poses;
    float prix_total;
    int somme_erreurs;
    int nb_rupture;
    char **lignes;
} ResultatPavage;

// Convertit un caractère hexadécimal en entier
int hexadecimal_en_decimal(char c){
    if(c >= '0' && c <= '9') {
        return c - '0';
    }

    else if(c >= 'A' && c <= 'F') {
        return c - 'A' + 10;
    }

    else if(c >= 'a' && c <= 'f') {
        return c - 'a' + 10;
    }
    return 0;
}

// Convertit une chaîne "RRGGBB" en Pixel
Pixel hexadecimal_en_pixel(const char *hex){
    Pixel pixel;
    pixel.rouge = hexadecimal_en_decimal(hex[0])*16 + hexadecimal_en_decimal(hex[1]);
    pixel.vert  = hexadecimal_en_decimal(hex[2])*16 + hexadecimal_en_decimal(hex[3]);
    pixel.bleu  = hexadecimal_en_decimal(hex[4])*16 + hexadecimal_en_decimal(hex[5]);
    return pixel;
}

void pixel_to_hex(Pixel p, char* hex) {
    snprintf(hex, 7, "%02x%02x%02x", p.rouge, p.vert, p.bleu);
}

// Calcule la différence entre deux pixels
int difference_pixel(Pixel p1, Pixel p2){
    int dr = (int)p1.rouge - (int)p2.rouge;
    int dv = (int)p1.vert - (int)p2.vert;
    int db = (int)p1.bleu - (int)p2.bleu;
    return dr*dr + dv*dv + db*db;
}

int lire_image(const char *chemin, Image *out) {
    FILE *f = fopen(chemin, "r");
    if (!f) { perror("Erreur ouverture fichier image"); return 0; }

    int w, h;
    if (fscanf(f, "%d %d", &w, &h) != 2) {
        fprintf(stderr, "Erreur : dimensions image invalides\n");
        fclose(f);
        return 0;
    }

    Pixel *pixels = malloc((size_t)w * h * sizeof(Pixel));
    if (!pixels) { perror("Erreur allocation image"); fclose(f); return 0; }

    char hex[7];
    hex[6] = '\0';
    for (int y = 0; y < h; ++y) {
        for (int x = 0; x < w; ++x) {
            if (fscanf(f, "%6s", hex) != 1) {
                fprintf(stderr, "Erreur : pixel invalide à (%d,%d)\n", x, y);
                free(pixels);
                fclose(f);
                return 0;
            }
            pixels[x + y * w] = hexadecimal_en_pixel(hex);
        }
    }

    fclose(f);
    out->largeur = w;
    out->hauteur = h;
    out->pixels = pixels;
    return 1;
}

// Lit les pièces de LEGO depuis un fichier
int lire_briques(const char *nomfichier, Brique **briques){
    FILE *file = fopen(nomfichier, "r");
    if(!file){
        perror("Erreur ouverture fichier pièces");
        return 0;
    }

    int nb_formes, nb_couleurs, nb_briques;
    if(fscanf(file, "%d %d %d", &nb_formes, &nb_couleurs, &nb_briques) != 3){
        printf("Erreur format fichier pièces\n");
        fclose(file);
        return 0;
    }

    // Lecture des formes
    int *formes_largeur = malloc(nb_formes * sizeof(int));
    int *formes_hauteur = malloc(nb_formes * sizeof(int));
    char buffer[20];

    for(int i=0;i<nb_formes;i++){
        if(fscanf(file, "%19s", buffer) != 1){
            fprintf(stderr,"Erreur lecture forme %d\n", i);
            fclose(file);
            free(formes_largeur);
            free(formes_hauteur);
            return 0;
        }

        // Vérifie si format largeurxhauteur ou largeurxhauteur-trous
        int w, h;
        if(sscanf(buffer, "%d-%d",&w,&h) != 2){
            fprintf(stderr,"Format invalide pour forme %d\n",i);
            fclose(file);
            free(formes_largeur);
            free(formes_hauteur);
            return 0;
        }
        formes_largeur[i] = w;
        formes_hauteur[i] = h;
    }

    // Lecture des couleurs
    Pixel *couleurs = malloc(nb_couleurs * sizeof(Pixel));
    char hex[7];
    for(int i=0;i<nb_couleurs;i++){
        if(fscanf(file, "%6s", hex) != 1){
            fprintf(stderr,"Erreur lecture couleur %d\n", i);
            fclose(file);
            free(formes_largeur);
            free(formes_hauteur);
            free(couleurs);
            return 0;
        }
        couleurs[i] = hexadecimal_en_pixel(hex);
    }

    // Lecture des briques
    Brique *liste = malloc(nb_briques * sizeof(Brique));
    for(int i=0;i<nb_briques;i++){
        int id_forme, id_couleur;
        float prix;
        int stock;
        int res = fscanf(file, "%d/%d %f %d", &id_forme, &id_couleur, &prix, &stock);
		if(res != 4){
		    fprintf(stderr,"Erreur lecture brique %d : fscanf a lu %d valeurs au lieu de 4\n", i, res);
		    fclose(file);
		    free(formes_largeur);
		    free(formes_hauteur);
		    free(couleurs);
		    free(liste);
		    return 0;
		}

        if(id_forme<0 || id_forme>=nb_formes || id_couleur<0 || id_couleur>=nb_couleurs){
            printf("Indice forme/couleur invalide pour brique %d\n", i);
            fclose(file);
            free(formes_largeur);
            free(formes_hauteur);
            free(couleurs);
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
    fclose(file);

    *briques = liste;
    return nb_briques;
}

int charger_image_et_briques_fichiers(const char* image_file, const char* pieces_file,
                                      Image* img, Brique** briques, int* nb_briques) {
    // Lire l'image
    if (!lire_image(image_file, img)) {
        fprintf(stderr, "Erreur lecture image %s\n", image_file);
        return 0;
    }

    // Lire les briques
    *nb_briques = lire_briques(pieces_file, briques);
    if (*nb_briques == 0) {
        fprintf(stderr, "Erreur lecture briques %s\n", pieces_file);
        free(img->pixels);
        return 0;
    }

    return 1;
}


void ecrire_resultat1x1(ResultatPavage* R){
    
    
    char sortie_fichier[512];
    snprintf(sortie_fichier, sizeof(sortie_fichier), "paving/out1x1.txt");

    FILE* fout = fopen(sortie_fichier, "w");
    if (!fout) {
        perror("Erreur ouverture fichier sortie");
        return;
    }
    fprintf(fout, "%d %.0f %d %d\n", R->nb_poses, R->prix_total, R->somme_erreurs, R->nb_rupture);
    for(int i=0;i<R->nb_poses;i++){
        fprintf(fout, "%s\n", R->lignes[i]);
    }
    fclose(fout);

    printf("%s %.0f %d %d\n", sortie_fichier, R->prix_total, R->somme_erreurs, R->nb_rupture);
}


void liberer_resultat(ResultatPavage* R){
    if(!R || !R->lignes) return;
    int total_cases = R->nb_poses; // idéalement stocker total_cases réel
    for(int i = 0; i < total_cases; i++){
        if(R->lignes[i]) free(R->lignes[i]);
    }
    free(R->lignes);
    R->lignes = NULL;
}

// Trouver la brique la plus proche en couleur
int trouver_brique_proche(Pixel pixel, Brique *briques, int nb_briques){
    int min_index = 0;
    int min_diff = difference_pixel(pixel, briques[0].couleur);
    for(int i = 1; i < nb_briques; i++){
        int diff = difference_pixel(pixel, briques[i].couleur);
        if(diff < min_diff){
            min_diff = diff;
            min_index = i;
        }
    }
    return min_index;
}

ResultatPavage algo1x1(Image *img, Brique *briques, int nb_briques) {
    int total_cases = img->largeur * img->hauteur;

    ResultatPavage R;
    R.nb_poses = 0;
    R.prix_total = 0.0f;
    R.somme_erreurs = 0;
    R.nb_rupture = 0;
    R.lignes = calloc(total_cases, sizeof(char*));

    for(size_t idx=0; idx < (size_t)(img->largeur*img->hauteur); idx++){
        Pixel p = img->pixels[idx];
        int b_index = trouver_brique_proche(p, briques, nb_briques);
        Brique *b = &briques[b_index];

        char couleur_hex[7];
        pixel_to_hex(b->couleur, couleur_hex);

        b->stock--;
        if(b->stock < 0) R.nb_rupture++;

        R.nb_poses++;
        R.prix_total += b->prix;
        R.somme_erreurs += difference_pixel(p, b->couleur);

        R.lignes[idx] = malloc(64);
        if (!R.lignes[idx]) {
            fprintf(stderr, "Erreur allocation ligne %zu\n", idx);
            exit(1);
        }
        sprintf(R.lignes[idx], "%dx%d/%s %d %d %d",
            b->largeur, b->hauteur, couleur_hex, (int)(idx % img->largeur), (int)(idx / img->largeur), 0);
    }


    return R;
}

int main(int argc, char *argv[]){
    if(argc < 2){
        printf("Usage: %s dossier\n", argv[0]);
        return 1;
    }

	Image img;
	Brique *briques;
	int nb_briques;
	
	if(!charger_image_et_briques_fichiers(argv[1], argv[2], &img, &briques, &nb_briques)) {
	    return 1;
	}


    // Pavage 1x1
    ResultatPavage R = algo1x1(&img, briques, nb_briques);
    ecrire_resultat1x1(&R);
    liberer_resultat(&R);
    free(img.pixels);
    free(briques);

    return 0;
}

