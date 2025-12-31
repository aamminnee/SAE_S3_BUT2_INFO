package fr.univ_eiffel.legotools.factory.impl;

import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import fr.univ_eiffel.legotools.factory.api.ApiSender;
import fr.univ_eiffel.legotools.factory.api.LegoFactory;
import fr.univ_eiffel.legotools.factory.api.PaymentStrategy;
import fr.univ_eiffel.legotools.model.FactoryBrick;

import java.io.*;
import java.lang.reflect.Type;
import java.net.HttpURLConnection;
import java.net.URI;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.List;
import java.util.Map;

public class HttpRestFactory implements LegoFactory {
    private static final String BASE_URL = "https://legofactory.plade.org";
    private final String email;
    private final String apiKey;
    private final Gson gson = new Gson();
    
    // STRATÉGIE : On peut la changer (ex: new PoWPaymentStrategy())
    private PaymentStrategy paymentStrategy = new PoWPaymentStrategy();

    public HttpRestFactory(String email, String apiKey) {
        this.email = email;
        this.apiKey = apiKey;
    }

    // Permet de changer de stratégie de paiement à la volée
    public void setPaymentStrategy(PaymentStrategy strategy) {
        this.paymentStrategy = strategy;
    }

    // L'implémentation de ApiSender pour notre stratégie
    private final ApiSender apiSender = this::sendRequest;

    private String sendRequest(String endpoint, String method, String jsonBody) throws IOException {
        URL url = URI.create(BASE_URL + endpoint).toURL();
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod(method);
        conn.setRequestProperty("X-Email", email);
        conn.setRequestProperty("X-Secret-Key", apiKey);
        
        if (jsonBody != null) {
            conn.setDoOutput(true);
            try (OutputStream os = conn.getOutputStream()) {
                os.write(jsonBody.getBytes(StandardCharsets.UTF_8));
            }
        }

        int code = conn.getResponseCode();
        if (code >= 400) {
            try (InputStream es = conn.getErrorStream()) {
                String error = (es != null) ? new String(es.readAllBytes(), StandardCharsets.UTF_8) : "";
                throw new IOException("Erreur API " + code + " sur " + endpoint + " : " + error);
            }
        }
        try (InputStream is = conn.getInputStream()) {
            return new String(is.readAllBytes(), StandardCharsets.UTF_8);
        }
    }

    @Override
    public long getBalance() throws IOException {
        String json = sendRequest("/billing/balance", "GET", null);
        Type type = new TypeToken<Map<String, String>>(){}.getType();
        Map<String, String> response = gson.fromJson(json, type);
        
        String balanceStr = response.get("balance");
        if (balanceStr == null) balanceStr = response.get("amount");
        return (long) Double.parseDouble(balanceStr);
    }

    @Override
    public void rechargeAccount(long minAmount) throws IOException {
        // Délégation à la stratégie !
        long current = getBalance();
        paymentStrategy.pay(minAmount, current, apiSender);
    }

    @Override
    public String requestQuote(Map<String, Integer> items) throws IOException {
        String body = gson.toJson(items);
        String response = sendRequest("/ordering/quote-request", "POST", body);
        Type type = new TypeToken<Map<String, Object>>(){}.getType();
        Map<String, Object> quote = gson.fromJson(response, type);
        System.out.println("Devis : " + quote.get("price") + " crédits");
        return (String) quote.get("id");
    }

    @Override
    public void acceptQuote(String quoteId) throws IOException {
        sendRequest("/ordering/order/" + quoteId, "POST", null);
    }

    @Override
    public List<FactoryBrick> retrieveOrder(String quoteId) throws IOException {
        String json = sendRequest("/ordering/deliver/" + quoteId, "GET", null);
        DeliveryResponse dr = gson.fromJson(json, DeliveryResponse.class);
        if (dr.built_blocks() == null) return List.of();
        return dr.built_blocks();
    }
    public boolean verifyBrick(FactoryBrick brick) {
        try {
            String body = gson.toJson(brick);
            // Si l'API répond 200, sendRequest ne lance pas d'exception -> return true
            // Si l'API répond 404 (invalide), sendRequest lance une IOException -> catch -> return false
            sendRequest("/verify", "POST", body);
            return true;
        } catch (IOException e) {
            System.err.println("Echec vérification brique " + brick.serial() + " : " + e.getMessage());
            return false;
        }
    }

    @Override
    public boolean ping() {
        try {
            sendRequest("/ping", "GET", null);
            return true;
        } catch (IOException e) {
            return false;
        }
    }

    @Override
    public String getProductionStats() throws IOException {
        return sendRequest("/production", "GET", null);
    }

    @Override
    public String getSignaturePublicKey() throws IOException {
        return sendRequest("/signature-public-key", "GET", null);
    }

    @Override
    public String getCatalog() throws IOException {
        return sendRequest("/catalog", "GET", null);
    }

    private record DeliveryResponse(Boolean completion_date, List<FactoryBrick> built_blocks) {}
}