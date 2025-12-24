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
    float* bPrix;
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
    float cost;
    int stock;
    SolItem* array;
} Solution;

typedef struct {
    int u1; 
    int u2; 
} Pair;

typedef struct {
    int nPair;  
    Pair* array;  
} Matching;

typedef struct { 
    int w; 
    int h; 
} ShapeWH;

typedef struct {
    int iBrique;
    float rentable;
    int shape;
    int w, h;
    int col;
    int price;
    int surface;
} BriqueRent;
#endif
