#include "structures.h"
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

// Fonction pour poser les pièces 2x2 et compléter le pavage
ResultatPavage algo4x2(Image *img, Brique *briques, int nb_briques) {
    int largeur = img->largeur;
    int hauteur = img->hauteur;
    int total_cases = largeur * hauteur;

    ResultatPavage R;
    R.nb_poses = 0;
    R.prix_total = 0.0f;
    R.somme_erreurs = 0;
    R.nb_rupture = 0;
    R.lignes = calloc(total_cases, sizeof(char*));
    if (!R.lignes) exit(1);

    int *deja_vu = calloc(total_cases, sizeof(int));
    char couleur_hex[7];

    // 2x2
    for (int y = 0; y < hauteur-1; y++) {
        for (int x = 0; x < largeur-1; x++) {
            int i0 = x + y * largeur;
            int i1 = (x+1) + y * largeur;
            int i2 = x + (y+1) * largeur;
            int i3 = (x+1) + (y+1) * largeur;

            if (deja_vu[i0] || deja_vu[i1] || deja_vu[i2] || deja_vu[i3]) continue;

            Pixel pixel0 = img->pixels[i0];
            Pixel pixel1 = img->pixels[i1];
            Pixel pixel2 = img->pixels[i2];
            Pixel pixel3 = img->pixels[i3];

            if (pixel0.rouge==pixel1.rouge && pixel0.rouge==pixel2.rouge && pixel0.rouge==pixel3.rouge && pixel0.vert==pixel1.vert && pixel0.vert==pixel2.vert && pixel0.vert==pixel3.vert && pixel0.bleu==pixel1.bleu && pixel0.bleu==pixel2.bleu && pixel0.bleu==pixel3.bleu) {

                int index_brique = trouver_brique_alternative(pixel0, briques, nb_briques, 2,2);
                if (index_brique == -1) { 
                    R.nb_rupture++; 
                    continue; 
                }

                Brique *brique = &briques[index_brique];
                if (brique->stock <=0) { 
                    R.nb_rupture++; 
                    continue; 
                }

                pixel_en_hexadecimal(brique->couleur, couleur_hex);
                brique->stock--;

                deja_vu[i0] = deja_vu[i1] = deja_vu[i2] = deja_vu[i3] = 1;

                R.lignes[i0] = malloc(128);
                sprintf(R.lignes[i0], "2x2/%s %d %d %d", couleur_hex, x, y, 0);

                R.nb_poses++;
                R.prix_total += brique->prix;
                R.somme_erreurs += 4 * difference_pixel(pixel0, brique->couleur);
            }
        }
    }

    // Fusion 2x2 en 4x2
    for (int y = 0; y < hauteur-1; y++) {
        for (int x = 0; x < largeur-3; x += 2) {
            int i0 = x + y*largeur;
            int i1 = (x+2) + y*largeur;

            if (!R.lignes[i0] || !R.lignes[i1]) {
                continue;
            }
            if (strncmp(R.lignes[i0], "2x2/", 4) != 0 || strncmp(R.lignes[i1], "2x2/", 4) != 0) {
                continue;
            }

            Pixel pixel0 = img->pixels[i0];
            Pixel pixel1 = img->pixels[i1];
            if (pixel0.rouge != pixel1.rouge || pixel0.vert != pixel1.vert || pixel0.bleu != pixel1.bleu) {
                continue;
            }

            int index_brique = trouver_brique_alternative(pixel0, briques, nb_briques, 4, 2);
            if (index_brique == -1) { 
                R.nb_rupture++; 
                continue; 
            }

            Brique *brique = &briques[index_brique];
            if (brique->stock <= 0) { 
                R.nb_rupture++; 
                continue; 
            }

            pixel_en_hexadecimal(brique->couleur, couleur_hex);
            brique->stock--;

            free(R.lignes[i0]);
            R.lignes[i0] = malloc(128);
            sprintf(R.lignes[i0], "4x2/%s %d %d %d", couleur_hex, x, y, 0);
            R.nb_poses--;
            free(R.lignes[i1]);
            R.lignes[i1] = NULL;
        }
    }

    // 1x1
    for (int y=0; y<hauteur; y++){
        for (int x=0; x<largeur; x++){
            int i = x + y*largeur;
            if (!deja_vu[i]){
                Pixel pixel = img->pixels[i];
                int index_brique = trouver_brique_proche(pixel, briques, nb_briques);
                Brique *brique = &briques[index_brique];
                if (brique->stock>0){
                    brique->stock--;
                } else {
                    R.nb_rupture++;
                }
                pixel_en_hexadecimal(brique->couleur, couleur_hex);
                R.lignes[i] = malloc(128);
                sprintf(R.lignes[i], "1x1/%s %d %d %d", couleur_hex, x, y, 0);
                deja_vu[i]=1;
                R.nb_poses++;
                R.prix_total += brique->prix;
                R.somme_erreurs += difference_pixel(pixel, brique->couleur);
            }
        }
    }

    // Fusion 1x1 en 2x1
    for (int y = 0; y < hauteur; y++){
        for (int x = 0; x < largeur - 1; x++){
            int i0 = x + y * largeur;
            int i1 = (x+1) + y * largeur;

            if (!R.lignes[i0] || !R.lignes[i1]) {
                continue;
            }

            if (strncmp(R.lignes[i0], "1x1/", 3) != 0 || strncmp(R.lignes[i1], "1x1/", 3) != 0) {
                continue;
            }

            Pixel pixel0 = img->pixels[i0];
            Pixel pixel1 = img->pixels[i1];
            if (pixel0.rouge != pixel1.rouge || pixel0.vert != pixel1.vert || pixel0.bleu != pixel1.bleu) {
                continue;
            }

            int index_brique = trouver_brique_2x1_proche(pixel0, pixel1, briques, nb_briques);
            if (index_brique != -1){
                Brique *brique = &briques[index_brique];
                if (brique->stock>0) {
                    brique->stock--;
                } else {
                    R.nb_rupture++;
                }
                
                pixel_en_hexadecimal(brique->couleur, couleur_hex);
                free(R.lignes[i0]);
                free(R.lignes[i1]);
                R.lignes[i0] = malloc(128);
                sprintf(R.lignes[i0], "2x1/%s %d %d %d", couleur_hex, x, y, 0);
                R.nb_poses--;
                R.lignes[i1] = NULL;
            }
        }
    }

    free(deja_vu);
    return R;
}
