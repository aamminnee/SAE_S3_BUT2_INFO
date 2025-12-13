#ifndef MAIN_H
#define MAIN_H


#include "structure.h"
#include "brique.h"
#include "image.h"
#include "solution_1x1.h"
#include "solution_greedy_1x2.h"
#include "solution_greedy_1x2_stock.h"
#include "solution_2x2.h"
#include "solution_rectfusion.h"
#include "solution_forme_arbitraire_rentable.h"
#include "solution_v4_stock.h"
#include "solution.h"
#include "util.h"


void execute_all(char *dir);
void execute_1x1(char *dir);
void execute_greedy_1x2(char *dir);
void execute_greedy_1x2_stock(char *dir);
void execute_2x2(char *dir);
void execute_all_brique(char *dir);
void execute_rentabilite(char *dir);
void execute_v4_AMINE(char *dir);

#endif
