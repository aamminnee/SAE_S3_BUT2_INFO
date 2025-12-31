package fr.uge.image;

import javax.imageio.ImageIO;
import java.awt.image.BufferedImage;
import java.io.File;
import java.io.IOException;

public class ImageService {
    public BufferedImage processImage(File uploadedFile, int targetWidth, int targetHeight, ScalingStrategy strategy) throws IOException {
        // Lecture du fichier
        BufferedImage source = ImageIO.read(uploadedFile);
        // Application de la stratégie selectionnée
        BufferedImage scaledImage = strategy.scale(source, targetWidth, targetHeight);
        return scaledImage; // Retourne l'aperçu ou le passe à l'étape suivante.
    }
}
