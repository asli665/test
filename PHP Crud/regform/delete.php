<?php 

$id = $_GET["userid"];

$conn = mysqli_connect("localhost","root","","dburs");
$sql = "DELETE from  usertb WHERE userid='$id'";

if($conn==false){
	die("Error: " . mysqli_connect_error());
}

if(mysqli_query($conn, $sql)){

    header("Location: studentview.php");
} else{
    echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
}
 
// Close connection
mysqli_close($conn);

?>





