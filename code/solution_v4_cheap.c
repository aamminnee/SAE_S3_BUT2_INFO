#include <stdlib.h>
#include <stdio.h>
#include <limits.h>

#include "dependance/structure.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"
#include "dependance/solution_v4_cheap.h"

#define POIDS_PRIX 50000

// fonction principale de l'algo v4 cheap (compromis rentabilité/qualité)
Solution run_algo_v4_cheap(Image* I, BriqueList* B) {
    Solution S;
    init_sol(&S, I);
    int npix = I->W * I->H;
    int* couvert = calloc(npix, sizeof(int));
    int* couleur_proche = malloc(npix * sizeof(int));
    // verification des allocations
    if (!couvert || !couleur_proche) {
        perror("malloc");
        exit(EXIT_FAILURE);
    }
    // etape 1 : calcul de la densité de prix pour chaque couleur
    // on cherche combien coûte 1 pixel de cette couleur avec la brique la plus rentable disponible
    int shape11 = lookupShape(B, 1, 1);
    int map11[MAX_COLORS];
    double prix_densite[MAX_COLORS];
    for(int i=0; i<MAX_COLORS; i++) {
        map11[i] = -1;
        prix_densite[i] = -1.0; 
    }
    // d'abord on regarde les briques 1x1 (obligatoires pour le fallback)
    for(int i=0; i<B->nBrique; i++) {
        if (B->bShape[i] == shape11) {
            map11[B->bCol[i]] = i;
            prix_densite[B->bCol[i]] = (double)B->bPrix[i];
        }
    }
    // ensuite on regarde si des grosses briques offrent un meilleur prix au pixel pour cette couleur
    for(int i=0; i<B->nBrique; i++) {
        int col = B->bCol[i];
        if (map11[col] != -1) { // on ne traite que les couleurs qui ont aussi une 1x1 (sécurité)
            int s = B->bShape[i];
            int aire = B->W[s] * B->H[s];
            if (aire > 0) {
                double densite = (double)B->bPrix[i] / (double)aire;
                if (densite < prix_densite[col]) {
                    prix_densite[col] = densite;
                }
            }
        }
    }
    // etape 2 : choix de la couleur cible pour chaque pixel
    // on cherche le meilleur compromis : Erreur + (Prix * Poids)
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            int meilleur_col = -1;
            long long min_score = LLONG_MAX;
            RGB pix = *get(I, x, y);
            for (int c = 0; c < B->nCol; c++) {
                if (map11[c] == -1) continue; // couleur non disponible en 1x1, on ignore
                // calcul de l'erreur visuelle
                int err = colError(B->col[c], pix);
                long long score = (long long)err + (long long)(prix_densite[c] * POIDS_PRIX);

                if (score < min_score) {
                    min_score = score;
                    meilleur_col = c;
                }
            }
            couleur_proche[getIndex(x, y, I)] = meilleur_col;
        }
    }
    // etape 3 : tri des formes par aire décroissante
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
    // etape 4 : placement glouton
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            if (couvert[getIndex(x, y, I)]) continue;
            int col_cible = couleur_proche[getIndex(x, y, I)];
            int place = 0;
            // on essaie les formes
            for (int k = 0; k < nb_formes && !place; k++) {
                int w_base = formes[k].w;
                int h_base = formes[k].h;
                for (int rot = 0; rot < 2 && !place; rot++) {
                    int w = (rot == 0) ? w_base : h_base;
                    int h = (rot == 0) ? h_base : w_base;
                    if (x + w > I->W || y + h > I->H) continue;
                    if (!rect_is_uncovered(x, y, w, h, I, couvert)) continue;
                    // on vérifie l'uniformité par rapport à notre carte de couleurs optimisée
                    if (!rect_has_uniform_closest(x, y, w, h, I, couleur_proche, col_cible)) continue;
                    int id_shape = lookupShape(B, w, h);
                    if (id_shape == -1) continue;
                    // on prend la brique de la bonne forme et de la couleur cible
                    int id_brique = getBriqueWithColor(B, id_shape, col_cible);
                    if (id_brique != -1) {
                        push_sol_with_error(&S, id_brique, x, y, 0, I, B);
                        mark_rect_covered(x, y, w, h, I, couvert);
                        place = 1;
                    }
                }
            }
            // fallback 1x1
            if (!place) {
                int brique_1x1 = map11[col_cible];
                if (brique_1x1 == -1) {
                    // sécurité ultime
                    for(int i=0; i<B->nBrique; i++) {
                        if(B->bShape[i] == shape11) { brique_1x1 = i; break; }
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