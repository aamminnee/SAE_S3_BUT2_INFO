package fr.uge.factory;

public interface BrickFactory {
    void ping();
    String getPublicKey();
    boolean verifyCertificate(String name, String serial, String certificate);
    double getBalance();
    QuoteRequestResponse requestQuote(QuoteRequest request);
    String placeOrder(String quoteId);
    DeliveryResponse requestDelivery(String quoteId);
}
