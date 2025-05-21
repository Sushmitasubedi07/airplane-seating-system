/* Seat Allocation */
document.addEventListener('DOMContentLoaded', function() {
    const bookingType = document.getElementById('booking-type');
    
    if (bookingType) {
        bookingType.addEventListener('change', function(e) {
            const isGroup = e.target.value === 'group';
            
            // Toggle visibility of passenger type fields
            document.getElementById('solo-passenger-field').style.display = isGroup ? 'none' : 'block';
            document.getElementById('group-passenger-field').style.display = isGroup ? 'block' : 'none';
            
            // Toggle required attribute for group size
            const groupSize = document.getElementById('group-size');
            if (groupSize) {
                groupSize.required = isGroup;
                
                // Reset the value when switching between types
                if (!isGroup) {
                    groupSize.value = '';
                }
            }
            
            // Toggle required attribute for passenger selects
            const soloPassenger = document.querySelector('[name="solo_passenger"]');
            const groupLeader = document.querySelector('[name="group_leader"]');
            
            if (soloPassenger) soloPassenger.required = !isGroup;
            if (groupLeader) groupLeader.required = isGroup;
        });
        
        // Trigger change event on page load in case group is pre-selected
        bookingType.dispatchEvent(new Event('change'));
    }

    const adminOverrideBtn = document.getElementById('adminOverrideBtn');
    const adminPopup = document.getElementById('adminPopup');
    const adminPasswordForm = document.getElementById('adminPasswordForm');
    const adminPasswordInput = document.getElementById('adminPassword');
    const cancelAdminBtn = document.getElementById('cancelAdminBtn');
    const logoutAdminBtn = document.getElementById('logoutAdminBtn');
    
    let adminMode = false;
    let selectedSeat = null;
    
    // Admin override button click
    adminOverrideBtn.addEventListener('click', function() {
        if (adminMode) {
            exitAdminMode();
        } else {
            adminPopup.classList.add('active');
        }
    });
    
    // Cancel admin mode
    cancelAdminBtn.addEventListener('click', function() {
        adminPopup.style.display = 'none';
        adminPasswordInput.value = '';
    });
    
    // Logout admin
    logoutAdminBtn.addEventListener('click', function() {
        fetch('admin/admin_logout.php')
            .then(() => {
                exitAdminMode();
                alert('Admin session ended');
            });
    });
    
    // Password form submission
    adminPasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const password = adminPasswordInput.value;
        
        fetch('admin/verify_admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ password: password })
        })
        .then(response => response.json())
        .then(data => {
            if (data.valid) {
                adminPopup.style.display = 'none';
                adminPasswordInput.value = '';
                enterAdminMode();
            } else {
                alert('Invalid admin password');
            }
        });
    });
    
    // Seat click handler in admin mode
    function handleAdminSeatClick(e) {
        if (!adminMode) return;
        
        const seatId = e.target.getAttribute('data-id');
        const currentPassenger = e.target.querySelector('.passenger-name')?.textContent || 'Empty';
        
        const newPassenger = prompt(`Reassign seat ${seatId}\nCurrent: ${currentPassenger}\nEnter new passenger name:`);
        
        if (newPassenger && newPassenger.trim() !== '') {
            fetch('admin/admin_override.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    seatId: seatId,
                    passengerName: newPassenger.trim()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Refresh to show changes
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }
    
    function enterAdminMode() {
        adminMode = true;
        document.body.classList.add('admin-mode');
        adminOverrideBtn.textContent = 'Exit Admin Mode';
        
        // Add click listeners to all seats
        document.querySelectorAll('.seat').forEach(seat => {
            seat.style.cursor = 'pointer';
            seat.addEventListener('click', handleAdminSeatClick);
        });
    }
    
    function exitAdminMode() {
        adminMode = false;
        document.body.classList.remove('admin-mode');
        adminOverrideBtn.textContent = 'Admin Override';
        
        // Hide logout button
        if (logoutAdminBtn) logoutAdminBtn.style.display = 'none';
        
        // Remove click listeners
        document.querySelectorAll('.seat').forEach(seat => {
            seat.style.cursor = '';
            seat.removeEventListener('click', handleAdminSeatClick);
        });
    }

    const resetSeatsBtn = document.getElementById('resetSeatsBtn');


    // Reset Seats Button
    resetSeatsBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to reset all seat allocations?')) {
            resetAllSeats();
        }
    });

    function resetAllSeats() {
        fetch('admin/reset_seats.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('All seat allocations have been reset.');
                location.reload();
            } else {
                alert('Error resetting seats: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while resetting seats.');
        });
    }

});