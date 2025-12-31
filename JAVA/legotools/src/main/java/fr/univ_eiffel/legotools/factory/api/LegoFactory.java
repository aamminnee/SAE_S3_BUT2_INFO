package fr.univ_eiffel.legotools.factory.api;

import fr.univ_eiffel.legotools.model.FactoryBrick;
import java.io.IOException;
import java.util.List;
import java.util.Map;

public interface LegoFactory {
    long getBalance() throws IOException;

    void rechargeAccount(long amountNeeded) throws IOException;

    String requestQuote(Map<String, Integer> items) throws IOException;

    void acceptQuote(String quoteId) throws IOException;

    List<FactoryBrick> retrieveOrder(String quoteId) throws IOException;

    boolean verifyBrick(FactoryBrick brick);

    boolean ping() throws IOException;

    String getProductionStats() throws IOException;

    String getSignaturePublicKey() throws IOException;

    String getCatalog() throws IOException;
}