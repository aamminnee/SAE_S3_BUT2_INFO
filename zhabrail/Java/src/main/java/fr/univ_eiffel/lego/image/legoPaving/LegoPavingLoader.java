package fr.univ_eiffel.lego.image.legoPaving;

import java.awt.Color;
import java.io.BufferedReader;
import java.io.FileReader;
import java.io.IOException;

public class LegoPavingLoader {
	
	public static LegoPaving load1x1(String filename) throws IOException {
		BufferedReader reader = new BufferedReader(new FileReader(filename));
		LegoPaving paving = new LegoPaving();
		
		String header = reader.readLine();
		String[] headerParts = header.split(" ");
		int nBriques = Integer.parseInt(headerParts[0]);
		
		for (int i = 0; i < nBriques; i++) {
			String line = reader.readLine();
			String[] parts = line.split(" ");
			String[] shapeColor = parts[0].split("/");
            String[] wh = shapeColor[0].split("x");
            int width = Integer.parseInt(wh[0]);
            int height = Integer.parseInt(wh[1]);
            String colorHex = shapeColor[1];
            Color color = new Color(
                    Integer.parseInt(colorHex.substring(0,2),16),
                    Integer.parseInt(colorHex.substring(2,4),16),
                    Integer.parseInt(colorHex.substring(4,6),16)
            );
            int x = Integer.parseInt(parts[1]);
            int y = Integer.parseInt(parts[2]);
            paving.addBrick(new LegoBrick(x, y, width, height, color));
		}
		reader.close();
		return paving;
	}
}
