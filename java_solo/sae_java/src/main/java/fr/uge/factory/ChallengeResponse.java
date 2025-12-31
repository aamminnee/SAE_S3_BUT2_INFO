package fr.uge.factory;

// Représente la réponse GET /billing/challenge
public record ChallengeResponse(
        String data_prefix,
        String hash_prefix,
        double reward
) {}