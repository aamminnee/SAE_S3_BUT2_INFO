#ifndef MAIN_H
#define MAIN_H

#include "structure.h"
#include "brique.h"
#include "image.h"
#include "solution_stock.h"
#include "solution_libre.h"
#include "solution_minimisation.h"
#include "solution_rentabilite.h"
#include "solution.h"
#include "util.h"

void execute_all(char *dir);
void execute_strategie_stock(char *dir);
void execute_strategie_minimisation(char *dir);
void execute_strategie_libre(char *dir);
void execute_strategie_rentabilite(char *dir);

#endif