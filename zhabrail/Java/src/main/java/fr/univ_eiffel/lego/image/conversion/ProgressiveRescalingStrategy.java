package fr.univ_eiffel.lego.image.conversion;

import java.awt.image.BufferedImage;
import java.util.List;

public class ProgressiveRescalingStrategy implements RescalingStrategy {

    private List<ImageRescaler> steps;

    public ProgressiveRescalingStrategy(List<ImageRescaler> steps) {
        this.steps = steps;
    }

    @Override
    public BufferedImage rescale(BufferedImage source, int targetWidth, int targetHeight) {
        BufferedImage current = source;

        int width = source.getWidth();
        int height = source.getHeight();

        for (int i = 0; i < steps.size(); i++) {
            ImageRescaler rescaler = steps.get(i);

            int stepWidth = (i == steps.size() - 1) ? targetWidth : Math.max(width / 2, targetWidth);
            int stepHeight = (i == steps.size() - 1) ? targetHeight : Math.max(height / 2, targetHeight);

            current = rescaler.rescale(current, stepWidth, stepHeight);

            width = stepWidth;
            height = stepHeight;
        }

        return current;
    }
}

