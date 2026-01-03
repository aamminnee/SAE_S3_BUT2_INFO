#ifndef UTIL_H
#define UTIL_H

#include "structure.h"

FILE* open_with_dir(char* dir, char* name, char* mode);
int charToMask(char c);
int coordToMask(int dx, int dy, int W);
int trou_str_to_int(char* buffer);
void trou_int_to_str(int T, char* buffer);
int getIndex(int x, int y, Image* I);
int getBrickFor(BriqueList* B, int shape, int col);
int compute_error_for_shape_at(int iBrique, int x0, int y0, int rot, BriqueList* B, Image* I);
int rect_is_uncovered(int x0, int y0, int w, int h, Image* I, int* covered);
void mark_rect_covered(int x0, int y0, int w, int h, Image* I, int* covered);
int choisir_brique1x1_disponible(BriqueList* B, int col, int* used);
int rect_has_uniform_closest(int x0, int y0, int w, int h, Image* I, int* closestColor, int colTarget);
int comparer_aire(const void* a, const void* b);
int is_area_compatible(Image* I, int x, int y, int w, int h, RGB bColor, int tolerance);

#endif