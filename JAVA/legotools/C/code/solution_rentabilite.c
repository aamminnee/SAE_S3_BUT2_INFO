#include <stdlib.h>
#include <stdio.h>
#include <limits.h>

#include "dependance/structure.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"
#include "dependance/solution_rentabilite.h"

// Seuil de tolérance pour la sélection des c
#define TOLERANCE_FAMILLE 3500
#define TOLERANCE_HOMOGENEITE 2500
#define MAX_FLOAT 9999999.0f

/**
 * Algorithme de rentabilité : cherche à couvrir l'image au coût minimal.
 * Pour chaque zone, il compare s'il est plus rentable de poser une grande brique
 * ou une multitude de petites briques 1x1.
 */

Solution run_algo_rentabilite(Image* I, BriqueList* B) {
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

    // Etape 1 : Calcul de la référence de prix en 1x1
    // Pour chaque pixel, on stocke le prix de la brique 1x1 la moins chère compatible
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            RGB pix = *get(I, x, y);
            float min_prix = MAX_FLOAT;

            for (int i = 0; i < B->nBrique; i++) {
                if (B->bShape[i] == shape11) {
                    // Vérification si la couleur est proche du pixel
                    if (colError(B->col[B->bCol[i]], pix) < TOLERANCE_FAMILLE) {
                        if (B->bPrix[i] < min_prix) {
                            min_prix = B->bPrix[i];
                        }
                    }
                }
            }
            // Si aucune brique n'est proche, on prend la moins chère absolue
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

    // Etape 2 : Préparation des formes disponibles
    Dimension* formes = malloc(B->nShape * sizeof(Dimension));
    int nb_formes = 0;
    for (int i = 0; i < B->nShape; i++) {
        int w = B->W[i];
        int h = B->H[i];
        if (w*h > 1) {
            formes[nb_formes].w = w;
            formes[nb_formes].h = h;
            formes[nb_formes].aire = w * h;
            nb_formes++;
        }
    }
    // Tri par taille pour essayer les plus grandes formes en premier
    qsort(formes, nb_formes, sizeof(Dimension), comparer_aire);

    // Etape 3 : Placement glouton basé sur l'économie
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            if (couvert[getIndex(x, y, I)]) {
                continue;
            }

            int place = 0;
            RGB pix_ref = *get(I, x, y);

            // On essaie les formes plus grandes d'abord
            for (int k = 0; k < nb_formes && !place; k++) {
                int w_base = formes[k].w;
                int h_base = formes[k].h;

                for (int rot = 0; rot < 2 && !place; rot++) {
                    int w = (rot == 0) ? w_base : h_base;
                    int h = (rot == 0) ? h_base : w_base;

                    // Vérification de la faisabilité
                    if (x + w > I->W || y + h > I->H) {
                        continue;
                    }

                    if (!rect_is_uncovered(x, y, w, h, I, couvert)) {
                        continue;
                    }
                    if (!is_area_compatible(I, x, y, w, h, pix_ref, TOLERANCE_HOMOGENEITE)) {
                        continue;
                    }

                    int id_shape = lookupShape(B, w, h);
                    if (id_shape == -1) {
                        continue;
                    }

                    int best_brique = -1;
                    float min_prix_brique = MAX_FLOAT;

                    // Trouver la brique la moins chère pour cette forme
                    for (int i = 0; i < B->nBrique; i++) {
                        if (B->bShape[i] == id_shape) {
                            if (colError(B->col[B->bCol[i]], pix_ref) < TOLERANCE_FAMILLE) {
                                if (B->bPrix[i] < min_prix_brique) {
                                    min_prix_brique = B->bPrix[i];
                                    best_brique = i;
                                }
                            }
                        }
                    }
                    if (best_brique != -1) {
                        // Calcul du coût si on utilisait des briques 1x1 à la place
                        float somme_petites = 0.0f;
                        for (int dy = 0; dy < h; dy++) {
                            for (int dx = 0; dx < w; dx++) {
                                somme_petites += cout_ref_1x1[getIndex(x + dx, y + dy, I)];
                            }
                        }

                        // On ne pose la grande brique que si elle coûte moins cher que les petites
                        if (min_prix_brique < somme_petites - 0.001f) {
                            push_sol_with_error(&S, best_brique, x, y, 0, I, B);
                            mark_rect_covered(x, y, w, h, I, couvert);
                            place = 1;
                        }
                    }
                }
            }
            
            // Etape 4 : Si aucune grande brique n'a été placée, on place une brique 1x1
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
                
                // Si aucune compatible, on prend la moins chère
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