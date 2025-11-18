<?php
include('../db.php');
$id = $_GET['id'];
$result = $conn->query("SELECT * FROM employees WHERE id_employee=$id");
echo json_encode($result->fetch_assoc());
?>
