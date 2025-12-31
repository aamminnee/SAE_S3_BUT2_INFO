package fr.uge.brick;

// Représente une brique dans notre stock ou une brique fabriquée
public record Brick(
        String name,
        String colorHex,
        // Champs spécifiques pour les briques livrées par l'usine :
        String serialNumber,
        String authenticityCertificate
) {}