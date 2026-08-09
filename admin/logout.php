<?php

require_once "../includes/session.php";


// Remove all session data
$_SESSION = [];


// Destroy the session
session_destroy();


// Return to login
header("Location: login.php");
exit();

?>