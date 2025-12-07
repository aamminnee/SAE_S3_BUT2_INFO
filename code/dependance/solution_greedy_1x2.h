#ifndef SOLUTION_GREEDY_1X2_H
#define SOLUTION_GREEDY_1X2_H

#include "structure.h"
#include "solution_1x1.h"
#include "image.h"
#include "util.h"
#include "brique.h"
#include "matching.h"

void greedyInsert(Matching* M, int u, Image* I);
Solution run_algo_greedy_1x2(Image* I, BriqueList* B);

#endif
