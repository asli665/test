<?php 


$conn = mysqli_connect("localhost","root","","dburs");

if($conn==false){
	die("Error: " . mysqli_connect_error());
}
?>