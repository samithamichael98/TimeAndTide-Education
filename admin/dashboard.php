<?php
session_start();
require_once "db_connect.php";

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

// Define variables and initialize with empty values
$student_name = $country = $description = $image_path = "";
$student_name_err = $country_err = $description_err = $image_path_err = "";
$success_msg = $error_msg = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
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
    if(isset($_FILES["image"]) && $_FILES["image"]["error"] == 0){
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES["image"]["name"];
        $filetype = $_FILES["image"]["type"];
        $filesize = $_FILES["image"]["size"];
    
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed)) {
            $image_path_err = "Error: Please select a valid file format.";
        }
    
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if($filesize > $maxsize) {
            $image_path_err = "Error: File size is larger than the allowed limit.";
        }
    
        // Verify MYME type of the file
        if(in_array($filetype, $allowed)){
            // Check if file already exists
            if(file_exists("../assets/images/" . $filename)){
                $image_path_err = $filename . " is already exists.";
            } else{
                if(move_uploaded_file($_FILES["image"]["tmp_name"], "../assets/images/" . $filename)){
                    $image_path = "assets/images/" . $filename;
                } else {
                    $image_path_err = "Error: There was a problem uploading your file. Please try again.";
                }
            } 
        } else{
            $image_path_err = "Error: Please select a valid file format."; 
        }
    } else{
        $image_path_err = "Error: " . $_FILES["image"]["error"];
    }

    // Check input errors before inserting in database
    if(empty($student_name_err) && empty($country_err) && empty($description_err) && empty($image_path_err)){
        
        // Prepare an insert statement
        $sql = "INSERT INTO success_stories (student_name, country, description, image_path) VALUES (?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($link, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssss", $param_student_name, $param_country, $param_description, $param_image_path);
            
            // Set parameters
            $param_student_name = $student_name;
            $param_country = $country;
            $param_description = $description;
            $param_image_path = $image_path;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                $_SESSION['success_msg'] = "Success story added successfully.";
                header("location: dashboard.php");
                exit();
            } else{
                $error_msg = "Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_msg = "Please fix the errors and try again.";
    }
    
    // Close connection
    mysqli_close($link);
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper" style="width: 500px; padding: 20px; margin: auto; margin-top: 50px;">
        <h2>Add Success Story</h2>

        <?php 
        if(!empty($success_msg)){
            echo '<div class="alert alert-success">' . $success_msg . '</div>';
        }        
        if(!empty($error_msg)){
            echo '<div class="alert alert-danger">' . $error_msg . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
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
            <div class="form-group <?php echo (!empty($image_path_err)) ? 'has-error' : ''; ?>">
                <label>Image</label>
                <input type="file" name="image" class="form-control">
                <span class="help-block"><?php echo $image_path_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Add Story">
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </form>
    </div>
</body>
</html>