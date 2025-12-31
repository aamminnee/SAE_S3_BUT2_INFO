package fr.uge.factory;

// Représente la réponse d'une demande de devis.
public record QuoteRequestResponse(
        String id, // numéro de devis
        double price, // prix total
        long delay // délai en nanosecondes
) {}
