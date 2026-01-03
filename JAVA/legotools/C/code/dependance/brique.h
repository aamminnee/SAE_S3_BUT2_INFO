#ifndef BRIQUE_H
#define BRIQUE_H

#include "structure.h"

void load_brique(char* dir, BriqueList* B);
void freeBrique(BriqueList B);
int lookupShape(BriqueList* B, int W, int H);
int getBriqueWithColor(BriqueList* B, int shape, int col); 

#endif
