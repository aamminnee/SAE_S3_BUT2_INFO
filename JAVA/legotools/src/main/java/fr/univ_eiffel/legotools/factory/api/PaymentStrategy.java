package fr.univ_eiffel.legotools.factory.api;

import java.io.IOException;

public interface PaymentStrategy {
    void pay(long amountNeeded, long currentBalance, ApiSender api) throws IOException;
}