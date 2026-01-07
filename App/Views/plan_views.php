<head>
    <meta charset="UTF-8">
    <title>Notice MyBrix #<?= $id ?></title>
    <style>
        :root { --lego-blue: #006CB7; --lego-red: #D92328; }
        
        /* FORCE L'AFFICHAGE DES COULEURS À L'IMPRESSION */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body { font-family: 'Segoe UI', Tahoma, sans-serif; margin: 0; padding: 0; background: #f0f2f5; }
        
        .print-btn { position: fixed; bottom: 30px; right: 30px; background: var(--lego-red); color: white; border: none; padding: 15px 25px; border-radius: 50px; font-weight: bold; cursor: pointer; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 100; }
        
        .page-container { 
            max-width: 800px; /* Réduit pour éviter que ça coupe à droite sur A4 */
            margin: 20px auto; 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.1); 
        }
        
        .notice-header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 4px solid var(--lego-blue); padding-bottom: 15px; margin-bottom: 30px; }
        .notice-header h1 { margin: 0; font-size: 1.8rem; text-transform: uppercase; color: var(--lego-blue); }

        /* Grille Page 1 */
        .grid-wrapper { 
            border: 5px solid #222; 
            background: #222; 
            display: inline-block; 
            padding: 2px;
            line-height: 0; /* Évite les espaces entre les lignes */
        }
        .lego-grid { 
            display: grid; 
            /* On utilise des pixels fixes pour la précision */
            grid-template-columns: repeat(<?= $plan['width'] ?>, 10px); 
            background: #444; 
        }
        .cell { 
            width: 10px; 
            height: 10px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 5px; 
            font-weight: bold; 
            color: rgba(0,0,0,0.4); 
            box-sizing: border-box;
        }

        .page-break { page-break-before: always; }

        /* Légende Page 2 */
        .legend-grid { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); /* 3 colonnes fixes pour éviter les débordements */
            gap: 10px; 
            margin-top: 20px;
        }
        .legend-item { display: flex; align-items: center; gap: 8px; padding: 5px; background: #fff; border-radius: 5px; border: 1px solid #ddd; }
        .swatch { width: 25px; height: 25px; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; border: 1px solid #000; }

        @media print {
            @page {
                margin: 0;
            }
            .print-btn { display: none; }
            body { background: white; }
            .page-container { 
                box-shadow: none; 
                margin: 0; 
                width: 100%; 
                max-width: 100%; 
                border:none; 
                padding: 10mm;
            }
            /* Assure que la grille ne soit pas coupée */
            .grid-wrapper { transform: scale(0.9); transform-origin: top center; }
        }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">📥 Télécharger / Imprimer (PDF)</button>

    <div class="page-container">
        <header class="notice-header">
            <div>
                <h1>Notice de Montage</h1>
                <span style="color: #666;">Modèle MyBrixStore #<?= $id ?></span>
            </div>
        </header>

        <div style="text-align: center;">
            <h3 style="text-transform: uppercase; color: #444; font-size: 1rem;">Étape 1 : Placement des briques</h3>
            <div class="grid-wrapper">
                <div class="lego-grid">
                    <?php 
                    $matrix = array_fill(0, $plan['height'], array_fill(0, $plan['width'], ['color'=>'#333','symbol'=>'']));
                    foreach($plan['bricks'] as $b) {
                        for($i=0; $i<$b['h']; $i++) {
                            for($j=0; $j<$b['w']; $j++) {
                                if (isset($matrix[$b['y']+$i][$b['x']+$j])) {
                                    $matrix[$b['y']+$i][$b['x']+$j] = ['color' => $b['color'], 'symbol' => $b['symbol']];
                                }
                            }
                        }
                    }
                    foreach($matrix as $row) {
                        foreach($row as $c) {
                            echo "<div class='cell' style='background-color: {$c['color']};'>{$c['symbol']}</div>";
                        }
                    }
                    ?>
                </div>
            </div>
            <p style="margin-top: 20px; font-style: italic; color: #888; font-size: 0.9rem;">Note : Reportez-vous à la page suivante pour la légende des symboles.</p>
        </div>
    </div>

    <div class="page-container page-break">
        <header class="notice-header">
            <h1>Inventaire des pièces</h1>

        </header>

        <p style="margin-bottom: 20px; font-size: 0.9rem;">Chaque symbole (A, B, C...) correspond à une couleur de brique spécifique :</p>

        <div class="legend-grid">
            <?php foreach($plan['legend'] as $color => $symbol): ?>
                <div class="legend-item">
                    <div class="swatch" style="background-color: <?= $color ?>;"><?= $symbol ?></div>
                    <div>
                        <small style="color:#999; font-size: 0.7rem;">CODE HEX</small><br>
                        <strong style="font-size: 0.8rem;"><?= strtoupper(str_replace('#','',$color)) ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <footer style="margin-top: 50px; text-align: center; color: #ccc; font-size: 0.7rem; border-top: 1px solid #eee; padding-top: 15px;">
            © <?= date('Y') ?> MyBrixStore - Tous droits réservés.
        </footer>
    </div>
</body>