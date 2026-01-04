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
    // Sécurité : Vérifier l'initialisation de S
    if (S.array == NULL && S.length != 0) {
        printf("Erreur: Solution non initialisée correctement dans init_sol.\n");
        exit(EXIT_FAILURE);
    }
    int npix = I->W * I->H;
    int* couvert = calloc(npix, sizeof(int));
    int* couleur_proche = malloc(npix * sizeof(int));
    if (!couvert || !couleur_proche) {
        perror("malloc");
        exit(EXIT_FAILURE);
    }
    // calcul de la meilleure couleur 1x1 pour chaque pixel
    int shape11 = lookupShape(B, 1, 1);
    if (shape11 == -1) {
        printf("CRITICAL ERROR: La forme '1-1' est introuvable dans briques.txt !\n");
        printf("Vérifiez le contenu de C/input/briques.txt\n");
        exit(EXIT_FAILURE);
    }
    int map11[MAX_COLORS];
    for(int i=0; i<MAX_COLORS; i++) map11[i] = -1;
    for(int i=0; i<B->nBrique; i++) {
        if (B->bShape[i] == shape11) {
            // Vérification bornes
            if(B->bCol[i] >= 0 && B->bCol[i] < MAX_COLORS) {
                map11[B->bCol[i]] = i;
            }
        }
    }
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            int meilleur_col = -1;
            int min_err = INT_MAX;
            RGB pix = *get(I, x, y);
            for (int c = 0; c < B->nCol; c++) {
                // On vérifie que c est dans les bornes de map11
                if (c >= MAX_COLORS || map11[c] == -1) continue; 
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
        // On ignore les formes invalides
        if (w <= 0 || h <= 0) continue;
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
            // Si aucune couleur proche n'a été trouvée (ex: pas de briques 1x1 dispos)
            if (col_cible == -1) {
                // On passe ce pixel (ou on arrête)
                continue;
            }
            int place = 0;
            for (int k = 0; k < nb_formes && !place; k++) {
                int w_base = formes[k].w;
                int h_base = formes[k].h;
                for (int rot = 0; rot < 2 && !place; rot++) {
                    int w = (rot == 0) ? w_base : h_base;
                    int h = (rot == 0) ? h_base : w_base;
                    if (x + w > I->W || y + h > I->H) continue;
                    if (!rect_is_uncovered(x, y, w, h, I, couvert)) continue;
                    if (!rect_has_uniform_closest(x, y, w, h, I, couleur_proche, col_cible)) continue;
                    int id_shape = lookupShape(B, w, h);
                    if (id_shape == -1) continue;
                    int id_brique = getBriqueWithColor(B, id_shape, col_cible);
                    if (id_brique != -1) {
                        push_sol_with_error(&S, id_brique, x, y, 0, I, B);
                        mark_rect_covered(x, y, w, h, I, couvert);
                        place = 1;
                    }
                }
            }
            // fallback sur 1x1 si aucune grande forme ne rentre
            if (!place) {
                int brique_1x1 = -1;
                if (col_cible >= 0 && col_cible < MAX_COLORS) {
                    brique_1x1 = map11[col_cible];
                }
                if (brique_1x1 == -1) {
                    // Dernier recours : chercher n'importe quelle brique 1x1
                    for(int i=0; i<B->nBrique; i++) {
                        if(B->bShape[i] == shape11) {
                            brique_1x1 = i;
                            break;
                        }
                    }
                }
                if (brique_1x1 != -1) {
                    push_sol_with_error(&S, brique_1x1, x, y, 0, I, B);
                    couvert[getIndex(x, y, I)] = 1;
                } else {
                    printf("Erreur fatale: Impossible de paver le pixel (%d,%d). Aucune brique 1x1 disponible.\n", x, y);
                    exit(EXIT_FAILURE);
                }
            }
        }
    }
    fill_sol_stock(&S, B);
    free(couvert);
    free(couleur_proche);
    free(formes);
    return S;
}