#include <stdio.h>
#include <stdlib.h>

#include "dependance/util.h"
#include "dependance/structure.h"
#include "dependance/image.h" 
// ajout de l'include brique pour la fonction getBriqueWithColor
#include "dependance/brique.h" 

FILE* open_with_dir(char* dir, char* name, char* mode) {
    char filename[256];
    snprintf(filename, sizeof(filename), "%s/%s", dir,name);
    printf("open file %s (%s)\n", filename, mode);
    FILE* fptr = fopen(filename, mode);
    assert(fptr!=NULL);
    return fptr;
}

int charToMask(char c) {
    assert(c>='0' && c<='9');
    return 1<<(c-'0');
}

int coordToMask(int dx, int dy, int W) {
    return 1<<(dx + W*dy);
}

int trou_str_to_int(char* buffer) {
    int T=0;
    for (int i=0; buffer[i]; i++)
        T += charToMask(buffer[i]);
    return T;
}

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

int getIndex(int x, int y, Image* I) {
    return y * I->W + x;
}

/**
 * Directions possibles (dir) :
 * 0 = haut, 1 = droite, 2 = bas, 3 = gauche
 */
int neighborIndex(int u, int dir, Image* I) {
    int x = u % I->W;
    int y = u / I->W;
    switch(dir) {
        case 0: y--; break; // haut
        case 1: x++; break; // droite
        case 2: y++; break; // bas
        case 3: x--; break; // gauche
        default: return -1;
    }
    if (x < 0 || x >= I->W || y < 0 || y >= I->H) return -1;
    return y * I->W + x;
}

// Fonction auxiliaire pour obtenir l'indice de brique pour une forme et une couleur
int getBrickFor(BriqueList* B, int shape, int col) {
    if (shape == -1) return -1;
    int idx = getBriqueWithColor(B, shape, col);
    if (idx >= 0) return idx;
    for (int k = 0; k < B->nBrique; k++)
        if (B->bShape[k] == shape) return k;
    return -1;
}

/* compte le score d'erreur pour une brique posée (réutilisé) */
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


/* vérifie que tous les pixels d’un rectangle sont encore uncovered */
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

/* marque un rectangle comme couvert */
void mark_rect_covered(int x0, int y0, int w, int h, Image* I, int* covered) {
    for (int dy = 0; dy < h; dy++)
        for (int dx = 0; dx < w; dx++)
            covered[getIndex(x0 + dx, y0 + dy, I)] = 1;
}

// Renvoie l’indice de couleur la plus proche pour un pixel
int closestColorIndex(Image* I, BriqueList* B, int x, int y) {
    RGB px = *get(I, x, y);
    int bestCol = 0;
    int bestErr = colError(B->col[0], px);
    for (int c = 1; c < B->nCol; c++) {
        int err = colError(B->col[c], px);
        if (err < bestErr) {
            bestErr = err;
            bestCol = c;
        }
    }
    return bestCol;
}

int cmpRent(const void* A, const void* B) {
    const BriqueRent* a = A;
    const BriqueRent* b = B;
    if (b->rentable > a->rentable) { 
        return 1;
    }
    if (b->rentable < a->rentable) { 
        return -1;
    }
    return 0;
}

int choisir_brique1x1_disponible(BriqueList* B, int col, int* used) {
    for (int i = 0; i < B->nBrique; i++) { 
        if (B->bShape[i] == 0 && B->bCol[i] == col && used[i] < B->bStock[i]) {
            return i;
        }
    }
    for (int i = 0; i < B->nBrique; i++) {
        if (B->bShape[i] == 0 && used[i] < B->bStock[i]) { 
            return i;
        }
    }
    for (int i = 0; i < B->nBrique; i++) { 
        if (B->bShape[i] == 0 && B->bCol[i] == col) { 
            return i;
        }
    }
    for (int i = 0; i < B->nBrique; i++) {
        if (B->bShape[i] == 0) { 
            return i;
        }
    }
    return -1;
}

int choisir_brique_shape_disponible_min_err(BriqueList* B, int shape, int col_pref, int x0, int y0, int rot, Image* I, int* used) {
    (void)col_pref; // permet d’indiquer au compilateur que col_pref est volontairement inutilisé
    int best = -1;
    int bestErr = INT_MAX;
    for (int i = 0; i < B->nBrique; i++) {
        if (B->bShape[i] != shape) continue;
        if (used[i] >= B->bStock[i]) continue;
        int e = compute_error_for_shape_at(i, x0, y0, rot, B, I);
        if (e < bestErr) { 
            bestErr = e; 
            best = i; 
        }
    }
    if (best != -1) return best;
    for (int i = 0; i < B->nBrique; i++) { 
        if (B->bShape[i] == shape) { 
            return i;
        }
    }
    return -1;
}

/* vérifie qu'un rectangle (x,y) largeur w hauteur h a tous les pixels avec closestColor = colTarget */
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