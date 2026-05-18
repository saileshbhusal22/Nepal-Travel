<?php
require_once __DIR__ . '/config/db.php';

$result = $conn->query("SELECT id, image_path FROM posts");

$updated_count = 0;

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $image_path = $row['image_path'];
    
    // Normalize path to check file existence
    $relative_path = ltrim($image_path, '/');
    $relative_path = preg_replace('/^Nepal-Travel\//', '', $relative_path);
    
    $full_path = __DIR__ . '/' . $relative_path;
    
    if (!file_exists($full_path)) {
        // File is missing, update the database to use a placeholder
        $placeholder = 'images/annapurna_trek.png';
        $stmt = $conn->prepare("UPDATE posts SET image_path = ? WHERE id = ?");
        $stmt->bind_param("si", $placeholder, $id);
        $stmt->execute();
        $stmt->close();
        $updated_count++;
        echo "Updated post ID $id (Missing image: $image_path) -> $placeholder\n";
    }
}

echo "Finished checking images. Total posts updated: $updated_count\n";
$conn->close();
?>
