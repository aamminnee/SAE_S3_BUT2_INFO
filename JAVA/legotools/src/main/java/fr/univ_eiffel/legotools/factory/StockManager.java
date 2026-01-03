package fr.univ_eiffel.legotools.factory;

import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import fr.univ_eiffel.legotools.model.FactoryBrick;

import java.io.*;
import java.nio.file.*;
import java.util.*;

public class StockManager {
    private static final String STOCK_FILE = "stock.json";
    private final List<FactoryBrick> bricks = new ArrayList<>();
    private final Gson gson = new Gson();

    public StockManager() {
        load();
    }

    public void addBricks(List<FactoryBrick> newBricks) {
        bricks.addAll(newBricks);
        save();
        System.out.println(newBricks.size() + " briques ajoutées au stock local.");
    }

    public void showStock() {
        System.out.println("\n--- ÉTAT DU STOCK ---");
        if (bricks.isEmpty()) {
            System.out.println("(Vide)");
        } else {
            // On compte les briques par type (nom)
            Map<String, Integer> counts = new HashMap<>();
            for (FactoryBrick b : bricks) {
                counts.merge(b.name(), 1, Integer::sum);
            }
            counts.forEach((name, count) -> System.out.println("- " + name + " : " + count));
            System.out.println("TOTAL : " + bricks.size() + " briques.");
        }
        System.out.println("---------------------");
    }

    private void save() {
        try {
            String json = gson.toJson(bricks);
            Files.writeString(Path.of(STOCK_FILE), json);
        } catch (IOException e) {
            System.err.println("Erreur sauvegarde stock : " + e.getMessage());
        }
    }

    private void load() {
        if (!Files.exists(Path.of(STOCK_FILE))) return;
        try {
            String json = Files.readString(Path.of(STOCK_FILE));
            List<FactoryBrick> loaded = gson.fromJson(json, new TypeToken<List<FactoryBrick>>(){}.getType());
            if (loaded != null) bricks.addAll(loaded);
        } catch (IOException e) {
            System.err.println("Erreur chargement stock : " + e.getMessage());
        }
    }
}