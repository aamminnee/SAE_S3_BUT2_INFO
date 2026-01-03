package fr.univ_eiffel.legotools.factory.api;
import fr.univ_eiffel.legotools.factory.security.ProofOfWorkSolver;
import java.io.IOException;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.util.HexFormat;

import com.google.gson.Gson;

/** Used to refill the prepaid account on the Lego factory */
public class AccountRefiller {
    public static final String FACTORY_URL = "https://legofactory.plade.org";
    public static final ProofOfWorkSolver POW_SOLVER = new ProofOfWorkSolver("SHA-256");

    private final String email;
    private final String apiKey;

    private Gson gson = new Gson();

    public AccountRefiller(String email, String apiKey) {
        this.email = email;
        this.apiKey = apiKey;
    }

    public record Challenge(String data_prefix, String hash_prefix) {}

    public Challenge fetchChallenge() throws IOException {
        @SuppressWarnings("deprecation")
        var connection = (HttpURLConnection)new URL(FACTORY_URL + "/billing/challenge").openConnection();
        connection.addRequestProperty("X-Email", email);
        connection.addRequestProperty("X-Secret-Key", apiKey);
        int status = connection.getResponseCode();
        if (status != 200)
            throw new IOException("Cannot get the challenge: status code is " + status);
        var answer = new String(connection.getInputStream().readAllBytes(), StandardCharsets.UTF_8);
        return gson.fromJson(answer, Challenge.class);
    }

    public byte[] solveChallenge(Challenge challenge) {
        var startTime = System.nanoTime();
        byte[] dataPrefix = HexFormat.of().parseHex(challenge.data_prefix());
        byte[] hashPrefix = HexFormat.of().parseHex(challenge.hash_prefix());
        byte[] solved = POW_SOLVER.solve(dataPrefix, hashPrefix);
        System.err.println("Challenge solved in " + (System.nanoTime() - startTime)/1e9 + " seconds");
        return solved;
    }

    public record ChallengeAnswer(String data_prefix, String hash_prefix, String answer) {}

    public void submitChallengeAnswer(ChallengeAnswer challengeAnswer) throws IOException {
        @SuppressWarnings("deprecation")
        var connection = (HttpURLConnection)new URL(FACTORY_URL + "/billing/challenge-answer").openConnection();
        connection.setRequestMethod("POST");
        connection.setDoOutput(true);
        connection.addRequestProperty("X-Email", email);
        connection.addRequestProperty("X-Secret-Key", apiKey);
        String body = gson.toJson(challengeAnswer);
        connection.getOutputStream().write(body.getBytes(StandardCharsets.UTF_8));
        int statusCode = connection.getResponseCode();
        if (statusCode != 200)
            throw new IOException("Status code is " + statusCode + " message:" + connection.getResponseMessage());
    }

    public record AccountBalance(String balance) {};

    public String fetchAccountBalance() throws IOException {
        @SuppressWarnings("deprecation")
        var connection = (HttpURLConnection)new URL(FACTORY_URL + "/billing/balance").openConnection();
        connection.addRequestProperty("X-Email", email);
        connection.addRequestProperty("X-Secret-Key", apiKey);
        int status = connection.getResponseCode();
        if (status != 200)
            throw new IOException("Cannot get the balance, status code: " + status);
        var answer = new String(connection.getInputStream().readAllBytes(), StandardCharsets.UTF_8);
        return gson.fromJson(answer, AccountBalance.class).balance();
    }

    public String refill() throws IOException {
        var challenge = fetchChallenge();
        System.err.println("Received PoW challenge: " + challenge);
        var solved = solveChallenge(challenge);
        var challengeAnswer = new ChallengeAnswer(challenge.data_prefix(), challenge.hash_prefix(), HexFormat.of().formatHex(solved));
        submitChallengeAnswer(challengeAnswer);
        return fetchAccountBalance();
    }
}
