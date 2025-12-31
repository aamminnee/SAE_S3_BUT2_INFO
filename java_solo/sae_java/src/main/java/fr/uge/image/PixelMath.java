package fr.uge.image;

public class PixelMath {
    public static int getRed(int rgb) { return (rgb >> 16) & 0xFF; }
    public static int getGreen(int rgb) { return (rgb >> 8) & 0xFF; }
    public static int getBlue(int rgb) { return rgb & 0xFF; }
    public static int toRGB(int r, int g, int b) {
        return (0xFF << 24) | ((r & 0xFF) << 16) | ((g & 0xFF) << 8) | (b & 0xFF);
    }
    public static int clamp(int val) {
        return Math.max(0, Math.min(255, val));
    }
}
