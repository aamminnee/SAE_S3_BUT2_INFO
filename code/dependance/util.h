#ifndef UTIL_H
#define UTIL_H

#include "structure.h"

FILE* open_with_dir(char* dir, char* name, char* mode);
int charToMask(char c);
int coordToMask(int dx, int dy, int W);
int trou_str_to_int(char* buffer);
void trou_int_to_str(int T, char* buffer);
int getIndex(int x, int y, Image* I);
int neighborIndex(int u, int dir, Image* I);
int getBrickFor(BriqueList* B, int shape, int col);
int cmpRent(const void* A, const void* B);
int closestColorIndex(Image* I, BriqueList* B, int x, int y);
int compute_error_for_shape_at(int iBrique, int x0, int y0, int rot, BriqueList* B, Image* I);
int rect_is_uncovered(int x0, int y0, int w, int h, Image* I, int* covered);
void mark_rect_covered(int x0, int y0, int w, int h, Image* I, int* covered);
int choisir_brique1x1_disponible(BriqueList* B, int col, int* used);
int choisir_brique_shape_disponible_min_err(BriqueList* B, int shape, int col_pref, int x0, int y0, int rot, Image* I, int* used);
int rect_has_uniform_closest(int x0, int y0, int w, int h, Image* I, int* closestColor, int colTarget);

#endif
