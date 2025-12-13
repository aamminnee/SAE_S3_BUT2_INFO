#include <stdlib.h>
#include <stdio.h>

#include "dependance/solution_greedy_1x2.h"
#include "dependance/util.h"
#include "dependance/structure.h"
#include "dependance/brique.h"
#include "dependance/image.h"
#include "dependance/solution_1x1.h"
#include "dependance/matching.h"
#include "dependance/solution.h"


void greedyInsert(Matching* M, int u, Image* I) {
    if (getMatch(M, u) != UNMATCHED)
        return;
    RGB pxU = I->rgb[u];
    for (int d = 0; d < 2; d++) {
        int v = neighborIndex(u, d, I);
        if (v == -1) continue;
        if (getMatch(M, v) != UNMATCHED) continue;
        RGB pxV = I->rgb[v];
        if (pxU.R == pxV.R && pxU.G == pxV.G && pxU.B == pxV.B) {
            addPair(M, u, v);
            return;
        }
    }
}

Solution run_algo_greedy_1x2(Image* I, BriqueList* B) {
    // pavage 1x1
    Solution S1 = run_algo_1x1(I, B);
    Matching M;
    // glouton
    initMatching(&M, I->W * I->H);
    for (int u = 0; u < I->W * I->H; u++) { 
        greedyInsert(&M, u, I);
    }
    //solution
    Solution S;
    init_sol(&S, I);
    int shape2x1 = lookupShape(B, 2, 1);
    int shape1x1 = lookupShape(B, 1, 1);
    int brique11WithColor[MAX_COLORS];
    for (int i = 0; i < MAX_COLORS; i++) { 
        brique11WithColor[i] = -1;
    }
    for (int i = 0; i < B->nBrique; i++) { 
        if (B->bShape[i] == shape1x1) { 
            brique11WithColor[B->bCol[i]] = i;
        }
    }
    for (int u = 0; u < I->W * I->H; u++) {
        int match = getMatch(&M, u);
        int x = u % I->W;
        int y = u / I->W;
        int col = closestColorIndex(I, B, x, y);
        if (match == UNMATCHED) {
            push_sol(&S, brique11WithColor[col], x, y, 0, I, B);
        } else if (match > u) {
            int brique2x1 = getBriqueWithColor(B, shape2x1, col);
            int rot = (match == u + 1 ? 0 : 1);
            push_sol(&S, brique2x1, x, y, rot, I, B);
        }
    }
    fill_sol_stock(&S, B);
    freeSolution(S1);
    freeMatching(&M);
    return S;
}