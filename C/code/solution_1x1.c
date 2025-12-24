#include <stdlib.h>
#include <stdio.h>

#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"
#include "dependance/solution_1x1.h"

Solution run_algo_1x1(Image* I, BriqueList* B) {
    Solution S;
    init_sol(&S, I);
    int shape11 = lookupShape(B, 1, 1);
    int brique11WithColor[MAX_COLORS];
    for (int i=0; i<MAX_COLORS; i++)
        brique11WithColor[i] = -1;
    for (int i=0; i<B->nBrique; i++)
        if (B->bShape[i] == shape11)
            brique11WithColor[B->bCol[i]] = i;
    int* closestColor = malloc(I->W * I->H * sizeof(int));
    int totalError = 0;
    for (int y=0; y<I->H; y++)
        for (int x=0; x<I->W; x++) {
            int bestCol = -1;
            int bestErr = INT_MAX;
            for (int c=0; c<B->nCol; c++) {
                if (brique11WithColor[c] == -1) continue;
                int err = colError(B->col[c], *get(I,x,y));
                if (err < bestErr) {
                    bestErr = err;
                    bestCol = c;
                }
            }
            closestColor[getIndex(x,y,I)] = bestCol;
            totalError += bestErr;
        }
    S.totalError = totalError;
    for (int y=0; y<I->H; y++) { 
        for (int x=0; x<I->W; x++) {
            int idx = getIndex(x,y,I);
            int bri = brique11WithColor[closestColor[idx]];
            push_sol(&S, bri, x, y, 0, I, B);
        }
    }
    fill_sol_stock(&S, B);
    free(closestColor);
    return S;
}

