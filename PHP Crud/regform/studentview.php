
<?php
/* Attempt MySQL server connection. Assuming you are running MySQL
server with default setting (user 'root' with no password) */

include 'conn.php';
// Check connection
if($conn == false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
 
// Attempt select query execution
$sql = "SELECT * FROM usertb";
if($result = mysqli_query($conn, $sql)){
    if(mysqli_num_rows($result) > 0){
        echo "<table>"; 
            echo "<tr>";
                echo "<th>USERID</th>";
                echo "<th>USERNAME</th>";
                echo "<th>FULL NAME</th>"; 
                echo "<th>PASSWORD</th>";
                echo "<th>GENDER</th>";
                echo "<th>COURSE</th>";
                echo "<th>PROGRAMMING LANGUAGE</th>";
                echo "<th>DATE CREATED</th>";
                echo "<th>Action</th>";
                echo "</tr>";
        while($row = mysqli_fetch_array($result)){
            echo "<tr>";
                $id = $row['userid'];
                echo "<td>" . $row['userid'] . "</td>";
                echo "<td>" . $row['uname'] . "</td>";
                echo "<td>" . $row['fname'] . " " .  $row['lname'] . "</td>";
                echo "<td>" . $row['password'] . "</td>";
                echo "<td>" . $row['gender'] . "</td>";
                echo "<td>" . $row['course'] . "</td>";
                echo "<td>" . $row['proglang'] . "</td>";
                echo "<td>" . $row['date'] . "</td>";
                echo"<td> <a href ='updateview.php?userid=$id'>Edit</a>";
                echo"<td> <a href ='delete.php?userid=$id'>Delete</a>";
            echo "</tr>";
        }
        echo "</table>";
        // Free result set
        mysqli_free_result($result);
    } else{
        echo "No records were found.";
    }
} else{
    echo "ERROR: Could not able to execute $sql. " . mysqli_error($conn);
}
 
// Close connection
mysqli_close($conn);
?>