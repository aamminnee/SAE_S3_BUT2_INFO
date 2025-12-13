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
    $(SRC_DIR)/solution_greedy_1x2.c \
    $(SRC_DIR)/solution_greedy_1x2_stock.c \
    $(SRC_DIR)/solution_2x2.c \
    $(SRC_DIR)/solution_rectfusion.c \
    $(SRC_DIR)/solution_forme_arbitraire_rentable.c \
    $(SRC_DIR)/solution.c \
    $(SRC_DIR)/solution_v4_stock.c

OBJ=$(OBJ_DIR)/main.o \
    $(OBJ_DIR)/image.o \
    $(OBJ_DIR)/brique.o \
    $(OBJ_DIR)/solution_1x1.o \
    $(OBJ_DIR)/util.o \
    $(OBJ_DIR)/matching.o \
    $(OBJ_DIR)/solution_greedy_1x2.o \
    $(OBJ_DIR)/solution_greedy_1x2_stock.o \
    $(OBJ_DIR)/solution_2x2.o \
    $(OBJ_DIR)/solution_rectfusion.o \
    $(OBJ_DIR)/solution_forme_arbitraire_rentable.o \
    $(OBJ_DIR)/solution.o \
    $(OBJ_DIR)/solution_v4_stock.o

OUT=$(DEST_FILE)/pavage_1x1.txt \
    $(DEST_FILE)/pavage_greedy_1x2.txt \
    $(DEST_FILE)/pavage_greedy_1x2_stock.txt \
    $(DEST_FILE)/pavage_2x2.txt \
    $(DEST_FILE)/pavage_rectfusion.txt \
    $(DEST_FILE)/pavage_forme_arbitraire_rentable.txt \
    $(DEST_FILE)/pavage_v4_stock.txt

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
