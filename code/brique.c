#include "dependance/brique.h"
#include "dependance/util.h"

void load_brique(char* dir, BriqueList* B) {
    FILE* fptr = open_with_dir(dir, "briques.txt", "r");
    fscanf(fptr, "%d %d %d", &B->nShape, &B->nCol, &B->nBrique);
    B->W = malloc(B->nShape * sizeof(int));
    B->H = malloc(B->nShape * sizeof(int));
    B->T = malloc(B->nShape * sizeof(int));
    B->col = malloc(B->nCol * sizeof(RGB));
    B->bShape = malloc(B->nBrique * sizeof(int));
    B->bCol = malloc(B->nBrique * sizeof(int));
    B->bPrix = malloc(B->nBrique * sizeof(int));
    B->bStock = malloc(B->nBrique * sizeof(int));
    char buffer[80];
    for (int i=0; i<B->nShape; i++) {
        int count = fscanf(fptr, "%d-%d-%s", &B->W[i], &B->H[i], buffer);
        if (count == 3) { 
            B->T[i] = trou_str_to_int(buffer);
        } else { 
            B->T[i] = 0;
        }
    }
    for (int i=0; i<B->nCol; i++) { 
        fscanf(fptr, "%02x%02x%02x", &B->col[i].R, &B->col[i].G, &B->col[i].B);
    }
    for (int i=0; i<B->nBrique; i++) { 
        fscanf(fptr, "%d/%d %d %d", &B->bShape[i], &B->bCol[i],
                                   &B->bPrix[i], &B->bStock[i]);
    }
    fclose(fptr);
}

int lookupShape(BriqueList* B, int W, int H) {
    for (int i=0; i<B->nShape; i++) { 
        if (B->W[i]==W && B->H[i]==H) { 
            return i;
        }
    }
    return -1;
}

int getBriqueWithColor(BriqueList* B, int shape, int col) {
    for (int i = 0; i < B->nBrique; i++) { 
        if (B->bShape[i] == shape && B->bCol[i] == col) { 
            return i;
        }
    }
    return -1;
}

void freeBrique(BriqueList B) {
    free(B.W);
    free(B.H);
    free(B.T);
    free(B.col);
    free(B.bShape);
    free(B.bCol);
    free(B.bPrix);
    free(B.bStock);
}
