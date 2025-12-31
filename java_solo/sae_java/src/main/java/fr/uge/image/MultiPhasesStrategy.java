package fr.uge.image;

import java.awt.image.BufferedImage;
import java.util.List;

public class MultiPhasesStrategy implements ScalingStrategy {
    private final List<ScalingStrategy> strategies;
    private final List<Double> steps; // ex: 0.5 pour réduire de moitié
    public MultiPhasesStrategy(List<ScalingStrategy> strategies, List<Double> steps) {
        if (strategies.size() != steps.size()) {
            throw new IllegalArgumentException("Il faut autant d'étapes que de stratégies");
        }
        this.strategies = strategies;
        this.steps = steps;
    }

    @Override
    public String getName() { return "Multi-passes Composite"; }

    @Override
    public BufferedImage scale(BufferedImage source, int targetWidth, int targetHeight) {
        BufferedImage currentImage = source;
        // Application des étapes intermédiaires
        for (int i = 0; i < strategies.size(); i++) {
            ScalingStrategy strategy = strategies.get(i);
            double scaleFactor = steps.get(i);
            // Calcul de la taille intermédiaire
            int w = (int) (currentImage.getWidth() * scaleFactor);
            int h = (int) (currentImage.getHeight() * scaleFactor);
            // Arrêt si l'étape réduit trop (plus petit que la cible finale)
            if (w < targetWidth || h < targetHeight) break;
            currentImage = strategy.scale(currentImage, w, h);
        }
        // Finition obligatoire vers la taille finale exacte (dernière stratégie de la liste utilisée)
        ScalingStrategy finalizer = strategies.get(strategies.size() - 1);
        return finalizer.scale(currentImage, targetWidth, targetHeight);
    }
}
