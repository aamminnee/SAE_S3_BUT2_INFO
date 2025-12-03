#include "structures.h"

#include <stdio.h>
#include <stdlib.h>
#include <limits.h>

// Algorithme de pavage 1x1
ResultatPavage algoStockAmeliore(Image *img, Brique *briques, int nb_briques) {
    int total_cases = img->largeur * img->hauteur;
    ResultatPavage R;
    R.nb_poses = 0;
    R.prix_total = 0.0f;
    R.somme_erreurs = 0;
    R.nb_rupture = 0;
    R.lignes = calloc(total_cases, sizeof(char*));
    if (!R.lignes) {
        fprintf(stderr, "Erreur lors de l'allocation de R.lignes\n");
        exit(EXIT_FAILURE);
    }

    // Parcours de toutes les cases de l'image
    for(int index = 0; index < total_cases; index++){
        Pixel pixel = img->pixels[index];

        int brique_index = trouver_brique_proche(pixel, briques, nb_briques);
        int choisi = brique_index;

        if (briques[brique_index].stock <= 0) {
            R.nb_rupture++;
            int min_diff = INT_MAX;
            choisi = brique_index;

            for (int i = 0; i < nb_briques; i++) {
                if (briques[i].stock > 0 && briques[i].largeur == briques[brique_index].largeur && briques[i].hauteur == briques[brique_index].hauteur) {
                    int diff = difference_pixel(pixel, briques[i].couleur);
                    if (diff < min_diff) {
                        min_diff = diff;
                        choisi = i;
                    }
                }
            }
        }

        Brique *brique = &briques[choisi];
        brique->stock--;

        char couleur_hex[7];
        pixel_en_hexadecimal(brique->couleur, couleur_hex);

        R.nb_poses++;
        R.prix_total += brique->prix;
        R.somme_erreurs += difference_pixel(pixel, brique->couleur);

        R.lignes[index] = malloc(64);
        if (!R.lignes[index]) {
            fprintf(stderr, "Erreur lors de l'allocation de la ligne %d\n", index);
            exit(1);
        }
        sprintf(R.lignes[index], "%dx%d/%s %d %d %d", brique->largeur, brique->hauteur, couleur_hex, (int)(index % img->largeur), (int)(index / img->largeur), 0);
    }
    return R;
}