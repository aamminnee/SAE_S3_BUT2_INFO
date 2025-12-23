#ifndef MAIN_H
#define MAIN_H

#include "structure.h"
#include "brique.h"
#include "image.h"
#include "solution_v4_stock.h"
#include "solution_v4_forme_libre.h"
#include "solution_v4_rupture.h"
#include "solution_v4_cheap.h"
#include "solution.h"
#include "util.h"

void execute_all(char *dir);
void execute_v4_AMINE(char *dir);
void execute_v4_ETHAN(char *dir);
void execute_v4_ZHABRAIL(char *dir);
void execute_v4_RAYAN(char *dir);

#endif