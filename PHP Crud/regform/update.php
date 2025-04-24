
<html>

<?php

/* Attempt MySQL server connection. Assuming you are running MySQL
server with default setting (user 'root' with no password) */
$link = mysqli_connect("localhost", "root", "", "dbplp");
 
// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
 
// Attempt select query execution
$id = $_REQUEST["userid"];
$sql = "SELECT * FROM usertb WHERE userid=$id";
if($result = mysqli_query($link, $sql)){
    $row = mysqli_fetch_array($result);
            $username = $row["username"];
            $password= $row["password"];
            $fullname = $row["fullname"];
           
    
} else{
    echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
}
 

// Close connection
mysqli_close($link);
?>  


<form action="updaterecord.php" method="POST">

Sign-up <br>
Userid:
<input type="text" name="id" value="<?php echo $id ?> "><br>
Username
<input type="text" name="uname" value="<?php echo $username ?> "><br>
Full Name:
<input type="text" name="fullname" value="<?php echo $fullname ?> "><br>
Password:
<input type="Password" name="pass" value="<?php echo $password ?> "><br>
<input type="submit" name="sbt" value="Update">
<input type="reset" value="Clear">

</form>



</html>