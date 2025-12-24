#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "dependance/main.h"

void execute_all(char * dir) {  
    execute_stock(dir);
    execute_classique(dir);
}

void execute_stock(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo v4 stock ===================
    printf("\nExécution de la version 4 algo gestion de stock...\n");
    Solution S = run_algo_stock(&I, &B);
    print_sol(&S, "output", "pavage_stock.txt", &B);
    // Libération mémoire
    freeSolution(S);
    freeImage(I);
    freeBrique(B);
}

void execute_classique(char * dir) {  
    // Charger l'image et la liste de briques
    Image I;
    BriqueList B;
    load_image(dir, &I);
    load_brique(dir, &B);
    // =================== Algo v4 classique (sans stock) ===================
    printf("\nExécution de la version 4 algo classique...\n");
    Solution S = run_algo_classique(&I, &B);
    print_sol(&S, "output", "pavage_classique.txt", &B);
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
        } else if (strcmp(algo, "stock") == 0) {
            execute_stock(dir);
        } else if (strcmp(algo, "classique") == 0) {
            execute_classique(dir);
        } else {
            printf("Erreur : option inconnue '%s'.\n", algo);
            printf("Options disponibles : all, stock, classique\n");
        }
    }
    return EXIT_SUCCESS;
}
