package fr.univ_eiffel.legotools.factory.api;

import fr.univ_eiffel.legotools.model.FactoryBrick;
import java.io.IOException;
import java.util.List;
import java.util.Map;

public interface LegoFactory {
    // récupère le solde actuel du compte
    long getBalance() throws IOException;
    // recharge le compte avec un montant minimum
    void rechargeAccount(long amountNeeded) throws IOException;
    
    // demande un devis pour une liste d'articles
    Quote requestQuote(Map<String, Integer> items) throws IOException;
    
    // accepte et paie un devis spécifique
    void acceptQuote(String quoteId) throws IOException;
    // récupère les briques livrées pour une commande
    List<FactoryBrick> retrieveOrder(String quoteId) throws IOException;
    // vérifie l'authenticité d'une brique
    boolean verifyBrick(FactoryBrick brick);

    // représente un devis avec son identifiant et son prix total
    record Quote(String id, float price) {}
}