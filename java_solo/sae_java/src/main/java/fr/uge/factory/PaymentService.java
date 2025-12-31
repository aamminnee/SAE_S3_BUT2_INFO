package fr.uge.factory;

import com.google.gson.Gson;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;

public class PaymentService implements PaymentStrategy {
    private final Gson gson = new Gson();
    private ChallengeResponse getChallenge() throws IOException {
        URL url = new URL(FactoryConfig.API_BASE_URL + "/billing/challenge");
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("GET");
        conn.setRequestProperty("Accept", "application/json");
        if (conn.getResponseCode() != 200) {
            throw new IOException("Erreur lors de la récupération du challenge. Code: " + conn.getResponseCode());
        }
        try (BufferedReader br = new BufferedReader(new InputStreamReader(conn.getInputStream(), StandardCharsets.UTF_8))) {
            return gson.fromJson(br, ChallengeResponse.class);
        }
    }
    private boolean submitAnswer(ChallengeAnswerRequest request) throws IOException {
        URL url = new URL(FactoryConfig.API_BASE_URL + "/billing/challenge-answer");
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("POST");
        conn.setRequestProperty("Content-Type", "application/json");
        conn.setRequestProperty("X-Email", FactoryConfig.EMAIL);
        conn.setRequestProperty("X-Secret-Key", FactoryConfig.SECRET_KEY);
        conn.setDoOutput(true);
        String jsonInputString = gson.toJson(request);
        try(OutputStream os = conn.getOutputStream()) {
            os.write(jsonInputString.getBytes(StandardCharsets.UTF_8));
        }
        int responseCode = conn.getResponseCode();
        return responseCode == 200;
    }
    public boolean rechargeAccount(BrickFactory factory, double targetAmount) {
        try {
            // Récupérer le challenge
            ChallengeResponse challenge = getChallenge();
            // Vérifier si le solde actuel est suffisant
            if (factory.getBalance() >= targetAmount) {
                System.out.println("Solde actuel suffisant. Rechargement ignoré.");
                return true;
            }
            // Résoudre le challenge
            String dataPrefix = challenge.data_prefix();
            String hashPrefix = challenge.hash_prefix();
            byte[] dataPrefixBytes = dataPrefix.getBytes(StandardCharsets.UTF_8);
            byte[] fullData = new byte[dataPrefixBytes.length + 1];
            System.arraycopy(dataPrefixBytes, 0, fullData, 0, dataPrefixBytes.length);
            MessageDigest digest = HashUtils.getSha256Digest();
            String answer = null;
            long attempts = 0;
            System.out.println("Début de la résolution recharge. Hash cible: " + hashPrefix + "...");
            for (int i = 0; i <= 255; i++) {
                attempts++;
                fullData[dataPrefixBytes.length] = (byte) i;
                // Calcul du hash SHA-256
                byte[] hash = digest.digest(fullData);
                String hashHex = HashUtils.bytesToHex(hash);
                if (hashHex.startsWith(hashPrefix)) {
                    answer = new String(fullData, StandardCharsets.UTF_8);
                    System.out.printf("Solution trouvée après %d tentatives: %s\n", attempts, answer);
                    break;
                }
                digest.reset();
            }
            if (answer == null) {
                System.err.println("Impossible de trouver la solution dans la plage [0-255].");
                return false;
            }
            // Soumettre la réponse
            ChallengeAnswerRequest answerRequest = new ChallengeAnswerRequest(
                    dataPrefix,
                    hashPrefix,
                    answer
            );
            boolean success = submitAnswer(answerRequest);
            if (success) {
                System.out.printf("Réponse soumise avec succès ! Compte crédité de %.2f.\n", challenge.reward());
                return true;
            } else {
                System.err.println("Soumission échouée ou réponse invalide.");
                return false;
            }
        } catch (IOException | RuntimeException e) {
            System.err.println("Erreur fatale lors du rechargement : " + e.getMessage());
            return false;
        }
    }
}
