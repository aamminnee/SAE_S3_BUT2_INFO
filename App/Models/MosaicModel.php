<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Db;
use PDO;
use Exception;

class MosaicModel extends Model {
    protected $table = 'Mosaic';

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
                if (strpos($filename, 'rupture') !== false) $type = 'rupture';
                elseif (strpos($filename, 'cheap') !== false || strpos($filename, 'rentable') !== false) $type = 'cheap';
                elseif (strpos($filename, 'stock') !== false) $type = 'stock';

                if (!isset($results[$type])) {
                    $results[$type] = ['img' => null, 'txt' => null];
                }

                $info = pathinfo($file);
                if (isset($info['extension']) && $info['extension'] === 'txt') {
                    $results[$type]['txt'] = file_get_contents($file);
                    @unlink($file);
                }
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
}