package fr.univ_eiffel.legotools.model;

import java.time.LocalDate;
import java.time.LocalTime;
import java.util.HexFormat;

public record FactoryBrick(String name, String serial, String certificate) {

    /**
     * Extrait la couleur du nom (format attendu : "2-2/c9cae2")
     * Si pas de séparateur, retourne une valeur par défaut ou null.
     */
    public String color() {
        if (name != null && name.contains("/")) {
            return name.substring(name.lastIndexOf('/') + 1);
        }
        // Valeur par défaut si le format est incorrect
        return "000000"; 
    }

    /**
     * Extrait le nom de la forme (ex: "2-2") sans la couleur.
     */
    public String shapeName() {
        if (name != null && name.contains("/")) {
            return name.substring(0, name.lastIndexOf('/'));
        }
        return name;
    }

    // Décodage du numéro de série (128 bits / 16 octets)
    public String getManufacturingDateInfo() {
        // Protection contre les chaînes vides ou nulles
        if (serial == null || serial.length() < 10) return "Date inconnue";

        try {
            byte[] bytes = HexFormat.of().parseHex(serial);
            
            // 2 octets pour le jour (depuis 01/01/2000)
            int daysSince2000 = ((bytes[0] & 0xFF) << 8) | (bytes[1] & 0xFF);
            
            // 3 octets pour les millisecondes dans le jour
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