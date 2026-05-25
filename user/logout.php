<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_start();
session_destroy();
header("Location: ../Public/index.php");
exit;
?>