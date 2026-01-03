#include "dependance/brique.h"
#include "dependance/util.h"

#include <string.h>

// fonction pour charger les données des briques depuis les fichiers
void load_brique(char* dir, BriqueList* B) {
    // ouverture du fichier briques.txt
    FILE* fptr = open_with_dir(dir, "briques.txt", "r");
    if (!fptr) {
        perror("impossible d'ouvrir briques.txt");
        exit(EXIT_FAILURE);
    }
    char line[512];
    if (fgets(line, sizeof(line), fptr) == NULL) {
        printf("erreur lecture header\n");
        exit(EXIT_FAILURE);
    }
    if (sscanf(line, "%d %d %d", &B->nShape, &B->nCol, &B->nBrique) != 3) {
        printf("format header invalide\n");
        exit(EXIT_FAILURE);
    }
    // allocations mémoire
    B->W = malloc(B->nShape * sizeof(int));
    B->H = malloc(B->nShape * sizeof(int));
    B->T = malloc(B->nShape * sizeof(int));
    B->col = malloc(B->nCol * sizeof(RGB));
    B->bShape = malloc(B->nBrique * sizeof(int));
    B->bCol = malloc(B->nBrique * sizeof(int));
    B->bPrix = malloc(B->nBrique * sizeof(float)); 
    B->bStock = malloc(B->nBrique * sizeof(int));
    for (int i = 0; i < B->nShape; i++) {
        if (fgets(line, sizeof(line), fptr) == NULL) {
            printf("erreur inattendue lecture forme %d\n", i);
            break;
        }
        // nettoyage fin de ligne
        line[strcspn(line, "\r\n")] = 0;
        int w, h;
        char buffer[256];
        B->T[i] = 0; // par défaut pas de trou
        // essai format avec masque: "w-h-mask"
        if (sscanf(line, "%d-%d-%s", &w, &h, buffer) == 3) {
            B->W[i] = w;
            B->H[i] = h;
            B->T[i] = trou_str_to_int(buffer);
        } 
        // sinon format simple: "w-h"
        else if (sscanf(line, "%d-%d", &w, &h) == 2) {
            B->W[i] = w;
            B->H[i] = h;
        } else {
            printf("format forme invalide ligne %d : %s\n", i+2, line);
        }
    }
    for (int i = 0; i < B->nCol; i++) {
        if (fgets(line, sizeof(line), fptr) == NULL) break;
        int r, g, b;
        // lecture hexadécimale r, g, b
        if (sscanf(line, "%02x%02x%02x", &r, &g, &b) == 3) {
            B->col[i].R = r;
            B->col[i].G = g;
            B->col[i].B = b;
        } else {
            printf("erreur lecture couleur index %d : %s\n", i, line);
        }
    }

    // 4. chargement des briques (stock)
    for (int i = 0; i < B->nBrique; i++) {
        if (fgets(line, sizeof(line), fptr) == NULL) break;
        int s, c, stk;
        float p; 
        if (sscanf(line, "%d/%d %f %d", &s, &c, &p, &stk) == 4) {
            B->bShape[i] = s;
            B->bCol[i]   = c;
            B->bPrix[i]  = p; 
            B->bStock[i] = stk;
        } else {
            printf("erreur lecture brique index %d : %s\n", i, line);
            B->bShape[i] = -1; // invalide
        }
    }
    fclose(fptr);
    printf("briques chargées avec succès: %d formes, %d couleurs, %d briques.\n", B->nShape, B->nCol, B->nBrique);
}

// fonction pour trouver l'index d'une forme par dimensions
int lookupShape(BriqueList* B, int W, int H) {
    for (int i=0; i<B->nShape; i++) { 
        if (B->W[i]==W && B->H[i]==H) { 
            return i;
        }
    }
    return -1;
}

// fonction pour trouver l'index d'une brique par forme et couleur
int getBriqueWithColor(BriqueList* B, int shape, int col) {
    for (int i = 0; i < B->nBrique; i++) { 
        if (B->bShape[i] == shape && B->bCol[i] == col) { 
            return i;
        }
    }
    return -1;
}

// libération de la mémoire
void freeBrique(BriqueList B) {
    free(B.W); free(B.H); free(B.T);
    free(B.col);
    free(B.bShape); free(B.bCol); free(B.bPrix); free(B.bStock);
}