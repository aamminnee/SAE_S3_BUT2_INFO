package fr.univ_eiffel.legotools.image;
import java.awt.image.BufferedImage;

public interface ResolutionStrategy {
    BufferedImage resize(BufferedImage source, int targetWidth, int targetHeight);
    
    // extraire les composantes RGB
    default int[] getRGB(int pixel) {
        int r = (pixel >> 16) & 0xFF;
        int g = (pixel >> 8) & 0xFF;
        int b = pixel & 0xFF;
        return new int[]{r, g, b};
    }
    
    // recomposer le pixel
    default int toRGB(int r, int g, int b) {
        r = Math.min(255, Math.max(0, r));
        g = Math.min(255, Math.max(0, g));
        b = Math.min(255, Math.max(0, b));
        return (0xFF << 24) | (r << 16) | (g << 8) | b;
    }
}