package fr.univ_eiffel.lego.image.conversion;

public class RescalerFactory {
	
	// Méthode de redimensionnement
	public enum RescalingMethod {
		NEAREST_NEIGHBOR,
		BILINEAR,
		BICUBIC,
		LANCZOS3
	}
	
	public static ImageRescaler createRescaler(RescalingMethod method) {
        switch(method) {
            case NEAREST_NEIGHBOR:
                return new NearestNeighborRescaler();
            case BILINEAR:
                return new BilinearRescaler(); 
            case BICUBIC:
                return new BicubicRescaler();
            case LANCZOS3:
            	return new Lanczos3Rescaler();
            default:
                return new NearestNeighborRescaler();
        }
    }
}
