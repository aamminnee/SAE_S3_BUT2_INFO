package fr.uge.stock;

import fr.uge.brick.Brick;

import java.util.List;
import java.util.Map;

public interface Inventory {
    // Récupère l'état du stock (exemple : Map<Référence: "8-8/fc97ac", Quantité: 5>)
    Map<String, Integer> getCurrentStock();
    // Met à jour le stock après une commande client ou une livraison usine
    void updateStock(String brickReference, int quantityChange);
    // Ajoute les briques spécifiques nouvellement livrées par l'usine
    void addBuiltBricks(List<Brick> builtBricks);
}
