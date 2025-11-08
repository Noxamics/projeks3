<?php
include('../db.php');
$result = $conn->query("SELECT id_status, status_name FROM statuses ORDER BY id_status ASC");

$statuses = [];
while ($row = $result->fetch_assoc()) {
    $statuses[] = $row;
}
echo json_encode($statuses);
?>