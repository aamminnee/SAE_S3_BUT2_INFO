package fr.univ_eiffel.lego.image.conversion;

import java.awt.image.BufferedImage;
import java.util.ArrayList;
import java.util.List;

import fr.univ_eiffel.lego.image.conversion.RescalerFactory.RescalingMethod;

public class AutoRescaler {

    private List<ProgressiveRescalingStrategy> strategies;

    public AutoRescaler() {
        strategies = List.of(
            // Stratégie 1
            new ProgressiveRescalingStrategy(List.of(
                RescalerFactory.createRescaler(RescalingMethod.BICUBIC),
                RescalerFactory.createRescaler(RescalingMethod.BILINEAR),
                RescalerFactory.createRescaler(RescalingMethod.LANCZOS3),
                RescalerFactory.createRescaler(RescalingMethod.NEAREST_NEIGHBOR)
            ))

            // Stratégie 2
//            new ProgressiveRescalingStrategy(List.of(
//                RescalerFactory.createRescaler(RescalingMethod.BILINEAR),
//                RescalerFactory.createRescaler(RescalingMethod.BICUBIC),
//                RescalerFactory.createRescaler(RescalingMethod.NEAREST_NEIGHBOR),
//                RescalerFactory.createRescaler(RescalingMethod.LANCZOS3)
//            )),

            // Stratégie 3 
//            new ProgressiveRescalingStrategy(List.of(
//            	RescalerFactory.createRescaler(RescalingMethod.LANCZOS3),
//                RescalerFactory.createRescaler(RescalingMethod.BICUBIC),
//                RescalerFactory.createRescaler(RescalingMethod.NEAREST_NEIGHBOR),
//                RescalerFactory.createRescaler(RescalingMethod.BILINEAR)
//            ))
        );
    }

    public List<BufferedImage> rescale(BufferedImage source, int targetWidth, int targetHeight) {
        List<BufferedImage> results = new ArrayList<>();
        for (ProgressiveRescalingStrategy strategy : strategies) {
            results.add(strategy.rescale(source, targetWidth, targetHeight));
        }
        return results;
    }
}

