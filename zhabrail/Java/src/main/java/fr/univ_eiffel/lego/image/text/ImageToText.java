package fr.univ_eiffel.lego.image.text;

import java.awt.image.BufferedImage;
import java.io.File;
import java.io.FileWriter;
import javax.imageio.ImageIO;

public class ImageToText {

    public static void main(String[] args) {
        if (args.length != 1) {
            System.out.println("Usage: java ImageToText <chemin_image>");
            return;
        }

        try {
            // Lire l'image depuis l'argument
            File imageFile = new File(args[0]);
            BufferedImage img = ImageIO.read(imageFile);

            int width = img.getWidth();
            int height = img.getHeight();

            // Créer le dossier matching s'il n'existe pas
            File matchingDir = new File("matching");
            if (!matchingDir.exists()) {
                matchingDir.mkdirs();
            }

            // Nom de base de l'image (sans extension)
            String baseName = imageFile.getName();
            if (baseName.contains(".")) {
                baseName = baseName.substring(0, baseName.lastIndexOf('.'));
            }

            // Fichier de sortie dans matching/
            String outputFileName = "matching/" + baseName + ".txt";
            FileWriter fw = new FileWriter(outputFileName);

            // Écrire largeur et hauteur
            fw.write(width + " " + height + "\n");

            // Écrire les pixels en hex
            for (int y = 0; y < height; y++) {
                for (int x = 0; x < width; x++) {
                    int rgb = img.getRGB(x, y);
                    int r = (rgb >> 16) & 0xFF;
                    int g = (rgb >> 8) & 0xFF;
                    int b = rgb & 0xFF;
                    fw.write(String.format("%02X%02X%02X", r, g, b));
                    if (x < width - 1) {
                        fw.write(" ");
                    }
                }
                fw.write("\n");
            }

            fw.close();
            System.out.println("Fichier " + outputFileName + " généré avec succès !");

        } catch (Exception e) {
            System.err.println("Erreur lors de la génération du fichier texte : " + e.getMessage());
            e.printStackTrace();
        }
    }
}
