package fr.univ_eiffel.lego.image.legoPaving;

import java.awt.Color;

public class LegoBrick {
	public int x, y;
	public int width, height;
	public Color color;
	
	public LegoBrick(int x, int y, int width, int height, Color color) {
		this.x = x;
        this.y = y;
        this.width = width;
        this.height = height;
        this.color = color;
	}
}
