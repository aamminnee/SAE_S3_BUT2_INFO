#include <stdlib.h>
#include <stdio.h>
#include <limits.h>
#include "dependance/solution_v4_stock.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"

#define MAX_COLORS_LOCAL 275

// fonction principale v4 avec gestion de stock
Solution run_algo_v4_stock(Image* I, BriqueList* B) {
    int sh11 = lookupShape(B, 1, 1), map11[MAX_COLORS_LOCAL], i, j, k, x, y, c, pix, placed, npix = I->W * I->H;
    int *close = malloc(npix * sizeof(int)), *cov = calloc(npix, sizeof(int)), *usd = calloc(B->nBrique, sizeof(int));
    int nsh = 0, maxsh = 256;
    ShapeWH *shs = malloc(maxsh * sizeof(ShapeWH));
    Solution S;
    // verif memoire et init sol
    if (!close || !cov || !usd || !shs) {
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
                    // choix brique selon stock
                    int best = -1, ideal = getBrickFor(B, sh_id, pix), min_e = INT_MAX;
                    if (ideal != -1 && usd[ideal] < B->bStock[ideal]) {
                        best = ideal;
                    } else {
                        for (k = 0; k < B->nBrique; k++) {
                            int e;
                            if (B->bShape[k] == sh_id && usd[k] < B->bStock[k] && (e = compute_error_for_shape_at(k, x, y, rot, B, I)) < min_e) {
                                min_e = e; best = k;
                            }
                        }
                    }
                    // placement si trouve
                    if (best != -1) {
                        push_sol_with_error(&S, best, x, y, rot, I, B);
                        mark_rect_covered(x, y, w, h, I, cov);
                        usd[best]++; placed = 1;
                    }
                }
            }
            // repli sur 1x1 si besoin
            if (!placed) {
                int b1 = map11[pix], alt = choisir_brique1x1_disponible(B, pix, usd);
                int final = (b1 != -1 && usd[b1] < B->bStock[b1]) ? b1 : ((alt != -1) ? alt : b1);
                if (final == -1) {
                    for (k = 0; k < B->nBrique; k++) {
                        if (B->bShape[k] == sh11) { final = k; break; }
                    }
                }
                push_sol_with_error(&S, final, x, y, 0, I, B);
                if (final != -1) {
                    usd[final]++;
                }
                cov[getIndex(x, y, I)] = 1;
            }
        }
    }
    fill_sol_stock(&S, B);
    free(close); free(cov); free(shs); free(usd);
    return S;
}