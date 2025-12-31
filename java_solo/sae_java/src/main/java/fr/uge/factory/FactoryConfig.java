package fr.uge.factory;

import com.google.gson.Gson;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;

public class FactoryConfig implements BrickFactory {
    public static final String API_BASE_URL = "https://legofactory.plade.org";
    public static final String EMAIL = "rayan.essaidi@edu.univ-eiffel.fr";
    public static final String SECRET_KEY = "60cd105719035ab008294240598152d5b3fab84a723a7c7f900206b0a29d4338";
    private final Gson gson = new Gson();
    // Méthode utilitaire pour configurer la connexion HTTP et les en-têtes
    private HttpURLConnection createConnection(String path, String method, boolean needsAuth, boolean doOutput) throws IOException {
        URL url = new URL(API_BASE_URL + path);
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod(method);
        conn.setRequestProperty("Content-Type", "application/json");
        conn.setRequestProperty("Accept", "application/json");

        if (needsAuth) {
            conn.setRequestProperty("X-Email", EMAIL);
            conn.setRequestProperty("X-Secret-Key", SECRET_KEY);
        }
        return conn;
    }
    private <T> T readResponse(HttpURLConnection conn, Class<T> responseType) throws IOException {
        try (BufferedReader br = new BufferedReader(new InputStreamReader(conn.getInputStream(), StandardCharsets.UTF_8))) {
            return gson.fromJson(br, responseType);
        }
    }
    @Override
    public void ping() {
        try {
            HttpURLConnection conn = createConnection("/ping", "GET", true, false);
            if (conn.getResponseCode() != 200) {
                throw new IOException("Ping échoué: " + conn.getResponseCode());
            }
        } catch (IOException e) {
            throw new RuntimeException("Erreur de connexion à l'usine.", e);
        }
    }
    @Override
    public String getPublicKey() {
        try {
            HttpURLConnection conn = createConnection("/signature-public-key", "GET", false, false);
            if (conn.getResponseCode() != 200) {
                throw new IOException("Erreur de récupération de la clé publique. Code: " + conn.getResponseCode());
            }
            try (BufferedReader br = new BufferedReader(new InputStreamReader(conn.getInputStream(), StandardCharsets.UTF_8))) {
                return br.readLine();
            }
        } catch (IOException e) {
            throw new RuntimeException("Erreur de communication avec l'usine.", e);
        }
    }
    public boolean verifyCertificate(String name, String serial, String certificate) {
        try {
            HttpURLConnection conn = createConnection("/verify", "POST", true, true);

            // Objet JSON à envoyer
            String jsonInputString = String.format("{\"name\": \"%s\", \"serial\": \"%s\", \"certificate\": \"%s\"}", name, serial, certificate);

            try(OutputStream os = conn.getOutputStream()) {
                os.write(jsonInputString.getBytes(StandardCharsets.UTF_8));
            }

            int responseCode = conn.getResponseCode();
            if (responseCode == 200) {
                return true;
            } else if (responseCode == 404) {
                return false; // Certificat invalide ou non trouvé (selon la doc)
            } else {
                throw new IOException("Erreur lors de la vérification du certificat. Code: " + responseCode);
            }
        } catch (IOException e) {
            throw new RuntimeException("Erreur de communication avec l'usine.", e);
        }
    }
    @Override
    public double getBalance() {
        try {
            HttpURLConnection conn = createConnection("/billing/balance", "GET", true, false);
            if (conn.getResponseCode() != 200) {
                throw new IOException("Erreur de solde. Code: " + conn.getResponseCode());
            }
            BalanceResponse response = readResponse(conn, BalanceResponse.class);
            return response.amount();
        } catch (IOException e) {
            throw new RuntimeException("Erreur de communication avec l'usine.", e);
        }
    }
    @Override
    public QuoteRequestResponse requestQuote(QuoteRequest request) {
        try {
            HttpURLConnection conn = createConnection("/ordering/quote-request", "POST", true, true);
            String jsonInputString = gson.toJson(request.items()); // Gson gère le Map directement
            try(OutputStream os = conn.getOutputStream()) {
                os.write(jsonInputString.getBytes(StandardCharsets.UTF_8));
            }
            if (conn.getResponseCode() != 200) {
                throw new IOException("Erreur lors de la demande de devis. Code: " + conn.getResponseCode());
            }
            return readResponse(conn, QuoteRequestResponse.class);

        } catch (IOException e) {
            throw new RuntimeException("Erreur de communication avec l'usine.", e);
        }
    }
    @Override
    public String placeOrder(String quoteId) {
        try {
            HttpURLConnection conn = createConnection("/ordering/order/" + quoteId, "POST", true, false);
            int responseCode = conn.getResponseCode();
            if (responseCode == 200) {
                return new String("Commande " + quoteId + " placée avec succès.");
            } else if (responseCode == 402) {
                throw new RuntimeException("Paiement refusé (Solde insuffisant).");
            } else {
                throw new IOException("Échec du placement de commande. Code: " + responseCode);
            }
        } catch (IOException e) {
            throw new RuntimeException("Erreur de communication avec l'usine.", e);
        }
    }
    @Override
    public DeliveryResponse requestDelivery(String quoteId) {
        try {
            HttpURLConnection conn = createConnection("/ordering/deliver/" + quoteId, "GET", true, false);
            if (conn.getResponseCode() != 200) {
                throw new IOException("Erreur lors de la livraison. Code: " + conn.getResponseCode());
            }
            return readResponse(conn, DeliveryResponse.class);
        } catch (IOException e) {
            throw new RuntimeException("Erreur de communication avec l'usine.", e);
        }
    }
    private record BalanceResponse(double amount) {}
}
