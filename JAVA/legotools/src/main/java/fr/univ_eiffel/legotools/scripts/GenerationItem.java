package fr.univ_eiffel.legotools.scripts;

import java.io.BufferedWriter;
import java.io.FileWriter;
import java.io.IOException;
import java.sql.*;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

public class GenerationItem {

    // Paramètres de connexion
    private static final String URL = "jdbc:mysql://localhost:3306/lego_tableau";
    private static final String USER = "root";
    private static final String PASSWORD = "";

    public static void main(String[] args) {
        List<String> shapesList = new ArrayList<>();
        Map<Integer, Integer> shapeIdToIndex = new HashMap<>();

        List<String> colorsList = new ArrayList<>();
        Map<Integer, Integer> colorIdToIndex = new HashMap<>();

        List<String> piecesLines = new ArrayList<>();

        try (Connection conn = DriverManager.getConnection(URL, USER, PASSWORD)) {

            // 1. Récupération des Formes via Procédure Stockée
            try (CallableStatement csShapes = conn.prepareCall("{call get_export_shapes()}");
                 ResultSet rsShapes = csShapes.executeQuery()) {
                
                int idxS = 0;
                while (rsShapes.next()) {
                    // On mappe l'ID de la BDD (ex: 40) vers un index séquentiel (ex: 0, 1, 2...)
                    shapeIdToIndex.put(rsShapes.getInt("id"), idxS++);
                    shapesList.add(rsShapes.getInt("width") + "-" + rsShapes.getInt("length"));
                }
            }

            // 2. Récupération des Couleurs via Procédure Stockée
            try (CallableStatement csColors = conn.prepareCall("{call get_export_colors()}");
                 ResultSet rsColors = csColors.executeQuery()) {
                
                int idxC = 0;
                while (rsColors.next()) {
                    colorIdToIndex.put(rsColors.getInt("id"), idxC++);
                    colorsList.add(rsColors.getString("hex_color"));
                }
            }

            // 3. Récupération des Pièces + Stock via Procédure Stockée
            // La procédure fait déjà le calcul (Entrées - Sorties)
            try (CallableStatement csItems = conn.prepareCall("{call get_export_items_stock()}");
                 ResultSet rsItems = csItems.executeQuery()) {
                
                while (rsItems.next()) {
                    int sId = rsItems.getInt("shape_id");
                    int cId = rsItems.getInt("color_id");
                    double price = rsItems.getDouble("price");
                    int stock = rsItems.getInt("current_stock");

                    Integer shapeIdx = shapeIdToIndex.get(sId);
                    Integer colorIdx = colorIdToIndex.get(cId);

                    // On ne garde que les pièces dont la forme et la couleur sont bien chargées
                    if (shapeIdx != null && colorIdx != null) {
                        // Formatage du prix avec un point (ex: 5.00)
                        String formattedPrice = String.format(Locale.US, "%.2f", price);
                        piecesLines.add(shapeIdx + "/" + colorIdx + " " + formattedPrice + " " + stock);
                    }
                }
            }

            // 4. Écriture du fichier final
            try (BufferedWriter writer = new BufferedWriter(new FileWriter("C/input/briques.txt"))) {
                // En-tête : NbFormes NbCouleurs NbPièces
                writer.write(shapesList.size() + " " + colorsList.size() + " " + piecesLines.size());
                writer.newLine();

                // Bloc des formes
                for (String s : shapesList) {
                    writer.write(s);
                    writer.newLine();
                }

                // Bloc des couleurs
                for (String c : colorsList) {
                    writer.write(c);
                    writer.newLine();
                }

                // Bloc des pièces
                for (String p : piecesLines) {
                    writer.write(p);
                    writer.newLine();
                }
            }

            System.out.println("Succès : brique.txt généré avec les procédures stockées !");

        } catch (SQLException | IOException e) {
            e.printStackTrace();
        }
    }
}