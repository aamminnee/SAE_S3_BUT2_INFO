package fr.univ_eiffel.lego.image.conversion;

import java.awt.image.BufferedImage;
import java.io.File;
import java.util.List;

import javax.imageio.ImageIO;

public class ImageConversionTest {
	
	public static void main(String[] args) {
		// Vérifie si on a bien les 2 arguments souhaités
		if (args.length != 2) {
			System.out.println("Usage: java ImageConversionTest source.jpg 128x128");
			return;
		}
		
		try {
			File sourceFile = new File(args[0]);
	        BufferedImage source = ImageIO.read(sourceFile);

	        String[] dims = args[1].split("x");
	        int width = Integer.parseInt(dims[0]);
	        int height = Integer.parseInt(dims[1]);

	        AutoRescaler auto = new AutoRescaler();
	        List<BufferedImage> results = auto.rescale(source, width, height);

	        String baseName = sourceFile.getName().split("\\.")[0];
	        String format = sourceFile.getName().split("\\.")[1];

	        for (int i = 0; i < results.size(); i++) {
	            File outFile = new File("images_conversion/" + baseName + "_conversion_" + (i+1) + "." + format);
	            ImageIO.write(results.get(i), format, outFile);
	        }

	        System.out.println("Images progressive générée !");
			
		} catch (Exception e) {
			System.err.println("Erreur: " + e.getMessage());
			e.printStackTrace();
		}
		
	}

}
