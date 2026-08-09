<?php

require_once "../includes/db.php";

echo "<h1>Database Connected Successfully!</h1>";

$result = mysqli_query($conn, "SELECT * FROM categories");

echo "<h2>Categories</h2>";

while ($row = mysqli_fetch_assoc($result)) {

    echo $row['category_name'] . "<br>";

}

?>