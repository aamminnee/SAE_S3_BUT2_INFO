package fr.univ_eiffel.legotools.scripts;

import io.github.cdimascio.dotenv.Dotenv; // Importation de Dotenv
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

    // Gestion du fichier .env pour éviter l'erreur "cannot find symbol loadEnv"
    private static final Dotenv dotenv = Dotenv.configure().ignoreIfMissing().load();

    /**
     * Méthode utilitaire pour récupérer une variable d'environnement.
     * Cherche d'abord dans le .env, puis dans le système.
     */
    private static String getEnv(String key, String defaultValue) {
        String value = dotenv.get(key);
        if (value == null) {
            value = System.getenv(key);
        }
        return (value != null) ? value : defaultValue;
    }

    public static void main(String[] args) {

        // Récupération des paramètres de connexion via getEnv
        String host = getEnv("DB_HOST", "localhost");
        String dbName = getEnv("DB_NAME", "SAE_S3_BUT2_INFO");
        String user = getEnv("DB_USER", "root");
        String password = getEnv("DB_PASS", "Vh-23f538"); // Mot de passe par défaut fourni

        String url = "jdbc:mysql://" + host + ":3306/" + dbName;
        
        List<String> shapesList = new ArrayList<>();
        Map<Integer, Integer> shapeIdToIndex = new HashMap<>();

        List<String> colorsList = new ArrayList<>();
        Map<Integer, Integer> colorIdToIndex = new HashMap<>();

        List<String> piecesLines = new ArrayList<>();

        try (Connection conn = DriverManager.getConnection(url, user, password)) {
            
            // 1. Récupération des formes (Shapes)
            try (CallableStatement csShapes = conn.prepareCall("{call get_export_shapes()}");
                 ResultSet rsShapes = csShapes.executeQuery()) {
                
                int idxS = 0;
                while (rsShapes.next()) {
                    shapeIdToIndex.put(rsShapes.getInt("id_shape"), idxS++);
                    shapesList.add(rsShapes.getInt("width") + "-" + rsShapes.getInt("length"));
                }
            }

            // 2. Récupération des couleurs (Colors)
            try (CallableStatement csColors = conn.prepareCall("{call get_export_colors()}");
                 ResultSet rsColors = csColors.executeQuery()) {
                
                int idxC = 0;
                while (rsColors.next()) {
                    colorIdToIndex.put(rsColors.getInt("id_color"), idxC++);
                    colorsList.add(rsColors.getString("hex_color"));
                }
            }

            // 3. Récupération des items et du stock
            try (CallableStatement csItems = conn.prepareCall("{call get_export_items_stock()}");
                 ResultSet rsItems = csItems.executeQuery()) {
                
                while (rsItems.next()) {
                    int sId = rsItems.getInt("shape_id");
                    int cId = rsItems.getInt("color_id");
                    double price = rsItems.getDouble("price");
                    int stock = rsItems.getInt("current_stock");

                    Integer shapeIdx = shapeIdToIndex.get(sId);
                    Integer colorIdx = colorIdToIndex.get(cId);

                    if (shapeIdx != null && colorIdx != null) {
                        String formattedPrice = String.format(Locale.US, "%.2f", price);
                        piecesLines.add(shapeIdx + "/" + colorIdx + " " + formattedPrice + " " + stock);
                    }
                }
            }

            // 4. Écriture du fichier briques.txt pour le code C
            try (BufferedWriter writer = new BufferedWriter(new FileWriter("C/input/briques.txt"))) {
                writer.write(shapesList.size() + " " + colorsList.size() + " " + piecesLines.size());
                writer.newLine();

                for (String s : shapesList) {
                    writer.write(s);
                    writer.newLine();
                }

                for (String c : colorsList) {
                    writer.write(c);
                    writer.newLine();
                }

                for (String p : piecesLines) {
                    writer.write(p);
                    writer.newLine();
                }
            }

            System.out.println("Succès : briques.txt généré avec succès dans C/input/ !");

        } catch (SQLException | IOException e) {
            System.err.println("Erreur lors de la génération de briques.txt : " + e.getMessage());
            e.printStackTrace();
        }
    }
}