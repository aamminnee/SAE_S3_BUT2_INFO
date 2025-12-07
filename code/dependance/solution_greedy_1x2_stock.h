#ifndef SOLUTION_GREEDY_1X2_STOCK_H
#define SOLUTION_GREEDY_1X2_STOCK_H

#include "image.h"
#include "brique.h"
#include "structure.h"
#include "matching.h"

void greedyInsert_1x2_stock(Matching* M, int u, Image* I, BriqueList* B, int* visited);
Solution run_algo_greedy_1x2_stock(Image* I, BriqueList* B);

#endif
