package fr.univ_eiffel.lego.paving.runner;

import java.io.*;
import java.util.*;

public class PavingRunner {

    public static void main(String[] args) {
        if (args.length < 2) {
            System.out.println("Usage: java PavageRunner <input_txt_file> <pieces_txt_file>");
            System.exit(1);
        }

        String inputFile = args[0];  
        String piecesFile = args[1];  
        String outputFile = "paving/out1x1.txt";

        File outputDir = new File("paving");
        if (!outputDir.exists()) {
            outputDir.mkdirs();
        }

        try {
            
            List<String> command = new ArrayList<>();
            command.add("./c/pavage");   
            command.add(inputFile);     
            command.add(piecesFile);   
            command.add(outputFile);    

            ProcessBuilder pb = new ProcessBuilder(command);
            pb.redirectErrorStream(true); 
            
            Process process = pb.start();

            BufferedReader reader = new BufferedReader(new InputStreamReader(process.getInputStream()));
            String line;
            while ((line = reader.readLine()) != null) {
                System.out.println(line);
            }

            int exitCode = process.waitFor();
            if (exitCode == 0) {
                System.out.println("Pavage terminé avec succès. Fichier généré : " + outputFile);
            } else {
                System.err.println("Erreur lors de l'exécution de pavage.c. Code : " + exitCode);
            }

        } catch (IOException | InterruptedException e) {
            e.printStackTrace();
        }
    }
}
