package fr.univ_eiffel.lego.image.conversion;

import java.awt.Color;
import java.awt.image.BufferedImage;

public class BilinearRescaler implements ImageRescaler {
	
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
				double sourceX = x * widthRatio;
				double sourceY = y * heightRatio;
				
				// Les 4 pixels les plus proches
				int x1 = (int) Math.floor(sourceX);
				int y1 = (int) Math.floor(sourceY);
				int x2 = Math.min(x1 + 1, source.getWidth() - 1);
				int y2 = Math.min(y1 + 1, source.getHeight() - 1);
				
				// Calcule la distance entre la position réelle et le pixel
				double weightX = sourceX - x1;
				double weightY = sourceY - y1;
				
				// Appelle la méthode interpolation avec les 4 pixels formant un carré
				Color color = bilinearInterpolation(
						new Color(source.getRGB(x1, y1)),
						new Color(source.getRGB(x2, y1)),
						new Color(source.getRGB(x1, y2)),
						new Color(source.getRGB(x2, y2)),
						weightX, weightY
				);
				
				destination.setRGB(x, y, color.getRGB());
			}
		}
		
		return destination;
	}
	
	private Color bilinearInterpolation(Color c00, Color c10, Color c01, Color c11, double wx, double wy) {
		
		// Interpolation horizantale sur le bord supérieur et inférieur
		Color top = interpolateColor(c00, c10, wx);
		Color bottom = interpolateColor(c01, c11, wx);
		
		// Interpolation vertical entre les deux résultats
		return interpolateColor(top, bottom, wy);
	}
	
	private Color interpolateColor(Color c1, Color c2, double weight) {
		// Caclule des composantes
		int r = (int) (c1.getRed() * (1 - weight) + c2.getRed() * weight);
		int g = (int) (c1.getGreen() * (1 - weight) + c2.getGreen() * weight);
		int b = (int) (c1.getBlue() * (1 - weight) + c2.getBlue() * weight);
		
		// Crée la nouvelle couleur
		return new Color(
				Math.min(Math.max(r, 0), 255),
				Math.min(Math.max(g, 0), 255),
				Math.min(Math.max(b, 0), 255)
		);
	}

}
