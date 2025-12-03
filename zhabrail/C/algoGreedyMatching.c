#include "structures.h"

#include <stdio.h>
#include <stdlib.h>

// Algorithme de pavage pour les briques 2x1
ResultatPavage algoGreedyMatching(Image *img, Brique *briques, int nb_briques) {
    int total_cases = img->largeur * img->hauteur;
    ResultatPavage R;
    R.nb_poses = 0;
    R.prix_total = 0.0f;
    R.somme_erreurs = 0;
    R.nb_rupture = 0;
    R.lignes = calloc(total_cases, sizeof(char*));
    if (!R.lignes) { fprintf(stderr, "Erreur lors de l'allocation de R.lignes\n"); exit(1); }

    Matching M; 
    greedyMatching(&M, img);
    char couleur_hex[7];

    // Parcours de toutes les cases de l'image
    for (int index = 0; index < total_cases; index++) {
        if (R.lignes[index]) {
            continue;
        }

        Pixel pixel = img->pixels[index];        
        int v = getMatch(&M, index);

        // Case 1x1
        if (v == UNMATCHED) {
            Brique *brique = &briques[trouver_brique_proche(pixel, briques, nb_briques)];
            pixel_en_hexadecimal(brique->couleur, couleur_hex);
            if (brique->stock <= 0) R.nb_rupture++;
            else brique->stock--;
            R.prix_total += brique->prix;
            R.somme_erreurs += difference_pixel(pixel, brique->couleur);
            R.nb_poses++;
            R.lignes[index] = malloc(128);
            sprintf(R.lignes[index], "1x1/%s %d %d %d", couleur_hex, index % img->largeur, index / img->largeur, 0);

        // Case 2x1
        } else if (v > index) {
            Pixel pixel2 = img->pixels[v];
            Brique *brique = &briques[trouver_brique_2x1_proche(pixel, pixel2, briques, nb_briques)];
            pixel_en_hexadecimal(brique->couleur, couleur_hex);
            int rotation = (v == index + 1) ? 0 : 1;

            if (brique->stock <= 0) R.nb_rupture++;
            else brique->stock--;
            R.prix_total += brique->prix;
            R.somme_erreurs += difference_pixel(pixel, brique->couleur) + difference_pixel(pixel2, brique->couleur);
            R.nb_poses++;
            R.lignes[index] = malloc(128);
            sprintf(R.lignes[index], "2x1/%s %d %d %d", couleur_hex, index % img->largeur, index / img->largeur, rotation);
            R.lignes[v] = (char*)1;
        }
    }

    for (int i = 0; i < total_cases; i++) {
        if (R.lignes[i] == (char*)1) R.lignes[i] = NULL;
    }

    freeMatching(&M);
    return R;
}
