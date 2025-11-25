<?php
session_start();
require_once "db_connect.php";

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Define variables and initialize with empty values
$title = $content = $posted_date = "";
$title_err = $content_err = $posted_date_err = "";
$success_msg = $error_msg = "";
$image_paths = []; // This will hold all image paths (existing + new)

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && !empty($_POST['id'])){
    $id = $_POST['id'];
    
    // Validate title
    if(empty(trim($_POST["title"]))){
        $title_err = "Please enter a title.";
    } else{
        $title = trim($_POST["title"]);
    }

    // Validate content
    if(empty(trim($_POST["content"]))){
        $content_err = "Please enter content.";
    } else{
        $content = trim($_POST["content"]);
    }

    // Validate posted date
    if(empty(trim($_POST["posted_date"]))){
        $posted_date_err = "Please enter a date.";
    } else{
        $posted_date = trim($_POST["posted_date"]);
    }

    // Get existing images from DB
    $sql = "SELECT image_paths FROM news WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $id);
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($result) == 1){
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $image_paths = json_decode($row["image_paths"], true) ?: [];
            }
        }
        mysqli_stmt_close($stmt);
    }

    // Process image deletions (from hidden input)
    if(isset($_POST['deleted_images']) && is_array($_POST['deleted_images'])){
        foreach($_POST['deleted_images'] as $image_to_delete){
            if(($key = array_search($image_to_delete, $image_paths)) !== false){
                unset($image_paths[$key]);
                // Delete the file from the server
                if(file_exists("../" . $image_to_delete)){
                    unlink("../" . $image_to_delete);
                }
            }
        }
    }

    // Validate and upload new images if provided
    $new_upload_errors = [];
    if(isset($_FILES["new_images"]) && !empty(array_filter($_FILES['new_images']['name']))){
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        
        foreach($_FILES['new_images']['name'] as $key=>$val){
            $filename = $_FILES["new_images"]["name"][$key];
            $filetype = $_FILES["new_images"]["type"][$key];
            $filesize = $_FILES["new_images"]["size"][$key];
            $tmp_name = $_FILES["new_images"]["tmp_name"][$key];

            $current_image_error = "";

            // Verify file extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if(!array_key_exists($ext, $allowed)) {
                $current_image_error = "Invalid file format for " . $filename . ".";
            }
        
            // Verify file size - 5MB maximum
            $maxsize = 5 * 1024 * 1024;
            if($filesize > $maxsize) {
                $current_image_error = "File " . $filename . " is larger than the allowed limit.";
            }
            
            if(empty($current_image_error)){
                $new_filename = uniqid() . "-" . $filename; // Generate unique filename
                if(move_uploaded_file($tmp_name, "../assets/images/" . $new_filename)){
                    $image_paths[] = "assets/images/" . $new_filename;
                } else {
                    $current_image_error = "Problem uploading " . $filename . ".";
                }
            }
            
            if(!empty($current_image_error)){
                $new_upload_errors[] = $current_image_error;
            }
        }
    }

    if(!empty($new_upload_errors)){
        $image_path_err = implode("<br>", $new_upload_errors);
    }


    // Check input errors before updating in database
    if(empty($title_err) && empty($content_err) && empty($posted_date_err) && empty($image_path_err)){
        
        $sql = "UPDATE news SET title = ?, content = ?, posted_date = ?, image_paths = ? WHERE id = ?";
         
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssi", $param_title, $param_content, $param_posted_date, $param_image_paths, $param_id);
            
            $param_title = $title;
            $param_content = $content;
            $param_posted_date = $posted_date;
            $param_image_paths = json_encode(array_values($image_paths)); // Re-index array after deletions
            $param_id = $id;
            
            if(mysqli_stmt_execute($stmt)){
                $_SESSION['success_msg'] = "News article updated successfully.";
                header("location: dashboard.php");
                exit();
            } else{
                $error_msg = "Database error: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_msg = "Please fix the validation errors and try again.";
        if (!empty($title_err)) $error_msg .= "<br>" . $title_err;
        if (!empty($content_err)) $error_msg .= "<br>" . $content_err;
        if (!empty($posted_date_err)) $error_msg .= "<br>" . $posted_date_err;
        if (!empty($image_path_err)) $error_msg .= "<br>" . $image_path_err;
    }
} else {
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        $id =  trim($_GET["id"]);
        $sql = "SELECT * FROM news WHERE id = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $id;
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $title = $row["title"];
                    $content = $row["content"];
                    $posted_date = $row["posted_date"];
                    $image_paths = json_decode($row["image_paths"], true) ?: [];
                } else{
                    $error_msg = "No records found.";
                }
            } else{
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        header("location: dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit News Article</title>
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .image-preview-container { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
        .image-preview { position: relative; width: 150px; height: 150px; border: 1px solid #ddd; border-radius: 5px; overflow: hidden;}
        .image-preview img { width: 100%; height: 100%; object-fit: cover; }
        .remove-image-btn {
            position: absolute;
            top: 0px;
            right: 0px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 0 0 0 5px;
            cursor: pointer;
            width: 25px;
            height: 25px;
            font-size: 16px;
            line-height: 25px;
            text-align: center;
            padding: 0;
        }
        .new-image-preview {
            position: relative;
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .new-image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .remove-new-image-btn {
            position: absolute;
            top: 0px;
            right: 0px;
            background: rgba(255, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 0 0 0 5px;
            cursor: pointer;
            width: 25px;
            height: 25px;
            font-size: 16px;
            line-height: 25px;
            text-align: center;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="admin-header">
            <h1>Edit News Article</h1>
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>

        <div class="form-container">
            <?php 
            if(!empty($error_msg)){
                echo '<div class="alert alert-danger" style="padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px;">' . $error_msg . '</div>';
            }        
            ?>

            <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $id; ?>"/>
                <div class="form-group <?php echo (!empty($title_err)) ? 'has-error' : ''; ?>">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo $title; ?>">
                    <span class="help-block"><?php echo $title_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($content_err)) ? 'has-error' : ''; ?>">
                    <label>Content</label>
                    <textarea name="content" class="form-control"><?php echo $content; ?></textarea>
                    <span class="help-block"><?php echo $content_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($posted_date_err)) ? 'has-error' : ''; ?>">
                    <label>Posted Date</label>
                    <input type="date" name="posted_date" class="form-control" value="<?php echo $posted_date; ?>">
                    <span class="help-block"><?php echo $posted_date_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Current Images</label>
                    <div class="image-preview-container" id="current-image-preview-container">
                        <?php
                        if(is_array($image_paths)){
                            foreach($image_paths as $image){
                                echo "<div class='image-preview' data-image-path='" . htmlspecialchars($image) . "'>";
                                echo "<img src='../" . htmlspecialchars($image) . "'>";
                                echo "<button type='button' class='remove-image-btn'>&times;</button>";
                                echo "</div>";
                            }
                        }
                        ?>
                    </div>
                </div>
                <div class="form-group <?php echo (!empty($image_path_err)) ? 'has-error' : ''; ?>">
                    <label>Add New Images (optional)</label>
                    <input type="file" id="new_images" name="new_images[]" class="form-control" multiple>
                    <div class="image-preview-container" id="new-image-preview-container"></div>
                    <span class="help-block"><?php echo $image_path_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Update News">
                    <a href="dashboard.php" class="btn btn-default">Cancel</a>
                </div>
                <div id="deleted-images-hidden-inputs"></div>
            </form>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const newImageInput = document.getElementById('new_images');
    const newPreviewContainer = document.getElementById('new-image-preview-container');
    const currentImageContainer = document.getElementById('current-image-preview-container');
    const deletedImagesHiddenInputs = document.getElementById('deleted-images-hidden-inputs');

    let newFilesDataTransfer = new DataTransfer();

    // Handle removing current images
    currentImageContainer.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-image-btn')) {
            const previewWrapper = event.target.closest('.image-preview');
            const imagePath = previewWrapper.dataset.imagePath;

            // Add a hidden input to mark this image for deletion on server-side
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'deleted_images[]';
            hiddenInput.value = imagePath;
            deletedImagesHiddenInputs.appendChild(hiddenInput);

            previewWrapper.remove();
        }
    });

    // Handle adding new images
    newImageInput.addEventListener('change', function () {
        for (const file of this.files) {
            newFilesDataTransfer.items.add(file);
            
            const reader = new FileReader();
            reader.onload = function (e) {
                const previewWrapper = document.createElement('div');
                previewWrapper.className = 'new-image-preview'; // Use a different class for new previews

                const img = document.createElement('img');
                img.src = e.target.result;

                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-new-image-btn';
                removeBtn.innerHTML = '&times;';
                removeBtn.addEventListener('click', function () {
                    // Find and remove the file from the DataTransfer object
                    for (let i = 0; i < newFilesDataTransfer.items.length; i++) {
                        if (newFilesDataTransfer.items[i].getAsFile().name === file.name) {
                            newFilesDataTransfer.items.remove(i);
                            break;
                        }
                    }
                    // Update the file input's files
                    newImageInput.files = newFilesDataTransfer.files;
                    // Remove the preview
                    previewWrapper.remove();
                });

                previewWrapper.appendChild(img);
                previewWrapper.appendChild(removeBtn);
                newPreviewContainer.appendChild(previewWrapper);
            };
            reader.readAsDataURL(file);
        }
        // Update the file input's files with the combined list
        newImageInput.files = newFilesDataTransfer.files;
    });
});
</script>
</body>
</html>