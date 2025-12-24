#ifndef IMAGE_H
#define IMAGE_H

#include "structure.h"

RGB* get(Image* I, int x, int y);
void reset(RGB* col);
int colError(RGB c1, RGB c2);

void load_image(char* dir, Image* I);
void freeImage(Image I);

#endif
