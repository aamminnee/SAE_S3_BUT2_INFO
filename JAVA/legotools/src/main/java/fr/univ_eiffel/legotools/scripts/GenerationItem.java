package fr.univ_eiffel.legotools.scripts;

import java.io.BufferedReader;
import java.io.FileReader;
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

    private static final Map<String, String> ENV = new HashMap<>();

    public static void main(String[] args) {

        // // chargement manuel du fichier .env
        // // assurez-vous de lancer le programme depuis la racine du projet (dossier legotools)
        loadEnv(".env");

        // // récupération des variables sans valeur par défaut (comme demandé)
        String host = ENV.get("DB_HOST");
        String dbName = ENV.get("DB_NAME");
        String user = ENV.get("DB_USER");
        String password = ENV.get("DB_PASSWORD");

        // // vérification de sécurité pour éviter les erreurs de connexion nulles
        if (host == null) {
            System.err.println("Erreur : DB_HOST manquant dans le fichier .env");
            return;
        }
        if (dbName == null) {
            System.err.println("Erreur : DB_NAME manquant dans le fichier .env");
            return;
        }
        if (user == null) {
            System.err.println("Erreur : DB_USER manquant dans le fichier .env");
            return;
        }
        // // gère le cas où le mot de passe est vide (cas fréquent en local)
        if (password == null) password = "";

        // // construction de l'url jdbc avec les variables du .env
        String url = "jdbc:mysql://" + host + ":3306/" + dbName;
        
        System.out.println("Connexion à la BDD : " + url + " (User: " + user + ")");

        List<String> shapesList = new ArrayList<>();
        Map<Integer, Integer> shapeIdToIndex = new HashMap<>();

        List<String> colorsList = new ArrayList<>();
        Map<Integer, Integer> colorIdToIndex = new HashMap<>();

        List<String> piecesLines = new ArrayList<>();

        // // utilisation des variables chargées pour la connexion
        try (Connection conn = DriverManager.getConnection(url, user, password)) {
            
            try (CallableStatement csShapes = conn.prepareCall("{call get_export_shapes()}");
                 ResultSet rsShapes = csShapes.executeQuery()) {
                
                int idxS = 0;
                while (rsShapes.next()) {
                    shapeIdToIndex.put(rsShapes.getInt("id_shape"), idxS++);
                    shapesList.add(rsShapes.getInt("width") + "-" + rsShapes.getInt("length"));
                }
            }
            try (CallableStatement csColors = conn.prepareCall("{call get_export_colors()}");
                 ResultSet rsColors = csColors.executeQuery()) {
                
                int idxC = 0;
                while (rsColors.next()) {
                    colorIdToIndex.put(rsColors.getInt("id_color"), idxC++);
                    colorsList.add(rsColors.getString("hex_color"));
                }
            }

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

            System.out.println("Succès : briques.txt généré avec les procédures stockées !");

        } catch (SQLException | IOException e) {
            e.printStackTrace();
        }
    }

    // // méthode manquante ajoutée pour parser le fichier .env
    private static void loadEnv(String filePath) {
        try (BufferedReader reader = new BufferedReader(new FileReader(filePath))) {
            String line;
            while ((line = reader.readLine()) != null) {
                line = line.trim();
                // // ignore les lignes vides ou les commentaires
                if (line.isEmpty() || line.startsWith("#")) continue;
                
                String[] parts = line.split("=", 2);
                if (parts.length >= 2) {
                    ENV.put(parts[0].trim(), parts[1].trim());
                } else if (parts.length == 1) {
                    // // gère le cas DB_PASSWORD= (vide)
                    ENV.put(parts[0].trim(), "");
                }
            }
        } catch (IOException e) {
            System.err.println("Erreur : Impossible de lire le fichier " + filePath);
            System.err.println("Vérifiez que vous êtes bien dans le dossier 'legotools' lors de l'exécution.");
        }
    }
}