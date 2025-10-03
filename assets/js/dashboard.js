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

    btn.disabled = true;
    btn.textContent = 'Getting Location...';
    locationStatus.className = 'location-status getting-location';
    locationStatus.textContent = 'Getting your location...';
    attendanceResult.textContent = '';
    attendanceResult.className = 'result';

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;

                // Show coordinates directly
                locationStatus.className = 'location-status location-found';
                locationStatus.textContent = `Coordinates: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                console.log('Latitude:', latitude, 'Longitude:', longitude); // Debug

                // For now, send coordinates as "location_address"
                const locationAddress = `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                sendAttendanceData(latitude, longitude, locationAddress);

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
                btn.disabled = false;
                btn.textContent = 'Mark Attendance';
            },
            { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 } // Increased timeout
        );
    } else {
        locationStatus.className = 'location-status location-error';
        locationStatus.textContent = 'Geolocation is not supported by this browser.';
        btn.disabled = false;
        btn.textContent = 'Mark Attendance';
    }
}

function sendAttendanceData(latitude, longitude, locationAddress) {
    const btn = document.getElementById('markAttendanceBtn');
    const attendanceResult = document.getElementById('attendanceResult');

    btn.textContent = 'Marking Attendance...';

    const data = { latitude, longitude, location_address: locationAddress };

    fetch('mark_attendance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            attendanceResult.className = 'result success';
            attendanceResult.innerHTML = `
                <strong>✓ ${data.message}</strong><br>
                Time: ${data.time}<br>
                Date: ${data.date}<br>
                Location (Lat,Long): ${locationAddress}
            `;
            setTimeout(() => location.reload(), 2000);
        } else {
            attendanceResult.className = 'result error';
            attendanceResult.textContent = '✗ ' + data.message;
            btn.disabled = false;
            btn.textContent = 'Mark Attendance';
        }
    })
    .catch(() => {
        attendanceResult.className = 'result error';
        attendanceResult.textContent = '✗ Network error. Please try again.';
        btn.disabled = false;
        btn.textContent = 'Mark Attendance';
    });
}
