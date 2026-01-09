package fr.univ_eiffel.legotools.factory.api;

import java.io.IOException;

// interface définissant une stratégie de paiement pour le compte usine
public interface PaymentStrategy {
    // méthode pour effectuer un paiement ou un rechargement de compte
    void pay(long amountNeeded, long currentBalance, ApiSender api) throws IOException;
}