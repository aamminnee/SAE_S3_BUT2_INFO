package fr.univ_eiffel.lego.image.conversion;

import java.awt.image.BufferedImage;

public interface ImageRescaler {
	// Redimensionne une image source avec une nouvelle résolution spécifiée
	BufferedImage rescale(BufferedImage source, int targetWidth, int targetHeight);
}
