#include "dependance/image.h"
#include "dependance/util.h"

RGB* get(Image* I, int x, int y) {
    return &(I->rgb[y * I->W + x]);
}

void reset(RGB* col) {
    col->R=0;
    col->G=0;
    col->B=0;
}

int colError(RGB c1, RGB c2) {
    return (c1.R-c2.R)*(c1.R-c2.R)
         + (c1.G-c2.G)*(c1.G-c2.G)
         + (c1.B-c2.B)*(c1.B-c2.B);
}

void load_image(char* dir, Image* I) {
    FILE* fptr = open_with_dir(dir, "image.txt", "r");
    fscanf(fptr, "%d %d", &I->W, &I->H);
    I->rgb = malloc(I->W * I->H * sizeof(RGB));
    for (int j=0;j<I->H;j++) {
        for (int i=0;i<I->W;i++) {
            RGB col;
            reset(&col);
            int count = fscanf(fptr, "%02x%02x%02x", &col.R, &col.G, &col.B);
            assert(count==3);
            *get(I,i,j) = col;
        }
    }
    fclose(fptr);
}

void freeImage(Image I) {
    free(I.rgb);
}
