<?php 

include 'conn.php';

$fname = $_GET["fname"];
$lname = $_GET["lname"];
$username = $_GET["uname"];
$password = $_GET["pw"];
$course = $_GET["course"]; 
$gender = $_GET["gender"];
$id = $_GET["userid"];
$pl =$_GET["lang"];
$chk = implode("," , $pl);//to display all values from array



$sql = "UPDATE usertb SET fname='$fname', lname='$lname', uname='$username', password='$password' , course='$course', gender='$gender', proglang='$chk' WHERE userid='$id'";


if(mysqli_query($conn, $sql)){
	
    header("Location: studentview.php");
} else{
    echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
}
 
// Close connection
mysqli_close($conn);

?>





