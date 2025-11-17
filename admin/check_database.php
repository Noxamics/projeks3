<?php
include('../db.php');

echo "<h2>Database Structure Check</h2>";

// Cek struktur tabel drops
echo "<h3>Tabel: drops</h3>";
$result = $conn->query("DESCRIBE drops");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Cek struktur tabel customers
echo "<h3>Tabel: customers</h3>";
$result = $conn->query("DESCRIBE customers");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Cek struktur tabel employees
echo "<h3>Tabel: employees</h3>";
$result = $conn->query("DESCRIBE employees");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Cek trigger yang ada
echo "<h3>Triggers</h3>";
$result = $conn->query("SHOW TRIGGERS");
if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Trigger</th><th>Event</th><th>Table</th><th>Timing</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Trigger']}</td>";
        echo "<td>{$row['Event']}</td>";
        echo "<td>{$row['Table']}</td>";
        echo "<td>{$row['Timing']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Tidak ada trigger</p>";
}
?>