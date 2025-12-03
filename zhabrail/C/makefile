# Nom de l'exécutable
EXEC = pavage

# Fichiers sources
SRC = main.c fonctions.c algo1x1.c algoGreedyMatching.c algoStockAmeliore.c algoStockForme.c algo2x2.c algo4x2.c

# Génération automatique des fichiers objets
OBJ = $(SRC:.c=.o)

# Options du compilateur
CC = gcc
CFLAGS = -Wall -Wextra -O2 -std=c11

# Règle par défaut : compilation de l'exécutable
all: $(EXEC)

# Compilation de l'exécutable
$(EXEC): $(OBJ)
	$(CC) $(CFLAGS) -o $@ $^

# Compilation des fichiers .c en .o
%.o: %.c structures.h
	$(CC) $(CFLAGS) -c $< -o $@

# Nettoyage
clean:
	rm -f $(OBJ) $(EXEC)

# Exécuter le programme directement avec un dossier de test
run:
	./$(EXEC) testMatching/
