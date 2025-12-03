package fr.univ_eiffel.lego.image.conversion;

import java.awt.image.BufferedImage;

public interface RescalingStrategy {
	BufferedImage rescale(BufferedImage source, int targetWidth, int targetHeight);
}
