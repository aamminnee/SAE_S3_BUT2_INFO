#include <stdlib.h>
#include <stdio.h>

#include "dependance/solution_2x2.h" 
#include "dependance/solution_1x1.h"
#include "dependance/matching.h"
#include "dependance/util.h"
#include "dependance/image.h"
#include "dependance/brique.h"
#include "dependance/solution.h"

#define MAX_COLORS_LOCAL 275

/*
   - pose gloutonne en priorité des formes plus larges disponibles
   - gère 4x2, 3x2, 2x2, 2x1/1x2, 1x1 et toute forme rectangulaire disponible
*/
Solution run_algo_rectfusion(Image* I, BriqueList* B) {
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
    int Npix = I->W * I->H;
    int* closestColor = malloc(Npix * sizeof(int));
    if (!closestColor) { 
        perror("malloc"); 
        exit(EXIT_FAILURE); 
    }
    int totalError1x1 = 0;
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            int bestCol = -1;
            int bestErr = INT_MAX;
            for (int c = 0; c < B->nCol; c++) {
                if (brique11WithColor[c] == -1) continue;
                int err = colError(B->col[c], *get(I, x, y));
                if (err < bestErr) {
                    bestErr = err; 
                    bestCol = c; 
                }
            }
            closestColor[getIndex(x, y, I)] = bestCol;
            totalError1x1 += bestErr;
        }
    }
    int* covered = calloc(Npix, sizeof(int));
    if (!covered) { 
        perror("calloc"); 
        exit(EXIT_FAILURE); 
    }
    Solution S;
    init_sol(&S, I);
    int maxShapes = 256;
    ShapeWH* shapes = malloc(maxShapes * sizeof(ShapeWH));
    int nsh = 0;
    for (int i = 0; i < B->nBrique; i++) {
        int s = B->bShape[i];
        int w = B->W[s], h = B->H[s];
        int found = 0;
        for (int j = 0; j < nsh; j++) { 
            if (shapes[j].w == w && shapes[j].h == h) { 
                found = 1; 
                break; 
            }
        }
        if (!found) {
            if (nsh >= maxShapes) { 
                maxShapes *= 2; 
                shapes = realloc(shapes, maxShapes * sizeof(ShapeWH)); 
            }
            shapes[nsh].w = w; 
            shapes[nsh].h = h; 
            nsh++;
        }
    }
    for (int i = 0; i < nsh; i++) {
        for (int j = i+1; j < nsh; j++) {
            int si = shapes[i].w * shapes[i].h;
            int sj = shapes[j].w * shapes[j].h;
            if (sj > si || (sj == si && shapes[j].w > shapes[i].w)) {
                ShapeWH tmp = shapes[i]; 
                shapes[i] = shapes[j]; 
                shapes[j] = tmp;
            }
        }
    }
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            if (covered[getIndex(x,y,I)]) continue;
            int targetCol = closestColor[getIndex(x,y,I)];
            int placed = 0;
            for (int si = 0; si < nsh && !placed; si++) {
                int w = shapes[si].w;
                int h = shapes[si].h;
                for (int rot = 0; rot < 2 && !placed; rot++) {
                    int ww = (rot==0) ? w : h;
                    int hh = (rot==0) ? h : w;
                    if (x + ww - 1 >= I->W || y + hh - 1 >= I->H) continue;
                    if (!rect_is_uncovered(x, y, ww, hh, I, covered)) continue;
                    if (!rect_has_uniform_closest(x, y, ww, hh, I, closestColor, targetCol)) continue;
                    
                    int briIndex = -1;
                    int lookupShapeId = lookupShape(B, ww, hh);
                    if (lookupShapeId != -1) {
                        briIndex = getBrickFor(B, lookupShapeId, targetCol);
                        if (briIndex >= 0) {
                            /* poser la brique */
                            push_sol_with_error(&S, briIndex, x, y, 0, I, B);
                            mark_rect_covered(x, y, ww, hh, I, covered);
                            placed = 1;
                            break;
                        } else {
                            /* fallback : poser n'importe quelle brique de la forme */
                            for (int t = 0; t < B->nBrique; t++) {
                                if (B->bShape[t] == lookupShapeId) { 
                                    briIndex = t; 
                                    break; 
                                }
                            }
                            if (briIndex >= 0) {
                                push_sol_with_error(&S, briIndex, x, y, 0, I, B);
                                mark_rect_covered(x, y, ww, hh, I, covered);
                                placed = 1;
                                break;
                            }
                        }
                    }
                } 
            } 
            if (!placed) {
                int colpix = closestColor[getIndex(x,y,I)];
                int bri1 = brique11WithColor[colpix];
                if (bri1 == -1) {
                    for (int t = 0; t < B->nBrique; t++) {
                        if (B->bShape[t] == shape11) { 
                            bri1 = t; 
                            break; 
                        }
                    }
                }
                push_sol_with_error(&S, bri1, x, y, 0, I, B);
                covered[getIndex(x,y,I)] = 1;
            }
        } 
    } 
    fill_sol_stock(&S, B);
    free(closestColor);
    free(covered);
    free(shapes);
    return S;
}