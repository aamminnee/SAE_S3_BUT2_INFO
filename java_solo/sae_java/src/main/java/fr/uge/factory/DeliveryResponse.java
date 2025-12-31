package fr.uge.factory;

import fr.uge.brick.Brick;
import java.util.List;
import java.util.Map;

// Représente la réponse d'une demande de livraison.
public record DeliveryResponse(
        String completion_date, // date de fin
        List<Brick> built_blocks, // tableau des briques livrées
        Map<String, Integer> pending_blocks // Clé : référence de la brique ; Valeur : Quantité restante
) {}
