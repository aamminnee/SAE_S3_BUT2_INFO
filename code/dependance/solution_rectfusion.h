#ifndef SOLUTION_RECTFUSION_H
#define SOLUTION_RECTFUSION_H

#include "solution_2x2.h"
#include "solution_1x1.h"
#include "matching.h"
#include "util.h"
#include "image.h"
#include "brique.h"

Solution run_algo_rectfusion(Image* I, BriqueList* B);

#endif
