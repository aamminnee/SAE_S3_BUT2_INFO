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

    // définit la structure d'un challenge reçu de l'usine
    private record Challenge(String data_prefix, String hash_prefix, String reward) {}
    // définit la structure de la réponse à envoyer au challenge
    private record ChallengeAnswer(String data_prefix, String hash_prefix, String answer) {}

    @Override
    public void pay(long amountNeeded, long currentBalance, ApiSender api) throws IOException {
        // boucle tant que le solde est insuffisant pour payer la commande
        while (currentBalance < amountNeeded) {
            System.out.println("Stratégie PoW : Minage en cours... (Solde: " + currentBalance + ")");
            
            // récupère un nouveau challenge de minage via l'api
            String json = api.send("/billing/challenge", "GET", null);
            Challenge challenge = gson.fromJson(json, Challenge.class);
            
            // résout le puzzle cryptographique en cherchant le bon hash
            byte[] solution = powSolver.solve(
                HexFormat.of().parseHex(challenge.data_prefix()), 
                HexFormat.of().parseHex(challenge.hash_prefix())
            );
            
            // formate et envoie la solution pour créditer le compte
            String answerHex = HexFormat.of().formatHex(solution);
            ChallengeAnswer answer = new ChallengeAnswer(challenge.data_prefix(), challenge.hash_prefix(), answerHex);    
            api.send("/billing/challenge-answer", "POST", gson.toJson(answer));
            
            // met à jour le solde local avec la récompense obtenue
            double rewardVal = Double.parseDouble(challenge.reward());
            currentBalance += (long) rewardVal;
            
            System.out.println("Gagné ! Nouveau solde estimé : " + currentBalance);
        }
        System.out.println("Fonds suffisants atteints !");
    }
}