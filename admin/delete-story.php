<?php
session_start();
require_once "db_connect.php";

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $id = trim($_GET["id"]);
    $sql = "DELETE FROM success_stories WHERE id = ?";
    
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;
        
        if(mysqli_stmt_execute($stmt)){
            $_SESSION['success_msg'] = "Success story deleted successfully.";
            header("location: dashboard.php");
            exit();
        } else{
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
     
    mysqli_stmt_close($stmt);
    mysqli_close($link);
} else{
    // URL doesn't contain id parameter. Redirect to error page
    header("location: dashboard.php");
    exit();
}
?>