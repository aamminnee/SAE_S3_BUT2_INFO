package fr.univ_eiffel.legotools.factory.impl;

import com.google.gson.Gson;
import fr.univ_eiffel.legotools.factory.api.ApiSender;
import fr.univ_eiffel.legotools.factory.api.PaymentStrategy;
import fr.univ_eiffel.legotools.factory.security.ProofOfWorkSolver;
import java.io.IOException;
import java.util.HexFormat;

public class PoWPaymentStrategy implements PaymentStrategy {
    private final Gson gson = new Gson();
    private final ProofOfWorkSolver powSolver = new ProofOfWorkSolver("SHA-256");

    private record Challenge(String data_prefix, String hash_prefix, String reward) {}
    private record ChallengeAnswer(String data_prefix, String hash_prefix, String answer) {}

    @Override
    public void pay(long amountNeeded, long currentBalance, ApiSender api) throws IOException {
        while (currentBalance < amountNeeded) {
            System.out.println("Stratégie PoW : Minage en cours... (Solde: " + currentBalance + ")");
            
            String json = api.send("/billing/challenge", "GET", null);
            Challenge challenge = gson.fromJson(json, Challenge.class);
            
            byte[] solution = powSolver.solve(
                HexFormat.of().parseHex(challenge.data_prefix()), 
                HexFormat.of().parseHex(challenge.hash_prefix())
            );
            
            String answerHex = HexFormat.of().formatHex(solution);
            ChallengeAnswer answer = new ChallengeAnswer(challenge.data_prefix(), challenge.hash_prefix(), answerHex);    
            api.send("/billing/challenge-answer", "POST", gson.toJson(answer));
            double rewardVal = Double.parseDouble(challenge.reward());
            currentBalance += (long) rewardVal;
            
            System.out.println("Gagné ! Nouveau solde estimé : " + currentBalance);
        }
        System.out.println("Fonds suffisants atteints !");
    }
}