package fr.univ_eiffel.legotools;

import fr.univ_eiffel.legotools.factory.StockManager;
import fr.univ_eiffel.legotools.factory.api.AccountRefiller;
import fr.univ_eiffel.legotools.factory.api.LegoFactory;
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
import java.util.HashMap;
import javax.imageio.ImageIO;

public class App {

    private static final Dotenv dotenv = Dotenv.configure().ignoreIfMissing().load();

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
                case "proactive" -> runProactiveOrder();
                case "visualize" -> runVisualize(args);
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
        System.out.println("  4. Commander : java -jar legotools.jar order");
        System.out.println("  5. Commande proactive : java -jar legotools.jar proactive");
        System.out.println("  6. Visualiser : java -jar legotools.jar visualize <input_txt> <output_png>");
    }

    private static void runRefill() throws IOException {
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");

        if (email == null || key == null) {
            System.err.println("Erreur : Variables LEGOFACTORY manquantes.");
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
            case "lanczos" -> processor.setStrategy(new LanczosStrategy()); // // Ajout accès direct
            // // MODIFICATION ICI : On ajoute LanczosStrategy à la liste et on passe à 4 étapes
            case "stepwise" -> processor.setStrategy(new StepwiseStrategy(List.of(
                new NearestNeighborStrategy(),
                new BilinearStrategy(),
                new BicubicStrategy(),
                new LanczosStrategy()
            ), 4));
            case "neighbor" -> processor.setStrategy(new NearestNeighborStrategy());
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
        String algoArg = (args.length > 4) ? args[4] : "all";

        BufferedImage source = ImageIO.read(new File(inputPath));
        if (source == null) throw new IOException("Image introuvable : " + inputPath);

        PavingService service = new PavingService(exePath);

        List<String> algos;
        if ("all".equalsIgnoreCase(algoArg)) {
            algos = List.of("v4_stock", "v4_libre", "v4_rupture", "v4_rentable");
        } else {
            algos = List.of(algoArg);
        }

        String basePath = outputBasePath;
        if (basePath.toLowerCase().endsWith(".png")) {
            basePath = basePath.substring(0, basePath.length() - 4);
        } else if (basePath.toLowerCase().endsWith(".jpg")) {
            basePath = basePath.substring(0, basePath.length() - 4);
        }

        for (String algo : algos) {
            System.out.println("\n--- Traitement : " + algo + " ---");
            try {
                String finalNamePng = basePath + "_" + algo + ".png";
                String finalNameTxt = basePath + "_" + algo + ".txt";
                BufferedImage result = service.generatePaving(source, algo, new File(finalNameTxt));
                ImageIO.write(result, "png", new File(finalNamePng));
                System.out.println("Image générée : " + finalNamePng);
            } catch (Exception e) {
                System.err.println("Erreur sur l'algo " + algo + " : " + e.getMessage());
            }
        }
    }

    private static void runOrder() {
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");
        if (email == null) return;

        HttpRestFactory factory = new HttpRestFactory(email, key);
        StockManager stock = new StockManager();
        stock.showStock();

        try {
            long balance = factory.getBalance();
            System.out.println("Solde : " + balance);

            Map<String, Integer> panier = Map.of("2-2/c9cae2", 1);
            
            // // récupération de l'objet quote (id + prix) pour stockage
            LegoFactory.Quote quote = factory.requestQuote(panier);
            
            factory.acceptQuote(quote.id());
            
            // // enregistrement correct de la commande en base avec id et prix
            stock.recordFactoryOrder(quote.id(), quote.price(), panier);
            
            System.out.println("Attente livraison...");
            processDelivery(factory, stock, quote.id());

        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private static void runProactiveOrder() {
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");
        if (email == null) {
             System.err.println("Variables d'environnement manquantes");
             return;
        }

        StockManager stockManager = new StockManager();
        HttpRestFactory factory = new HttpRestFactory(email, key);
        
        System.out.println("Analyse proactive du stock...");
        
        Map<String, Integer> popularItems = stockManager.getPopularItems(10);
        Map<String, Integer> currentStock = stockManager.getStockCounts();
        Map<String, Integer> toOrder = new HashMap<>();
        
        int TARGET_BUFFER = 50; 
        
        for (Map.Entry<String, Integer> entry : popularItems.entrySet()) {
            String itemKey = entry.getKey();
            int current = currentStock.getOrDefault(itemKey, 0);
            
            if (current < TARGET_BUFFER) {
                int needed = TARGET_BUFFER - current;
                toOrder.put(itemKey, needed);
                System.out.println("Besoin identifié : " + itemKey + " (actuel: " + current + ", commande: " + needed + ")");
            }
        }
        
        if (toOrder.isEmpty()) {
            System.out.println("Stock suffisant, pas de commande nécessaire.");
            return;
        }
        
        try {
            System.out.println("Demande de devis pour réapprovisionnement...");
            // // récupération de l'objet quote avec le prix
            LegoFactory.Quote quote = factory.requestQuote(toOrder);
            
            System.out.println("Validation commande " + quote.id());
            factory.acceptQuote(quote.id());
            
            // // enregistrement correct de la commande
            stockManager.recordFactoryOrder(quote.id(), quote.price(), toOrder);
            
            processDelivery(factory, stockManager, quote.id());
            
        } catch (Exception e) {
            System.err.println("Echec commande proactive : " + e.getMessage());
        }
    }
    
    private static void processDelivery(HttpRestFactory factory, StockManager stock, String quoteId) throws IOException, InterruptedException {
        List<FactoryBrick> briques = List.of();
        while (briques.isEmpty()) {
            briques = factory.retrieveOrder(quoteId);
            if (briques.isEmpty()) {
                System.out.print(".");
                Thread.sleep(1000);
            }
        }
        System.out.println("\nRéception de " + briques.size() + " briques.");
        
        List<FactoryBrick> verifiedBricks = new java.util.ArrayList<>();
        for (FactoryBrick b : briques) {
            if (factory.verifyBrick(b)) {
                verifiedBricks.add(b);
            } else {
                System.err.println("ALERTE : Brique rejetée (signature invalide) : " + b.serial());
            }
        }
        
        if (!verifiedBricks.isEmpty()) {
            stock.addBricks(verifiedBricks);
        }
    }

    private static void runVisualize(String[] args) throws IOException {
        if (args.length < 3) {
            System.out.println("Usage: visualize <input_txt> <output_png>");
            return;
        }
        String inputPath = args[1];
        String outputPath = args[2];

        PavingService service = new PavingService("dummy");
        service.createVisualization(new File(inputPath), new File(outputPath));
        System.out.println("Visualisation générée : " + outputPath);
    }
}