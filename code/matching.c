#include <stdlib.h>

#include "dependance/structure.h"
#include "dependance/matching.h"


void initMatching(Matching* M, int maxPairs) {
    M->nPair = 0;
    M->array = malloc(sizeof(Pair) * maxPairs);

    if (!M->array) {
        fprintf(stderr, "Erreur allocation Matching\n");
        exit(EXIT_FAILURE);
    }
}

int getMatch(const Matching* M, int u) {
    for (int i = 0; i < M->nPair; i++) {
        if (M->array[i].u1 == u)
            return M->array[i].u2;
        if (M->array[i].u2 == u)
            return M->array[i].u1;
    }
    return UNMATCHED;
}

// Ajoute un couple dans le Matching
void addPair(Matching* M, int u1, int u2) {
    M->array[M->nPair].u1 = u1;
    M->array[M->nPair].u2 = u2;
    M->nPair++;
}

void freeMatching(Matching* M) {
    if (M->array) {
        free(M->array);
        M->array = NULL;
    }
    M->nPair = 0;
}

// renvoie 1 si la pièce a été libérée, 0 sinon
int liberer(Matching* M, int u, int* visited, int size) {
    if (u < 0 || u >= size) return 0; 
    if (visited[u]) return 0;    
    visited[u] = 1;
    int v = getMatch(M, u);
    if (v == UNMATCHED) return 1;
    if (liberer(M, v, visited, size)) {
        for (int i = 0; i < M->nPair; i++) {
            if ((M->array[i].u1 == u && M->array[i].u2 == v) ||
                (M->array[i].u1 == v && M->array[i].u2 == u)) {
                for (int j = i; j < M->nPair - 1; j++)
                    M->array[j] = M->array[j + 1];
                M->nPair--;
                break;
            }
        }
        return 1; 
    }
    return 0; 
}