#include "structures.h"
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// Algorithme de pavage 2x2
ResultatPavage algo2x2(Image *img, Brique *briques, int nb_briques) {
    int largeur = img->largeur;
    int hauteur = img->hauteur;
    int total_cases = largeur * hauteur;

    ResultatPavage R;
    R.nb_poses = 0;
    R.prix_total = 0.0f;
    R.somme_erreurs = 0;
    R.nb_rupture = 0;
    R.lignes = calloc(total_cases, sizeof(char*));
    if (!R.lignes) { 
        fprintf(stderr, "Erreur allocation R.lignes\n"); 
        exit(1); 
    }

    int *deja_vu = calloc(total_cases, sizeof(int));
    char couleur_hex[7];

    for (int y = 0; y < hauteur; y++) {
        for (int x = 0; x < largeur; x++) {
            int i = x + y * largeur;
            if (deja_vu[i]) {
                continue; 
            }

            Pixel pixel = img->pixels[i];
            int index_brique = -1;

            // 2x2
            if (x + 1 < largeur && y + 1 < hauteur) {
                int i0 = i;
                int i1 = (x+1) + y * largeur;
                int i2 = x + (y+1) * largeur;
                int i3 = (x+1) + (y+1) * largeur;
                Pixel pixel1 = img->pixels[i1];
                Pixel pixel2 = img->pixels[i2];
                Pixel pixel3 = img->pixels[i3];

                if (!deja_vu[i1] && !deja_vu[i2] && !deja_vu[i3] && pixel.rouge==pixel1.rouge && pixel.rouge==pixel2.rouge && pixel.rouge==pixel3.rouge && pixel.vert==pixel1.vert && pixel.vert==pixel2.vert && pixel.vert==pixel3.vert && pixel.bleu==pixel1.bleu && pixel.bleu==pixel2.bleu && pixel.bleu==pixel3.bleu) {

                    index_brique = trouver_brique_alternative(pixel, briques, nb_briques, 2, 2);
                    if (index_brique != -1 && briques[index_brique].stock > 0) {
                        Brique *brique = &briques[index_brique];
                        pixel_en_hexadecimal(brique->couleur, couleur_hex);
                        brique->stock--;
                        deja_vu[i0]=deja_vu[i1]=deja_vu[i2]=deja_vu[i3]=1;
                        R.lignes[R.nb_poses] = malloc(128);
                        sprintf(R.lignes[R.nb_poses], "2x2/%s %d %d %d", couleur_hex, x, y, 0);
                        R.nb_poses++;
                        R.prix_total += brique->prix;
                        R.somme_erreurs += 4*difference_pixel(pixel, brique->couleur);
                        continue;
                    } else {
                        R.nb_rupture++;
                        Brique *brique = &briques[index_brique];
                        pixel_en_hexadecimal(brique->couleur, couleur_hex);
                        deja_vu[i0]=deja_vu[i1]=deja_vu[i2]=deja_vu[i3]=1;
                        R.lignes[R.nb_poses] = malloc(128);
                        sprintf(R.lignes[R.nb_poses], "2x2/%s %d %d %d", couleur_hex, x, y, 0);
                        R.nb_poses++;
                        R.prix_total += brique->prix;
                        R.somme_erreurs += 4*difference_pixel(pixel,brique->couleur);
                        continue;
                    }
                }
            }

            // 2x1
            if (x + 1 < largeur && !deja_vu[i+1]) {
                Pixel pixel1 = img->pixels[i+1];
                if (pixel.rouge==pixel1.rouge && pixel.vert==pixel1.vert && pixel.bleu==pixel1.bleu) {
                    index_brique = trouver_brique_alternative(pixel, briques, nb_briques, 2, 1);
                    if (index_brique != -1 && briques[index_brique].stock > 0) {
                        Brique *brique = &briques[index_brique];
                        pixel_en_hexadecimal(brique->couleur, couleur_hex);
                        brique->stock--;
                        deja_vu[i] = deja_vu[i+1] = 1;
                        R.lignes[R.nb_poses] = malloc(128);
                        sprintf(R.lignes[R.nb_poses], "2x1/%s %d %d %d", couleur_hex, x, y, 0);
                        R.nb_poses++;
                        R.prix_total += brique->prix;
                        R.somme_erreurs += 2*difference_pixel(pixel, brique->couleur);
                        continue;
                    } else {
                        R.nb_rupture++;
                        Brique *brique = &briques[index_brique];
                        pixel_en_hexadecimal(brique->couleur, couleur_hex);
                        deja_vu[i] = deja_vu[i+1] = 1;
                        R.lignes[R.nb_poses] = malloc(128);
                        sprintf(R.lignes[R.nb_poses], "2x1/%s %d %d %d", couleur_hex, x, y, 0);
                        R.nb_poses++;
                        R.prix_total += brique->prix;
                        R.somme_erreurs += 2*difference_pixel(pixel, brique->couleur);
                        continue;
                    }
                }
            }

            // 1x1
            index_brique = trouver_brique_alternative(pixel, briques, nb_briques, 1, 1);
            if (index_brique != -1 && briques[index_brique].stock > 0) {
                Brique *brique = &briques[index_brique];
                pixel_en_hexadecimal(brique->couleur, couleur_hex);
                brique->stock--;
                deja_vu[i] = 1;
                R.lignes[R.nb_poses] = malloc(128);
                sprintf(R.lignes[R.nb_poses], "1x1/%s %d %d %d", couleur_hex, x, y, 0);
                R.nb_poses++;
                R.prix_total += brique->prix;
                R.somme_erreurs += difference_pixel(pixel, brique->couleur);
            } else {
                R.nb_rupture++;
                Brique *brique = &briques[index_brique];
                pixel_en_hexadecimal(brique->couleur, couleur_hex);
                deja_vu[i] = 1;
                R.lignes[R.nb_poses] = malloc(128);
                sprintf(R.lignes[R.nb_poses], "1x1/%s %d %d %d", couleur_hex, x, y, 0);
                R.nb_poses++;
                R.prix_total += brique->prix;
                R.somme_erreurs += difference_pixel(pixel, brique->couleur);
            }
        }
    }

    free(deja_vu);
    return R;
}
