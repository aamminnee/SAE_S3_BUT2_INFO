#include <stdio.h>
#include <stdlib.h>

#include "dependance/util.h"
#include "dependance/structure.h"
#include "dependance/image.h" 
#include "dependance/brique.h" 

// ouvre un fichier dans un dossier spécifique
FILE* open_with_dir(char* dir, char* name, char* mode) {
    char filename[256];
    snprintf(filename, sizeof(filename), "%s/%s", dir,name);
    // printf("open file %s (%s)\n", filename, mode); // debug désactivé pour alléger
    FILE* fptr = fopen(filename, mode);
    assert(fptr!=NULL);
    return fptr;
}

// convertit un caractère chiffre en masque binaire
int charToMask(char c) {
    assert(c>='0' && c<='9');
    return 1<<(c-'0');
}

// convertit une coordonnée locale en masque binaire
int coordToMask(int dx, int dy, int W) {
    return 1<<(dx + W*dy);
}

// convertit la chaine représentant les trous en entier
int trou_str_to_int(char* buffer) {
    int T=0;
    for (int i=0; buffer[i]; i++)
        T += charToMask(buffer[i]);
    return T;
}

// convertit l'entier des trous en chaine de caractères
void trou_int_to_str(int T, char* buffer) {
    int ibuffer=0;
    char current='0';
    while (T>0) {
        assert(current <= '9');
        if (T%2 == 1)
            buffer[ibuffer++] = current;
        current++;
        T /= 2;
    }
    buffer[ibuffer] = 0;
}

// retourne l'index linéaire pour des coordonnées (x,y)
int getIndex(int x, int y, Image* I) {
    return y * I->W + x;
}

// fonction auxiliaire pour obtenir l'indice de brique pour une forme et une couleur
int getBrickFor(BriqueList* B, int shape, int col) {
    if (shape == -1) return -1;
    int idx = getBriqueWithColor(B, shape, col);
    if (idx >= 0) return idx;
    // fallback : cherche n'importe quelle brique de cette forme
    for (int k = 0; k < B->nBrique; k++)
        if (B->bShape[k] == shape) return k;
    return -1;
}

// calcule le score d'erreur pour une brique posée à une position donnée
int compute_error_for_shape_at(int iBrique, int x0, int y0, int rot, BriqueList* B, Image* I) {
    int shape = B->bShape[iBrique];
    int W = B->W[shape];
    int H = B->H[shape];
    int Tmask = B->T[shape];
    int colIndex = B->bCol[iBrique];
    RGB brickColor = B->col[colIndex];
    int err = 0;
    for (int dy = 0; dy < H; dy++) {
        for (int dx = 0; dx < W; dx++) {
            int maskbit = coordToMask(dx, dy, W);
            if (Tmask && (Tmask & maskbit)) continue;
            int px = (rot == 0) ? x0 + dx : x0 + dy;
            int py = (rot == 0) ? y0 + dy : y0 + dx;
            if (px < 0 || px >= I->W || py < 0 || py >= I->H) continue;
            RGB target = *get(I, px, py);
            err += colError(brickColor, target);
        }
    }
    return err;
}

// vérifie que tous les pixels d’un rectangle ne sont pas encore couverts
int rect_is_uncovered(int x0, int y0, int w, int h, Image* I, int* covered) {
    for (int dy = 0; dy < h; dy++) {
        for (int dx = 0; dx < w; dx++) {
            int px = x0 + dx, py = y0 + dy;
            if (px < 0 || px >= I->W || py < 0 || py >= I->H) return 0;
            if (covered[getIndex(px, py, I)]) return 0;
        }
    }
    return 1;
}

// marque un rectangle comme couvert dans le tableau covered
void mark_rect_covered(int x0, int y0, int w, int h, Image* I, int* covered) {
    for (int dy = 0; dy < h; dy++)
        for (int dx = 0; dx < w; dx++)
            covered[getIndex(x0 + dx, y0 + dy, I)] = 1;
}

// choisit une brique 1x1 disponible, en priorisant la couleur demandée
int choisir_brique1x1_disponible(BriqueList* B, int col, int* used) {
    // 1. cherche brique 1x1 bonne couleur et en stock
    for (int i = 0; i < B->nBrique; i++) { 
        if (B->bShape[i] == 0 && B->bCol[i] == col && used[i] < B->bStock[i]) {
            return i;
        }
    }
    // 2. cherche n'importe quelle brique 1x1 en stock
    for (int i = 0; i < B->nBrique; i++) {
        if (B->bShape[i] == 0 && used[i] < B->bStock[i]) { 
            return i;
        }
    }
    // 3. fallback : cherche brique 1x1 bonne couleur (même sans stock)
    for (int i = 0; i < B->nBrique; i++) { 
        if (B->bShape[i] == 0 && B->bCol[i] == col) { 
            return i;
        }
    }
    // 4. dernier recours : n'importe quelle brique 1x1
    for (int i = 0; i < B->nBrique; i++) {
        if (B->bShape[i] == 0) { 
            return i;
        }
    }
    return -1;
}

// vérifie qu'un rectangle a une couleur cible uniforme selon le tableau closestcolor
int rect_has_uniform_closest(int x0, int y0, int w, int h, Image* I, int* closestColor, int colTarget) {
    for (int dy = 0; dy < h; dy++) {
        for (int dx = 0; dx < w; dx++) {
            int px = x0 + dx, py = y0 + dy;
            if (px < 0 || px >= I->W || py < 0 || py >= I->H) return 0;
            if (closestColor[getIndex(px, py, I)] != colTarget) return 0;
        }
    }
    return 1;
}

// fonction de comparaison pour trier les dimensions par aire décroissante
int comparer_aire(const void* a, const void* b) {
    Dimension* da = (Dimension*)a;
    Dimension* db = (Dimension*)b;
    return db->aire - da->aire;
}