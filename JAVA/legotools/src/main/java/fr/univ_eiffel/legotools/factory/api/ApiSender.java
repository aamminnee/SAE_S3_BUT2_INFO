package fr.univ_eiffel.legotools.factory.api;

import java.io.IOException;

@FunctionalInterface
public interface ApiSender {
    String send(String endpoint, String method, String body) throws IOException;
}