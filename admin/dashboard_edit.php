<?php
include('../db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_drop = intval($_POST['id_drop'] ?? 0);
    $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name'] ?? '');
    $phone_number = mysqli_real_escape_string($conn, $_POST['phone_number'] ?? '');
    $brand = mysqli_real_escape_string($conn, $_POST['brand'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 0);
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $trans_date = mysqli_real_escape_string($conn, $_POST['tanggal_masuk'] ?? '');
    $est_finish_date = mysqli_real_escape_string($conn, $_POST['tanggal_selesai'] ?? '');
    $status_id = intval($_POST['status_id'] ?? 1);
    $payment_status = mysqli_real_escape_string($conn, $_POST['payment_status'] ?? 'Belum Lunas');
    $payment_method = mysqli_real_escape_string($conn, trim($_POST['payment_method'] ?? 'Tunai'));
    $payment_date = !empty($_POST['payment_date']) ? mysqli_real_escape_string($conn, $_POST['payment_date']) : null;
    $amount_paid = floatval($_POST['amount_paid'] ?? 0);

    // Tambahkan di awal file untuk debug:
    error_log("=== DASHBOARD EDIT DEBUG ===");
    error_log("POST Data: " . print_r($_POST, true));
    error_log("ID Drop: $id_drop");
    error_log("Service ID: $service_id");
    error_log("Employee ID: $employee_id");

    // Cek koneksi database
    if (!$conn) {
        error_log("Database connection failed: " . mysqli_connect_error());
        header("Location: dashboard.php?error=Koneksi database gagal");
        exit();
    }

    if ($payment_status === 'Lunas' && empty($payment_date)) {
        $payment_date = date('Y-m-d');
    }

    mysqli_begin_transaction($conn);

    try {
        // Validasi id_drop
        if ($id_drop <= 0) {
            throw new Exception("ID Drop tidak valid");
        }

        // Ambil customer_id dari drops
        $check_drop = "SELECT customer_id FROM drops WHERE id_drop = $id_drop";
        error_log("Query check drop: $check_drop");
        
        $result = mysqli_query($conn, $check_drop);
        
        if (!$result) {
            throw new Exception("Error query: " . mysqli_error($conn));
        }
        
        if (mysqli_num_rows($result) === 0) {
            throw new Exception("Data drop dengan ID $id_drop tidak ditemukan");
        }
        
        $drop_data = mysqli_fetch_assoc($result);
        $customer_id = $drop_data['customer_id'];
        
        error_log("Customer ID found: $customer_id");

        // Update customer
        $update_customer = "UPDATE customers SET name = '$customer_name', phone = '$phone_number' WHERE id_customer = $customer_id";
        if (!mysqli_query($conn, $update_customer)) {
            throw new Exception("Gagal update customer: " . mysqli_error($conn));
        }

        // Update drops
        $update_drop = "UPDATE drops SET 
        trans_date = '$trans_date',
        est_finish_date = '$est_finish_date',
        status_id = '$status_id',
        employee_id = '$employee_id'
        WHERE id_drop = '$id_drop'";
        
        error_log("Update drop query: $update_drop");
        
        if (!mysqli_query($conn, $update_drop)) {
            throw new Exception("Gagal update drops: " . mysqli_error($conn));
        }

        // Update drop_items
        $update_items = "UPDATE drop_items SET service_id = $service_id, brand = '$brand' WHERE drop_id = $id_drop";
        if (!mysqli_query($conn, $update_items)) {
            throw new Exception("Gagal update drop_items: " . mysqli_error($conn));
        }

        // Handle payments
        $check_payment = "SELECT id_payment FROM payments WHERE drop_id = $id_drop";
        $payment_result = mysqli_query($conn, $check_payment);

        if (mysqli_num_rows($payment_result) > 0) {
            // Update payment
            if ($payment_date === null) {
                $update_payment = "UPDATE payments SET 
                                  amount_paid = $amount_paid, 
                                  payment_method = '$payment_method', 
                                  payment_date = NULL, 
                                  status = '$payment_status'
                                  WHERE drop_id = $id_drop";
            } else {
                $update_payment = "UPDATE payments SET 
                                  amount_paid = $amount_paid, 
                                  payment_method = '$payment_method', 
                                  payment_date = '$payment_date', 
                                  status = '$payment_status'
                                  WHERE drop_id = $id_drop";
            }
            
            if (!mysqli_query($conn, $update_payment)) {
                throw new Exception("Gagal update payment: " . mysqli_error($conn));
            }
        } else {
            // Insert payment baru
            if ($payment_date === null) {
                $insert_payment = "INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
                                  VALUES ($id_drop, $amount_paid, '$payment_method', NULL, '$payment_status')";
            } else {
                $insert_payment = "INSERT INTO payments (drop_id, amount_paid, payment_method, payment_date, status)
                                  VALUES ($id_drop, $amount_paid, '$payment_method', '$payment_date', '$payment_status')";
            }
            
            if (!mysqli_query($conn, $insert_payment)) {
                throw new Exception("Gagal insert payment: " . mysqli_error($conn));
            }
        }

        // Update deadlines
        $update_deadline = "UPDATE deadlines SET deadline_date = '$est_finish_date', status_id = $status_id WHERE drop_id = $id_drop";
        if (!mysqli_query($conn, $update_deadline)) {
            error_log("Warning: Gagal update deadline - " . mysqli_error($conn));
        }

        mysqli_commit($conn);
        
        header("Location: dashboard.php?success=1");
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Error: " . $e->getMessage());
        
        $error_message = urlencode($e->getMessage());
        header("Location: dashboard.php?error=" . $error_message);
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>