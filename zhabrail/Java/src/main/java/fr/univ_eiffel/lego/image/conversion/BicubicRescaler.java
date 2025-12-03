package fr.univ_eiffel.lego.image.conversion;

import java.awt.image.BufferedImage;

public class BicubicRescaler implements ImageRescaler {

	// Coefficient du filtre bicubique
    private static final double a = -0.5;

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

                // Boucle sur les 4 voisins verticaux
                for (int m = -1; m <= 2; m++) {
                	
                	// Coordonnée réelle y du voisin
                    int yy = clamp(iy + m, 0, source.getHeight() - 1);
                    
                    // Poids vertical selon dy
                    double wy = cubicWeight(m - dy);

                    // Accumulateurs horizontaux 
                    double rRow = 0, gRow = 0, bRow = 0;

                    // Boucle sur les 4 voisin horizontaux
                    for (int n = -1; n <= 2; n++) {
                    	
                    	// Coordonnée réelle x du voison
                        int xx = clamp(ix + n, 0, source.getWidth() - 1);
                        
                        // Poids horizontal selon dx
                        double wx = cubicWeight(n - dx);

                        // Lecture du pixel voisin
                        int rgb = source.getRGB(xx, yy);
                        
                        // Extraction des composantes couleur
                        int rr = (rgb >> 16) & 0xFF;
                        int gg = (rgb >> 8) & 0xFF;
                        int bb = rgb & 0xFF;

                        // Ajout de la contribution horizontale
                        rRow += rr * wx;
                        gRow += gg * wx;
                        bRow += bb * wx;
                    }

                    // Ajout de la contribution verticale
                    r += rRow * wy;
                    g += gRow * wy;
                    b += bRow * wy;
                }

                // S'assure que les valeurs sont entre 0 et 255
                int R = clamp((int) Math.round(r), 0, 255);
                int G = clamp((int) Math.round(g), 0, 255);
                int B = clamp((int) Math.round(b), 0, 255);

                // Reconstruction du pixel final
                destination.setRGB(x, y, (R << 16) | (G << 8) | B);
            }
        }

        return destination;
    }

    private double cubicWeight(double t) {
    	// Distance absolue
        t = Math.abs(t);
        
        // Si proche du pixel central
        if (t <= 1) {
            return (a + 2) * t * t * t - (a + 3) * t * t + 1;
            
        // Sinon pixels plus éloignés
        } else if (t < 2) {
            return a * t * t * t - 5 * a * t * t + 8 * a * t - 4 * a;
        }
        
        return 0;
    }

    private int clamp(int v, int min, int max) {
    	// Limite la valeur entre un minimum et un maximum
        return Math.max(min, Math.min(max, v));
    }
}
