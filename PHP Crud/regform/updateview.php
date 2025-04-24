
<html>

<?php

include 'conn.php';
 
// Attempt select query execution
$id = $_REQUEST["userid"];
$sql = "SELECT * FROM usertb WHERE userid=$id";
if($result = mysqli_query($conn, $sql)){
    $row = mysqli_fetch_array($result);
            $username = $row["uname"];
            $password= $row["password"];
            $fname = $row["fname"];
            $lname = $row["lname"];
            $gender = $row["gender"];
            $course = $row["course"];
            
    
} else{
    echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
}
 

// Close connection
mysqli_close($conn);
?>  


<form action="updaterecord.php" method="GET">

Information <br>

<input type="text" name="userid" value="<?php echo $id ?>"><br>
First Name:
<input type="text" name="fname" value="<?php echo $fname ?> "><br>
Last Name:
<input type="text" name="lname" value="<?php echo $lname ?> "><br>
Username:
<input type="text" name="uname" value="<?php echo $username ?> "><br>
Password:
<input type="password" name="pw" value="<?php echo $password ?> "><br>
Gender:<br>
<input type="radio" name="gender" value="Male" <?php if($row['gender']=="Male"){ echo "checked";}?>/>Male
<input type="radio" name="gender" value="Female" <?php if($row['gender']=="Female"){ echo "checked";}?>/>Female <br>

Course:
<select name="course">
	<option value=0>Please Select</option>
	<option value="BSIS">BSIS</option>
	<option value="BSIT">BSIT</option>
</select><br>	


Programming Language:<br>
<?php 
	//get selected value from database
	$checked_arr = explode(",",$row['proglang']);
    $languages_arr = array("C#","JAVA","Python");
    foreach($languages_arr as $language){
      $checked = "";
      if(in_array($language,$checked_arr)){
        $checked = "checked";
      }
      echo '<input type="checkbox" name="lang[]" value="'.$language.'" '.$checked.' > '.$language.' <br/>';
    }
  ?>  

<input type="reset" value="Clear">
<input type="submit" value="Update">

</form>



</html>