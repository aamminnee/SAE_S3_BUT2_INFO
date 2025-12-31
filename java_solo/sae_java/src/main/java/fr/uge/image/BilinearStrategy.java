package fr.uge.image;

import java.awt.image.BufferedImage;

public class BilinearStrategy implements ScalingStrategy {
    @Override
    public String getName() { return "Interpolation Bilinéaire (Lissé)"; }

    @Override
    public BufferedImage scale(BufferedImage source, int targetWidth, int targetHeight) {
        BufferedImage output = new BufferedImage(targetWidth, targetHeight, BufferedImage.TYPE_INT_RGB);
        int wSrc = source.getWidth();
        int hSrc = source.getHeight();
        float xRatio = (float) (wSrc - 1) / targetWidth;
        float yRatio = (float) (hSrc - 1) / targetHeight;
        for (int y = 0; y < targetHeight; y++) {
            for (int x = 0; x < targetWidth; x++) {
                // Coordonnée source flottante
                float srcX = x * xRatio;
                float srcY = y * yRatio;
                // Partie entière (coordonnée du pixel en haut à gauche)
                int x0 = (int) srcX;
                int y0 = (int) srcY;
                // Coordonnée du pixel en bas à droite (borné)
                int x1 = Math.min(x0 + 1, wSrc - 1);
                int y1 = Math.min(y0 + 1, hSrc - 1);
                // Poids (différence entre coordonnée flottante et entière)
                float xDiff = srcX - x0;
                float yDiff = srcY - y0;
                // Récupération des 4 pixels voisins
                int pA = source.getRGB(x0, y0);
                int pB = source.getRGB(x1, y0);
                int pC = source.getRGB(x0, y1);
                int pD = source.getRGB(x1, y1);
                // Interpolation pour chaque canal (red, green, blue)
                int red = interpolate(PixelMath.getRed(pA), PixelMath.getRed(pB),
                        PixelMath.getRed(pC), PixelMath.getRed(pD), xDiff, yDiff);
                int green = interpolate(PixelMath.getGreen(pA), PixelMath.getGreen(pB),
                        PixelMath.getGreen(pC), PixelMath.getGreen(pD), xDiff, yDiff);
                int blue = interpolate(PixelMath.getBlue(pA), PixelMath.getBlue(pB),
                        PixelMath.getBlue(pC), PixelMath.getBlue(pD), xDiff, yDiff);
                output.setRGB(x, y, PixelMath.toRGB(red, green, blue));
            }
        }
        return output;
    }
    private int interpolate(int a, int b, int c, int d, float xDiff, float yDiff) {
        float val = (a * (1 - xDiff) * (1 - yDiff)) +
                (b * xDiff * (1 - yDiff)) +
                (c * (1 - xDiff) * yDiff) +
                (d * xDiff * yDiff);
        return (int) val;
    }
}
