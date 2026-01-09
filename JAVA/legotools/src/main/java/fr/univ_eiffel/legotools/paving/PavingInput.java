package fr.univ_eiffel.legotools.paving;

public class PavingInput {
    // largeur de l'image à paver
    int width;
    // hauteur de l'image à paver
    int height;
    // matrice des couleurs des pixels
    int[][] pixels; 
    // indique si l'on doit respecter strictement le stock
    boolean useStock; 
    
    // constructeur pour préparer les données de pavage
    public PavingInput(int width, int height, int[][] pixels, boolean useStock) {
        this.width = width;
        this.height = height;
        this.pixels = pixels;
        this.useStock = useStock;
    }
}