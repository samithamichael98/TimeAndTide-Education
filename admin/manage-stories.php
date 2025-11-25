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

// Define variables and initialize with empty values for Success Stories
$student_name = $country = $description = "";
$student_name_err = $country_err = $description_err = $image_path_err = "";
$success_msg = $error_msg = "";

// Processing form data when form is submitted for adding a story
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_story'])){
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
    $story_image_path = ""; 
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
                $story_image_path = "assets/images/" . $new_filename;
            } else {
                $image_path_err = "Error: There was a problem uploading your file. Please try again.";
            }
        } else if (empty($image_path_err)) {
            $image_path_err = "Error: Please select a valid file format."; 
        }
    } else if ($_FILES["image"]["error"] != 4) {
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
                header("location: manage-stories.php"); // Redirect to this page
                exit();
            } else{
                $error_msg = "Database error (Success Story): " . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error_msg = "Please fix the validation errors for the success story and try again: " . $student_name_err . " " . $country_err . " " . $description_err . " " . $image_path_err;
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
    <title>Manage Success Stories</title>
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>
    <div class="admin-wrapper">
        <div class="admin-header">
            <h1>Manage Success Stories</h1>
            <div>
                <a href="dashboard.php" class="btn">Dashboard</a>
                <a href="logout.php" class="btn">Logout</a>
            </div>
        </div>

        <?php 
        if(!empty($success_msg)){
            echo '<div class="alert alert-success" style="padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px;">' . $success_msg . '</div>';
        }        
        if(!empty($error_msg)){
            echo '<div class="alert alert-danger" style="padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px;">' . $error_msg . '</div>';
        }        
        ?>

        <div class="form-container">
            <h2>Add New Success Story</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="add_story" value="1">
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
                </div>
            </form>
        </div>

        <hr>

        <h2>Existing Success Stories</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Student Name</th>
                    <th>Country</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT * FROM success_stories";
                if($result = mysqli_query($link, $sql)){
                    if(mysqli_num_rows($result) > 0){
                        while($row = mysqli_fetch_array($result)){
                            echo "<tr>";
                                echo "<td>" . $row['id'] . "</td>";
                                echo "<td>" . $row['student_name'] . "</td>";
                                echo "<td>" . $row['country'] . "</td>";
                                echo "<td>" . substr($row['description'], 0, 100) . "...</td>";
                                echo "<td><img src='../" . $row['image_path'] . "' width='100'></td>";
                                echo "<td>";
                                    echo "<a href='edit-story.php?id=". $row['id'] ."' class='btn btn-primary'>Update</a>";
                                    echo "<a href='delete-story.php?id=". $row['id'] ."' class='btn btn-danger'>Delete</a>";
                                echo "</td>";
                            echo "</tr>";
                        }
                        mysqli_free_result($result);
                    } else{
                        echo "<tr><td colspan='6'>No stories found.</td></tr>";
                    }
                } else{
                    echo "<tr><td colspan='6'>ERROR: Could not able to execute $sql. " . mysqli_error($link) . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>