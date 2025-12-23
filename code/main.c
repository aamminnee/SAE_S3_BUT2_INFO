#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "dependance/main.h"

void execute_all(char * dir) {  
    execute_v4_AMINE(dir);
    execute_v4_ETHAN(dir);
    execute_v4_ZHABRAIL(dir);
    execute_v4_RAYAN(dir);
}

void execute_v4_AMINE(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo v4 stock ===================
    printf("\nExécution de la version 4 algo gestion de stock...\n");
    Solution S = run_algo_v4_stock(&I, &B);
    print_sol(&S, "output", "pavage_v4_stock.txt", &B);
    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}

void execute_v4_ETHAN(char * dir) {
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo v4 forme libre ===================
    printf("\nExécution de l'algo v4 forme libre (toutes pièces)...\n");
    Solution S = run_algo_v4_forme_libre(&I, &B);
    print_sol(&S, "output", "pavage_v4_forme_libre.txt", &B);
    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}

void execute_v4_ZHABRAIL(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo v4 rupture ===================
    printf("\nExécution de la version 4 algo minimalisation du l'erreur...\n");
    Solution S = run_algo_v4_rupture(&I, &B);
    print_sol(&S, "output", "pavage_v4_rupture.txt", &B);
    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}

void execute_v4_RAYAN(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo v4 rentabilité ===================
    printf("\nExécution de la version 4 algo rentabilité...\n");
    Solution S = run_algo_v4_cheap(&I, &B);
    print_sol(&S, "output", "pavage_v4_rentable.txt", &B);
    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}

int main(int argc, char** argv) {
    char* dir = "input";
    printf("\n");
    if (argc > 1) {
        dir = argv[1]; 
    }
    FILE* test = fopen(dir, "r");
    if (!test) {
        printf("Erreur : le dossier '%s' n'existe pas ou n'est pas accessible.\n", dir);
        return EXIT_FAILURE;
    }
    fclose(test);
    if (argc == 2) {
        execute_all(dir);
        return EXIT_SUCCESS;
    }
    for (int i = 2; i < argc; i++) {
        char* algo = argv[i];
        if (strcmp(algo, "all") == 0) {
            execute_all(dir);
        } else if (strcmp(algo, "v4_stock") == 0) {
             execute_v4_AMINE(dir);
        } else if (strcmp(algo, "v4_libre") == 0) {
            execute_v4_ETHAN(dir);
        } else if (strcmp(algo, "v4_rupture") == 0) { 
            execute_v4_ZHABRAIL(dir);
        } else if (strcmp(algo, "v4_rentable") == 0) { 
            execute_v4_RAYAN(dir);
        } else {
            printf("Erreur : option inconnue '%s'.\n", algo);
            printf("Options : all, v4_stock, v4_libre, v4_rupture, v4_rentable\n");
        }
    }
    return EXIT_SUCCESS;
}
