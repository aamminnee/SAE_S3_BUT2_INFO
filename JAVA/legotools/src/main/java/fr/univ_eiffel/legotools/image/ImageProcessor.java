package fr.univ_eiffel.legotools.image;

import java.awt.image.BufferedImage;
import javax.imageio.ImageIO;
import java.io.File;
import java.io.IOException;

public class ImageProcessor {

    private ResolutionStrategy strategy;

    public ImageProcessor() {
        this.strategy = new NearestNeighborStrategy();
    }

    public void setStrategy(ResolutionStrategy strategy) {
        this.strategy = strategy;
    }

    public void processImage(String inputPath, String outputPath, int targetWidth, int targetHeight) throws IOException {
        // 1. Chargement
        File inputFile = new File(inputPath);
        BufferedImage source = ImageIO.read(inputFile);
        if (source == null) {
            throw new IOException("Impossible de lire le fichier image : " + inputPath);
        }

        System.out.println("Traitement de l'image avec la stratégie : " + strategy.getClass().getSimpleName());
        BufferedImage result = strategy.resize(source, targetWidth, targetHeight);
        File outputFile = new File(outputPath);
        String fileName = outputFile.getName();
        String formatName = fileName.substring(fileName.lastIndexOf('.') + 1);
        
        ImageIO.write(result, formatName, outputFile);
        System.out.println("Image sauvegardée dans : " + outputPath);
    }
}