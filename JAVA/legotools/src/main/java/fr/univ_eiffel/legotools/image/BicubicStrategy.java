package fr.univ_eiffel.legotools.image;
import java.awt.image.BufferedImage;

public class BicubicStrategy implements ResolutionStrategy {
    private static final double A = -0.5;

    @Override
    public BufferedImage resize(BufferedImage source, int targetWidth, int targetHeight) {
        BufferedImage output = new BufferedImage(targetWidth, targetHeight, BufferedImage.TYPE_INT_RGB);
        double xRatio = (double) source.getWidth() / targetWidth;
        double yRatio = (double) source.getHeight() / targetHeight;
        for (int y = 0; y < targetHeight; y++) {
            for (int x = 0; x < targetWidth; x++) {
                double srcX = x * xRatio;
                double srcY = y * yRatio;
                int xInt = (int) srcX;
                int yInt = (int) srcY;
                int pixel = getBicubicPixel(source, xInt, yInt, srcX - xInt, srcY - yInt);
                output.setRGB(x, y, pixel);
            }
        }
        return output;
    }

    /**
     * Fonction de pondération cubique
     */
    private double cubicKernel(double t) {
        t = Math.abs(t);
        if (t <= 1) {
            return (A + 2) * t * t * t - (A + 3) * t * t + 1;
        } else if (t < 2) {
            return A * t * t * t - 5 * A * t * t + 8 * A * t - 4 * A;
        }
        return 0;
    }

    private int getBicubicPixel(BufferedImage img, int xBase, int yBase, double dx, double dy) {
        double r = 0, g = 0, b = 0;
        // On parcourt la grille 4x4 autour du pixel : m varie de -1 à 2, n varie de -1 à 2
        for (int m = -1; m <= 2; m++) {
            for (int n = -1; n <= 2; n++) {
                int px = Math.min(Math.max(xBase + m, 0), img.getWidth() - 1);
                int py = Math.min(Math.max(yBase + n, 0), img.getHeight() - 1);
                int pixel = img.getRGB(px, py);
                int[] rgb = getRGB(pixel);
                double weight = cubicKernel(m - dx) * cubicKernel(n - dy);
                r += rgb[0] * weight;
                g += rgb[1] * weight;
                b += rgb[2] * weight;
            }
        }
        return toRGB((int) r, (int) g, (int) b);
    }
}