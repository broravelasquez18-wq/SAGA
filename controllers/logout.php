<?php

session_start();

session_unset();

session_destroy();

/* redirigir al login o home */
header("Location: ../views/home.php");
exit();

?>