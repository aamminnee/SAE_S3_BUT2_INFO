package fr.uge.factory;

public interface PaymentStrategy {
    boolean rechargeAccount(BrickFactory factory, double targetAmount);
}
