CC=gcc
CFLAGS=-g -Wall -Wextra -Icode/dependance
SRC_DIR=code
OBJ_DIR=code/exec
DEST_FILE=output
BIN=$(OBJ_DIR)/pavage

SRC=$(SRC_DIR)/main.c \
    $(SRC_DIR)/image.c \
    $(SRC_DIR)/brique.c \
    $(SRC_DIR)/solution_1x1.c \
    $(SRC_DIR)/util.c \
    $(SRC_DIR)/matching.c \
    $(SRC_DIR)/solution.c \
    $(SRC_DIR)/solution_stock.c \
    $(SRC_DIR)/solution_classique.c

OBJ=$(OBJ_DIR)/main.o \
    $(OBJ_DIR)/image.o \
    $(OBJ_DIR)/brique.o \
    $(OBJ_DIR)/solution_1x1.o \
    $(OBJ_DIR)/util.o \
    $(OBJ_DIR)/matching.o \
    $(OBJ_DIR)/solution.o \
    $(OBJ_DIR)/solution_stock.o \
    $(OBJ_DIR)/solution_classique.o

OUT=$(DEST_FILE)/pavage_stock.txt \
    $(DEST_FILE)/pavage_classique.txt

# variable pour choisir quel algo (ou quels algos) exécuter
ARGO ?= all

all: $(BIN)

# compilation des .c en .o
$(OBJ_DIR)/%.o: $(SRC_DIR)/%.c
	$(CC) $(CFLAGS) -c $< -o $@

# création de l'exécutable
$(BIN): $(OBJ)
	$(CC) $(OBJ) -o $(BIN)

# nettoyage des fichiers générés
clean:
	rm -f $(OBJ) $(BIN) $(OUT)

# exécute pavage avec argument(s)
run: $(BIN)
	./$(BIN) input $(ARGO)

# debug avec gdb
debug: $(BIN)
	@gdb ./$(BIN)
