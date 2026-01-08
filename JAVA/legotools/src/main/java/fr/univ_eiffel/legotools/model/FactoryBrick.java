package fr.univ_eiffel.legotools.model;

import java.time.LocalDate;
import java.time.LocalTime;
import java.util.HexFormat;

public record FactoryBrick(String name, String serial, String certificate) {

    // extrait la couleur du nom au format hexadécimal
    public String color() {
        if (name != null && name.contains("/")) {
            return name.substring(name.lastIndexOf('/') + 1);
        }
        // retourne noir par défaut si le format est invalide
        return "000000"; 
    }

    // extrait le nom de la forme sans la partie couleur
    public String shapeName() {
        if (name != null && name.contains("/")) {
            return name.substring(0, name.lastIndexOf('/'));
        }
        return name;
    }

    // décode les informations de date et d'heure depuis le numéro de série
    public String getManufacturingDateInfo() {
        // vérifie si le numéro de série est présent et assez long
        if (serial == null || serial.length() < 10) return "Date inconnue";

        try {
            byte[] bytes = HexFormat.of().parseHex(serial);
            
            // récupère le nombre de jours écoulés depuis le premier janvier deux mille
            int daysSince2000 = ((bytes[0] & 0xFF) << 8) | (bytes[1] & 0xFF);
            
            // récupère le nombre de millisecondes écoulées dans la journée
            long msInDay = ((bytes[2] & 0xFF) << 16) | ((bytes[3] & 0xFF) << 8) | (bytes[4] & 0xFF);

            LocalDate baseDate = LocalDate.of(2000, 1, 1);
            LocalDate date = baseDate.plusDays(daysSince2000);
            LocalTime time = LocalTime.ofNanoOfDay(msInDay * 1_000_000);

            return date.toString() + " à " + time.toString();
        } catch (Exception e) {
            return "Date invalide";
        }
    }
}