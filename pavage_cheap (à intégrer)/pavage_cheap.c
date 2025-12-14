#include <stdlib.h>
#include <stdio.h>
#include <assert.h>
#include <limits.h>
#include <string.h>
#define PRICE_WEIGHT 500
#define MAX_COLORS 275
#define UNMATCHED -1

/* --- Structures (Reprises de pavage.c) --- */

typedef struct {
    int R; int G; int B;
} RGB;

typedef struct {
    int W;
    int H;
    RGB* rgb;
} Image; 

typedef struct {
    int nShape;
    int nCol; 
    int nBrique;
    int* W;
    int* H;
    int* T;         // Trous (bitmask)
    RGB* col;       // Palette couleurs
    int* bCol;      // Couleur de la brique i
    int* bShape;    // Forme de la brique i
    int* bPrix;     // Prix de la brique i
    int* bStock;    // Stock de la brique i
} BriqueList;

typedef struct {
    int iBrique;
    int x;
    int y;
    int rot;
} SolItem;

typedef struct {
    int length;
    int totalError;
    int cost;
    int ruptures; // Anciennement 'stock', ren nommé pour la consigne
    SolItem* array;
} Solution;

/* --- Fonctions Utilitaires --- */

FILE* open_with_dir(char* dir, char* name, char* mode) {
    char filename[256];
    if (dir && strlen(dir) > 0)
        snprintf(filename, sizeof(filename), "%s/%s", dir, name);
    else
        snprintf(filename, sizeof(filename), "%s", name);
    
    FILE* fptr = fopen(filename, mode);
    if (!fptr) {
        fprintf(stderr, "Erreur: Impossible d'ouvrir %s\n", filename);
        exit(1);
    }
    return fptr;
}

RGB* get_pixel(Image* I, int x, int y) {
    if (x < 0 || x >= I->W || y < 0 || y >= I->H) return NULL;
    return &(I->rgb[y*I->W + x]);
}

int colError(RGB c1, RGB c2) {
    return (c1.R-c2.R)*(c1.R-c2.R) + (c1.G-c2.G)*(c1.G-c2.G) + (c1.B-c2.B)*(c1.B-c2.B);
}

int charToMask(char c) {
    return 1<<(c-'0');
}

void trou_int_to_str(int T,  char* buffer) {
    if (T == 0) {
        buffer[0] = '\0';
        return;
    }
    int ibuffer=0;
    char current = '0';
    buffer[ibuffer++] = '-'; // Ajout du tiret séparateur
    while (T>0) {
        if (T%2 == 1) {
            buffer[ibuffer]= current;
            ibuffer++;
        }
        current+=1;
        T/=2;
    }
    buffer[ibuffer] = 0;
}

/* --- Gestion Mémoire et Chargement --- */

void load_image(char* dir, Image* I) {
    FILE* fptr = open_with_dir(dir, "image.txt", "r");
    fscanf(fptr, "%d %d", &I->W, &I->H);
    I->rgb = malloc(I->W * I->H * sizeof(RGB));
    for (int j=0; j<I->H; j++) {
        for (int i=0; i<I->W; i++) {
            RGB col;
            fscanf(fptr, "%02x%02x%02x", &col.R, &col.G, &col.B);
            I->rgb[j*I->W + i] = col;          
        }
    }
    fclose(fptr);
}

void load_brique(char* dir, BriqueList* B) {
    FILE* fptr = open_with_dir(dir, "briques.txt", "r");
    fscanf(fptr, "%d %d %d", &B->nShape, &B->nCol, &B->nBrique);

    B->W = malloc(B->nShape * sizeof(int));
    B->H = malloc(B->nShape * sizeof(int));
    B->T = malloc(B->nShape * sizeof(int));
    B->col = malloc(B->nCol * sizeof(RGB));
    B->bCol = malloc(B->nBrique * sizeof(int));
    B->bShape = malloc(B->nBrique * sizeof(int));
    B->bPrix = malloc(B->nBrique * sizeof(int));
    B->bStock = malloc(B->nBrique * sizeof(int));

    char buffer[80];
    for (int i=0; i<B->nShape; i++) {
        // Lecture des formes (ex: 1-1 ou 2-1-0)
        int w, h;
        // On lit d'abord grossièrement la chaine pour parser
        fscanf(fptr, "%s", buffer);
        
        // Parsing manuel simple pour gérer le format W-H[-T]
        char* token = strtok(buffer, "-");
        B->W[i] = atoi(token);
        token = strtok(NULL, "-");
        B->H[i] = atoi(token);
        token = strtok(NULL, "-");
        
        if (token) {
            int T=0;
            for (int k=0; token[k]; k++) T+=charToMask(token[k]);
            B->T[i]=T;
        } else {
            B->T[i]=0;
        }
    }

    for (int i=0; i<B->nCol; i++) { 
        fscanf(fptr, "%02x%02x%02x", &B->col[i].R, &B->col[i].G, &B->col[i].B);
    }
    for (int i=0; i<B->nBrique; i++) { 
        fscanf(fptr, "%d/%d %d %d", &B->bShape[i], &B->bCol[i], &B->bPrix[i], &B->bStock[i]);
    }
    fclose(fptr);
}

void init_sol(Solution* sol, Image* I) {
    sol->length=0;
    sol->totalError=0;
    sol->cost=0;
    sol->ruptures=0;
    sol->array = malloc(I->W * I->H * sizeof(SolItem));
}

void push_sol(Solution* sol, int iBrique, int x, int y, int rot, BriqueList* B, int errorToAdd) {
    sol->array[sol->length].iBrique = iBrique;
    sol->array[sol->length].x = x;
    sol->array[sol->length].y = y;
    sol->array[sol->length].rot = rot;    
    sol->length++;
    sol->cost += B->bPrix[iBrique];
    sol->totalError += errorToAdd;
}

void freeData(Image* I, BriqueList* B, Solution* S) {
    if(I->rgb) free(I->rgb);
    if(B->W) free(B->W);
    if(B->H) free(B->H);
    if(B->T) free(B->T);
    if(B->col) free(B->col);
    if(B->bShape) free(B->bShape);
    if(B->bCol) free(B->bCol);
    if(B->bPrix) free(B->bPrix);
    if(B->bStock) free(B->bStock);
    if(S->array) free(S->array);
}

/* --- Export --- */

void print_sol_cheap(Solution* sol, char* dir, char* name, BriqueList* B) {
    
    FILE* fptr = open_with_dir(dir, name, "w");
    
    fprintf(fptr, "%d %d %d %d\n", sol->length, sol->cost, sol->totalError, sol->ruptures);
    
    for (int i=0; i<sol->length; i++) {
        int ib = sol->array[i].iBrique;
        int is = B->bShape[ib];
        int ic = B->bCol[ib];
        
        char holesBuf[20] = "";
        if (B->T[is] != 0) {
            trou_int_to_str(B->T[is], holesBuf);
            // holesBuf contient déjà le tiret initial ex: "-0"
        }

        // Format: LargeurxHauteur[-trous]/RRGGBB x y rot
        fprintf(fptr, "%dx%d%s/%02X%02X%02X %d %d %d\n", 
            B->W[is], B->H[is], holesBuf,
            B->col[ic].R, B->col[ic].G, B->col[ic].B,
            sol->array[i].x, sol->array[i].y, sol->array[i].rot);
    }
    
    fclose(fptr);
    printf("Export terminé: %s/%s (Prix: %d, Erreur: %d)\n", dir ? dir : ".", name, sol->cost, sol->totalError);
}

/* --- Algorithme Cheap --- */

// Calcule le score d'une brique pour une position donnée
long long calculate_score(int iBrique, int x, int y, int rot, Image* I, BriqueList* B, int* occupied) {
    if (B->bStock[iBrique] <= 0) return LLONG_MAX;

    int shapeIdx = B->bShape[iBrique];
    int w = B->W[shapeIdx];
    int h = B->H[shapeIdx];
    int t = B->T[shapeIdx]; // Masque des trous
    
    // Dimensions réelles après rotation
    int rw = (rot == 1) ? h : w; // 1 = 90 deg
    int rh = (rot == 1) ? w : h;
    
    // Vérification limites
    if (x + rw > I->W || y + rh > I->H) return LLONG_MAX;

    long long currentError = 0;
    
    // Parcours des pixels couverts par la brique
    for (int dy = 0; dy < rh; dy++) {
        for (int dx = 0; dx < rw; dx++) {
            
            // Vérification occupation grille image
            if (occupied[(y + dy) * I->W + (x + dx)]) return LLONG_MAX;

            RGB* pixelImg = get_pixel(I, x + dx, y + dy);
            RGB brickCol = B->col[B->bCol[iBrique]];
            
            currentError += colError(*pixelImg, brickCol);
        }
    }

    // Formule magique "Cheap"
    return currentError + (long long)(PRICE_WEIGHT * B->bPrix[iBrique]);
}

void run_algo_cheap(Image* I, BriqueList* B, Solution* S) {
    int* occupied = calloc(I->W * I->H, sizeof(int));
    
    // Identification des formes de base, principalement 1x1, 1x2, 2x1.
    
    for (int y = 0; y < I->H; y++) {
        for (int x = 0; x < I->W; x++) {
            if (occupied[y * I->W + x]) continue;

            long long bestScore = LLONG_MAX;
            int bestBrique = -1;
            int bestRot = 0;
            long long bestErrorPure = 0;
            
            for (int i = 0; i < B->nBrique; i++) {
                if (B->bStock[i] <= 0) continue;

                int sIdx = B->bShape[i];
                // On tente rotation 0
                long long score0 = calculate_score(i, x, y, 0, I, B, occupied);
                if (score0 < bestScore) {
                    bestScore = score0;
                    bestBrique = i;
                    bestRot = 0;
                    // Recalcul erreur pure pour stats
                    // (on refait le calcul simplifié, optimisation possible)
                    int w = B->W[sIdx]; int h = B->H[sIdx];
                    bestErrorPure = 0;
                    for(int dy=0; dy<h; dy++) 
                        for(int dx=0; dx<w; dx++) 
                             bestErrorPure += colError(*get_pixel(I, x+dx, y+dy), B->col[B->bCol[i]]);
                }

                // On tente rotation 1 (90°) pour les briques rectangulaires
                if (B->W[sIdx] != B->H[sIdx]) {
                    long long score1 = calculate_score(i, x, y, 1, I, B, occupied);
                    if (score1 < bestScore) {
                        bestScore = score1;
                        bestBrique = i;
                        bestRot = 1;
                        int w = B->H[sIdx]; int h = B->W[sIdx]; // Swap dimensions
                        bestErrorPure = 0;
                        for(int dy=0; dy<h; dy++) 
                            for(int dx=0; dx<w; dx++) 
                                bestErrorPure += colError(*get_pixel(I, x+dx, y+dy), B->col[B->bCol[i]]);
                    }
                }
            }

            if (bestBrique != -1) {
                // Appliquer la brique
                push_sol(S, bestBrique, x, y, bestRot, B, (int)bestErrorPure);
                B->bStock[bestBrique]--;
                
                // Marquer occupé
                int sIdx = B->bShape[bestBrique];
                int w = (bestRot == 0) ? B->W[sIdx] : B->H[sIdx];
                int h = (bestRot == 0) ? B->H[sIdx] : B->W[sIdx];
                
                for(int dy=0; dy<h; dy++) {
                    for(int dx=0; dx<w; dx++) {
                        occupied[(y+dy)*I->W + (x+dx)] = 1;
                    }
                }
            } else {
                // Rupture de stock totale (aucune brique ne rentre ou stock vide)
                S->ruptures++;
                // Pour éviter boucle infinie si on ne remplit pas, on marque pixel occupé virtuellement
                occupied[y*I->W + x] = 1; 
            }
        }
    }
    
    free(occupied);
}

int main(int argc, char** argv) {
    char* dir = "test"; // Dossier par défaut contenant image.txt et briques.txt
    if (argc > 1) dir = argv[1];

    Image I;
    BriqueList B;
    Solution S;

    load_image(dir, &I);
    load_brique(dir, &B);
    init_sol(&S, &I);

    printf("Lancement Pavage Cheap (Optimisation Prix/Couleur)...\n");
    run_algo_cheap(&I, &B, &S);

    print_sol_cheap(&S, dir, "Out_cheap.txt", &B);

    freeData(&I, &B, &S);
    return 0;
}