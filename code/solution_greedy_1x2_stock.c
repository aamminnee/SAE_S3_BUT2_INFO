#include <stdlib.h>
#include <stdio.h>


#include "dependance/solution_1x1.h"
#include "dependance/solution.h"
#include "dependance/util.h"
#include "dependance/structure.h"
#include "dependance/brique.h"
#include "dependance/image.h"
#include "dependance/matching.h"

#define MAX_COLORS 275
static const int try_dirs[2] = {1, 2};

void greedyInsert_1x2_stock(Matching* M, int u, Image* I, BriqueList* B, int* visited) {
    if (getMatch(M, u) != UNMATCHED) return;
    int xu = u % I->W, yu = u / I->W;
    int cU = closestColorIndex(I, B, xu, yu);
    visited[u] = 1;
    for (int k = 0; k < 2; k++) {
        int d = try_dirs[k];
        int v = neighborIndex(u, d, I);
        if (v == -1 || visited[v]) continue;
        int xv = v % I->W, yv = v / I->W;
        int cV = closestColorIndex(I, B, xv, yv);
        int matchV = getMatch(M, v);
        if (matchV == UNMATCHED && cU == cV) { 
            addPair(M, u, v);
            return; 
        }
        if (matchV != UNMATCHED) {
            if (liberer(M, v, visited, I->W * I->H)) {
                int cV2 = closestColorIndex(I, B, xv, yv);
                if (cU == cV2) { 
                    addPair(M, u, v); 
                    return; 
                }
            }
        }
    }
}

Solution run_algo_greedy_1x2_stock(Image* I, BriqueList* B) {
    Solution S1 = run_algo_1x1(I, B);
    Matching M;
    initMatching(&M, I->W * I->H);
    int* visited = calloc(I->W * I->H, sizeof(int));
    for (int u = 0; u < I->W * I->H; u++) { for (int i = 0; i < I->W * I->H; i++) visited[i] = 0; greedyInsert_1x2_stock(&M, u, I, B, visited); }
    free(visited);
    Solution S;
    init_sol(&S, I);
    int shape2x1 = lookupShape(B, 2, 1);
    int shape1x1 = lookupShape(B, 1, 1);
    int* used = calloc(B->nBrique, sizeof(int));
    int brique11WithColor[MAX_COLORS];
    for (int i = 0; i < MAX_COLORS; i++) brique11WithColor[i] = -1;
    for (int i = 0; i < B->nBrique; i++) if (B->bShape[i] == shape1x1) brique11WithColor[B->bCol[i]] = i;
    for (int u = 0; u < I->W * I->H; u++) {
        int match = getMatch(&M, u);
        int xu = u % I->W, yu = u / I->W;
        int col = closestColorIndex(I, B, xu, yu);
        if (match == UNMATCHED) {
            int bri1 = brique11WithColor[col];
            if (bri1 == -1) {
                for (int k = 0; k < B->nBrique; k++) if (B->bShape[k] == shape1x1) { bri1 = k; break; }
            }
            int chosen = -1;
            if (bri1 != -1 && used[bri1] < B->bStock[bri1]) chosen = bri1;
            else chosen = choisir_brique1x1_disponible(B, col, used);
            if (chosen >= 0) { push_sol(&S, chosen, xu, yu, 0, I, B); used[chosen]++; }
            else { push_sol(&S, bri1, xu, yu, 0, I, B); if (bri1>=0) used[bri1]++; }
        } else if (match > u) {
            int v = match;
            int xv = v % I->W, yv = v / I->W;
            int x0 = (xu < xv) ? xu : xv;
            int y0 = (yu < yv) ? yu : yv;
            int rot = (yu == yv) ? 0 : 1;
            int b2 = -1;
            if (shape2x1 != -1) b2 = getBriqueWithColor(B, shape2x1, col);
            if (b2 >= 0 && used[b2] < B->bStock[b2]) {
                push_sol(&S, b2, x0, y0, rot, I, B);
                used[b2]++;
            } else {
                int alt = -1;
                if (shape2x1 != -1) alt = choisir_brique_shape_disponible_min_err(B, shape2x1, col, x0, y0, rot, I, used);
                if (alt >= 0 && used[alt] < B->bStock[alt]) {
                    push_sol(&S, alt, x0, y0, rot, I, B);
                    used[alt]++;
                } else {
                    int bri_u = brique11WithColor[col];
                    if (bri_u == -1) { for (int k = 0; k < B->nBrique; k++) if (B->bShape[k] == shape1x1) { bri_u = k; break; } }
                    int col_v = closestColorIndex(I, B, xv, yv);
                    int bri_v = brique11WithColor[col_v];
                    if (bri_v == -1) { for (int k = 0; k < B->nBrique; k++) if (B->bShape[k] == shape1x1) { bri_v = k; break; } }
                    int chosen_u = -1;
                    if (bri_u != -1 && used[bri_u] < B->bStock[bri_u]) chosen_u = bri_u;
                    else chosen_u = choisir_brique1x1_disponible(B, col, used);
                    int chosen_v = -1;
                    if (bri_v != -1 && used[bri_v] < B->bStock[bri_v]) chosen_v = bri_v;
                    else chosen_v = choisir_brique1x1_disponible(B, col_v, used);
                    if (chosen_u >= 0) { 
                        push_sol(&S, chosen_u, xu, yu, 0, I, B); used[chosen_u]++; 
                    } else if (bri_u >= 0) { 
                        push_sol(&S, bri_u, xu, yu, 0, I, B); 
                        if (bri_u>=0) { 
                            used[bri_u]++; 
                        }
                    }
                    if (chosen_v >= 0) { 
                        push_sol(&S, chosen_v, xv, yv, 0, I, B); 
                        used[chosen_v]++; 
                    } else if (bri_v >= 0) { 
                        push_sol(&S, bri_v, xv, yv, 0, I, B); 
                        if (bri_v>=0) { 
                            used[bri_v]++; 
                        }
                    }
                }
            }
        }
    }
    fill_sol_stock(&S, B);
    freeSolution(S1);
    freeMatching(&M);
    return S;
}
