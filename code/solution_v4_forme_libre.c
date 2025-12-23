#include <stdlib.h>
#include <stdio.h>
#include <limits.h>

#include "dependance/structure.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"
#include "dependance/solution_v4_forme_libre.h"


Solution run_algo_v4_forme_libre(Image* I, BriqueList* B) {
    Solution S;
    init_sol(&S, I);
    int npix = I->W * I->H;
    int* couvert = calloc(npix, sizeof(int));
    int* couleur_proche = malloc(npix * sizeof(int));
    if (!couvert || !couleur_proche) {
        perror("malloc");
        exit(EXIT_FAILURE);
    }
    // calcul de la meilleure couleur 1x1 pour chaque pixel
    int shape11 = lookupShape(B, 1, 1);
    int map11[MAX_COLORS];
    for(int i=0; i<MAX_COLORS; i++) map11[i] = -1;
    for(int i=0; i<B->nBrique; i++) {
        if (B->bShape[i] == shape11) {
            map11[B->bCol[i]] = i;
        }
    }
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            int meilleur_col = -1;
            int min_err = INT_MAX;
            RGB pix = *get(I, x, y);
            for (int c = 0; c < B->nCol; c++) {
                if (map11[c] == -1) continue; 
                int err = colError(B->col[c], pix);
                if (err < min_err) {
                    min_err = err;
                    meilleur_col = c;
                }
            }
            couleur_proche[getIndex(x, y, I)] = meilleur_col;
        }
    }
    // recenser toutes les formes disponibles dans briques.txt
    int max_formes = B->nShape; 
    Dimension* formes = malloc(max_formes * sizeof(Dimension));
    int nb_formes = 0;
    for (int i = 0; i < B->nShape; i++) {
        int w = B->W[i];
        int h = B->H[i];
        formes[nb_formes].w = w;
        formes[nb_formes].h = h;
        formes[nb_formes].aire = w * h;
        nb_formes++;
    }
    qsort(formes, nb_formes, sizeof(Dimension), comparer_aire);
    // placement glouton des formes
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            if (couvert[getIndex(x, y, I)]) continue;
            int col_cible = couleur_proche[getIndex(x, y, I)];
            int place = 0;
            for (int k = 0; k < nb_formes && !place; k++) {
                int w_base = formes[k].w;
                int h_base = formes[k].h;
                for (int rot = 0; rot < 2 && !place; rot++) {
                    // gestion dimensions selon rotation
                    int w = (rot == 0) ? w_base : h_base;
                    int h = (rot == 0) ? h_base : w_base;
                    // verif 1 : limites image
                    if (x + w > I->W || y + h > I->H) continue;
                    // verif 2 : zone non couverte
                    if (!rect_is_uncovered(x, y, w, h, I, couvert)) continue;
                    // verif 3 : uniformite de couleur (toute la zone veut la meme couleur)
                    if (!rect_has_uniform_closest(x, y, w, h, I, couleur_proche, col_cible)) continue;
                    // identification de la forme dans la BriqueList
                    int id_shape = lookupShape(B, w, h);
                    if (id_shape == -1) continue; // ne devrait pas arriver
                    // chercher si une brique de cette forme et couleur existe
                    int id_brique = getBriqueWithColor(B, id_shape, col_cible);
                    if (id_brique != -1) {
                        // on place la brique
                        push_sol_with_error(&S, id_brique, x, y, 0, I, B); // rot est 0 car w/h sont deja ajustes
                        mark_rect_covered(x, y, w, h, I, couvert);
                        place = 1;
                    }
                }
            }
            // fallback sur 1x1 si aucune grande forme ne rentre
            if (!place) {
                int brique_1x1 = map11[col_cible];
                // si pas de couleur exacte, on prend n'importe quelle 1x1 (ne devrait pas arriver avec map11)
                if (brique_1x1 == -1) {
                    for(int i=0; i<B->nBrique; i++) {
                        if(B->bShape[i] == shape11) {
                            brique_1x1 = i;
                            break;
                        }
                    }
                }
                push_sol_with_error(&S, brique_1x1, x, y, 0, I, B);
                couvert[getIndex(x, y, I)] = 1;
            }
        }
    }
    fill_sol_stock(&S, B);
    free(couvert);
    free(couleur_proche);
    free(formes);
    return S;
}