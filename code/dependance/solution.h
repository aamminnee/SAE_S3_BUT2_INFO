#ifndef SOLUTION_H
#define SOLUTION_H

#include "structure.h"
#include "image.h"
#include "brique.h"


void init_sol(Solution* sol, Image* I);
void push_sol(Solution* sol, int iBrique, int x, int y, int rot, Image* I, BriqueList* B);
void push_sol_with_error(Solution* sol, int iBrique, int x, int y, int rot, Image* I, BriqueList* B);
void print_sol(Solution* sol, char* dir, char* name, BriqueList* B);
void fill_sol_stock(Solution* sol, BriqueList* B);
void freeSolution(Solution S);
void push_sol_with_error(Solution* sol, int iBrique, int x, int y, int rot, Image* I, BriqueList* B);

#endif