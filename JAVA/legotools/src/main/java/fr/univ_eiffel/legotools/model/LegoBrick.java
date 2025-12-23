package fr.univ_eiffel.legotools.model;

public class LegoBrick {
    // Position (x, y) en pixels/tenons
    private int x;
    private int y;
    // Dimensions
    private int width;
    private int height;
    // Couleur (Hexadécimal, ex: "FF0000")
    private String color;

    // Constructeur vide pour Gson
    public LegoBrick() {}

    public LegoBrick(int x, int y, int width, int height, String color) {
        this.x = x;
        this.y = y;
        this.width = width;
        this.height = height;
        this.color = color;
    }

    // Getters
    public int getX() { return x; }
    public int getY() { return y; }
    public int getWidth() { return width; }
    public int getHeight() { return height; }
    public String getColor() { return color; }
}