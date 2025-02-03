<?php
// Configuration du dossier de destination
$uploadDir = './image/';

// Recevoir le nom du fichier à supprimer
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['filename'])) {
    $fileName = $data['filename'];
    $filePath = $uploadDir . $fileName;

    // Vérifier si le fichier existe et le supprimer
    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            echo "Le fichier a été supprimé avec succès : $fileName";
        } else {
            echo "Erreur lors de la suppression du fichier.";
        }
    } else {
        echo "Le fichier n'existe pas.";
    }
} else {
    echo "Nom de fichier invalide.";
}
?>
