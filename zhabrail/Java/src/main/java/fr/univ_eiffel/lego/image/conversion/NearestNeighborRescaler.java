package fr.univ_eiffel.lego.image.conversion;

import java.awt.image.BufferedImage;

public class NearestNeighborRescaler implements ImageRescaler {
	
	@Override
	public BufferedImage rescale(BufferedImage source, int targetWidth, int targetHeight) {
		
		// Création de l'image vide
		BufferedImage destination = new BufferedImage(targetWidth, targetHeight, BufferedImage.TYPE_INT_RGB);
		
		// Calcule du ratio
		double widthRatio = (double) source.getWidth() / targetWidth;
		double heightRatio = (double) source.getHeight() / targetHeight;
		
		// Parcours la nouvelle image
		for (int x = 0; x < targetWidth; x++) {
			for (int y = 0; y < targetHeight; y++) {
				
				// Calcule les coordonnées correspondantes 
				int sourceX = (int) (x * widthRatio);
				int sourceY = (int) (y * heightRatio);
				
				// Récupère la couleur du pixel à la position calculée
				// Place cette couleur dans le pixel (x,y) de la nouvelle image
				destination.setRGB(x,  y,  source.getRGB(sourceX, sourceY));
			}
		}
		
		return destination;
	}

}
