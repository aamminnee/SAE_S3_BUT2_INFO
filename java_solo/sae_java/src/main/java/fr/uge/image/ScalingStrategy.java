package fr.uge.image;

import java.awt.image.BufferedImage;

public interface ScalingStrategy {
    /**
     * Redimensionne une image source vers les dimensions cibles.
     * @param source Image d'origine
     * @param targetWidth Largeur souhaitée
     * @param targetHeight Hauteur souhaitée
     * @return Nouvelle image redimensionnée
     */
    BufferedImage scale(BufferedImage source, int targetWidth, int targetHeight);
    String getName();
}