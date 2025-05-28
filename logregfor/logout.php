<?php
session_start();
session_unset();
session_destroy();
header("Location: ../logregfor/login.php");
exit;
