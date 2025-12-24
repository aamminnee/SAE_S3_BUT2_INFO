package fr.univ_eiffel.legotools;

import fr.univ_eiffel.legotools.factory.StockManager;
import fr.univ_eiffel.legotools.factory.api.AccountRefiller;
import fr.univ_eiffel.legotools.factory.impl.HttpRestFactory;
import fr.univ_eiffel.legotools.model.FactoryBrick;
import fr.univ_eiffel.legotools.image.*;
import fr.univ_eiffel.legotools.paving.PavingService;
import io.github.cdimascio.dotenv.Dotenv; 

import java.awt.image.BufferedImage;
import java.io.File;
import java.io.IOException;
import java.util.List;
import java.util.Map;
import javax.imageio.ImageIO;

public class App {

    // charge le .env (ignore s'il est absent pour éviter de planter en prod)
    private static final Dotenv dotenv = Dotenv.configure().ignoreIfMissing().load();

    // méthode utilitaire : cherche dans .env, sinon dans le système
    private static String getEnv(String key) {
        String value = dotenv.get(key);
        if (value == null) {
            return System.getenv(key);
        }
        return value;
    }

    public static void main(String[] args) {
        if (args.length < 1) {
            printUsage();
            return;
        }

        String command = args[0];

        try {
            switch (command) {
                case "refill" -> runRefill();
                case "resize" -> runResize(args);
                case "pave" -> runPave(args);
                case "order" -> runOrder();
                default -> {
                    System.err.println("Commande inconnue : " + command);
                    printUsage();
                }
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private static void printUsage() {
        System.out.println("Usage :");
        System.out.println("  1. Recharger le compte : java -jar legotools.jar refill");
        System.out.println("  2. Redimensionner : java -jar legotools.jar resize <input> <output> <WxH> [strategy]");
        System.out.println("  3. Paver : java -jar legotools.jar pave <input> <output_base> <exe_c> [algo|all]");
        System.out.println("     Algos disponibles : v4_stock, v4_libre, v4_rupture, v4_rentable, all");
        System.out.println("  4. Commander : java -jar legotools.jar order");
    }

    private static void runRefill() throws IOException {
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");

        if (email == null || key == null) {
            System.err.println("Erreur : Variables LEGOFACTORY manquantes dans le .env ou le système.");
            return;
        }
        var refiller = new AccountRefiller(email, key);
        System.out.println("Nouveau solde : " + refiller.refill());
    }

    private static void runResize(String[] args) throws IOException {
        if (args.length < 4) {
            System.out.println("Usage: resize <input> <output> <WxH> [strategy]");
            return;
        }
        String input = args[1];
        String output = args[2];
        String[] dims = args[3].split("x");
        int w = Integer.parseInt(dims[0]);
        int h = Integer.parseInt(dims[1]);
        String algo = (args.length > 4) ? args[4].toLowerCase() : "neighbor";

        ImageProcessor processor = new ImageProcessor();
        switch (algo) {
            case "bilinear" -> processor.setStrategy(new BilinearStrategy());
            case "bicubic" -> processor.setStrategy(new BicubicStrategy());
            case "stepwise" -> processor.setStrategy(new StepwiseStrategy(List.of(new BilinearStrategy()), 3));
            case "neighbor" -> processor.setStrategy(new NearestNeighborStrategy());
            case "lanczos" -> processor.setStrategy(new LanczosStrategy());
            default -> System.out.println("Stratégie inconnue, utilisation de NearestNeighbor.");
        }
        processor.processImage(input, output, w, h);
    }

    private static void runPave(String[] args) throws IOException, InterruptedException {
        if (args.length < 4) {
            System.out.println("Usage: pave <input> <output_prefix> <exe_c> [algo|all]");
            return;
        }
        
        String inputPath = args[1];
        String outputBasePath = args[2];
        String exePath = args[3];
        // si l'argument algo est 'all' ou absent, on fait tout
        String algoArg = (args.length > 4) ? args[4] : "all";

        BufferedImage source = ImageIO.read(new File(inputPath));
        if (source == null) throw new IOException("Image introuvable : " + inputPath);

        PavingService service = new PavingService(exePath);

        // liste des algorithmes à exécuter
        List<String> algos;
        if ("all".equalsIgnoreCase(algoArg)) {
            algos = List.of("v4_stock", "v4_libre", "v4_rupture", "v4_rentable");
        } else {
            algos = List.of(algoArg);
        }

        // nettoyage du chemin de sortie pour gérer l'extension proprement
        String basePath = outputBasePath;
        if (basePath.toLowerCase().endsWith(".png")) {
            basePath = basePath.substring(0, basePath.length() - 4);
        } else if (basePath.toLowerCase().endsWith(".jpg")) {
            basePath = basePath.substring(0, basePath.length() - 4);
        }

        // boucle sur chaque algorithme demandé
        for (String algo : algos) {
            System.out.println("\n--- Traitement : " + algo + " ---");
            try {
                // génération du pavage via le service (qui appelle le C)
                BufferedImage result = service.generatePaving(source, algo);

                // construction du nom de fichier final (ex: output_v4_stock.png)
                String finalName = basePath + "_" + algo + ".png";
                ImageIO.write(result, "png", new File(finalName));
                
                System.out.println("Image générée : " + finalName);
            } catch (Exception e) {
                System.err.println("Erreur sur l'algo " + algo + " : " + e.getMessage());
                // on continue la boucle même si un algo échoue pour traiter les autres
            }
        }
    }

    private static void runOrder() {
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");
        if (email == null) return;

        // 1. instanciation
        HttpRestFactory factory = new HttpRestFactory(email, key);
        
        StockManager stock = new StockManager();
        stock.showStock();

        try {
            long balance = factory.getBalance();
            System.out.println("Solde : " + balance);

            if (balance < 100) {
                factory.rechargeAccount(100);
            }

            Map<String, Integer> panier = Map.of("2-2/c9cae2", 1);
            String quoteId = factory.requestQuote(panier);
            factory.acceptQuote(quoteId);
            
            System.out.println("Attente livraison...");
            List<FactoryBrick> briques = List.of();
            while (briques.isEmpty()) {
                briques = factory.retrieveOrder(quoteId);
                if (briques.isEmpty()) Thread.sleep(1000);
            }
            
            // 2. vérification et stockage
            System.out.println("Réception de " + briques.size() + " briques.");
            List<FactoryBrick> verifiedBricks = new java.util.ArrayList<>();
            
            for (FactoryBrick b : briques) {
                if (factory.verifyBrick(b)) {
                    System.out.println("Brique authentique : " + b.serial());
                    verifiedBricks.add(b);
                } else {
                    System.err.println("ALERTE : Brique contrefaite détectée ! " + b.serial());
                }
            }

            // 3. sauvegarde dans le stock local
            stock.addBricks(verifiedBricks);
            stock.showStock();

        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}