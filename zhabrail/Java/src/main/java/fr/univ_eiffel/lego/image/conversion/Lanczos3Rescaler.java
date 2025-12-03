package fr.univ_eiffel.lego.image.conversion;

import java.awt.image.BufferedImage;

public class Lanczos3Rescaler implements ImageRescaler {
	
	// Taille de la fenêtre Lanczos
	private static final int A = 3;
	
	@Override
    public BufferedImage rescale(BufferedImage source, int targetWidth, int targetHeight) {

    	// Image de sortie
        BufferedImage destination = new BufferedImage(targetWidth, targetHeight, BufferedImage.TYPE_INT_RGB);

        // Calcul du ratio entre l'image source et l'image cible
        double ratioX = (double) source.getWidth() / targetWidth;
        double ratioY = (double) source.getHeight() / targetHeight;

        // Parcours de tous les pixels de l'image cible
        for (int x = 0; x < targetWidth; x++) {
            for (int y = 0; y < targetHeight; y++) {

            	// Coordonnées réelles dans l'image source
                double sx = x * ratioX;
                double sy = y * ratioY;
                
                // Pixel entier de référence dans la source
                int ix = (int) Math.floor(sx);
                int iy = (int) Math.floor(sy);

                // Distance entre la coordonnée réelle et la coordonnée entière
                double dx = sx - ix;
                double dy = sy - iy;

                // Accumulateurs des composantes couleurs
                double r = 0, g = 0, b = 0;
                
                // Somme des poids
                double totalWeight = 0;
                
                // Parcours du voisinage vertical de taille 6
                for (int m = -A + 1; m <= A; m++) {

                	// Coordonnée réelle y du pixel voisin
                    int yy = clamp(iy + m, 0, source.getHeight() - 1);
                    
                    // Poids vertical selon la distance dy
                    double wy = lanczosWeight(m - dy);

                    // Parcours du voisinage horizontal
                    for (int n = -A + 1; n <= A; n++) {
                    	
                    	// Coordonnée réelle x du voisin
                    	int xx = clamp(ix + n, 0, source.getWidth() - 1);
                    	
                    	// Poids horizontal selon la distance dx
                        double wx = lanczosWeight(n - dx);

                        // Poids total du pixel
                        double w = wx * wy;

                        // Lecture du pixel source
                        int rgb = source.getRGB(xx, yy);
                        int rr = (rgb >> 16) & 0xFF;
                        int gg = (rgb >> 8) & 0xFF;
                        int bb = rgb & 0xFF;

                        // Ajout aux accululateurs
                        r += rr * w;
                        g += gg * w;
                        b += bb * w;

                        // Accumulation des poids pour normalisation
                        totalWeight += w;
                    }
                }

                // Normalisation des poids
                r /= totalWeight;
                g /= totalWeight;
                b /= totalWeight;

                // Eviter le depassement 
                int R = clamp((int) Math.round(r), 0, 255);
                int G = clamp((int) Math.round(g), 0, 255);
                int B = clamp((int) Math.round(b), 0, 255);

                // Ecriture du pixel dans l'image de destination
                destination.setRGB(x, y, (R << 16) | (G << 8) | B);
            }
        }

        return destination;
    }
	
	private double lanczosWeight(double x) {
		// Utilisation de la distance absolue
		x = Math.abs(x);
		
		// Poids maximal au centre
		if (x == 0.0) {
			return 1.0;
		}
		// Pixels trop éloignés
		if (x >= A) {
			return 0.0;
		}
		// Définition standard du filtre Lanczos
		return sinc(x) * sinc(x / A);
	}
	
	private double sinc(double x) {
		if (x == 0.0) {
			return 1.0;
		}
		// Fonction sinc
		return Math.sin(Math.PI * x) / (Math.PI * x);
	}
	
	private int clamp(int v, int min, int max) {
		// Limite la valeur entre min et max
		return Math.max(min, Math.min(max, v));
	}
}
                    
                	
                	
                


