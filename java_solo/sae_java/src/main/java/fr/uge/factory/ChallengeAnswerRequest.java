package fr.uge.factory;

// Représente la requête POST /billing/challenge-answer
public record ChallengeAnswerRequest(
        String data_prefix,
        String hash_prefix,
        String answer
) {}
