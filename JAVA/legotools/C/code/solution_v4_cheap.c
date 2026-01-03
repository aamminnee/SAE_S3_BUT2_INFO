#include <stdlib.h>
#include <stdio.h>
#include <limits.h>

#include "dependance/structure.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"
#include "dependance/solution_v4_cheap.h"

// Seuil de tolérance pour considérer qu'un pixel appartient à la "famille"
#define TOLERANCE_FAMILLE 3500

// Seuil strict pour l'homogénéité de la zone sur l'image
#define TOLERANCE_HOMOGENEITE 2500

#define MAX_FLOAT 9999999.0f

Solution run_algo_v4_cheap(Image* I, BriqueList* B) {
    Solution S;
    init_sol(&S, I);
    int npix = I->W * I->H;
    int* couvert = calloc(npix, sizeof(int));
    float* cout_ref_1x1 = malloc(npix * sizeof(float));
    if (!couvert || !cout_ref_1x1) {
        perror("malloc");
        exit(EXIT_FAILURE);
    }
    int shape11 = lookupShape(B, 1, 1);
    // On cherche le coût minimal pour couvrir chaque pixel avec une 1x1 "acceptable"
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            RGB pix = *get(I, x, y);
            float min_prix = MAX_FLOAT;
            // On cherche la brique 1x1 la moins chère compatible
            for (int i = 0; i < B->nBrique; i++) {
                if (B->bShape[i] == shape11) {
                    if (colError(B->col[B->bCol[i]], pix) < TOLERANCE_FAMILLE) {
                        if (B->bPrix[i] < min_prix) {
                            min_prix = B->bPrix[i];
                        }
                    }
                }
            }
            // Fallback : si rien trouvé dans la tolérance, on prend le moins cher tout court
            if (min_prix == MAX_FLOAT) {
                 for (int i = 0; i < B->nBrique; i++) {
                    if (B->bShape[i] == shape11 && B->bPrix[i] < min_prix) {
                        min_prix = B->bPrix[i];
                    }
                 }
            }
            cout_ref_1x1[getIndex(x, y, I)] = min_prix;
        }
    }
    Dimension* formes = malloc(B->nShape * sizeof(Dimension));
    int nb_formes = 0;
    for (int i = 0; i < B->nShape; i++) {
        int w = B->W[i];
        int h = B->H[i];
        if (w*h > 1) { // On exclut les 1x1 ici, on les gère en fallback
            formes[nb_formes].w = w;
            formes[nb_formes].h = h;
            formes[nb_formes].aire = w * h;
            nb_formes++;
        }
    }
    qsort(formes, nb_formes, sizeof(Dimension), comparer_aire);

    // --- ETAPE 3 : Placement Glouton ---
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            if (couvert[getIndex(x, y, I)]) continue;

            int place = 0;
            RGB pix_ref = *get(I, x, y); // La couleur du pixel coin haut-gauche sert de guide

            // On essaie les formes (Grand -> Petit)
            for (int k = 0; k < nb_formes && !place; k++) {
                int w_base = formes[k].w;
                int h_base = formes[k].h;
                for (int rot = 0; rot < 2 && !place; rot++) {
                    int w = (rot == 0) ? w_base : h_base;
                    int h = (rot == 0) ? h_base : w_base;
                    if (x + w > I->W || y + h > I->H) continue;
                    if (!rect_is_uncovered(x, y, w, h, I, couvert)) continue;
                    if (!is_area_compatible(I, x, y, w, h, pix_ref, TOLERANCE_HOMOGENEITE)) continue;

                    int id_shape = lookupShape(B, w, h);
                    if (id_shape == -1) continue;
                    int best_brique = -1;
                    float min_prix_brique = MAX_FLOAT;
                    for (int i = 0; i < B->nBrique; i++) {
                        if (B->bShape[i] == id_shape) {
                            // Est-ce que cette brique est dans la "famille" de couleur ?
                            if (colError(B->col[B->bCol[i]], pix_ref) < TOLERANCE_FAMILLE) {
                                // Est-ce la moins chère ?
                                if (B->bPrix[i] < min_prix_brique) {
                                    min_prix_brique = B->bPrix[i];
                                    best_brique = i;
                                }
                            }
                        }
                    }
                    if (best_brique != -1) {
                        float somme_petites = 0.0f;
                        for (int dy = 0; dy < h; dy++) {
                            for (int dx = 0; dx < w; dx++) {
                                somme_petites += cout_ref_1x1[getIndex(x + dx, y + dy, I)];
                            }
                        }
                        // Si c'est rentable (strictement moins cher), on prend !
                        if (min_prix_brique < somme_petites - 0.001f) {
                            push_sol_with_error(&S, best_brique, x, y, 0, I, B);
                            mark_rect_covered(x, y, w, h, I, couvert);
                            place = 1;
                        }
                    }
                }
            }
            // Fallback : Brique 1x1
            if (!place) {
                int best_1x1 = -1;
                float min_p = MAX_FLOAT;
                for (int i = 0; i < B->nBrique; i++) {
                    if (B->bShape[i] == shape11) {
                         if (colError(B->col[B->bCol[i]], pix_ref) < TOLERANCE_FAMILLE) {
                             if (B->bPrix[i] < min_p) {
                                 min_p = B->bPrix[i];
                                 best_1x1 = i;
                             }
                         }
                    }
                }
                // 2. Si aucune compatible, la moins chère absolue
                if (best_1x1 == -1) {
                    for (int i = 0; i < B->nBrique; i++) {
                        if (B->bShape[i] == shape11) {
                             if (B->bPrix[i] < min_p) {
                                 min_p = B->bPrix[i];
                                 best_1x1 = i;
                             }
                        }
                    }
                }
                if (best_1x1 != -1) {
                    push_sol_with_error(&S, best_1x1, x, y, 0, I, B);
                } else {
                    push_sol_with_error(&S, -1, x, y, 0, I, B);
                }
                couvert[getIndex(x, y, I)] = 1;
            }
        }
    }
    fill_sol_stock(&S, B);
    free(couvert);
    free(cout_ref_1x1);
    free(formes);
    return S;
}