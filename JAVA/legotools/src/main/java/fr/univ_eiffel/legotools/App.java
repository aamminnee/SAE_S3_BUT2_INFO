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
import java.util.ArrayList;
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
                case "restock" -> runFullRestock(); 
                case "buy" -> runBuy(args);
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
        System.out.println("  7. Restockage complet : java -jar legotools.jar restock");
        System.out.println("  8. Achat ciblé : java -jar legotools.jar buy <ref> <qty>");
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
            case "lanczos" -> processor.setStrategy(new LanczosStrategy()); // // ajout accès direct
            // // modification ici : on ajoute lanczosstrategy à la liste et on passe à 4 étapes
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
            // // appel mis à jour avec quantité 1
            processDelivery(factory, stock, quote.id(), 1);

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
            
            // // calcul du total attendu
            int totalExpected = toOrder.values().stream().mapToInt(Integer::intValue).sum();
            processDelivery(factory, stockManager, quote.id(), totalExpected);
            
        } catch (Exception e) {
            System.err.println("Echec commande proactive : " + e.getMessage());
        }
    }
    
    // // méthode mise à jour pour attendre la quantité exacte
    private static void processDelivery(HttpRestFactory factory, StockManager stock, String quoteId, int expectedQuantity) throws IOException, InterruptedException {
        List<FactoryBrick> briques = new ArrayList<>();
        
        // // on boucle tant que la quantité reçue est inférieure à celle commandée
        while (briques.size() < expectedQuantity) {
            briques = factory.retrieveOrder(quoteId);
            
            if (briques.size() < expectedQuantity) {
                // // affichage de progression
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

    private static void runFullRestock() {
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");
        if (email == null) return;

        StockManager stock = new StockManager();
        HttpRestFactory factory = new HttpRestFactory(email, key);

        System.out.println("Préparation de la commande massive (75 unités par brique)...");

        // // récupération de tous les types de briques possibles
        List<String> allTypes = stock.getAllBrickTypes();
        
        if (allTypes.isEmpty()) {
            System.out.println("Aucune brique trouvée en base.");
            return;
        }

        // // on découpe la commande en lots de 50 pour éviter de tout bloquer sur une erreur
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
        
        // // traiter le reste du dernier lot
        if (!currentBatch.isEmpty()) {
            processBatch(factory, stock, currentBatch);
        }
        
        System.out.println("Restockage terminé.");
    }

    // // méthode utilitaire pour gérer les erreurs sur un lot
    private static void processBatch(HttpRestFactory factory, StockManager stock, Map<String, Integer> batch) {
        try {
            // // tentative de commande en bloc
            LegoFactory.Quote quote = factory.requestQuote(batch);
            factory.acceptQuote(quote.id());
            stock.recordFactoryOrder(quote.id(), quote.price(), batch);
            System.out.println("// Lot de " + batch.size() + " références commandé avec succès.");
            
            // // calcul du total pour le lot
            int total = batch.values().stream().mapToInt(Integer::intValue).sum();
            processDelivery(factory, stock, quote.id(), total);
            
        } catch (Exception e) {
            System.err.println("// Erreur sur le lot (" + e.getMessage() + "). Filtrage des items invalides...");
            
            // // en cas d'erreur, on identifie les items valides un par un
            Map<String, Integer> safeBatch = new HashMap<>();
            
            for (Map.Entry<String, Integer> entry : batch.entrySet()) {
                try {
                    // // on demande un devis pour un seul item pour vérifier s'il existe
                    factory.requestQuote(Map.of(entry.getKey(), entry.getValue()));
                    safeBatch.put(entry.getKey(), entry.getValue());
                } catch (Exception ex) {
                    System.err.println("// Item invalide retiré de la commande : " + entry.getKey());
                }
            }
            
            // // si on a trouvé des items valides, on relance la commande pour eux
            if (!safeBatch.isEmpty()) {
                try {
                    LegoFactory.Quote quote = factory.requestQuote(safeBatch);
                    factory.acceptQuote(quote.id());
                    stock.recordFactoryOrder(quote.id(), quote.price(), safeBatch);
                    System.out.println("// Lot corrigé (" + safeBatch.size() + " références) commandé.");
                    
                    // // calcul du total pour le lot sécurisé
                    int totalSafe = safeBatch.values().stream().mapToInt(Integer::intValue).sum();
                    processDelivery(factory, stock, quote.id(), totalSafe);
                    
                } catch (Exception ex) {
                    ex.printStackTrace();
                }
            }
        }
    }

    // // fonction pour acheter une quantité spécifique d'un item donné par sa référence
    private static void runBuy(String[] args) {
        if (args.length < 3) {
            System.out.println("Usage: buy <reference> <quantité>");
            System.out.println("Exemple: buy 2-2/c9cae2 50");
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
        
        var email = getEnv("LEGOFACTORY_EMAIL");
        var key = getEnv("LEGOFACTORY_KEY");
        if (email == null) return;

        StockManager stock = new StockManager();
        HttpRestFactory factory = new HttpRestFactory(email, key);

        try {
            System.out.println("Préparation de la commande : " + quantity + " x " + reference);
            
            Map<String, Integer> itemToOrder = Map.of(reference, quantity);
            
            // // demande de devis pour cet item spécifique
            LegoFactory.Quote quote = factory.requestQuote(itemToOrder);
            System.out.println("Devis reçu : " + quote.price() + " crédits");
            
            // // validation de la commande
            factory.acceptQuote(quote.id());
            
            // // enregistrement dans la base de données
            stock.recordFactoryOrder(quote.id(), quote.price(), itemToOrder);
            
            System.out.println("Commande validée. En attente de livraison...");
            
            // // réception et ajout au stock avec la quantité
            processDelivery(factory, stock, quote.id(), quantity);
            
        } catch (Exception e) {
            System.err.println("Echec de la commande (" + e.getMessage() + ")");
        }
    }
}