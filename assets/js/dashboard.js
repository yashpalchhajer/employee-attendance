console.log("dashboard.js loaded");

document.addEventListener('DOMContentLoaded', () => {
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const locationAddressInput = document.getElementById('location_address');
    const locationStatus = document.getElementById('locationStatus');
    const resultDiv = document.getElementById('attendanceResult');

    
    function updateCurrentTime() {
        const currentTimeElement = document.getElementById('currentTime');
        if (currentTimeElement) {
            const nowUtc = new Date();
            const istOffset = 5.5 * 60; 
            const nowIst = new Date(nowUtc.getTime() + istOffset * 60 * 1000);
            const options = { 
                weekday:'long', year:'numeric', month:'long', day:'numeric',
                hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:true
            };
            currentTimeElement.textContent = nowIst.toLocaleString('en-US', options);
        }
    }
    setInterval(updateCurrentTime, 1000);
    updateCurrentTime();
    

    
    function cleanDuplicateLocation(locationStr) {
        if (!locationStr) return locationStr;
        
    
        const parts = locationStr.split(', India, ');
        if (parts.length > 1 && parts[0] === parts[1].replace(', India', '')) {
            locationStr = parts[0] + ', India';
        }
        
        
        const addressParts = locationStr.split(', ');
        
        
        let area = '';
        let city = '';
        let state = '';
        let pincode = '';
        let country = '';
        
        for (let i = 0; i < addressParts.length; i++) {
            const part = addressParts[i].trim();
            
            if (/^\d{6}$/.test(part)) {
                pincode = part;
            }
            else if (part === 'India') {
                country = part;
            }
            else if (['Rajasthan', 'Gujarat', 'Maharashtra', 'Delhi', 'Karnataka', 'Tamil Nadu', 'Punjab', 'Haryana', 'Uttar Pradesh', 'Madhya Pradesh'].includes(part)) {
                state = part;
            }
            else if (part.includes('Jodhpur') && !part.includes('Tehsil') && !city) {
                city = 'Jodhpur';
            }
            else if (part && !part.includes('Tehsil') && !area && i < 2) {
                area = part;
            }
        }
        
    
        let cleanAddress = [];
        if (area) cleanAddress.push(area);
        if (city) cleanAddress.push(city);
        if (state && pincode) {
            cleanAddress.push(`${state} ${pincode}`);
        } else {
            if (state) cleanAddress.push(state);
            if (pincode) cleanAddress.push(pincode);
        }
        if (country) cleanAddress.push(country);
        
        return cleanAddress.join(', ') || locationStr;
    }

    
    if (navigator.geolocation) {
        locationStatus.textContent = "Fetching location...";
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;

                latitudeInput.value = lat;
                longitudeInput.value = lon;

                locationStatus.textContent = `Location acquired (${lat.toFixed(4)}, ${lon.toFixed(4)})`;

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`, {
                        headers: { 'User-Agent': 'AttendanceApp/1.0 (your_email@example.com)' }
                    });
                    const data = await response.json();
                    
            
                    const rawLocation = data.display_name || `${lat}, ${lon}`;
                    const cleanedLocation = cleanDuplicateLocation(rawLocation);
                    locationAddressInput.value = cleanedLocation;
                    
                } catch (err) {
                    console.warn("Reverse geocoding failed:", err);
                    locationAddressInput.value = `${lat}, ${lon}`;
                }
            },
            (error) => {
                locationStatus.textContent = "⚠ Unable to get location.";
                console.warn("Geolocation error:", error);
            }
        );
    } else {
        locationStatus.textContent = "Geolocation not supported.";
    }
    

  
    document.querySelectorAll('button[data-action]').forEach(button => {
        button.addEventListener('click', async function () {
            const action = this.getAttribute('data-action');

            if (!action) {
                resultDiv.innerHTML = `<p class="error">Action not found</p>`;
                return;
            }

            const allButtons = document.querySelectorAll('button[data-action]');
            allButtons.forEach(btn => btn.disabled = true);

            
            const cleanedLocation = cleanDuplicateLocation(locationAddressInput.value);

            const payload = {
                action: action,
                latitude: latitudeInput.value,
                longitude: longitudeInput.value,
                location_address: cleanedLocation
            };

            resultDiv.innerHTML = `<p class="info">Processing ${action.replace('_', ' ')}...</p>`;

            try {
                const res = await fetch('mark_attendance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await res.json();

                if (result.success) {
                    resultDiv.innerHTML = `<p class="success">${result.message}</p>`;
                    setTimeout(() => location.reload(), 1500);
                } else {
                    resultDiv.innerHTML = `<p class="error">${result.message}</p>`;
                    allButtons.forEach(btn => btn.disabled = false);
                }

            } catch (err) {
                resultDiv.innerHTML = `<p class="error">Server error. Try again.</p>`;
                console.error('Fetch error:', err);
                allButtons.forEach(btn => btn.disabled = false);
            }
        });
    });
    
});