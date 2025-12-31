package fr.uge.stock;

import fr.uge.factory.BrickFactory;
import fr.uge.factory.PaymentStrategy;
import fr.uge.factory.QuoteRequest;

import java.util.HashMap;
import java.util.Map;

public class StockService {
    private final BrickFactory factory;
    private final Inventory inventory;
    private final PaymentStrategy paymentStrategy;
    public StockService(BrickFactory factory, Inventory inventory, PaymentStrategy paymentStrategy) {
        this.factory = factory;
        this.inventory = inventory;
        this.paymentStrategy = paymentStrategy;
    }
    private Map<String, Integer> determineMissingBricks(Map<String, Integer> requiredBricks) {
        // Récupérer l'état actuel du stock depuis le Repository.
        Map<String, Integer> currentStock = inventory.getCurrentStock();
        // Initialiser la Map pour stocker les pièces manquantes.
        Map<String, Integer> missingBricks = new HashMap<>();
        // Parcourir toutes les briques requises par le client.
        for (Map.Entry<String, Integer> entry : requiredBricks.entrySet()) {
            String brickReference = entry.getKey();
            int requiredQuantity = entry.getValue();
            // Récupérer la quantité disponible en stock.
            int availableQuantity = currentStock.getOrDefault(brickReference, 0);
            // Calculer le manque.
            int shortfall = requiredQuantity - availableQuantity;
            // Si le manque est positif, ajouter à la liste des commandes.
            if (shortfall > 0) {
                missingBricks.put(brickReference, shortfall);
            }
            // Mise à jour du stock local
            int quantityToDeduct = requiredQuantity - shortfall;
            // Si quantityToDeduct > 0, on met à jour le stock (soustraction)
            if (quantityToDeduct > 0) {
                inventory.updateStock(brickReference, -quantityToDeduct);
            }
        }
        return missingBricks;
    }
    public boolean orderClientBricks(Map<String, Integer> requiredBricks) {
        // Vérifier le stock local
        Map<String, Integer> missingBricks = determineMissingBricks(requiredBricks);
        if (missingBricks.isEmpty()) {
            return true; // Stock suffisant, pas de commande usine nécessaire.
        }
        // Demander un devis
        var quoteResponse = factory.requestQuote(new QuoteRequest(missingBricks));
        // Vérifier le solde et recharger si nécessaire
        if (factory.getBalance() < quoteResponse.price()) {
            paymentStrategy.rechargeAccount(factory, quoteResponse.price());
        }
        // Placer la commande
        factory.placeOrder(quoteResponse.id());
        return true;
    }
}
