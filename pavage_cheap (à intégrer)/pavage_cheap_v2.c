#include <stdlib.h>
#include <stdio.h>
#include <assert.h>
#include <limits.h>
#include <string.h>

/* --- Configuration --- */
#define PRICE_WEIGHT 500  // Poids du prix vs Erreur couleur
#define MAX_COLORS 275
#define UNMATCHED -1
#define DEBUG_LOAD 0

/* --- Structures --- */
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
    int* T;
    RGB* col;
    int* bCol;
    int* bShape;
    int* bPrix;
    int* bStock;
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
    int ruptures; 
    SolItem* array;
} Solution;

// Structure Matching
typedef struct {
    int* pair;
    int size;
} Matching;

// Fonctions de base (Image, Couleurs, Fichiers)

FILE* open_with_dir(char* dir, char* name, char* mode) {
    char filename[256];
    if (dir && strlen(dir) > 0) snprintf(filename, sizeof(filename), "%s/%s", dir, name);
    
    else snprintf(filename, sizeof(filename), "%s", name);
    FILE* fptr = fopen(filename, mode);
    assert(fptr!=NULL);
    return fptr;
}

RGB* get(Image* I, int x, int y) {
    return &(I->rgb[y*I->W + x]);
}

int colError(RGB c1, RGB c2) {
    return (c1.R-c2.R)*(c1.R-c2.R) + (c1.G-c2.G)*(c1.G-c2.G) + (c1.B-c2.B)*(c1.B-c2.B);
}

int charToMask(char c) {
    return 1<<(c-'0');
}

void trou_int_to_str(int T,  char* buffer) {
    if(T==0) { buffer[0]=0; return; }

    int ibuffer=0;
    char current = '0';
    buffer[ibuffer++] = '-'; // Séparateur
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

// Gestion du Matching (Issu de pavage.c)

void initMatching(Matching* M, Image* I) {
    assert(I != NULL);
    M->size = I->W * I->H;
    M->pair = malloc(M->size * sizeof(int));
    for (int i = 0; i < M->size; i++) M->pair[i] = UNMATCHED;
}

int getIndex(int x, int y, Image* I) {
    return x + y*I->W;
}

int neighborIndex(int u, int dir, Image* I) {
    int x = u % I->W;
    int y = u / I->W;
    switch (dir) {
        case 0: y--; break; // Haut
        case 1: x++; break; // Droite
        case 2: y++; break; // Bas
        case 3: x--; break; // Gauche
        default: return -1;
    }
    if (x < 0 || x >= I->W || y < 0 || y >= I->H) return -1;
    return getIndex(x, y, I);
}

int getMatch(int u, Matching* M) {
    return M->pair[u];
}

// Recherche de briques et couleurs (Adapté pour "Cheap")

int lookupShape(BriqueList* B, int W, int H) {
    for (int i=0; i<B->nShape; i++) {
        if (B->W[i]==W && B->H[i]==H) return i;
    }
    return -1;
}

// Minimiser les pris d'une brique
int findBestCheapColor(BriqueList* B, RGB pixel, int* map11, int* map12) {
    int bestCol = -1;
    long long bestScore = LLONG_MAX;
    for (int c = 0; c < B->nCol; c++) {
        int iBrique1 = map11[c];
        if (iBrique1 == -1) continue; 
        int err = colError(B->col[c], pixel);
        // Calcul du prix unitaire
        double prixUnitaire = (double)B->bPrix[iBrique1];
        // Si une brique 2x1 existe, on vérifie si elle est plus rentable au pixel
        int iBrique2 = map12[c];
        if (iBrique2 != -1) {
            double prix2x1_par_pixel = (double)B->bPrix[iBrique2] / 2.0;
            if (prix2x1_par_pixel < prixUnitaire) {
                prixUnitaire = prix2x1_par_pixel;
            }
        }
        // Formule de coût
        long long score = (long long)err + (long long)(prixUnitaire * PRICE_WEIGHT);
        if (score < bestScore) {
            bestScore = score;
            bestCol = c;
        }
    }
    if (bestCol == -1) return 0; // Fallback
    return bestCol;
}

// Algorithmes de Matching

// Insertion gloutonne : si un voisin a la même couleur idéale et qu'on a une brique 2x1 compatible
void greedyInsert(Matching* M, int u, Image* I, BriqueList* B, int* closestColor, int* map12) {
    if (getMatch(u, M) != UNMATCHED) return;
    
    int color = closestColor[u];
    // Vérifier si une brique 2x1 existe pour cette couleur
    if (map12[color] == -1) return;

    int dirs[2] = {1, 2};
    for (int k = 0; k < 2; k++) {
        int v = neighborIndex(u, dirs[k], I);
        if (v == -1) continue;
        if (getMatch(v, M) != UNMATCHED) continue;
        if (closestColor[v] != color) continue;
        M->pair[u] = v;
        M->pair[v] = u;
        return;
    }
}

// Fonction récursive pour de libérer un noeud
int liberer(Matching* M, int u, Image* I, BriqueList* B, int* closestColor, int* visited) {
    visited[u] = 1;
    int dirs[2] = {1, 2};
    int color = closestColor[u];
    for (int k=0; k<2; k++) {
        int v = neighborIndex(u, dirs[k], I);
        if (v == -1) continue;
        if (closestColor[v] != color) continue;
        int mate = getMatch(v, M);
        if (mate == UNMATCHED) {
            M->pair[u] = v;
            M->pair[v] = u;
            return 1;
        }
        // Si v est couplé, on libère son partenaire
        if (!visited[mate] && liberer(M, mate, I, B, closestColor, visited)) {
            M->pair[u] = v;
            M->pair[v] = u;
            return 1;
        }
    }
    return 0;
}

// Insertion optimale, à exécuté de préférence après le Greedy
void optimalInsert(Matching* M, int u, Image* I, BriqueList* B, int* closestColor, int* map12) {
    if (getMatch(u, M) != UNMATCHED) return;
    
    int color = closestColor[u];
    if (map12[color] == -1) return; // Pas de brique 2x1 pour cette couleur

    int dirs[2] = {1, 2};
    for (int k=0; k<2; k++) {
        int v = neighborIndex(u, dirs[k], I);
        if (v == -1) continue;
        if (closestColor[v] != color) continue;
        if (getMatch(v, M) == UNMATCHED) {
            M->pair[u] = v;
            M->pair[v] = u;
            return;
        } 
        // Tentative d'optimisation
        int* visited = calloc(M->size, sizeof(int));
        if (liberer(M, v, I, B, closestColor, visited)) {
            M->pair[u] = v;
            M->pair[v] = u;
            free(visited);
            return;
        }
        free(visited);
    }
}

// Construction de la solution

void init_sol(Solution* sol, Image* I) {
    sol->length=0;
    sol->totalError=0;
    sol->cost=0;
    sol->ruptures=0;
    sol->array =malloc(I->W*I->H*sizeof(SolItem));
}

void push_sol(Solution* sol, int iBrique, int x, int y, int rot, BriqueList* B, Image* I) {
    sol->array[sol->length].iBrique = iBrique;
    sol->array[sol->length].x = x;
    sol->array[sol->length].y = y;
    sol->array[sol->length].rot = rot;    
    sol->length++;
    sol->cost += B->bPrix[iBrique];
    
    // Vérification stock et incrément rupture
    if (B->bStock[iBrique] > 0) {
        B->bStock[iBrique]--;
    } else {
        sol->ruptures++;
    }
    // Calcul de l'erreur
    int shapeIdx = B->bShape[iBrique];
    int w = (rot == 1 || rot == 3) ? B->H[shapeIdx] : B->W[shapeIdx];
    int h = (rot == 1 || rot == 3) ? B->W[shapeIdx] : B->H[shapeIdx];
    RGB brickCol = B->col[B->bCol[iBrique]];
    for(int dy=0; dy<h; dy++){
        for(int dx=0; dx<w; dx++){
            // Vérif limites image
            if(x+dx < I->W && y+dy < I->H) {
               RGB px = *get(I, x+dx, y+dy);
               sol->totalError += colError(px, brickCol); 
            }
        }
    }
}

Solution buildSolutionFromMatching(Matching* M, Image* I, BriqueList* B, int* closestColor, int* map11, int* map12) {
    Solution S;
    init_sol(&S, I);
    int W = I->W;
    int* processed = calloc(I->W * I->H, sizeof(int));
    for(int u=0; u < M->size; u++) {
        if (processed[u]) continue;
        int match = getMatch(u, M);
        int color = closestColor[u];
        if(match != UNMATCHED) {
            // Cas de brique 2x1
            assert(closestColor[match] == color);
            int ib = map12[color];
            // Déterminer les coordonnées et la rotation de la pièce
            int x = u % W;
            int y = u / W;
            int rot = 0;
            if (match == neighborIndex(u, 1, I)) { // Voisin à droite, donc pièce horizontale
                rot = 0; 
            } else { // Voisin en bas, donc pièce verticale
                rot = 1; 
            }
            push_sol(&S, ib, x, y, rot, B, I);
            processed[u] = 1;
            processed[match] = 1;
        }
        else {
            // Cas de brique 1x1
            int ib = map11[color];
            int x = u % W;
            int y = u / W;
            push_sol(&S, ib, x, y, 0, B, I);
            processed[u] = 1;
        }
    }
    free(processed);
    return S;
}

// Chargement depuis les fichier

void load_image(char* dir, Image* I) {
    FILE* fptr = open_with_dir(dir, "image.txt", "r");
    fscanf(fptr, "%d %d", &I->W, &I->H);
    I->rgb=malloc(I->W*I->H*sizeof(RGB));
    for (int j=0;j<I->H;j++) {
        for (int i=0;i<I->W;i++) {
            RGB col;
            fscanf(fptr, "%02x%02x%02x", &col.R, &col.G, &col.B);
            *get(I, i, j) = col;          
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
        fscanf(fptr, "%s", buffer);
        int w, h;
        char* token = strtok(buffer, "-");
        B->W[i] = atoi(token);
        token = strtok(NULL, "-");
        B->H[i] = atoi(token);
        token = strtok(NULL, "-");
        if(token) {
            int T=0;
            for (int k=0; token[k]; k++) T+=charToMask(token[k]);
            B->T[i]=T;
        } else B->T[i]=0;
    }
    for (int i=0; i<B->nCol; i++) { 
        fscanf(fptr, "%02x%02x%02x", &B->col[i].R, &B->col[i].G, &B->col[i].B);
    }
    for (int i=0; i<B->nBrique; i++) { 
        fscanf(fptr, "%d/%d %d %d", &B->bShape[i], &B->bCol[i], &B->bPrix[i], &B->bStock[i]);
    }
    fclose(fptr);
}

void print_sol_cheap(Solution* sol, char* dir, char* name, BriqueList* B) {
    FILE* fptr = open_with_dir(dir, name, "w");
    fprintf(fptr, "%d %d %d %d\n", sol->length, sol->cost, sol->totalError, sol->ruptures);
    for (int i=0; i<sol->length; i++) {
        int ibrique = sol->array[i].iBrique;
        int ishape = B->bShape[ibrique];
        int icol = B->bCol[ibrique];
        char buffer[20] = "";
        trou_int_to_str(B->T[ishape], buffer);
        fprintf(fptr, "%dx%d%s/%02X%02X%02X %d %d %d\n", 
            B->W[ishape], B->H[ishape], buffer, 
            B->col[icol].R, B->col[icol].G, B->col[icol].B, 
            sol->array[i].x, sol->array[i].y, sol->array[i].rot);
    }
    fclose(fptr);
    printf("Sortie générée: %s/%s\n", dir?dir:".", name);
}

void freeData(Image* I, BriqueList* B, Solution* S, Matching* M, int* closest) {
    free(I->rgb);
    free(B->W); free(B->H); free(B->T); free(B->col);
    free(B->bShape); free(B->bCol); free(B->bPrix); free(B->bStock);
    free(S->array);
    if(M->pair) free(M->pair);
    if(closest) free(closest);
}

int main(int argc, char** argv) {
    char* dir = "test";
    if (argc > 1) dir = argv[1];
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);

    // 1. Préparation des mappings
    int shape11 = lookupShape(&B, 1, 1);
    int shape21 = lookupShape(&B, 2, 1);
    int shape12 = lookupShape(&B, 1, 2);
    int map11[MAX_COLORS];
    int map12[MAX_COLORS];
    for(int i=0; i<MAX_COLORS; i++) { map11[i] = -1; map12[i] = -1; }

    for(int i=0; i<B.nBrique; i++){
        if(B.bShape[i] == shape11) 
            map11[B.bCol[i]] = i;
        if(B.bShape[i] == shape21 || B.bShape[i] == shape12) 
            map12[B.bCol[i]] = i;
    }
    // 2. Calcul des couleurs optimales (Cheap)
    int size = I.W * I.H;
    int* closestColor = malloc(size * sizeof(int));
    printf("Calcul des couleurs optimales (Stratégie Cheap 1x1 & 2x1)...\n");
    for(int y=0; y<I.H; y++){
        for(int x=0; x<I.W; x++){
            int u = getIndex(x,y,&I);
            RGB px = *get(&I, x, y);
            closestColor[u] = findBestCheapColor(&B, px, map11, map12);
        }
    }

    // 3. Test avec différents algorithmes de Matching
    Matching M;
    initMatching(&M, &I);

    // Algorithme Greedy en premier
    for(int u=0; u<size; u++)
        greedyInsert(&M, u, &I, &B, closestColor, map12);

    // Puis algorithme Optimale (Backtracking)
    for(int u=0; u<size; u++)
        optimalInsert(&M, u, &I, &B, closestColor, map12);

    // 4. Construction et Export
    Solution S = buildSolutionFromMatching(&M, &I, &B, closestColor, map11, map12);
    print_sol_cheap(&S, dir, "Out_cheap.txt", &B);
    freeData(&I, &B, &S, &M, closestColor);
    return 0;
}