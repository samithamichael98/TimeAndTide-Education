<?php
session_start();
require_once "db_connect.php";

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Define variables and initialize with empty values
$student_name = $country = $description = "";
$student_name_err = $country_err = $description_err = $image_path_err = "";
$success_msg = $error_msg = "";
$current_image_path = ""; // To store the path of the image currently in DB

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && !empty($_POST['id'])){
    $id = $_POST['id'];
    
    // Validate student name
    if(empty(trim($_POST["student_name"]))){
        $student_name_err = "Please enter a student name.";
    } else{
        $student_name = trim($_POST["student_name"]);
    }

    // Validate country
    if(empty(trim($_POST["country"]))){
        $country_err = "Please enter a country.";
    } else{
        $country = trim($_POST["country"]);
    }

    // Validate description
    if(empty(trim($_POST["description"]))){
        $description_err = "Please enter a description.";
    } else{
        $description = trim($_POST["description"]);
    }

    // Fetch current image path from DB to handle deletion or replacement
    $sql_fetch_current_image = "SELECT image_path FROM success_stories WHERE id = ?";
    if($stmt_fetch = mysqli_prepare($link, $sql_fetch_current_image)){
        mysqli_stmt_bind_param($stmt_fetch, "i", $id);
        mysqli_stmt_execute($stmt_fetch);
        $result_fetch = mysqli_stmt_get_result($stmt_fetch);
        if(mysqli_num_rows($result_fetch) == 1){
            $row_fetch = mysqli_fetch_array($result_fetch, MYSQLI_ASSOC);
            $current_image_path = $row_fetch['image_path'];
        }
        mysqli_stmt_close($stmt_fetch);
    }

    $new_image_uploaded_path = "";
    // Check if current image is marked for deletion
    $image_deleted = isset($_POST['delete_current_image']) && $_POST['delete_current_image'] === '1';

    // Validate and upload new image if provided
    if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0){
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES["image"]["name"];
        $filetype = $_FILES["image"]["type"];
        $filesize = $_FILES["image"]["size"];
    
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) {
            $image_path_err = "Error: Please select a valid file format.";
        }
    
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) {
            $image_path_err = "Error: File size is larger than the allowed limit.";
        }
    
        if(empty($image_path_err) && in_array($filetype, $allowed)){
            $new_filename = uniqid() . "-" . $filename;
            if(move_uploaded_file($_FILES["image"]["tmp_name"], "../assets/images/" . $new_filename)){
                $new_image_uploaded_path = "assets/images/" . $new_filename;
                // If a new image is uploaded, and there was an old one, delete the old one
                if(!empty($current_image_path) && file_exists("../" . $current_image_path)){
                    unlink("../" . $current_image_path);
                }
            } else {
                $image_path_err = "Error: There was a problem uploading your file. Please try again.";
            }
        } else if (empty($image_path_err)) {
            $image_path_err = "Error: Please select a valid file format."; 
        }
    } else if ($image_deleted && empty($new_image_uploaded_path)) {
        // If old image was deleted and no new image uploaded, clear the path
        $new_image_uploaded_path = "";
        if(!empty($current_image_path) && file_exists("../" . $current_image_path)){
            unlink("../" . $current_image_path);
        }
    } else {
        // No new image uploaded, and old one not deleted, keep old path
        $new_image_uploaded_path = $current_image_path;
    }


    // Check input errors before updating in database
    if(empty($student_name_err) && empty($country_err) && empty($description_err) && empty($image_path_err)){
        
        $sql = "UPDATE success_stories SET student_name = ?, country = ?, description = ?, image_path = ? WHERE id = ?";
         
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssssi", $param_student_name, $param_country, $param_description, $param_image_path, $param_id);
            
            $param_student_name = $student_name;
            $param_country = $country;
            $param_description = $description;
            $param_image_path = $new_image_uploaded_path;
            $param_id = $id;
            
            if(mysqli_stmt_execute($stmt)){
                $_SESSION['success_msg'] = "Success story updated successfully.";
                header("location: manage-stories.php"); // Redirect to manage-stories
                exit();
            } else{
                $error_msg = "Database error: " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_msg = "Please fix the validation errors and try again.";
        if (!empty($student_name_err)) $error_msg .= "<br>" . $student_name_err;
        if (!empty($country_err)) $error_msg .= "<br>" . $country_err;
        if (!empty($description_err)) $error_msg .= "<br>" . $description_err;
        if (!empty($image_path_err)) $error_msg .= "<br>" . $image_path_err;
    }
} else {
    if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
        $id =  trim($_GET["id"]);
        $sql = "SELECT * FROM success_stories WHERE id = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $id;
            if(mysqli_stmt_execute($stmt)){
                $result = mysqli_stmt_get_result($stmt);
                if(mysqli_num_rows($result) == 1){
                    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $student_name = $row["student_name"];
                    $country = $row["country"];
                    $description = $row["description"];
                    $current_image_path = $row["image_path"];
                } else{
                    $error_msg = "No records found.";
                }
            } else{
                $error_msg = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        header("location: manage-stories.php"); // Redirect to manage-stories
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Success Story</title>
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-preview {
            position: relative;
            width: 150px;
            height: 150px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
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
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="admin-header">
            <h1>Edit Success Story</h1>
            <a href="manage-stories.php" class="btn">Back to Stories</a>
        </div>

        <div class="form-container">
            <?php 
            if(!empty($error_msg)){
                echo '<div class="alert alert-danger" style="padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px;">' . $error_msg . '</div>';
            }        
            ?>

            <form action="<?php echo htmlspecialchars(basename($_SERVER['REQUEST_URI'])); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $id; ?>"/>
                <div class="form-group <?php echo (!empty($student_name_err)) ? 'has-error' : ''; ?>">
                    <label>Student Name</label>
                    <input type="text" name="student_name" class="form-control" value="<?php echo $student_name; ?>">
                    <span class="help-block"><?php echo $student_name_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($country_err)) ? 'has-error' : ''; ?>">
                    <label>Country</label>
                    <input type="text" name="country" class="form-control" value="<?php echo $country; ?>">
                    <span class="help-block"><?php echo $country_err; ?></span>
                </div>
                <div class="form-group <?php echo (!empty($description_err)) ? 'has-error' : ''; ?>">
                    <label>Description</label>
                    <textarea name="description" class="form-control"><?php echo $description; ?></textarea>
                    <span class="help-block"><?php echo $description_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Current Image</label>
                    <div class="image-preview-container" id="current-image-preview-container">
                        <?php if(!empty($current_image_path)): ?>
                            <div class="image-preview" data-image-path="<?php echo htmlspecialchars($current_image_path); ?>">
                                <img src="../<?php echo htmlspecialchars($current_image_path); ?>" alt="Current Image">
                                <button type="button" class="remove-image-btn">&times;</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group <?php echo (!empty($image_path_err)) ? 'has-error' : ''; ?>">
                    <label>New Image (optional)</label>
                    <input type="file" id="new_image" name="image" class="form-control">
                    <div class="image-preview-container" id="new-image-preview-container"></div>
                    <span class="help-block"><?php echo $image_path_err; ?></span>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-primary" value="Update Story">
                    <a href="manage-stories.php" class="btn btn-default">Cancel</a>
                </div>
                <input type="hidden" name="delete_current_image" id="delete_current_image" value="0">
            </form>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const currentImageContainer = document.getElementById('current-image-preview-container');
    const newImageInput = document.getElementById('new_image');
    const newImagePreviewContainer = document.getElementById('new-image-preview-container');
    const deleteCurrentImageHiddenInput = document.getElementById('delete_current_image');

    // Handle removing current image
    if (currentImageContainer) {
        currentImageContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('remove-image-btn')) {
                const previewWrapper = event.target.closest('.image-preview');
                if (previewWrapper) {
                    previewWrapper.remove();
                    deleteCurrentImageHiddenInput.value = '1'; // Mark for deletion
                }
            }
        });
    }

    // Handle new image preview
    newImageInput.addEventListener('change', function () {
        newImagePreviewContainer.innerHTML = ''; // Clear previous new image preview
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const reader = new FileReader();
            reader.onload = function (e) {
                const previewWrapper = document.createElement('div');
                previewWrapper.className = 'image-preview';

                const img = document.createElement('img');
                img.src = e.target.result;

                const removeBtn = document.createElement('button');
                removeBtn.className = 'remove-image-btn';
                removeBtn.innerHTML = '&times;';
                removeBtn.addEventListener('click', function () {
                    newImageInput.value = ''; // Clear the file input
                    newImagePreviewContainer.innerHTML = ''; // Remove the preview
                });

                previewWrapper.appendChild(img);
                previewWrapper.appendChild(removeBtn);
                newImagePreviewContainer.appendChild(previewWrapper);
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>
</body>
</html>