<?php
/**
 * Test file untuk melihat raw response
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTING CHECK IN ===<br><br>";

// Test 1: Connection
echo "1. Testing connection...<br>";
require_once '../../db.php';

if (isset($conn)) {
    echo "✓ Connection OK<br><br>";
} else {
    echo "✗ Connection FAILED<br><br>";
    exit;
}

// Test 2: Employee table
echo "2. Testing employee table...<br>";
$result = $conn->query("SELECT * FROM employee LIMIT 1");
if ($result && $result->num_rows > 0) {
    $emp = $result->fetch_assoc();
    echo "✓ Employee table OK<br>";
    echo "Sample employee: " . $emp['name'] . " (ID: " . $emp['id_employee'] . ")<br><br>";
} else {
    echo "✗ Employee table FAILED or EMPTY<br><br>";
}

// Test 3: Attendance table structure
echo "3. Testing attendance table...<br>";
$result = $conn->query("DESCRIBE attendance");
if ($result) {
    echo "✓ Attendance table exists<br>";
    echo "Columns: ";
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    echo implode(', ', $columns) . "<br><br>";
} else {
    echo "✗ Attendance table FAILED<br>";
    echo "Error: " . $conn->error . "<br><br>";
}

// Test 4: Insert test
echo "4. Testing insert...<br>";
$test_emp_id = $emp['id_employee'];
$test_time = date('Y-m-d H:i:s');
$test_role = 'cleaning';
$test_notes = 'test';

$stmt = $conn->prepare("INSERT INTO attendance (id_employee, check_in, role_today, notes_check_in) VALUES (?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("ssss", $test_emp_id, $test_time, $test_role, $test_notes);
    if ($stmt->execute()) {
        echo "✓ Insert OK (ID: " . $stmt->insert_id . ")<br>";

        // Delete test data
        $conn->query("DELETE FROM attendance WHERE id_attendance = " . $stmt->insert_id);
        echo "✓ Test data deleted<br><br>";
    } else {
        echo "✗ Insert FAILED: " . $stmt->error . "<br><br>";
    }
} else {
    echo "✗ Prepare FAILED: " . $conn->error . "<br><br>";
}

// Test 5: JSON output
echo "5. Testing JSON output...<br>";
$test_json = [
    'success' => true,
    'message' => 'Test OK',
    'data' => [
        'test' => 'value'
    ]
];
echo "JSON: " . json_encode($test_json) . "<br><br>";

echo "=== ALL TESTS COMPLETED ===";

$conn->close();