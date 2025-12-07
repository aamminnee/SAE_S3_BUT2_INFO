#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "dependance/main.h"

void execute_all(char * dir) {  
    execute_1x1(dir);
    execute_greedy_1x2(dir);
    execute_greedy_1x2_stock(dir);
    execute_2x2(dir);
    execute_all_brique(dir);
    execute_rentabilite(dir);
}

void execute_1x1(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo 1x1 ===================
    printf("\nExécution de l'algo 1x1...\n");
    Solution S = run_algo_1x1(&I, &B);
    print_sol(&S, "output", "pavage_1x1.txt", &B);
    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}

void execute_greedy_1x2(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo glouton 1x2 ===================
    printf("\nExécution de l'algo glouton 1x2...\n");
    Solution S = run_algo_greedy_1x2(&I, &B);
    print_sol(&S, "output", "pavage_greedy_1x2.txt", &B);
    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}


void execute_greedy_1x2_stock(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo 1x2 avec remplacement briques (gestion de stock) ===================
    printf("\nExécution de l'algo optimal 1x2 gestion de stock...\n");
    Solution S = run_algo_greedy_1x2_stock(&I, &B);
    print_sol(&S, "output", "pavage_greedy_1x2_stock.txt", &B);
    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}


void execute_2x2(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo 2x2 & 4x2 ===================
    printf("\nExécution de l'algo 2x2...\n");
    Solution S = run_algo_2x2(&I, &B);
    print_sol(&S, "output", "pavage_2x2.txt", &B);
    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}


void execute_all_brique(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo 1x1 2x1 2x2 3x2 4x2 ===================
    printf("\nExécution de l'algo (1x1 2x1 2x2 3x2 4x2)...\n");
    Solution S = run_algo_rectfusion(&I, &B);
    print_sol(&S, "output", "pavage_rectfusion.txt", &B);
    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}


void execute_rentabilite(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo 1x1 2x1 2x2 3x2 4x2 + forme arbitraire + rentabiilité ===================
    printf("\nExécution de l'algo avec els formes arbitraire (rentabilité)...\n");
    Solution S = run_algo_forme_rentable(&I, &B);
    print_sol(&S, "output", "pavage_forme_arbitraire_rentable.txt", &B);
    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}

int main(int argc, char** argv) {
    char* dir = "input";
    printf("\n");
    if (argc > 1) {
        dir = argv[1]; // dossier d'entrée
    }
    // Vérifier que le dossier existe (simple test)
    FILE* test = fopen(dir, "r");
    if (!test) {
        printf("Erreur : le dossier '%s' n'existe pas ou n'est pas accessible.\n", dir);
        return EXIT_FAILURE;
    }
    fclose(test);
    // Si aucun algorithme n'est passé, ou "all", exécuter tous
    if (argc == 2) {
        execute_all(dir);
        return EXIT_SUCCESS;
    }
    for (int i = 2; i < argc; i++) {
        char* algo = argv[i];
        if (strcmp(algo, "all") == 0) {
            execute_all(dir);
        } else if (strcmp(algo, "1x1") == 0) {
            execute_1x1(dir);
        } else if (strcmp(algo, "greedy_1x2") == 0) {
            execute_greedy_1x2(dir);
        } else if (strcmp(algo, "greedy_1x2_stock") == 0) {
            execute_greedy_1x2_stock(dir);
        } else if (strcmp(algo, "2x2") == 0) {
            execute_2x2(dir);
        } else if (strcmp(algo, "rectfusion") == 0) {
            execute_all_brique(dir);
        } else if (strcmp(algo, "forme_rentable") == 0) {
            execute_rentabilite(dir);
        } else {
            printf("Erreur : option inconnue '%s'.\n", algo);
            printf("Options disponibles : all, 1x1, greedy_1x2, greedy_1x2_stock, 2x2, rectfusion, forme_rentable\n");
        }
    }
    return EXIT_SUCCESS;
}
