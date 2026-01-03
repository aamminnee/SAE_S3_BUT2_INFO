#include <stdlib.h>
#include <stdio.h>
#include <limits.h>
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"
#include "dependance/solution_v4_rupture.h"

#define MAX_COLORS_LOCAL 275

// fonction principale v4 avec priorité absolue à la qualité
Solution run_algo_v4_rupture(Image* I, BriqueList* B) {
    int sh11 = lookupShape(B, 1, 1), map11[MAX_COLORS_LOCAL], i, j, k, x, y, c, pix, placed, npix = I->W * I->H;
    int *close = malloc(npix * sizeof(int)), *cov = calloc(npix, sizeof(int)), *usd = calloc(B->nBrique, sizeof(int));
    int nsh = 0, maxsh = 256;
    ShapeWH *shs = malloc(maxsh * sizeof(ShapeWH));
    Solution S;
    if (!close || !cov || !shs || !usd) { 
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
    for (i = 0; i < npix; i++) {
        int best = -1, min_err = INT_MAX, err;
        for (c = 0; c < B->nCol; c++) {
            if (map11[c] != -1 && (err = colError(B->col[c], *get(I, i % I->W, i / I->W))) < min_err) {
                min_err = err; best = c;
            }
        }
        close[i] = best;
    }
    for (i = 0; i < nsh; i++) {
        for (j = i + 1; j < nsh; j++) {
            if ((shs[j].w * shs[j].h > shs[i].w * shs[i].h) || (shs[j].w * shs[j].h == shs[i].w * shs[i].h && shs[j].w > shs[i].w)) {
                ShapeWH tmp = shs[i]; shs[i] = shs[j]; shs[j] = tmp;
            }
        }
    }
    for (y = 0; y < I->H; y++) {
        for (x = 0; x < I->W; x++) {
            if (cov[getIndex(x, y, I)]) {
                continue;
            }
            placed = 0; pix = close[getIndex(x, y, I)];
            for (i = 0; i < nsh && !placed; i++) {
                for (int rot = 0; rot < 2 && !placed; rot++) {
                    int w = (rot) ? shs[i].h : shs[i].w, h = (rot) ? shs[i].w : shs[i].h;
                    int sh_id = lookupShape(B, shs[i].w, shs[i].h);
                    if (x + w > I->W || y + h > I->H || !rect_is_uncovered(x, y, w, h, I, cov) || !rect_has_uniform_closest(x, y, w, h, I, close, pix)) {
                        continue;
                    }
                    // calcul de l'erreur de référence si on utilisait des 1x1 (qualité optimale)
                    long long ref_err = 0;
                    for(int dy = 0; dy < h; dy++){
                        for(int dx = 0; dx < w; dx++){
                            ref_err += (long long)colError(B->col[pix], *get(I, x+dx, y+dy));
                        }
                    }
                    int best = -1;
                    long long min_e = LLONG_MAX; // utilisation de long long par sécurité
                    for (k = 0; k < B->nBrique; k++) {
                        if (B->bShape[k] == sh_id) { 
                            // on calcule l'erreur potentielle de cette grosse brique
                            long long e = (long long)compute_error_for_shape_at(k, x, y, rot, B, I);
                            if (e < min_e) {
                                min_e = e; best = k;
                            }
                        }
                    }
                    // placement seulement si la grosse brique ne dégrade pas la qualité par rapport aux 1x1
                    if (best != -1) {
                        // si l'erreur de la grosse brique est supérieure à l'erreur des 1x1, on refuse ce placement
                        // on veut la qualité à tout prix (rupture de stock ou pas)
                        if (min_e <= ref_err) {
                            push_sol_with_error(&S, best, x, y, rot, I, B);
                            mark_rect_covered(x, y, w, h, I, cov);
                            // incr stock utilisé et compteur rupture
                            usd[best]++;
                            if (usd[best] > B->bStock[best]) {
                                S.stock++; 
                            }
                            placed = 1;
                        }
                    }
                }
            }
            // repli sur 1x1 si besoin
            if (!placed) {
                int final = -1;
                int b1 = map11[pix]; // brique 1x1 de couleur idéale
                if (b1 != -1) {
                    final = b1;
                } else {
                    // sinon, on cherche n'importe quel 1x1
                    for (k = 0; k < B->nBrique; k++) {
                        if (B->bShape[k] == sh11) { final = k; break; } 
                    }
                }
                if (final != -1) {
                    push_sol_with_error(&S, final, x, y, 0, I, B);
                    // incr stock utilisé et compteur rupture
                    usd[final]++;
                    if (usd[final] > B->bStock[final]) {
                        S.stock++; 
                    }
                } else {
                    // si aucune brique 1x1 n'existe, on place un trou (-1)
                    push_sol_with_error(&S, -1, x, y, 0, I, B);
                }
                cov[getIndex(x, y, I)] = 1;
            }
        }
    }
    // liberation
    free(close); free(cov); free(shs); free(usd);
    return S;
}