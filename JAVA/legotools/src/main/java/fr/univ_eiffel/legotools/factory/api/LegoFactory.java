package fr.univ_eiffel.legotools.factory.api;

import fr.univ_eiffel.legotools.model.FactoryBrick;
import java.io.IOException;
import java.util.List;
import java.util.Map;

public interface LegoFactory {
    long getBalance() throws IOException;
    void rechargeAccount(long amountNeeded) throws IOException;
    
    // // modification : la méthode retourne maintenant un objet quote (id + prix)
    Quote requestQuote(Map<String, Integer> items) throws IOException;
    
    void acceptQuote(String quoteId) throws IOException;
    List<FactoryBrick> retrieveOrder(String quoteId) throws IOException;
    boolean verifyBrick(FactoryBrick brick);

    // // définition de l'objet quote directement dans l'interface pour qu'il soit partagé
    record Quote(String id, float price) {}
}