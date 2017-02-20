<?php
$str_json = file_get_contents('php://input'); //($_POST doesn't work with JSON)
$response = json_decode($str_json, true); // decoding received JSON to array

// connecting to database
$mysqli = new mysqli(getenv("DB_URL"),getenv("DB_USER"), getenv("DB_PASS"), "heroku_9f3983ebc41e915");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
} else {
  // creating a prepared statement, sql injection safeguard
  $stmt = $mysqli->prepare("SELECT idnew_emails FROM new_emails where email_address=(?);");
  // filling statement
  $stmt->bind_param("s",$response["email"]);
  // execute query
  $stmt->execute();
  $stmt->store_result();

  // Get the number of rows
  $num_of_rows = $stmt->num_rows;
  $id = 0;
  if ($num_of_rows) {
    // we have our id
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();
  } else {
    $stmt->close();
    // adding new email_address
    // creating a prepared statement, sql injection safeguard
    $stmt = $mysqli->prepare("INSERT INTO `new_emails` (`email_address`) VALUES (?)");

    // filling statement
    $stmt->bind_param("s",$response["email"]);

    // execute query
    $stmt->execute();
    $id = mysqli_insert_id($mysqli);

    // close statement
    $stmt->close();
  }
  // Bind the result to variables
  $stmt = $mysqli->prepare("INSERT INTO new_table (message, poster_email) VALUES (?,?)");

  // filling statement
  $stmt->bind_param("si",$response["message"],$id);

  // execute query
  $stmt->execute();
  // close statement
  $stmt->close();
}
// closing database connection
mysqli_close($mysqli);

// make new JSON
include "makejson.php";
?>