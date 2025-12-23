#ifndef STRUCTURE_H
#define STRUCTURE_H

#include <stdlib.h>
#include <stdio.h>
#include <assert.h>
#include <limits.h>

#define DEBUG_LOAD 1
#define MAX_COLORS 275
#define UNMATCHED -1

typedef struct {
    int R, G, B;
} RGB;

typedef struct {
    int W, H;
    RGB* rgb;
} Image;

typedef struct {
    int nShape, nCol, nBrique;
    int* W;
    int* H;
    int* T;
    RGB* col;
    int* bCol;
    int* bShape;
    int* bPrix;
    int* bStock;
} BriqueList;

typedef struct {
    int iBrique;
    int x, y;
    int rot;
} SolItem;

typedef struct {
    int length;
    int totalError;
    int cost;
    int stock;
    SolItem* array;
} Solution;

typedef struct { 
    int w; 
    int h; 
} ShapeWH;

typedef struct {
    int w;
    int h;
    int aire;
} Dimension;

#endif