#include <stdlib.h>
#include <stdio.h>

#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/util.h"
#include "dependance/matching.h"
#include "dependance/solution.h"
#include "dependance/solution_1x1.h"
#include "dependance/solution_2x2.h"


#define MAX_COLORS_LOCAL 275


Solution run_algo_2x2(Image* I, BriqueList* B) {
    int shape11 = lookupShape(B, 1, 1);
    int brique11WithColor[MAX_COLORS_LOCAL];
    for (int i = 0; i < MAX_COLORS_LOCAL; i++) { 
        brique11WithColor[i] = -1;
    }
    for (int i = 0; i < B->nBrique; i++) { 
        if (B->bShape[i] == shape11) { 
            brique11WithColor[B->bCol[i]] = i;
        }
    }
    int* closestColor = malloc(I->W * I->H * sizeof(int));
    int totalError1x1 = 0;
    /* calcul couleur la plus proche pour chaque pixel (baseline 1x1) */
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            int bestCol = -1;
            int bestErr = INT_MAX;
            for (int c = 0; c < B->nCol; c++) {
                if (brique11WithColor[c] == -1) continue;
                int err = colError(B->col[c], *get(I, x, y));
                if (err < bestErr) { 
                    bestErr = err; bestCol = c; 
                }
            }
            closestColor[getIndex(x, y, I)] = bestCol;
            totalError1x1 += bestErr;
        }
    }
    /* détection des candidats 2x2 */
    int nx = (I->W + 1) / 2;
    int ny = (I->H + 1) / 2;
    int maxNodes = nx * ny;
    int *nodeX = calloc(maxNodes, sizeof(int));
    int *nodeY = calloc(maxNodes, sizeof(int));
    int *nodeColor = calloc(maxNodes, sizeof(int));
    int *nodeIdAt = calloc(I->W * I->H, sizeof(int));
    for (int i = 0; i < I->W * I->H; i++) nodeIdAt[i] = -1;
    int nNodes = 0;
    for (int y = 0; y + 1 < I->H; y += 2) {
        for (int x = 0; x + 1 < I->W; x += 2) {
            int c1 = closestColor[getIndex(x, y, I)];
            int c2 = closestColor[getIndex(x+1, y, I)];
            int c3 = closestColor[getIndex(x, y+1, I)];
            int c4 = closestColor[getIndex(x+1, y+1, I)];
            if (c1 != -1 && c1 == c2 && c1 == c3 && c1 == c4) {
                nodeX[nNodes] = x;
                nodeY[nNodes] = y;
                nodeColor[nNodes] = c1;
                nodeIdAt[getIndex(x,y,I)]     = nNodes;
                nodeIdAt[getIndex(x+1,y,I)]   = nNodes;
                nodeIdAt[getIndex(x,y+1,I)]   = nNodes;
                nodeIdAt[getIndex(x+1,y+1,I)] = nNodes;
                nNodes++;
            }
        }
    }
    /* appariement glouton horizontal pour 4x2 */
    Matching M2;
    initMatching(&M2, nNodes);
    for (int u = 0; u < nNodes; u++) {
        if (getMatch(&M2, u) != UNMATCHED) continue;
        int xu = nodeX[u], yu = nodeY[u];
        for (int v = 0; v < nNodes; v++) {
            if (u == v) continue;
            if (nodeY[v] != yu) continue;
            if (nodeX[v] != xu + 2) continue;
            if (nodeColor[u] != nodeColor[v]) continue;
            if (getMatch(&M2, v) != UNMATCHED) continue;
            addPair(&M2, u, v);
            break;
        }
    }
    int *covered = calloc(I->W * I->H, sizeof(int));
    Solution S;
    init_sol(&S, I);
    int shape2x2 = lookupShape(B, 2, 2);
    int shape4x2 = lookupShape(B, 4, 2);
    int shape2x1 = lookupShape(B, 2, 1);
    for (int u = 0; u < nNodes; u++) {
        int match = getMatch(&M2, u);
        if (match == UNMATCHED) continue;
        if (match < u) continue;
        int v = match;
        int xu = nodeX[u], yu = nodeY[u];
        int xv = nodeX[v], yv = nodeY[v];
        int x0 = (xu < xv) ? xu : xv;
        int y0 = yu;
        int col = nodeColor[u];
        /* tenter d’utiliser une brique 4x2 si possible */
        if (shape4x2 != -1) {
            int b4 = getBrickFor(B, shape4x2, col);
            if (b4 >= 0) {
                push_sol_with_error(&S, b4, x0, y0, 0, I, B);
                for (int dy = 0; dy < 2; dy++)
                    for (int dx = 0; dx < 4; dx++)
                        covered[getIndex(x0+dx, y0+dy, I)] = 1;
                continue;
            }
        }
        /* sinon deux 2x2 ou fallback 1x1 */
        int b2 = getBrickFor(B, shape2x2, col);
        if (b2 >= 0) {
            push_sol_with_error(&S, b2, xu, yu, 0, I, B);
            push_sol_with_error(&S, b2, xv, yv, 0, I, B);
            for (int k = 0; k < 2; k++) { 
                for (int dy = 0; dy < 2; dy++) { 
                    for (int dx = 0; dx < 2; dx++) { 
                        covered[getIndex((k==0?xu:xv)+dx, yu+dy, I)] = 1;
                    }
                }
            }
        } else {
            for (int kx = xu; kx <= xv+1; kx++) { 
                for (int ky = yu; ky <= yu+1; ky++) {
                    int colpix = closestColor[getIndex(kx, ky, I)];
                    int bri1 = brique11WithColor[colpix];
                    if (bri1 == -1)
                        for (int t = 0; t < B->nBrique; t++)
                            if (B->bShape[t] == shape11) { bri1 = t; break; }
                    push_sol_with_error(&S, bri1, kx, ky, 0, I, B);
                    covered[getIndex(kx, ky, I)] = 1;
                }
            }
        }
    }
    int Npixels = I->W * I->H;
    Matching M1;
    initMatching(&M1, Npixels);
    for (int u = 0; u < Npixels; u++) {
        if (covered[u]) continue;
        int x = u % I->W, y = u / I->W;
        if (x + 1 < I->W && !covered[getIndex(x+1,y,I)]) {
            RGB p1 = *get(I, x, y);
            RGB p2 = *get(I, x+1, y);
            if (p1.R==p2.R && p1.G==p2.G && p1.B==p2.B) { 
                addPair(&M1, u, getIndex(x+1, y, I));
            }
        }
        if (y + 1 < I->H && !covered[getIndex(x,y+1,I)]) {
            RGB p1 = *get(I, x, y);
            RGB p2 = *get(I, x, y+1);
            if (p1.R==p2.R && p1.G==p2.G && p1.B==p2.B) { 
                addPair(&M1, u, getIndex(x, y+1, I));
            }
        }
    }
    for (int u = 0; u < Npixels; u++) {
        if (covered[u]) continue;
        int match = getMatch(&M1, u);
        int x = u % I->W, y = u / I->W;
        int colpix = closestColor[getIndex(x, y, I)];
        if (match == UNMATCHED) {
            int bri1 = brique11WithColor[colpix];
            if (bri1 == -1) for (int t = 0; t < B->nBrique; t++) { 
                if (B->bShape[t] == shape11) {
                    bri1 = t; 
                    break; 
                }
            }
            push_sol_with_error(&S, bri1, x, y, 0, I, B);
            covered[u] = 1;
        } else if (match > u) {
            int v = match;
            int xv = v % I->W, yv = v / I->W;
            int x0 = (x < xv) ? x : xv;
            int y0 = (y < yv) ? y : yv;
            int rot = (y == yv) ? 0 : 1;
            int b2 = -1;
            if (shape2x1 != -1) { 
                b2 = getBriqueWithColor(B, shape2x1, colpix);
            }
            if (b2 >= 0) { 
                push_sol_with_error(&S, b2, x0, y0, rot, I, B);
            }
            else {
                int bri1 = brique11WithColor[colpix];
                if (bri1 == -1) for (int t = 0; t < B->nBrique; t++)
                    if (B->bShape[t] == shape11) { bri1 = t; break; }
                push_sol_with_error(&S, bri1, x, y, 0, I, B);
                int colv = closestColor[getIndex(xv, yv, I)];
                int bri2 = brique11WithColor[colv];
                if (bri2 == -1) {
                    for (int t = 0; t < B->nBrique; t++) {
                        if (B->bShape[t] == shape11) {
                            bri2 = t;
                            break;
                        }
                    }
                }
                push_sol_with_error(&S, bri2, xv, yv, 0, I, B);
            }
            covered[u] = 1;
            covered[v] = 1;
        }
    }
    fill_sol_stock(&S, B);
    free(closestColor);
    free(nodeX);
    free(nodeY); 
    free(nodeColor); 
    free(nodeIdAt);
    freeMatching(&M2);
    freeMatching(&M1);
    free(covered);
    return S;
}
