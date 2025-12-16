#include <stdlib.h>
#include <stdio.h>
#include <limits.h>
#include "dependance/solution_classique.h"
#include "dependance/solution_1x1.h"
#include "dependance/matching.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"

#define MAX_COLORS_LOCAL 275

// fonction principale v4 SANS contrainte de stock, MAIS avec comptage de rupture
Solution run_algo_classique(Image* I, BriqueList* B) {
    int sh11 = lookupShape(B, 1, 1), map11[MAX_COLORS_LOCAL], i, j, k, x, y, c, pix, placed, npix = I->W * I->H;
    
    // Rétablissement de l'array 'usd' (stock utilisé)
    int *close = malloc(npix * sizeof(int)), *cov = calloc(npix, sizeof(int)), *usd = calloc(B->nBrique, sizeof(int));
    int nsh = 0, maxsh = 256;
    ShapeWH *shs = malloc(maxsh * sizeof(ShapeWH));
    Solution S;
    
    // verif memoire et init sol
    if (!close || !cov || !shs || !usd) { // Mise à jour de la vérification avec 'usd'
        perror("malloc"); exit(EXIT_FAILURE);
    }
    init_sol(&S, I);
    
    for (i = 0; i < MAX_COLORS_LOCAL; i++) {
        map11[i] = -1;
    }
    for (i = 0; i < B->nBrique; i++) {
        if (B->bShape[i] == sh11) {
            map11[B->bCol[i]] = i;
        }
        int s = B->bShape[i], w = B->W[s], h = B->H[s], f = 0;
        for (j = 0; j < nsh; j++) {
            if (shs[j].w == w && shs[j].h == h) f = 1;
        }
        if (!f) {
            if (nsh >= maxsh) {
                shs = realloc(shs, (maxsh *= 2) * sizeof(ShapeWH));
            }
            shs[nsh++] = (ShapeWH){w, h};
        }
    }
    
    // calcul couleurs ideales
    for (i = 0; i < npix; i++) {
        int best = -1, min_err = INT_MAX, err;
        for (c = 0; c < B->nCol; c++) {
            if (map11[c] != -1 && (err = colError(B->col[c], *get(I, i % I->W, i / I->W))) < min_err) {
                min_err = err; best = c;
            }
        }
        close[i] = best;
    }
    
    // tri formes decroissant
    for (i = 0; i < nsh; i++) {
        for (j = i + 1; j < nsh; j++) {
            if ((shs[j].w * shs[j].h > shs[i].w * shs[i].h) || (shs[j].w * shs[j].h == shs[i].w * shs[i].h && shs[j].w > shs[i].w)) {
                ShapeWH tmp = shs[i]; shs[i] = shs[j]; shs[j] = tmp;
            }
        }
    }
    
    // boucle principale placement
    for (y = 0; y < I->H; y++) {
        for (x = 0; x < I->W; x++) {
            if (cov[getIndex(x, y, I)]) {
                continue;
            }
            placed = 0; pix = close[getIndex(x, y, I)];
            // essai formes
            for (i = 0; i < nsh && !placed; i++) {
                for (int rot = 0; rot < 2 && !placed; rot++) {
                    int w = (rot) ? shs[i].h : shs[i].w, h = (rot) ? shs[i].w : shs[i].h;
                    int sh_id = lookupShape(B, shs[i].w, shs[i].h);
                    
                    // valid limites et uniformite
                    if (x + w > I->W || y + h > I->H || !rect_is_uncovered(x, y, w, h, I, cov) || !rect_has_uniform_closest(x, y, w, h, I, close, pix)) {
                        continue;
                    }
                    
                    // choix brique SANS contrainte de stock: on cherche le minimum d'erreur
                    int best = -1, min_e = INT_MAX;
                    
                    for (k = 0; k < B->nBrique; k++) {
                        int e;
                        if (B->bShape[k] == sh_id) { 
                            e = compute_error_for_shape_at(k, x, y, rot, B, I);
                            if (e < min_e) {
                                min_e = e; best = k;
                            }
                        }
                    }
                    
                    // placement si trouvé
                    if (best != -1) {
                        push_sol_with_error(&S, best, x, y, rot, I, B);
                        mark_rect_covered(x, y, w, h, I, cov);
                        
                        // Incrémentation du stock utilisé et du compteur de rupture
                        usd[best]++;
                        if (usd[best] > B->bStock[best]) {
                            S.stock++; // <-- Rupture incrémentée ici
                        }
                        
                        placed = 1;
                    }
                }
            }
            
            // repli sur 1x1 si besoin
            if (!placed) {
                int final = -1;
                int b1 = map11[pix]; // Brique 1x1 de couleur idéale
                
                if (b1 != -1) {
                    final = b1;
                } else {
                    // Sinon, on cherche n'importe quel 1x1
                    for (k = 0; k < B->nBrique; k++) {
                        if (B->bShape[k] == sh11) { final = k; break; } 
                    }
                }

                if (final != -1) {
                    push_sol_with_error(&S, final, x, y, 0, I, B);
                    
                    // Incrémentation du stock utilisé et du compteur de rupture
                    usd[final]++;
                    if (usd[final] > B->bStock[final]) {
                        S.stock++; // <-- Rupture incrémentée ici
                    }
                } else {
                    // Si aucune brique 1x1 n'existe, on place un trou (-1)
                    push_sol_with_error(&S, -1, x, y, 0, I, B);
                }

                cov[getIndex(x, y, I)] = 1;
            }
        }
    }
    
    // Suppression de l'appel à fill_sol_stock (car on gère le stock ici)
    free(close); free(cov); free(shs); free(usd);
    return S;
}