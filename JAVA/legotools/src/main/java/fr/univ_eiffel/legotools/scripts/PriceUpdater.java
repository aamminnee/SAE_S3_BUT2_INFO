package fr.univ_eiffel.legotools.scripts;

import fr.univ_eiffel.legotools.factory.impl.HttpRestFactory;
import fr.univ_eiffel.legotools.factory.api.LegoFactory;
import fr.univ_eiffel.legotools.factory.StockManager;

import java.util.HashMap;
import java.util.Map;

public class PriceUpdater {

    // On commande 10 pièces pour éviter les frais minimums ou les arrondis
    // Si 1 pièce = 0.10 (minimum) et 10 pièces = 0.10, alors le vrai prix est 0.01
    private static final int BATCH_SIZE = 10;

    public static void run(String url, String email, String key) {
        System.out.println("--- DÉMARRAGE DE LA MISE À JOUR DES PRIX (LISSAGE PAR " + BATCH_SIZE + ") ---");

        StockManager stockManager = new StockManager();
        HttpRestFactory factory = new HttpRestFactory(url, email, key);

        Map<Integer, String> allItems = stockManager.getAllItemsRef();
        System.out.println(allItems.size() + " articles trouvés en base.");

        int count = 0;

        for (Map.Entry<Integer, String> entry : allItems.entrySet()) {
            int id = entry.getKey();
            String ref = entry.getValue();

            try {
                // On crée un panier avec 10 briques au lieu d'une seule
                Map<String, Integer> cart = new HashMap<>();
                cart.put(ref, BATCH_SIZE);

                // On demande le devis
                LegoFactory.Quote quote = factory.requestQuote(cart);
                
                // Calcul du prix unitaire : Prix Total / 10
                double totalPrice = quote.price();
                double unitPrice = totalPrice / BATCH_SIZE;

                // 3. Mise à jour en BDD
                stockManager.updateItemPrice(id, unitPrice);
                
                System.out.printf("[%d/%d] %s : Total %.2f pour %d => Unité %.3f €%n", 
                        ++count, allItems.size(), ref, totalPrice, BATCH_SIZE, unitPrice);

                // Petite pause
                Thread.sleep(100); 

            } catch (Exception e) {
                System.err.println("Erreur sur l'article " + ref + " : " + e.getMessage());
            }
        }

        System.out.println("--- MISE À JOUR TERMINÉE ---");
    }
}