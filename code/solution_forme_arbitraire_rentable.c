#include <stdlib.h>
#include <stdio.h>

#include "dependance/structure.h"
#include "dependance/matching.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"
#include "dependance/solution_1x1.h"
#include "dependance/solution_2x2.h"

#define SURFACE_SEUIL 3  // seuil pour la premiere phase de l'algo principale


Solution run_algo_forme_rentable(Image* I, BriqueList* B) {
    int Npix = I->W * I->H;
    int* covered = calloc(Npix, sizeof(int));
    if (!covered) { 
        perror("calloc"); 
        exit(EXIT_FAILURE); 
    }
    Solution S;
    init_sol(&S, I);
    BriqueRent* R = malloc(B->nBrique * sizeof(BriqueRent));
    if (!R) { 
        perror("malloc");
        exit(EXIT_FAILURE); 
    }
    for (int i = 0; i < B->nBrique; i++) {
        int shape = B->bShape[i];
        int w = B->W[shape];
        int h = B->H[shape];
        int price = B->bPrix[i];
        int surface = w * h;
        R[i].iBrique = i;
        R[i].w = w;
        R[i].h = h;
        R[i].shape = shape;
        R[i].col = B->bCol[i];
        R[i].price = price;
        R[i].surface = surface;
        // rentabilité = prix / surface
        R[i].rentable = (float)price / (float)surface;
    }
    // Trier décroissant par rentabilité
    qsort(R, B->nBrique, sizeof(BriqueRent), cmpRent);
    for (int rb = 0; rb < B->nBrique; rb++) {
        if (R[rb].surface < SURFACE_SEUIL)
            continue; // ignorer les petites briques
        int iBrique = R[rb].iBrique;
        int w = R[rb].w;
        int h = R[rb].h;
        int shape = R[rb].shape;
        for (int y = 0; y < I->H; y++) {
            for (int x = 0; x < I->W; x++) {
                for (int rot = 0; rot < 2; rot++) {
                    int ww = (rot == 0 ? w : h);
                    int hh = (rot == 0 ? h : w);

                    if (x + ww > I->W || y + hh > I->H) continue;

                    // Vérifie que le rectangle n'est pas encore couvert
                    if (!rect_is_uncovered(x, y, ww, hh, I, covered)) continue;

                    int ok = 1;
                    for (int dy = 0; dy < hh && ok; dy++) {
                        for (int dx = 0; dx < ww && ok; dx++) {
                            int px = (rot == 0) ? x + dx : x + dy;
                            int py = (rot == 0) ? y + dy : y + dx;
                            RGB target = *get(I, px, py);
                            RGB brick  = B->col[R[rb].col];
                            if (colError(brick, target) != 0) {  
                                ok = 0;
                            }
                        }
                    }
                    if (!ok) continue;
                    push_sol_with_error(&S, iBrique, x, y, rot, I, B);
                    mark_rect_covered(x, y, ww, hh, I, covered);
                }
            }
        }
    }
    int shape11 = lookupShape(B, 1, 1);
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            if (!covered[getIndex(x,y,I)]) {
                RGB pix = *get(I,x,y);
                int best = -1;
                int bestErr = INT_MAX;
                for (int i = 0; i < B->nBrique; i++) {
                    if (B->bShape[i] != shape11) continue;
                    int col = B->bCol[i];
                    int err = colError(pix, B->col[col]);
                    if (err < bestErr) {
                        best = i;
                        bestErr = err;
                    }
                }
                push_sol_with_error(&S, best, x, y, 0, I, B);
                covered[getIndex(x,y,I)] = 1;
            }
        }
    }
    free(R);
    free(covered);
    return S;
}
