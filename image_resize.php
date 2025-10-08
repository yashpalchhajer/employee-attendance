<?php
function resizeAndCompress($source, $destination, $maxWidth, $maxHeight, $targetKB) {
    list($width, $height, $type) = getimagesize($source);

    // Maintain aspect ratio
    $ratio = $width / $height;
    if ($maxWidth / $maxHeight > $ratio) {
        $newWidth = $maxHeight * $ratio;
        $newHeight = $maxHeight;
    } else {
        $newHeight = $maxWidth / $ratio;
        $newWidth = $maxWidth;
    }

    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }

    // Resize
    imagecopyresampled($newImage, $image, 0, 0, 0, 0,
        $newWidth, $newHeight, $width, $height);

    // Start high quality and reduce until target KB reached
    $quality = 90;
    do {
        imagejpeg($newImage, $destination, $quality);
        clearstatcache();
        $filesize = filesize($destination) / 1024; // KB
        $quality -= 5;
    } while ($filesize > $targetKB && $quality > 60); // don’t go below 60 (to keep good quality)

    imagedestroy($image);
    imagedestroy($newImage);

    return $destination;
}

$resizedPath = null;
$originalSize = null;
$finalSize = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']['tmp_name'])) {
    $uploadDir = __DIR__ . "/uploads/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $source = $_FILES['image']['tmp_name'];
    $fileName = time() . "_" . basename($_FILES['image']['name']);
    $destination = $uploadDir . $fileName;

    // Save original size for display
    $originalSize = round(filesize($source) / 1024 / 1024, 2); // MB

    // Resize + compress → target 500KB
    resizeAndCompress($source, $destination, 1024, 1024, 500);

    $resizedPath = "uploads/" . $fileName;
    $finalSize = round(filesize($destination) / 1024, 2); // KB
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Image Upload & Compress</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-lg p-4 rounded-3">
        <h3 class="text-center mb-4">Upload & Convert Image (MB → KB)</h3>
        
        <form action="" method="post" enctype="multipart/form-data" class="mb-3">
          <div class="mb-3">
            <input class="form-control" type="file" name="image" id="imageInput" accept="image/*" required>
          </div>

          <!-- Preview before upload -->
          <div id="previewArea" class="mb-3 text-center d-none">
            <h6>📸 Selected Image:</h6>
            <img id="previewImage" class="img-fluid rounded shadow" style="max-height: 250px;" alt="Preview">
          </div>

          <div class="d-grid">
            <button class="btn btn-primary" type="submit">Upload & Compress</button>
          </div>
        </form>

        <?php if ($resizedPath): ?>
          <div class="alert alert-success text-center">
            ✅ Image converted successfully!
          </div>
          <ul class="list-group mb-3">
            <li class="list-group-item">Original Size: <strong><?php echo $originalSize; ?> MB</strong></li>
            <li class="list-group-item">Final Size: <strong><?php echo $finalSize; ?> KB</strong></li>
          </ul>
          <div class="text-center">
            <h6>Resized Image:</h6>
            <img src="<?php echo $resizedPath; ?>" class="img-fluid rounded shadow" style="max-height: 300px;" alt="Resized Image">
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
  // Show preview before upload
  document.getElementById("imageInput").addEventListener("change", function(event) {
    let file = event.target.files[0];
    if (file) {
      let reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById("previewImage").src = e.target.result;
        document.getElementById("previewArea").classList.remove("d-none");
      };
      reader.readAsDataURL(file);
    }
  });
</script>

</body>
</html>
