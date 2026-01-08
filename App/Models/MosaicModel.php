<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use Exception;

class MosaicModel extends Model {
    protected $table = 'Mosaic';

    // Coefficient multiplicateur de marge sur la matière première
    public const MARGIN_COEFF = 2;

    // Frais fixes (emballage, logistique)
    public const HANDLING_FEE = 5.99;

    // Frais de livraison
    public const DELIVERY_FEE = 4.99;

    // // génération des mosaïques (code existant)
    public function generateTemporaryMosaics($idImage, $blobData, $extension) {
        $projectRoot = dirname(__DIR__, 2); 
        $workDir = $projectRoot . '/JAVA/legotools';
        $jarPath = $projectRoot . '/bin/legotools-1.0-SNAPSHOT.jar';
        $inputDir = $projectRoot . '/JAVA/legotools/C/input';
        $outputDir = $projectRoot . '/JAVA/legotools/C/output';

        if (!is_writable($inputDir) || !is_writable($outputDir)) {
            throw new Exception("Erreur de permissions sur les dossiers input/output.");
        }

        $this->updateBriquesFile($inputDir . '/briques.txt');

        $inputFilename = 'image_' . $idImage . '.' . $extension;
        $outputFilename = 'image_' . $idImage . '.' . $extension;
        $inputPath = $inputDir . '/' . $inputFilename;
        $outputPath = $outputDir . '/' . $outputFilename;

        file_put_contents($inputPath, $blobData);
        $execName = $projectRoot . '/bin/pavage'; 

        $cmd = sprintf(
            'cd %s && java -jar %s pave %s %s %s all 2>&1',
            escapeshellarg($workDir),
            escapeshellarg($jarPath),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath),
            escapeshellarg($execName)
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        $results = [];
        $searchPattern = $outputDir . '/image_' . $idImage . '*';
        $generatedFiles = glob($searchPattern);

        if ($generatedFiles) {
            foreach ($generatedFiles as $file) {
                $filename = basename($file);
                $type = 'default';
                
                // Détection du type
                // Remplacer le bloc de détection (lignes 70-75) par celui-ci :
                if (strpos($filename, 'minimisation') !== false) $type = 'minimisation';
                elseif (strpos($filename, 'rentabilite') !== false || strpos($filename, 'rentable') !== false) $type = 'rentabilite';
                elseif (strpos($filename, 'stock') !== false) $type = 'stock';
                elseif (strpos($filename, 'libre') !== false) $type = 'libre';
                else $type = 'libre'; // Valeur par défaut pour éviter le type 'default'
                if (!isset($results[$type])) {
                    $results[$type] = ['img' => null, 'txt' => null, 'count' => 0];
                }

                $info = pathinfo($file);

                // CAS 1 : C'est le fichier d'inventaire
                if (strpos($filename, 'inventory') !== false) {
                    $content = file_get_contents($file);
                    // On cherche "Total de briques : X"
                    if (preg_match('/Total de briques\s*:\s*(\d+)/', $content, $matches)) {
                        $results[$type]['count'] = (int)$matches[1];
                    }
                    @unlink($file); // On supprime après lecture
                }
                // CAS 2 : C'est le fichier texte du pavage (PAS l'inventaire)
                elseif (isset($info['extension']) && $info['extension'] === 'txt') {
                    $results[$type]['txt'] = file_get_contents($file);
                    @unlink($file);
                }
                // CAS 3 : C'est l'image de prévisualisation
                elseif (isset($info['extension']) && in_array($info['extension'], ['png', 'jpg', 'jpeg'])) {
                    $imgContent = file_get_contents($file);
                    if ($imgContent) {
                        $mime = mime_content_type($file);
                        $results[$type]['img'] = "data:$mime;base64," . base64_encode($imgContent);
                    }
                    @unlink($file);
                }
            }
        }
        @unlink($inputPath);
        return $results;
    }

    // // sauvegarde le choix
    public function saveSelectedMosaic($idImage, $content, $type) {
        $db = Db::getInstance();
        $sql = "INSERT INTO Mosaic (pavage, id_Image, generation_date) VALUES (?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(1, $content, PDO::PARAM_LOB);
        $stmt->bindParam(2, $idImage, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $db->lastInsertId();
        }
        return false;
    }

    // // méthode pour visualiser le pavage final via java
    public function getMosaicVisual($idMosaic) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT pavage FROM Mosaic WHERE id_Mosaic = ?");
        $stmt->execute([$idMosaic]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res || empty($res['pavage'])) {
            return null;
        }

        $pavageContent = $res['pavage'];
        $projectRoot = dirname(__DIR__, 2);
        
        // // définition des chemins conformes à votre demande
        $workDir = $projectRoot . '/JAVA/legotools';
        $inputDir = $workDir . '/C/input';
        $outputDir = $workDir . '/C/output';
        
        // // création des dossiers si besoin
        if (!is_dir($inputDir)) mkdir($inputDir, 0777, true);
        if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

        // // nom unique pour éviter les conflits
        $uniqueId = uniqid();
        $txtFilename = 'visual_' . $uniqueId . '.txt';
        $pngFilename = 'visual_' . $uniqueId . '.png';
        
        $inputPath = $inputDir . '/' . $txtFilename;
        $outputPath = $outputDir . '/' . $pngFilename;

        // // 1. écrire le fichier .txt dans l'input java
        file_put_contents($inputPath, $pavageContent);

        // // chemin absolu du jar
        $jarPath = $projectRoot . '/bin/legotools-1.0-SNAPSHOT.jar';
        
        // // 2. exécuter la commande java visualize
        // // on se déplace dans java/legotools pour que java trouve ses dépendances si besoin
        $cmd = sprintf(
            'cd %s && java -jar %s visualize %s %s 2>&1',
            escapeshellarg($workDir),
            escapeshellarg($jarPath),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        $base64Image = null;

        // // 3. lire l'image générée et nettoyer
        if (file_exists($outputPath)) {
            $data = file_get_contents($outputPath);
            if ($data !== false) {
                $base64Image = 'data:image/png;base64,' . base64_encode($data);
            }
            // // supprimer le fichier de sortie
            @unlink($outputPath);
        } else {
            // // debug : afficher l'erreur dans les logs si besoin
            error_log("Erreur Java Visualize: " . implode(" | ", $output));
        }

        // // supprimer le fichier d'entrée
        @unlink($inputPath);

        return $base64Image;
    }

    public function getBricksList($idMosaic) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT pavage FROM Mosaic WHERE id_Mosaic = ?");
        $stmt->execute([$idMosaic]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res || empty($res['pavage'])) {
            return [];
        }

        // On découpe le contenu ligne par ligne
        $lines = explode("\n", $res['pavage']);
        $inventory = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Format attendu par ligne : "2x4/ff0000 10 20 0"
            $parts = explode(' ', $line);
            $key = $parts[0]; // ex: "2x4/ff0000"
            
            // Sécurité : on ignore les lignes qui ne sont pas des briques (ex: entêtes)
            if (strpos($key, '/') === false) continue; 

            if (!isset($inventory[$key])) {
                $inventory[$key] = 0;
            }
            $inventory[$key]++;
        }

        // On formate proprement pour la vue
        $finalList = [];
        foreach ($inventory as $key => $count) {
            // Séparation taille et couleur
            list($size, $color) = explode('/', $key);
            
            // Ajout du # si manquant pour le CSS
            if ($color[0] !== '#') $color = '#' . $color;

            $finalList[] = [
                'size' => $size,
                'color' => $color,
                'count' => $count
            ];
        }

        // Tri : d'abord par taille (décroissant), puis par couleur
        array_multisort(array_column($finalList, 'size'), SORT_DESC, $finalList);

        return $finalList;
    }

    // Sauvegarde la composition dans la table de liaison
    public function saveMosaicComposition($idMosaic) {
        // Récupère la liste des briques
        $bricks = $this->getBricksList($idMosaic);
        
        if (empty($bricks)) return false;

        $db = Db::getInstance();

        foreach ($bricks as $brick) {
            // Trouve l'ID de l'item correspondant
            $idItem = $this->findItemId($brick['size'], $brick['color']);

            if ($idItem) {
                // Insertion dans MosaicComposition
                $sql = "INSERT IGNORE INTO MosaicComposition (id_Mosaic, id_Item, quantity_needed) VALUES (?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([$idMosaic, $idItem, $brick['count']]);
            }
        }
        return true;
    }

    // Trouve l'ID de l'item via Jointure
    private function findItemId($size, $hexColor) {
        $db = Db::getInstance();

        $cleanHex = str_replace('#', '', $hexColor);

        // Parsing de la taille
        $dims = explode('x', $size);
        if (count($dims) < 2) return null;
        $w = (int)$dims[0];
        $l = (int)$dims[1];

        // Requête avec jointures
        $sql = "SELECT I.id_Item 
                FROM Item I
                JOIN Shapes S ON I.shape_id = S.id_shape
                JOIN Colors C ON I.color_id = C.id_color
                WHERE C.hex_color = ? 
                AND (
                    (S.width = ? AND S.length = ?) 
                    OR 
                    (S.width = ? AND S.length = ?)
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$cleanHex, $w, $l, $l, $w]);
        
        return $stmt->fetchColumn(); 
    }
    
    // Ajout d'une petite méthode utilitaire pour éviter les doublons si on rafraichit la page
    public function hasComposition($idMosaic) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT 1 FROM MosaicComposition WHERE id_Mosaic = ? LIMIT 1");
        $stmt->execute([$idMosaic]);
        return (bool)$stmt->fetch();
    }

    // Ajoutez cette méthode à la classe MosaicModel
    public function getMosaicPrice($idMosaic) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT pavage FROM Mosaic WHERE id_Mosaic = ?");
        $stmt->execute([$idMosaic]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res || empty($res['pavage'])) {
            return 0.00; // Prix par défaut ou gestion d'erreur
        }

        $lines = explode("\n", $res['pavage']);
        $firstLine = trim($lines[0]);
        $parts = preg_split('/\s+/', $firstLine);

        $rawCost = 0.00;

        if (isset($parts[1]) && is_numeric($parts[1])) {
            $rawCost = (float) $parts[1];
        }

        if ($rawCost <= 0) {
            return 19.99; // Prix par défaut minimum
        }

        // Formule de rentabilité
        $finalPrice = ($rawCost * self::MARGIN_COEFF) + self::HANDLING_FEE;

        // Arrondir
        return floor($finalPrice) + 0.99;
    }

    // App/Models/MosaicModel.php

    /**
     * Calcule le prix estimé à partir du contenu brut du fichier de pavage.
     * Utilise la même formule que getMosaicPrice.
     */
    public function calculatePriceFromContent($pavageContent) {
        if (empty($pavageContent)) return 0.00;

        $lines = explode("\n", $pavageContent);
        $firstLine = trim($lines[0]);
        $parts = preg_split('/\s+/', $firstLine);

        $rawCost = (isset($parts[1]) && is_numeric($parts[1])) ? (float)$parts[1] : 0.00;
        if ($rawCost <= 0) return 19.99;

        // Formule : (Coût briques * 2) + 4.99€ d'emballage
        // Le client voit ça comme le prix du produit
        $finalPrice = ($rawCost * self::MARGIN_COEFF) + self::HANDLING_FEE;

        return floor($finalPrice) + 0.99;
    }

    // App/Models/MosaicModel.php

    /**
     * Compte le nombre de briques à partir du contenu du fichier texte.
     * Plus fiable que le parsing du fichier inventory séparé.
     */
    public function countPiecesFromContent($pavageContent) {
        if (empty($pavageContent)) {
            return 0;
        }

        $lines = explode("\n", $pavageContent);
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            // On ignore les lignes vides
            if (empty($line)) continue;
            
            // On ignore la ligne de coût ou les en-têtes (qui ne contiennent généralement pas de '/')
            // Format attendu d'une brique : "2x4/ff0000 10 20 0"
            if (strpos($line, '/') === false) continue;

            $count++;
        }

        return $count;
    }

    public function getMosaicsByOrderId($orderId) {
        $sql = "SELECT m.id_Mosaic, m.pavage, i.file, i.file_type 
                FROM Mosaic m
                LEFT JOIN CustomerImage i ON m.id_Image = i.id_Image
                WHERE m.id_Order = ?";
        
        // --- CORRECTION ICI : 'requete' au lieu de 'query' ---
        $results = $this->requete($sql, [$orderId])->fetchAll();
        
        // Traitement pour l'affichage (Conversion Blob -> Base64)
        foreach ($results as $row) {
            if (!empty($row->file)) {
                $row->visuel = "data:" . $row->file_type . ";base64," . base64_encode($row->file);
            } else {
                $row->visuel = null;
            }
            // Valeurs par défaut
            $row->size = 64; 
            $row->style = 'Standard';
        }
        
        return $results;
    }

    // Dans App/Models/MosaicModel.php

// Dans App/Models/MosaicModel.php

    public function getMosaicGridHtml($idMosaic) {
        $db = \App\Core\Db::getInstance();
        $stmt = $db->prepare("SELECT pavage FROM Mosaic WHERE id_Mosaic = ?");
        $stmt->execute([$idMosaic]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$res || empty($res['pavage'])) return "Contenu introuvable";

        $lines = explode("\n", trim($res['pavage']));
        $bricksData = [];
        $maxX = 0; $maxY = 0;
        $colorToSymbol = [];
        $symbolIndex = 0;
        $symbols = range('A', 'Z');

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '/') === false) continue;

            $parts = preg_split('/\s+/', $line);
            $info = explode('/', $parts[0]);
            $color = "#" . $info[1];
            
            if (!isset($colorToSymbol[$color])) {
                $colorToSymbol[$color] = $symbols[$symbolIndex % 26] . (floor($symbolIndex / 26) ?: '');
                $symbolIndex++;
            }

            $size = explode('x', $info[0]);
            $w = (int)$size[0]; 
            $l = (int)$size[1];
            $x = (int)$parts[1]; 
            $y = (int)$parts[2];
            $rot = (int)($parts[3] ?? 0);

            // --- CORRECTION CRUCIALE DE LA ROTATION ---
            // Si rot = 1, on inverse largeur et longueur pour le dessin
            $finalW = ($rot == 1) ? $l : $w;
            $finalH = ($rot == 1) ? $w : $l;

            $bricksData[] = [
                'x' => $x, 'y' => $y, 'w' => $finalW, 'h' => $finalH, 
                'color' => $color, 'symbol' => $colorToSymbol[$color]
            ];

            if ($x + $finalW > $maxX) $maxX = $x + $finalW;
            if ($y + $finalH > $maxY) $maxY = $y + $finalH;
        }

        // Taille d'un tenon (scale) pour le PDF
        $scale = 12; 
        
        // Conteneur en position relative
        $html = '<div style="position: relative; width: '.($maxX * $scale).'pt; height: '.($maxY * $scale).'pt; background: #ffffff; border: 1pt solid #333;">';
        
        foreach ($bricksData as $b) {
            $html .= '<div style="
                position: absolute;
                left: '.($b['x'] * $scale).'pt;
                top: '.($b['y'] * $scale).'pt;
                width: '.($b['w'] * $scale).'pt;
                height: '.($b['h'] * $scale).'pt;
                background-color: '.$b['color'].';
                border: 0.2pt solid rgba(0,0,0,0.4);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 6pt;
                font-weight: bold;
                color: rgba(0,0,0,0.5);
                box-sizing: border-box;
                overflow: hidden;
            ">'.$b['symbol'].'</div>';
        }
        $html .= '</div>';

        return ['html' => $html, 'legend' => $colorToSymbol];
    }

    // Dans App/Models/MosaicModel.php

    // Ajoute ceci dans App/Models/MosaicModel.php

    public function getMosaicPlanData($idMosaic) {
        $db = Db::getInstance();
        $stmt = $db->prepare("SELECT pavage FROM Mosaic WHERE id_Mosaic = ?");
        $stmt->execute([$idMosaic]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$res || empty($res['pavage'])) return null;

        $lines = explode("\n", trim($res['pavage']));
        
        $bricks = [];
        $maxX = 0; 
        $maxY = 0;
        
        $colorToSymbol = [];
        $symbols = range('A', 'Z'); // A, B, C...
        $symbolIndex = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            // On ignore les lignes vides ou les entêtes (celles qui n'ont pas de '/')
            if (empty($line) || strpos($line, '/') === false) continue;

            // Format ligne : 1x12/078bc9 1 0 1  (Dim/Coul X Y Rot)
            $parts = preg_split('/\s+/', $line);
            $info = explode('/', $parts[0]);
            
            $colorHex = "#" . $info[1];
            
            // Attribution d'un symbole unique par couleur
            if (!isset($colorToSymbol[$colorHex])) {
                // Gestion de plus de 26 couleurs (A..Z, puis A1..Z1, etc.)
                $suffix = floor($symbolIndex / 26) > 0 ? floor($symbolIndex / 26) : '';
                $colorToSymbol[$colorHex] = $symbols[$symbolIndex % 26] . $suffix;
                $symbolIndex++;
            }

            $size = explode('x', $info[0]);
            $w = (int)$size[0]; // Largeur brute
            $h = (int)$size[1]; // Hauteur brute
            
            $x = (int)$parts[1]; 
            $y = (int)$parts[2];
            $rot = (int)($parts[3] ?? 0); // Rotation (0 ou 1)

            // --- C'EST ICI QUE LE BUG EST CORRIGÉ ---
            // Si la brique est tournée (rot=1), on inverse sa largeur et sa hauteur pour le dessin
            $finalW = ($rot == 1) ? $h : $w;
            $finalH = ($rot == 1) ? $w : $h;

            $bricks[] = [
                'x' => $x,
                'y' => $y,
                'w' => $finalW,
                'h' => $finalH,
                'color' => $colorHex,
                'symbol' => $colorToSymbol[$colorHex]
            ];

            // Calcul des dimensions totales du canevas
            if ($x + $finalW > $maxX) $maxX = $x + $finalW;
            if ($y + $finalH > $maxY) $maxY = $y + $finalH;
        }

        return [
            'width' => $maxX,
            'height' => $maxY,
            'bricks' => $bricks,
            'legend' => $colorToSymbol
        ];
    }

    /**
     * Génère le fichier briques.txt formaté pour le programme C
     */
    /**
     * Génère briques.txt en utilisant les dimensions réelles de la BDD.
     * Format : Largeur-Longueur[-Hole] (ex: 1-1-14)
     */
    /**
     * Génère briques.txt avec toutes les pièces distinctes (par ID).
     */
    private function updateBriquesFile($filePath) {
        $stockModel = new StockModel();
        $items = $stockModel->getFullStockDetails(); 

        // Listes ordonnées pour générer les indices
        $shapesList = []; // Liste des définitions "W-H-T"
        $colorsList = []; // Liste des codes Hex (peut contenir des doublons visuels)
        
        // Maps pour retrouver l'index rapidement
        // Clé = Definition de forme (ex: "2-4") => Valeur = Index (0, 1...)
        $shapeMap = []; 
        // Clé = ID Couleur (ex: 58) => Valeur = Index (0, 1...)
        $colorMap = []; 

        $brickLines = [];

        foreach ($items as $item) {
            // --- 1. Gestion de la Forme ---
            $shapeDef = $item['width'] . '-' . $item['length'];
            if (!empty($item['hole'])) {
                $shapeDef .= '-' . $item['hole'];
            }

            if (!isset($shapeMap[$shapeDef])) {
                $shapeMap[$shapeDef] = count($shapesList);
                $shapesList[] = $shapeDef;
            }
            $sIdx = $shapeMap[$shapeDef];

            // --- 2. Gestion de la Couleur (Par ID et non par Hex) ---
            $cId = $item['id_color'];
            $cHex = str_replace('#', '', $item['hex_color']);

            if (!isset($colorMap[$cId])) {
                $colorMap[$cId] = count($colorsList);
                $colorsList[] = $cHex; // On stocke le hex, même s'il existe déjà pour un autre ID
            }
            $cIdx = $colorMap[$cId];

            // --- 3. Préparation de la ligne Brique ---
            // Format : IndexForme/IndexCouleur Prix Stock
            $price = $item['price'];
            $qty = max(0, intval($item['current_stock']));
            
            $brickLines[] = "$sIdx/$cIdx $price $qty";
        }
        
        // --- 4. Écriture du fichier ---
        $content = "";
        
        // Entête : NbFormes NbCouleurs NbBriques
        $content .= count($shapesList) . " " . count($colorsList) . " " . count($brickLines) . "\n";

        // Liste des formes
        foreach ($shapesList as $s) {
            $content .= "$s\n";
        }

        // Liste des couleurs
        foreach ($colorsList as $c) {
            $content .= "$c\n";
        }

        // Liste des briques
        foreach ($brickLines as $line) {
            $content .= "$line\n";
        }

        file_put_contents($filePath, $content);
    }
}