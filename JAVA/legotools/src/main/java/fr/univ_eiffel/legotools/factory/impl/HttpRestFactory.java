package fr.univ_eiffel.legotools.factory.impl;

import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import fr.univ_eiffel.legotools.factory.api.ApiSender;
import fr.univ_eiffel.legotools.factory.api.LegoFactory;
import fr.univ_eiffel.legotools.factory.api.PaymentStrategy;
import fr.univ_eiffel.legotools.model.FactoryBrick;

import java.io.*;
import java.lang.reflect.Type;
import java.math.BigInteger;
import java.net.HttpURLConnection;
import java.net.URI;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.security.KeyFactory;
import java.security.PublicKey;
import java.security.Signature;
import java.security.spec.X509EncodedKeySpec;
import java.util.*;

public class HttpRestFactory implements LegoFactory {
    private static final String BASE_URL = "https://legofactory.plade.org";
    private final String email;
    private final String apiKey;
    private final Gson gson = new Gson();
    
    private static PublicKey cachedPublicKey = null;
    private PaymentStrategy paymentStrategy = new PoWPaymentStrategy();

    public HttpRestFactory(String email, String apiKey) {
        this.email = email;
        this.apiKey = apiKey;
    }

    // définit la stratégie de paiement à utiliser
    public void setPaymentStrategy(PaymentStrategy strategy) {
        this.paymentStrategy = strategy;
    }

    private final ApiSender apiSender = this::sendRequest;

    // envoie une requête http à l'api de l'usine
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
                String errorMsg = (es != null) ? new String(es.readAllBytes(), StandardCharsets.UTF_8) : "";
                throw new IOException("HTTP_" + code + " : " + errorMsg);
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
        long current = getBalance();
        paymentStrategy.pay(minAmount, current, apiSender);
    }

    @Override
    public LegoFactory.Quote requestQuote(Map<String, Integer> items) throws IOException {
        String body = gson.toJson(items);
        String response = sendRequest("/ordering/quote-request", "POST", body);
        Type type = new TypeToken<Map<String, Object>>(){}.getType();
        Map<String, Object> quoteMap = gson.fromJson(response, type);
        
        String id = (String) quoteMap.get("id");
        Object priceObj = quoteMap.get("price");
        float price = Float.parseFloat(priceObj.toString());
        
        System.out.println("devis reçu : " + price + " crédits (id: " + id + ")");
        return new LegoFactory.Quote(id, price);
    }

    @Override
    public void acceptQuote(String quoteId) throws IOException {
        try {
            sendRequest("/ordering/order/" + quoteId, "POST", null);
        } catch (IOException e) {
            if (e.getMessage().contains("HTTP_402")) {
                System.out.println("erreur 402 : solde insuffisant. tentative de rechargement...");
                rechargeAccount(1000); 
                sendRequest("/ordering/order/" + quoteId, "POST", null);
            } else if (e.getMessage().contains("HTTP_404")) {
                throw new IOException("Le devis " + quoteId + " a expiré.");
            } else {
                throw e;
            }
        }
    }

    @Override
    public List<FactoryBrick> retrieveOrder(String quoteId) throws IOException {
        String json = sendRequest("/ordering/deliver/" + quoteId, "GET", null);
        DeliveryResponse dr = gson.fromJson(json, DeliveryResponse.class);
        if (dr.built_blocks() == null) return List.of();
        return dr.built_blocks();
    }

    @Override
    public boolean verifyBrick(FactoryBrick brick) {
        try {
            String body = gson.toJson(brick);
            sendRequest("/verify", "POST", body);
            return true;
        } catch (IOException e) {
            System.err.println("vérification en ligne échouée (" + e.getMessage() + "), tentative hors-ligne...");
            return verifyBrickOffline(brick);
        }
    }

    // vérifie la signature de la brique sans connexion réseau
    public boolean verifyBrickOffline(FactoryBrick brick) {
        try {
            if (cachedPublicKey == null) {
                fetchPublicKey();
            }
            byte[] nameBytes = brick.name().getBytes(StandardCharsets.US_ASCII);
            BigInteger serialBi = new BigInteger(brick.serial(), 16);
            byte[] serialRaw = serialBi.toByteArray();
            byte[] serialBytes = new byte[16];
            if (serialRaw.length > 16) {
                System.arraycopy(serialRaw, serialRaw.length - 16, serialBytes, 0, 16);
            } else {
                System.arraycopy(serialRaw, 0, serialBytes, 16 - serialRaw.length, serialRaw.length);
            }

            ByteArrayOutputStream outputStream = new ByteArrayOutputStream();
            outputStream.write(nameBytes);
            outputStream.write(serialBytes);
            byte[] dataToVerify = outputStream.toByteArray();

            Signature sig = Signature.getInstance("Ed25519");
            sig.initVerify(cachedPublicKey);
            sig.update(dataToVerify);
            
            byte[] signatureBytes = hexStringToByteArray(brick.certificate());
            return sig.verify(signatureBytes);

        } catch (Exception e) {
            System.err.println("erreur vérification hors-ligne : " + e.getMessage());
            return false;
        }
    }

    // récupère la clé publique de l'usine pour la vérification
    private void fetchPublicKey() throws Exception {
        String json = sendRequest("/signature-public-key", "GET", null);
        String keyHex = json.replaceAll("[^a-fA-F0-9]", ""); 
        byte[] keyBytes = hexStringToByteArray(keyHex);
        X509EncodedKeySpec spec = new X509EncodedKeySpec(keyBytes);
        KeyFactory kf = KeyFactory.getInstance("Ed25519");
        cachedPublicKey = kf.generatePublic(spec);
    }

    // convertit une chaîne hexadécimale en tableau d'octets
    private byte[] hexStringToByteArray(String s) {
        int len = s.length();
        byte[] data = new byte[len / 2];
        for (int i = 0; i < len; i += 2) {
            data[i / 2] = (byte) ((Character.digit(s.charAt(i), 16) << 4)
                                 + Character.digit(s.charAt(i+1), 16));
        }
        return data;
    }

    private record DeliveryResponse(Boolean completion_date, List<FactoryBrick> built_blocks) {}
}