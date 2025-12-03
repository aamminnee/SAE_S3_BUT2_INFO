#include "structures.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>

int main(int argc, char *argv[]){
    if(argc < 2){
        printf("Usage: %s dossier\n", argv[0]);
        return 1;
    }

    const char *dossier = argv[1];          
    Image img;
    Brique *briques;
    int nb_briques;

    if(!charger_image_et_briques(dossier, &img, &briques, &nb_briques)) return 1;

    // Pavage 1x1
    Brique *briques1 = copier_briques(briques, nb_briques);
    ResultatPavage R1 = algo1x1(&img, briques1, nb_briques);
    ecrire_resultat(dossier, "out1x1.txt", &R1, 0, 0);
    liberer_resultat(&R1);
    free(briques1);

    // Pavage 2x1 greedy
    Brique *briques2 = copier_briques(briques, nb_briques);
    ResultatPavage R2 = algoGreedyMatching(&img, briques2, nb_briques);
    ecrire_resultat(dossier, "outGreedyMatching.txt", &R2, img.largeur, img.hauteur);
    liberer_resultat(&R2);
    free(briques2);

    // Pavage avec stock amélioré
    Brique *briques3 = copier_briques(briques, nb_briques);
    ResultatPavage R3 = algoStockAmeliore(&img, briques3, nb_briques);
    ecrire_resultat(dossier, "outStockAmeliore.txt", &R3, 0, 0);
    liberer_resultat(&R3);
    free(briques3);

    // Pavage avec stock par forme
    Brique *briques4 = copier_briques(briques, nb_briques);
    ResultatPavage R4 = algoStockForme(&img, briques4, nb_briques);
    ecrire_resultat(dossier, "outStockForme.txt", &R4, img.largeur, img.hauteur);
    liberer_resultat(&R4);
    free(briques4);

    // Pavage 2x2
    Brique *briques5 = copier_briques(briques, nb_briques);
    ResultatPavage R5 = algo2x2(&img, briques5, nb_briques);
    ecrire_resultat(dossier, "out2x2.txt", &R5, img.largeur, img.hauteur);
    liberer_resultat(&R5);
    free(briques5);

    // Pavage 4x2
    Brique *briques6 = copier_briques(briques, nb_briques);
    ResultatPavage R6 = algo4x2(&img, briques6, nb_briques);
    ecrire_resultat(dossier, "out4x2.txt", &R6, img.largeur, img.hauteur);
    liberer_resultat(&R6);
    free(briques6);

    free(img.pixels);
    free(briques);

    return 0;
}