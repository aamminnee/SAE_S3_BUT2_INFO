#ifndef MATCHING_H
#define MATCHING_H

#include "structure.h"
#include "image.h"

void initMatching(Matching* M, int maxPairs);
int getMatch(const Matching* M, int u); 
void addPair(Matching* M, int u1, int u2);
void freeMatching(Matching* M);
int liberer(Matching* M, int u, int* visited, int size);

#endif