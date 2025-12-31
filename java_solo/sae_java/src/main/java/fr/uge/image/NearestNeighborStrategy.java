package fr.uge.image;

import java.awt.image.BufferedImage;

public class NearestNeighborStrategy implements ScalingStrategy{
    @Override
    public String getName() {
        return "Plus Proche Voisin (Rapide, très Pixelisé)";
    }

    @Override
    public BufferedImage scale(BufferedImage source, int targetWidth, int targetHeight) {
        BufferedImage output = new BufferedImage(targetWidth, targetHeight, BufferedImage.TYPE_INT_RGB);
        int wSrc = source.getWidth();
        int hSrc = source.getHeight();
        double xRatio = (double) wSrc / targetWidth;
        double yRatio = (double) hSrc / targetHeight;
        for (int y = 0; y < targetHeight; y++) {
            for (int x = 0; x < targetWidth; x++) {
                // Calcul des coordonnées dans l'image source.
                double srcXFloat = x * xRatio;
                double srcYFloat = y * yRatio;
                // Trouver le pixel source le plus proche (arrondi à l'entier).
                int srcX = (int) srcXFloat;
                int srcY = (int) srcYFloat;
                // Récupérer la valeur du pixel et l'appliquer à l'image de sortie.
                int pixelValue = source.getRGB(
                        Math.min(srcX, wSrc - 1),
                        Math.min(srcY, hSrc - 1)
                );
                output.setRGB(x, y, pixelValue);
            }
        }
        return output;
    }
}
