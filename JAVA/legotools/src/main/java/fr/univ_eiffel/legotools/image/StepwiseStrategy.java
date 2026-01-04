package fr.univ_eiffel.legotools.image;

import java.awt.image.BufferedImage;
import java.util.List;

public class StepwiseStrategy implements ResolutionStrategy {
    private final List<ResolutionStrategy> strategies;
    private final int steps;

    public StepwiseStrategy(List<ResolutionStrategy> strategies, int numberOfSteps) {
        this.strategies = strategies;
        this.steps = numberOfSteps;
    }

    @Override
    public BufferedImage resize(BufferedImage source, int targetWidth, int targetHeight) {
        BufferedImage currentImage = source;
        int w = source.getWidth();
        int h = source.getHeight();
        double wStep = Math.pow((double)targetWidth / w, 1.0 / steps);
        double hStep = Math.pow((double)targetHeight / h, 1.0 / steps);
        for (int i = 0; i < steps; i++) {
            // Calcul de la nouvelle taille intermédiaire
            int nextW = (int) (w * Math.pow(wStep, i + 1));
            int nextH = (int) (h * Math.pow(hStep, i + 1));
            if (i == steps - 1) {
                nextW = targetWidth;
                nextH = targetHeight;
            }
            ResolutionStrategy strategyToUse = strategies.get(i % strategies.size());
            System.out.println("Étape " + (i+1) + "/" + steps + " : Redimensionnement vers " + nextW + "x" + nextH + " avec " + strategyToUse.getClass().getSimpleName());
            currentImage = strategyToUse.resize(currentImage, nextW, nextH);
        }
        
        return currentImage;
    }
}