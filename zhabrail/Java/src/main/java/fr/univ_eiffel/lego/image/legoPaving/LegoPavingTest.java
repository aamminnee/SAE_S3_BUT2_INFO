package fr.univ_eiffel.lego.image.legoPaving;

import java.awt.image.BufferedImage;
import java.io.File;
import javax.imageio.ImageIO;

public class LegoPavingTest {
    public static void main(String[] args) {
    	if (args.length < 1) {
            System.out.println("Usage: java LegoPavingTest <chemin_image_source>");
            return;
        }
    	
        try {
            String inputFile1x1 = "paving/out1x1.txt";

            LegoPaving paving1x1 = LegoPavingLoader.load1x1(inputFile1x1);
          
            String sourceImagePath = args[0];
            BufferedImage sourceImg = ImageIO.read(new File(sourceImagePath));
            int width = sourceImg.getWidth();
            int height = sourceImg.getHeight();

            BufferedImage img1x1 = paving1x1.generateImage(width, height);

            String baseName = new File(sourceImagePath).getName();
            baseName = baseName.substring(0, baseName.lastIndexOf('.'));

            
            File outFile = new File("images_result/" + baseName + "_lego.png");
            ImageIO.write(img1x1, "png", outFile);

            System.out.println("Image 1x1 générée dans le dossier images_result/");

        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}
