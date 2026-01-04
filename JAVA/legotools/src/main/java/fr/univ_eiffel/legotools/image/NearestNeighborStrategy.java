package fr.univ_eiffel.legotools.image;

import java.awt.image.BufferedImage;

public class NearestNeighborStrategy implements ResolutionStrategy {
    @Override
    public BufferedImage resize(BufferedImage source, int targetWidth, int targetHeight) {
        var output = new BufferedImage(targetWidth, targetHeight, source.getType());
        double xRatio = (double) source.getWidth() / targetWidth;
        double yRatio = (double) source.getHeight() / targetHeight;

        for (int y = 0; y < targetHeight; y++) {
            for (int x = 0; x < targetWidth; x++) {
                // le pixel correspondant sans interpolation
                int srcX = (int) (x * xRatio);
                int srcY = (int) (y * yRatio);
                output.setRGB(x, y, source.getRGB(srcX, srcY));
            }
        }
        return output;
    }
}