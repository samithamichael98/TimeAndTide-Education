<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$title = $content = $posted_date = "";
$title_err = $content_err = $posted_date_err = "";
$success_msg = $error_msg = "";

// Set default posted date for news form
$default_posted_date = date('Y-m-d');

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST['add_story'])){
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

        // Validate image
        $story_image_path = ""; // Use a different variable name to avoid conflict
        if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0){
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
            $filename = $_FILES["image"]["name"];
            $filetype = $_FILES["image"]["type"];
            $filesize = $_FILES["image"]["size"];
        
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
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
                    $story_image_path = "assets/images/" . $new_filename;
                } else {
                    $image_path_err = "Error: There was a problem uploading your file. Please try again.";
                }
            } else if (empty($image_path_err)) { // Only set if no other error
                $image_path_err = "Error: Please select a valid file format."; 
            }
        } else if ($_FILES["image"]["error"] != 4) { // Error 4 means no file was uploaded
            $image_path_err = "Error: " . $_FILES["image"]["error"];
        } else {
            $image_path_err = "Please select an image.";
        }

        // Check input errors before inserting in database
        if(empty($student_name_err) && empty($country_err) && empty($description_err) && empty($image_path_err)){
            
            $sql = "INSERT INTO success_stories (student_name, country, description, image_path) VALUES (?, ?, ?, ?)";
             
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "ssss", $param_student_name, $param_country, $param_description, $param_image_path);
                
                $param_student_name = $student_name;
                $param_country = $country;
                $param_description = $description;
                $param_image_path = $story_image_path;
                
                if(mysqli_stmt_execute($stmt)){
                    $_SESSION['success_msg'] = "Success story added successfully.";
                    header("location: manage-stories.php");
                    exit();
                } else{
                    $error_msg = "Database error (Success Story): " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $error_msg = "Please fix the validation errors for the success story and try again: " . $student_name_err . " " . $country_err . " " . $description_err . " " . $image_path_err;
        }
    } elseif (isset($_POST['add_news'])) {
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

        // Validate and upload images
        $news_image_paths = []; // Use a different variable name
        $upload_errors = [];

        if(isset($_FILES["images"]) && !empty(array_filter($_FILES['images']['name']))){
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
            
            foreach($_FILES['images']['name'] as $key=>$val){
                $filename = $_FILES["images"]["name"][$key];
                $filetype = $_FILES["images"]["type"][$key];
                $filesize = $_FILES["images"]["size"][$key];
                $tmp_name = $_FILES["images"]["tmp_name"][$key];

                $current_image_error = "";

                // Verify file extension
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if(!array_key_exists($ext, $allowed)) {
                    $current_image_error = "Invalid file format for " . $filename . ".";
                }
            
                $maxsize = 5 * 1024 * 1024;
                if($filesize > $maxsize) {
                    $current_image_error = "File " . $filename . " is larger than the allowed limit.";
                }
            
                if(empty($current_image_error)){ // Only proceed if no errors so far for this file
                    $new_filename = uniqid() . "-" . $filename;
                    if(move_uploaded_file($tmp_name, "../assets/images/" . $new_filename)){
                        $news_image_paths[] = "assets/images/" . $new_filename;
                    } else {
                        $current_image_error = "Problem uploading " . $filename . ".";
                    }
                }
                
                if(!empty($current_image_error)){
                    $upload_errors[] = $current_image_error;
                }
            }
        } else {
            $upload_errors[] = "Please select at least one image.";
        }

        if(!empty($upload_errors)){
            $image_path_err = implode("<br>", $upload_errors);
        }

        // Check input errors before inserting in database
        if(empty($title_err) && empty($content_err) && empty($posted_date_err) && empty($image_path_err)){
            
            $sql = "INSERT INTO news (title, content, posted_date, image_paths, image_path) VALUES (?, ?, ?, ?, ?)";
             
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sssss", $param_title, $param_content, $param_posted_date, $param_image_paths, $param_image_path);
                
                $param_title = $title;
                $param_content = $content;
                $param_posted_date = $posted_date;
                $param_image_paths = json_encode($news_image_paths);
                $param_image_path = $news_image_paths[0] ?? ""; // Use the first uploaded image as the main image_path
                
                if(mysqli_stmt_execute($stmt)){
                    $_SESSION['success_msg'] = "News article added successfully.";
                    header("location: manage-news.php");
                    exit();
                } else{
                    $error_msg = "Database error (News Article): " . mysqli_error($link);
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $error_msg = "Please fix the validation errors for the news article and try again: " . $title_err . " " . $content_err . " " . $posted_date_err . " " . $image_path_err;
        }
    }
}

if(isset($_SESSION['success_msg'])){
    $success_msg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .welcome-message {
            text-align: center;
            font-size: 2.5rem;
            color: #007bff; /* A blue color from your admin-style.css */
            margin-top: 50px;
            margin-bottom: 30px;
        }
        .dashboard-options {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 50px;
        }
        .dashboard-options .btn {
            padding: 20px 40px;
            font-size: 1.2rem;
            text-decoration: none;
            color: white;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }
        .dashboard-options .btn-primary {
            background-color: #007bff;
        }
        .dashboard-options .btn-primary:hover {
            background-color: #0056b3;
        }
        .dashboard-options .btn-info {
            background-color: #17a2b8;
        }
        .dashboard-options .btn-info:hover {
            background-color: #117a8b;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <a href="logout.php" class="btn">Logout</a>
        </div>

        <?php 
        if(!empty($success_msg)){
            echo '<div class="alert alert-success" style="padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px;">' . $success_msg . '</div>';
        }        
        ?>

        <h1 class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h1>

        <div class="dashboard-options">
            <a href="manage-stories.php" class="btn btn-primary">Manage Success Stories</a>
            <a href="manage-news.php" class="btn btn-info">Manage News Articles</a>
        </div>
    </div>
</body>
</html>