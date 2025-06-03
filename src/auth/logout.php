<?php
session_start();
session_unset(); 
session_destroy(); 
header("Location: /assignet/public/pages/login.php");
exit();
?>