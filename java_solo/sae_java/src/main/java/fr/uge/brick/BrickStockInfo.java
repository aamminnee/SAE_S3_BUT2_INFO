package fr.uge.brick;

// Représente les informations de stock
public record BrickStockInfo(
        String name,
        String colorHex,
        int quantityAvailable
) {}
