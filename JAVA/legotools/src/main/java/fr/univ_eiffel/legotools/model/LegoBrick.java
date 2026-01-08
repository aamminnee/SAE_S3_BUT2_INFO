package fr.univ_eiffel.legotools.model;

public class LegoBrick {
    // position x en pixels ou tenons
    private int x;
    // position y en pixels ou tenons
    private int y;
    // largeur de la brique
    private int width;
    // hauteur de la brique
    private int height;
    // couleur hexadécimale de la brique
    private String color;

    // constructeur par défaut pour la désérialisation
    public LegoBrick() {}

    // constructeur complet pour initialiser une brique
    public LegoBrick(int x, int y, int width, int height, String color) {
        this.x = x;
        this.y = y;
        this.width = width;
        this.height = height;
        this.color = color;
    }

    public int getX() { 
        return x; 
    }

    public int getY() { 
        return y; 
    }

    public int getWidth() { 
        return width; 
    }
    public int getHeight() { 
        return height; 
    }

    public String getColor() { 
        return color; 
    }
}