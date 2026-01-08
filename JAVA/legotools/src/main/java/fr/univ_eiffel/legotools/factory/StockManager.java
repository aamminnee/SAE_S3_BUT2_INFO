package fr.univ_eiffel.legotools.factory;

import fr.univ_eiffel.legotools.model.FactoryBrick;
import io.github.cdimascio.dotenv.Dotenv;

import java.sql.*;
import java.util.*;

public class StockManager {

    // // configuration de la connexion bdd
    private final String url;
    private final String user;
    private final String password;

    public StockManager() {
        // // chargement des variables d'environnement
        Dotenv dotenv = Dotenv.configure().ignoreIfMissing().load();
        
        // // récupération des infos de connexion ou valeurs par défaut
        String host = dotenv.get("DB_HOST", "localhost");
        String port = dotenv.get("DB_PORT", "3306");
        String dbName = dotenv.get("DB_NAME", "SAE_S3_BUT2_INFO");
        
        this.url = "jdbc:mysql://" + host + ":" + port + "/" + dbName;
        this.user = dotenv.get("DB_USER", "root");
        this.password = dotenv.get("DB_PASSWORD", "");
        
        // // initialisation de la table spécifique au composant java
        initTables();
    }

    private void initTables() {
        // // création de la table pour stocker les détails techniques des briques (serial, certificat)
        String sql = """
            CREATE TABLE IF NOT EXISTS FactoryBrick (
                serial VARCHAR(32) PRIMARY KEY,
                certificate TEXT,
                shape_id INT,
                color_id INT,
                purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """;
        try (Connection conn = getConnection(); Statement stmt = conn.createStatement()) {
            stmt.execute(sql);
        } catch (SQLException e) {
            System.err.println("// erreur init table factorybrick : " + e.getMessage());
        }
    }

    private Connection getConnection() throws SQLException {
        return DriverManager.getConnection(url, user, password);
    }

    // // récupère l'état du stock (entrées - sorties) pour chaque type de brique
    public Map<String, Integer> getStockCounts() {
        Map<String, Integer> counts = new HashMap<>();
        
        // // requête calculant le stock actuel disponible
        String sql = """
            SELECT 
                s.name AS shape_name, 
                c.hex_color,
                (IFNULL(entries.total_in, 0) - IFNULL(sales.total_out, 0)) AS quantity
            FROM Item i
            JOIN Shapes s ON i.shape_id = s.id_shape
            JOIN Colors c ON i.color_id = c.id_color
            LEFT JOIN (SELECT id_Item, SUM(quantity) AS total_in FROM StockEntry GROUP BY id_Item) entries ON i.id_Item = entries.id_Item
            LEFT JOIN (SELECT id_Item, SUM(quantity) AS total_out FROM OrderItem GROUP BY id_Item) sales ON i.id_Item = sales.id_Item
            HAVING quantity > 0
        """;

        try (Connection conn = getConnection(); Statement stmt = conn.createStatement(); ResultSet rs = stmt.executeQuery(sql)) {
            while (rs.next()) {
                // // format attendu par le code de pavage : "2-4/fc97ac"
                String key = rs.getString("shape_name") + "/" + rs.getString("hex_color").toLowerCase();
                counts.put(key, rs.getInt("quantity"));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return counts;
    }

    // // ajoute les briques reçues de l'usine dans la bdd
    public void addBricks(List<FactoryBrick> bricks) {
        if (bricks.isEmpty()) return;

        String insertBrick = "INSERT IGNORE INTO FactoryBrick (serial, certificate, shape_id, color_id) VALUES (?, ?, ?, ?)";
        // // on ajoute une entrée dans stockentry pour rendre la brique disponible à la vente
        String insertStock = "INSERT INTO StockEntry (id_Item, quantity, date_import) VALUES (?, 1, NOW())";
        
        try (Connection conn = getConnection()) {
            conn.setAutoCommit(false);
            try (PreparedStatement psBrick = conn.prepareStatement(insertBrick);
                 PreparedStatement psStock = conn.prepareStatement(insertStock)) {
                
                int count = 0;
                for (FactoryBrick b : bricks) {
                    
                    // // plus de conversion ici, on utilise directement le nom de l'api qui correspond maintenant à la bdd
                    int[] ids = findItemIds(conn, b.shapeName(), b.color());
                    
                    if (ids == null) {
                        System.err.println("// item inconnu en base : " + b.shapeName() + " / " + b.color());
                        continue; 
                    }

                    // // 1. sauvegarde des métadonnées usine
                    psBrick.setString(1, b.serial());
                    psBrick.setString(2, b.certificate());
                    psBrick.setInt(3, ids[1]); // shape_id
                    psBrick.setInt(4, ids[2]); // color_id
                    psBrick.executeUpdate();

                    // // 2. mise à jour du stock quantité
                    psStock.setInt(1, ids[0]); // id_item
                    psStock.executeUpdate();
                    
                    count++;
                }
                conn.commit();
                System.out.println("// " + count + " briques ajoutées en base de données.");
            } catch (SQLException e) {
                conn.rollback();
                throw e;
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // // utilitaire pour trouver id_item, shape_id, color_id à partir du nom et couleur
    private int[] findItemIds(Connection conn, String shapeName, String hexColor) throws SQLException {
        // // on cherche la brique correspondante
        String sql = """
            SELECT i.id_Item, s.id_shape, c.id_color 
            FROM Item i 
            JOIN Shapes s ON i.shape_id = s.id_shape 
            JOIN Colors c ON i.color_id = c.id_color 
            WHERE s.name = ? AND (c.hex_color = ? OR c.hex_color = ?)
        """;
        try (PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setString(1, shapeName); 
            // // gestion de la casse et du # pour la couleur
            ps.setString(2, hexColor.replace("#", "").toUpperCase());
            ps.setString(3, hexColor.replace("#", "").toLowerCase());
            
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return new int[]{rs.getInt(1), rs.getInt(2), rs.getInt(3)};
                }
            }
        }
        return null;
    }
    
    // // récupère les items les plus vendus pour la stratégie proactive
    public Map<String, Integer> getPopularItems(int limit) {
        Map<String, Integer> popular = new HashMap<>();
        String sql = """
            SELECT s.name, c.hex_color, SUM(oi.quantity) as total 
            FROM OrderItem oi
            JOIN Item i ON oi.id_Item = i.id_Item
            JOIN Shapes s ON i.shape_id = s.id_shape
            JOIN Colors c ON i.color_id = c.id_color
            GROUP BY oi.id_Item
            ORDER BY total DESC
            LIMIT ?
        """;
        try (Connection conn = getConnection(); PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, limit);
            ResultSet rs = ps.executeQuery();
            while(rs.next()) {
                String key = rs.getString(1) + "/" + rs.getString(2).toLowerCase();
                popular.put(key, rs.getInt(3));
            }
        } catch (SQLException e) { 
            System.err.println("// erreur lecture populaires : " + e.getMessage());
        }
        return popular;
    }
    
    public void showStock() {
        Map<String, Integer> stock = getStockCounts();
        System.out.println("\n--- ÉTAT DU STOCK (SQL) ---");
        if (stock.isEmpty()) {
            System.out.println("(Vide ou erreur connexion)");
        } else {
            stock.forEach((k, v) -> System.out.println("- " + k + " : " + v));
        }
        System.out.println("---------------------------");
    }

    // // NOUVELLE MÉTHODE : Enregistre la commande avec structure En-tête / Détails
    public void recordFactoryOrder(String quoteId, float totalPrice, Map<String, Integer> items) {
        if (items.isEmpty()) return;

        // // 1. Insertion de l'en-tête de commande (Prix total connu ici)
        String sqlHeader = "INSERT INTO FactoryOrder (id_FactoryOrder, total_price, order_date) VALUES (?, ?, CURDATE())";
        
        // // 2. Insertion des détails (Quantités uniquement, pas de prix unitaire)
        String sqlDetail = "INSERT INTO FactoryOrderDetails (id_FactoryOrder, id_Item, quantity) VALUES (?, ?, ?)";

        try (Connection conn = getConnection()) {
            conn.setAutoCommit(false);
            try {
                // // Etape A : Enregistrer la commande globale
                try (PreparedStatement psHead = conn.prepareStatement(sqlHeader)) {
                    psHead.setString(1, quoteId);
                    psHead.setFloat(2, totalPrice);
                    psHead.executeUpdate();
                }

                // // Etape B : Enregistrer chaque ligne d'article
                try (PreparedStatement psDet = conn.prepareStatement(sqlDetail)) {
                    for (Map.Entry<String, Integer> entry : items.entrySet()) {
                        String key = entry.getKey();
                        int quantity = entry.getValue();

                        // // Récupération des IDs (Item/Forme/Couleur)
                        String shape = key.contains("/") ? key.substring(0, key.lastIndexOf('/')) : key;
                        String color = key.contains("/") ? key.substring(key.lastIndexOf('/') + 1) : "000000";

                        int[] ids = findItemIds(conn, shape, color);
                        if (ids == null) {
                            System.err.println("// Item inconnu (non enregistré) : " + key);
                            continue;
                        }

                        psDet.setString(1, quoteId); // Lien vers la commande parente
                        psDet.setInt(2, ids[0]);     // id_Item
                        psDet.setInt(3, quantity);   // Quantité
                        psDet.addBatch();            // Ajout au batch pour performance
                    }
                    psDet.executeBatch();
                }
                
                conn.commit();
                System.out.println("// Commande " + quoteId + " enregistrée (Prix: " + totalPrice + ")");
                
            } catch (SQLException e) {
                conn.rollback();
                System.err.println("// Erreur transaction FactoryOrder : " + e.getMessage());
                throw e;
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // récupère la liste de toutes les briques référencées en base, même si le stock est vide
    public List<String> getAllBrickTypes() {
        List<String> types = new ArrayList<>();
        String sql = """
            SELECT s.name, c.hex_color 
            FROM Item i
            JOIN Shapes s ON i.shape_id = s.id_shape
            JOIN Colors c ON i.color_id = c.id_color
        """;
        
        try (Connection conn = getConnection(); 
             Statement stmt = conn.createStatement(); 
             ResultSet rs = stmt.executeQuery(sql)) {
            
            while (rs.next()) {
                // // construction de la clé unique type "2-2/c9cae2"
                String shape = rs.getString("name");
                String color = rs.getString("hex_color").replace("#", "").toLowerCase();
                types.add(shape + "/" + color);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return types;
    }
}