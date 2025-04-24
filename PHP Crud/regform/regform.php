<html>
Registration Form<br>
<form action="info.php" method="GET">
First Name:
<input type="text" name="fname" required><br>
Last Name:
<input type="text" name="lname" required><br>
Username:
<input type="text" name="uname" required><br>
Password:
<input type="password" name="pw" required><br>
Gender:<br>
<input type="radio" name="gender" value="Male">Male
<input type="radio" name="gender" value="Female">Female<br>
Course:
<select name="course">
	<option value="BSIS">BSIS</option>
	<option value="BSIT">BSIT</option>
</select><br>	
Programming Language:<br>
<input type="checkbox" name='lang[]' value="C#">C#
<input type="checkbox" name='lang[]' value="JAVA">JAVA
<input type="checkbox" name='lang[]' value="Python">Python<br>
<input type="submit" value="Register">
<input type="reset" value="Clear">

</form>





</html>
