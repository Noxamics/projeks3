// ================================================
// Attendance Modal Handler
// File: js/karyawan/attendance.js
// ================================================

// Clock interval variable (declare once)
let clockInterval = null;

// Start real-time clock
function startClock() {
    // Clear existing interval if any
    if (clockInterval) {
        clearInterval(clockInterval);
    }

    updateClock(); // Update immediately
    clockInterval = setInterval(updateClock, 1000);
}

// Update clock display
function updateClock() {
    const now = new Date();
    
    // Format date: Senin, 03 Desember 2024
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    const dayName = days[now.getDay()];
    const date = now.getDate();
    const month = months[now.getMonth()];
    const year = now.getFullYear();
    
    const dateStr = `${dayName}, ${date} ${month} ${year}`;
    
    // Format time: HH:MM:SS
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    
    const timeStr = `${hours}:${minutes}:${seconds}`;
    
    // Update DOM
    const dateEl = document.getElementById('current_date');
    const timeEl = document.getElementById('current_time');
    
    if (dateEl) dateEl.textContent = dateStr;
    if (timeEl) timeEl.textContent = timeStr;
}

// Stop clock when modal closes
function stopClock() {
    if (clockInterval) {
        clearInterval(clockInterval);
        clockInterval = null;
    }
}

// Open attendance modal
function openAttendanceModal(employee) {
    console.log('Opening attendance modal for:', employee);
    
    const modal = document.getElementById('modalAttendance');
    if (!modal) {
        console.error('Attendance modal not found');
        return;
    }

    // Set employee info
    document.getElementById('attendance_name').textContent = employee.name;
    document.getElementById('attendance_code').textContent = employee.employee_code;
    
    // Set photo
    const photoEl = document.getElementById('attendance_photo');
    if (employee.photo && employee.photo.trim() !== '') {
        photoEl.src = '../uploads/employee/' + employee.photo;
    } else {
        const initial = employee.name.substring(0, 2).toUpperCase();
        photoEl.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(initial)}&size=400&background=667eea&color=fff&bold=true`;
    }

    // Set employee ID in forms
    document.getElementById('checkin_employee_id').value = employee.id_employee;
    document.getElementById('checkout_employee_id').value = employee.id_employee;

    // Populate role dropdown
    populateRoleDropdown(employee);

    // Check attendance status and show appropriate section
    checkAttendanceStatus(employee.id_employee);

    // Start clock
    startClock();

    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Populate role dropdown from employee roles
function populateRoleDropdown(employee) {
    const roleSelect = document.getElementById('checkin_role');
    if (!roleSelect) return;

    // Clear existing options except the first one
    roleSelect.innerHTML = '<option value="">-- Pilih Role --</option>';

    // Get roles from employee data
    const roles = employee.roles ? employee.roles.split(',') : [];
    
    if (roles.length > 0) {
        roles.forEach(role => {
            const roleValue = role.trim().toLowerCase();
            const roleLabel = roleValue.charAt(0).toUpperCase() + roleValue.slice(1);
            
            const option = document.createElement('option');
            option.value = roleValue;
            option.textContent = roleLabel;
            roleSelect.appendChild(option);
        });
    } else {
        // Fallback jika tidak ada role
        const option = document.createElement('option');
        option.value = 'cleaning';
        option.textContent = 'Cleaning';
        roleSelect.appendChild(option);
    }
}

// Check if employee has checked in today
async function checkAttendanceStatus(employeeId) {
    try {
        const response = await fetch('../actions/karyawan/check_attendance_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `employee_id=${employeeId}`
        });

        const data = await response.json();
        
        if (data.hasCheckedIn) {
            // Show checkout section
            document.getElementById('checkin-section').style.display = 'none';
            document.getElementById('checkout-section').style.display = 'block';
            
            // Update summary
            updateWorkSummary(data.summary);
        } else {
            // Show checkin section
            document.getElementById('checkin-section').style.display = 'block';
            document.getElementById('checkout-section').style.display = 'none';
        }
    } catch (error) {
        console.error('Error checking attendance:', error);
        // Default to checkin
        document.getElementById('checkin-section').style.display = 'block';
        document.getElementById('checkout-section').style.display = 'none';
    }
}

// Update work summary in checkout section
function updateWorkSummary(summary) {
    if (!summary) return;

    document.getElementById('summary_shoes').textContent = summary.shoes_done || 0;
    document.getElementById('summary_duration').textContent = summary.work_hours || '0';
    document.getElementById('summary_role').textContent = summary.role_today || '-';
    document.getElementById('summary_score').textContent = summary.score || 0;
}

// Close attendance modal
function closeAttendanceModal() {
    const modal = document.getElementById('modalAttendance');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        
        // Stop clock
        stopClock();
        
        // Reset forms
        document.getElementById('formCheckIn').reset();
        document.getElementById('formCheckOut').reset();
    }
}

// Handle Check In form submission
document.addEventListener('DOMContentLoaded', function() {
    const checkInForm = document.getElementById('formCheckIn');
    
    if (checkInForm) {
        checkInForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Memproses...';
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('../actions/karyawan/checkin.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('success', 'Check In Berhasil!', data.message);
                    closeAttendanceModal();
                    
                    // Refresh page after 1 second
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification('error', 'Check In Gagal', data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'Error', 'Terjadi kesalahan saat check in');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
    
    // Handle Check Out form submission
    const checkOutForm = document.getElementById('formCheckOut');
    
    if (checkOutForm) {
        checkOutForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Memproses...';
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('../actions/karyawan/checkout.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('success', 'Check Out Berhasil!', data.message);
                    closeAttendanceModal();
                    
                    // Refresh page after 1 second
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification('error', 'Check Out Gagal', data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('error', 'Error', 'Terjadi kesalahan saat check out');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
});

console.log('Attendance.js loaded successfully');