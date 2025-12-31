package fr.uge.factory;

import java.util.Map;

// Représente la requête de devis.
public record QuoteRequest(
        Map<String, Integer> items // Clé : taille-taille ; Valeur : couleur
) {}