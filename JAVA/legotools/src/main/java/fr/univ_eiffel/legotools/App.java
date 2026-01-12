package fr.univ_eiffel.legotools;

import fr.univ_eiffel.legotools.factory.StockManager;
import fr.univ_eiffel.legotools.factory.api.AccountRefiller;
import fr.univ_eiffel.legotools.factory.api.LegoFactory;
import fr.univ_eiffel.legotools.factory.impl.HttpRestFactory;
import fr.univ_eiffel.legotools.model.FactoryBrick;
import fr.univ_eiffel.legotools.image.*;
import fr.univ_eiffel.legotools.paving.PavingService;
import fr.univ_eiffel.legotools.scripts.PriceUpdater; // Import du script de prix

import io.github.cdimascio.dotenv.Dotenv; 

import java.awt.image.BufferedImage;
import java.io.File;
import java.io.IOException;
import java.util.List;
import java.util.ArrayList;
import java.util.Map;
import java.util.HashMap;
import javax.imageio.ImageIO;

public class App {

    private static final Dotenv dotenv = Dotenv.configure().ignoreIfMissing().load();

    public static void main(String[] args) {
        // 1. On charge les variables AVANT de les utiliser
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");
        var url = getEnv("LEGOFACTORY_URL");

        if (args.length < 1) {
            printUsage();
            return;
        }

        String command = args[0];

        try {
            switch (command) {
                case "refill" -> {
                    if (checkEnv(url, email, key)) runRefill(url, email, key);
                }
                case "resize" -> runResize(args);
                case "pave" -> runPave(args);
                case "order" -> {
                    if (checkEnv(url, email, key)) runOrder(url, email, key);
                }
                case "proactive" -> {
                    if (checkEnv(url, email, key)) runProactiveOrder(url, email, key);
                }
                case "visualize" -> runVisualize(args);
                case "restock" -> {
                    if (checkEnv(url, email, key)) runFullRestock(url, email, key);
                }
                case "buy" -> {
                    if (checkEnv(url, email, key)) runBuy(args, url, email, key);
                }
                case "update-prices" -> {
                    if (checkEnv(url, email, key)) PriceUpdater.run(url, email, key);
                }
                default -> {
                    System.err.println("Commande inconnue : " + command);
                    printUsage();
                }
            }
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    // --- Méthodes Utilitaires ---

    private static String getEnv(String key) {
        String value = dotenv.get(key);
        if (value == null) {
            return System.getenv(key);
        }
        return value;
    }

    private static boolean checkEnv(String url, String email, String key) {
        if (email == null || key == null || url == null) {
             System.err.println("ERREUR : Variables d'environnement manquantes (LEGOFACTORY_EMAIL, KEY, URL)");
             return false;
        }
        return true;
    }

    private static void printUsage() {
        System.out.println("Usage :");
        System.out.println("  1. Recharger le compte : java -jar legotools.jar refill");
        System.out.println("  2. Redimensionner : java -jar legotools.jar resize <input> <output> <WxH> [strategy]");
        System.out.println("  3. Paver : java -jar legotools.jar pave <input> <output_base> <exe_c> [algo|all]");
        System.out.println("  4. Commander : java -jar legotools.jar order");
        System.out.println("  5. Commande proactive : java -jar legotools.jar proactive");
        System.out.println("  6. Visualiser : java -jar legotools.jar visualize <input_txt> <output_png>");
        System.out.println("  7. Restockage complet : java -jar legotools.jar restock");
        System.out.println("  8. Achat ciblé : java -jar legotools.jar buy <ref> <qty>");
        System.out.println("  9. Mettre à jour les prix : java -jar legotools.jar update-prices");
    }

    // --- Commandes ---

    private static void runRefill(String url, String email, String key) throws IOException {
        var refiller = new AccountRefiller(url, email, key);
        System.out.println("Nouveau solde : " + refiller.refill());
    }

    // gère le redimensionnement d'une image avec une stratégie choisie
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
            case "lanczos" -> processor.setStrategy(new LanczosStrategy());
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

    // exécute l'algorithme de pavage en appelant le programme externe en c
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
            algos = List.of("stock", "libre", "minimisation", "rentabilite");
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

    private static void runOrder(String url, String email, String key) {
        HttpRestFactory factory = new HttpRestFactory(url, email, key);
        StockManager stock = new StockManager();
        stock.showStock();

        try {
            long balance = factory.getBalance();
            System.out.println("Solde : " + balance);

            Map<String, Integer> panier = Map.of("2-2/c9cae2", 1);
            
            LegoFactory.Quote quote = factory.requestQuote(panier);
            factory.acceptQuote(quote.id());
            stock.recordFactoryOrder(quote.id(), quote.price(), panier);
            
            System.out.println("Attente livraison...");
            processDelivery(factory, stock, quote.id(), 1);

        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    private static void runProactiveOrder(String url, String email, String key) {
        StockManager stockManager = new StockManager();
        HttpRestFactory factory = new HttpRestFactory(url, email, key);
        
        System.out.println("Interrogation de la vue 'View_LowStockDetails'...");
        
        Map<String, Integer> alerts = stockManager.getLowStockItems();
        
        if (alerts.isEmpty()) {
            System.out.println("Aucune alerte. Tout le stock est supérieur à 50.");
            return;
        }

        Map<String, Integer> toOrder = new HashMap<>();
        int TARGET_BUFFER = 50; 
        
        for (Map.Entry<String, Integer> entry : alerts.entrySet()) {
            String itemKey = entry.getKey();
            int current = entry.getValue();
            
            int needed = TARGET_BUFFER - current;
            
            if (needed > 0) {
                toOrder.put(itemKey, needed);
                System.out.println(" > ALERTE : " + itemKey + " (Stock: " + current + " -> Commande: " + needed + ")");
            }
        }
        
        try {
            System.out.println("Envoi commande pour " + toOrder.size() + " références...");
            LegoFactory.Quote quote = factory.requestQuote(toOrder);
            
            long balance = factory.getBalance();
            if (balance < quote.price()) {
                long missing = (long)quote.price() - balance + 500;
                System.out.println(" ! Solde insuffisant. Minage automatique (" + missing + ")...");
                factory.rechargeAccount(missing);
            }

            factory.acceptQuote(quote.id());
            stockManager.recordFactoryOrder(quote.id(), quote.price(), toOrder);
            
            int totalExpected = toOrder.values().stream().mapToInt(Integer::intValue).sum();
            processDelivery(factory, stockManager, quote.id(), totalExpected);
            
        } catch (Exception e) {
            System.err.println("Erreur proactive : " + e.getMessage());
        }
    }
    
    private static void processDelivery(HttpRestFactory factory, StockManager stock, String quoteId, int expectedQuantity) throws IOException, InterruptedException {
        List<FactoryBrick> briques = new ArrayList<>();
        
        while (briques.size() < expectedQuantity) {
            briques = factory.retrieveOrder(quoteId);
            if (briques.size() < expectedQuantity) {
                System.out.print("\rAttente livraison... (" + briques.size() + "/" + expectedQuantity + ")");
                Thread.sleep(1000);
            }
        }
        System.out.println("\nRéception complète de " + briques.size() + " briques.");
        
        List<FactoryBrick> verifiedBricks = new ArrayList<>();
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

    private static void runFullRestock(String url, String email, String key) {
        StockManager stock = new StockManager();
        HttpRestFactory factory = new HttpRestFactory(url, email, key);

        System.out.println("Préparation de la commande massive (75 unités par brique)...");

        List<String> allTypes = stock.getAllBrickTypes();
        if (allTypes.isEmpty()) {
            System.out.println("Aucune brique trouvée en base.");
            return;
        }

        int BATCH_SIZE = 50;
        Map<String, Integer> currentBatch = new HashMap<>();
        
        System.out.println("Traitement de " + allTypes.size() + " références...");

        for (String type : allTypes) {
            currentBatch.put(type, 75);
            if (currentBatch.size() >= BATCH_SIZE) {
                processBatch(factory, stock, currentBatch);
                currentBatch.clear();
            }
        }
        if (!currentBatch.isEmpty()) {
            processBatch(factory, stock, currentBatch);
        }
        System.out.println("Restockage terminé.");
    }

    private static void processBatch(HttpRestFactory factory, StockManager stock, Map<String, Integer> batch) {
        try {
            LegoFactory.Quote quote = factory.requestQuote(batch);
            factory.acceptQuote(quote.id());
            stock.recordFactoryOrder(quote.id(), quote.price(), batch);
            System.out.println("// Lot de " + batch.size() + " références commandé avec succès.");
            
            int total = batch.values().stream().mapToInt(Integer::intValue).sum();
            processDelivery(factory, stock, quote.id(), total);
            
        } catch (Exception e) {
            System.err.println("// Erreur sur le lot (" + e.getMessage() + "). Filtrage des items invalides...");
        }
    }

    private static void runBuy(String[] args, String url, String email, String key) {
        if (args.length < 3) {
            System.out.println("Usage: buy <reference> <quantité>");
            return;
        }
        String reference = args[1];
        int quantity;
        try {
            quantity = Integer.parseInt(args[2]);
        } catch (NumberFormatException e) {
            System.err.println("Erreur : La quantité doit être un nombre entier.");
            return;
        }

        StockManager stock = new StockManager();
        HttpRestFactory factory = new HttpRestFactory(url, email, key);

        try {
            System.out.println("Préparation de la commande : " + quantity + " x " + reference);
            Map<String, Integer> itemToOrder = Map.of(reference, quantity);
            LegoFactory.Quote quote = factory.requestQuote(itemToOrder);
            System.out.println("Devis reçu : " + quote.price() + " crédits");
            
            factory.acceptQuote(quote.id());
            stock.recordFactoryOrder(quote.id(), quote.price(), itemToOrder);
            System.out.println("Commande validée. En attente de livraison...");
            processDelivery(factory, stock, quote.id(), quantity);
            
        } catch (Exception e) {
            System.err.println("Echec de la commande (" + e.getMessage() + ")");
        }
    }
}