package fr.univ_eiffel.legotools.image;

import java.awt.image.BufferedImage;

public class BilinearStrategy implements ResolutionStrategy {
    @Override
    public BufferedImage resize(BufferedImage source, int targetWidth, int targetHeight) {
        var output = new BufferedImage(targetWidth, targetHeight, BufferedImage.TYPE_INT_RGB);
        // Ratio inverse pour parcourir la source
        double xRatio = (double) (source.getWidth() - 1) / targetWidth;
        double yRatio = (double) (source.getHeight() - 1) / targetHeight;
        for (int y = 0; y < targetHeight; y++) {
            for (int x = 0; x < targetWidth; x++) {
                // Coordonnées précises dans la source
                double srcX = x * xRatio;
                double srcY = y * yRatio;
                int xBase = (int) srcX;
                int yBase = (int) srcY;
                double xDiff = srcX - xBase;
                double yDiff = srcY - yBase;
                int pixelA = source.getRGB(xBase, yBase);
                int pixelB = source.getRGB(xBase + 1, yBase);
                int pixelC = source.getRGB(xBase, yBase + 1);
                int pixelD = source.getRGB(xBase + 1, yBase + 1);
                int newPixel = interpolateColor(pixelA, pixelB, pixelC, pixelD, xDiff, yDiff);
                output.setRGB(x, y, newPixel);
            }
        }
        return output;
    }

    private int interpolateColor(int a, int b, int c, int d, double xDiff, double yDiff) {
        int[] rgbA = getRGB(a);
        int[] rgbB = getRGB(b);
        int[] rgbC = getRGB(c);
        int[] rgbD = getRGB(d);

        int[] result = new int[3];
        for (int i = 0; i < 3; i++) { // pour R, G et B
            // formule bilinéaire : val = A(1-x)(1-y) + B(x)(1-y) + C(1-x)(y) + D(x)(y)
            double val = 
                rgbA[i] * (1 - xDiff) * (1 - yDiff) +
                rgbB[i] * xDiff * (1 - yDiff) +
                rgbC[i] * (1 - xDiff) * yDiff +
                rgbD[i] * xDiff * yDiff;
            result[i] = (int) val;
        }
        return toRGB(result[0], result[1], result[2]);
    }
}