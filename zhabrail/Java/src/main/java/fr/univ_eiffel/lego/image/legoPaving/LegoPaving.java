package fr.univ_eiffel.lego.image.legoPaving;

import java.awt.Graphics;
import java.awt.image.BufferedImage;
import java.util.ArrayList;
import java.util.List;

public class LegoPaving {
	
	private List<LegoBrick> bricks = new ArrayList<>();
	
	public void addBrick(LegoBrick brick) {
		bricks.add(brick);
	}
	
	public BufferedImage generateImage(int width, int height) {
		BufferedImage img = new BufferedImage(width, height, BufferedImage.TYPE_INT_RGB);
		Graphics g = img.getGraphics();
		
		for (LegoBrick b : bricks) {
			g.setColor(b.color);
			g.fillRect(b.x, b.y, b.width, b.height);
		}
		g.dispose();
		return img;
	}
	
	public List<LegoBrick> getBricks() {
		return bricks;
	}

}
