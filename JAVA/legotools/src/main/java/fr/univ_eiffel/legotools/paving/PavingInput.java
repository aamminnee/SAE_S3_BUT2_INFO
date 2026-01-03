package fr.univ_eiffel.legotools.paving;

/**
 * Ce que Java envoie au programme C.
 */
public class PavingInput {
    int width;
    int height;
    int[][] pixels; // Matrice des couleurs (RGB int)
    boolean useStock; // Mode strict (stock) ou infini
    
    // On pourrait ajouter ici la liste du stock disponible
    // List<StockEntry> stock; 

    public PavingInput(int width, int height, int[][] pixels, boolean useStock) {
        this.width = width;
        this.height = height;
        this.pixels = pixels;
        this.useStock = useStock;
    }
}