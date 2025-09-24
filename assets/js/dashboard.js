// Dashboard JavaScript for Employee Attendance System

document.addEventListener('DOMContentLoaded', function() {
    // Update current time every second
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
    
    // Handle attendance marking
    const markAttendanceBtn = document.getElementById('markAttendanceBtn');
    if (markAttendanceBtn) {
        markAttendanceBtn.addEventListener('click', markAttendance);
    }
});

function updateCurrentTime() {
    const currentTimeElement = document.getElementById('currentTime');
    if (currentTimeElement) {
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };
        currentTimeElement.textContent = now.toLocaleDateString('en-US', options);
    }
}

function markAttendance() {
    const btn = document.getElementById('markAttendanceBtn');
    const locationStatus = document.getElementById('locationStatus');
    const attendanceResult = document.getElementById('attendanceResult');
    
    // Disable button and show loading
    btn.disabled = true;
    btn.textContent = 'Getting Location...';
    
    locationStatus.className = 'location-status getting-location';
    locationStatus.textContent = 'Getting your location...';
    
    attendanceResult.textContent = '';
    attendanceResult.className = 'result';
    
    // Get user's location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                
                locationStatus.className = 'location-status location-found';
                locationStatus.textContent = `Location found: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                
                // Get location address using reverse geocoding
                getLocationAddress(latitude, longitude)
                    .then(address => {
                        // Send attendance data to server
                        sendAttendanceData(latitude, longitude, address);
                    })
                    .catch(error => {
                        console.log('Address lookup failed, proceeding without address');
                        sendAttendanceData(latitude, longitude, null);
                    });
            },
            function(error) {
                let errorMessage = 'Unable to get location. ';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage += 'Please allow location access and try again.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage += 'Location information is unavailable.';
                        break;
                    case error.TIMEOUT:
                        errorMessage += 'Location request timed out.';
                        break;
                    default:
                        errorMessage += 'An unknown error occurred.';
                        break;
                }
                
                locationStatus.className = 'location-status location-error';
                locationStatus.textContent = errorMessage;
                
                // Re-enable button
                btn.disabled = false;
                btn.textContent = 'Mark Attendance';
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        locationStatus.className = 'location-status location-error';
        locationStatus.textContent = 'Geolocation is not supported by this browser.';
        
        // Re-enable button
        btn.disabled = false;
        btn.textContent = 'Mark Attendance';
    }
}

function getLocationAddress(latitude, longitude) {
    return new Promise((resolve, reject) => {
        // Using OpenStreetMap Nominatim for reverse geocoding (free service)
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data && data.display_name) {
                    resolve(data.display_name);
                } else {
                    reject('Address not found');
                }
            })
            .catch(error => {
                reject(error);
            });
    });
}

function sendAttendanceData(latitude, longitude, locationAddress) {
    const btn = document.getElementById('markAttendanceBtn');
    const attendanceResult = document.getElementById('attendanceResult');
    
    btn.textContent = 'Marking Attendance...';
    
    const data = {
        latitude: latitude,
        longitude: longitude,
        location_address: locationAddress
    };
    
    fetch('mark_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            attendanceResult.className = 'result success';
            attendanceResult.innerHTML = `
                <strong>✓ ${data.message}</strong><br>
                Time: ${data.time}<br>
                Date: ${data.date}
            `;
            
            // Hide the attendance form and show success message
            setTimeout(() => {
                location.reload(); // Refresh to show attendance marked state
            }, 2000);
        } else {
            attendanceResult.className = 'result error';
            attendanceResult.textContent = '✗ ' + data.message;
            
            // Re-enable button
            btn.disabled = false;
            btn.textContent = 'Mark Attendance';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        attendanceResult.className = 'result error';
        attendanceResult.textContent = '✗ Network error. Please try again.';
        
        // Re-enable button
        btn.disabled = false;
        btn.textContent = 'Mark Attendance';
    });
}

// Additional utility functions
function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    
    const container = document.querySelector('.main-content');
    if (container) {
        container.insertBefore(messageDiv, container.firstChild);
        
        // Auto-hide message after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
}

// Handle form confirmations
document.addEventListener('click', function(e) {
    if (e.target.matches('button[name="delete_employee"]')) {
        if (!confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
            e.preventDefault();
        }
    }
});

// Add loading states to forms
document.addEventListener('submit', function(e) {
    const submitBtn = e.target.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Processing...';
        
        // Re-enable after 3 seconds in case of errors
        setTimeout(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }, 3000);
    }
});