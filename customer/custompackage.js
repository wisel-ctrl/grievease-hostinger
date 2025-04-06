document.addEventListener('DOMContentLoaded', function() {
    // Variables to store custom package selections
    let selectedServiceType = '';
    let selectedCasket = null;
    let selectedViewing = null;
    let selectedFlowers = null;
    let selectedAddons = [];
    let totalPackagePrice = 0;
    
    // Attach event listener to the custom package button
document.querySelectorAll('button.customtraditionalpckg').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        openCustomPackageModal();
    });
});
    
    // Function to open custom package modal
    function openCustomPackageModal() {
        // Reset selections
        resetCustomSelections();
        
        // Show the modal
        document.getElementById('customPackageModal').classList.remove('hidden');
        
        // Show step 1 (service type selection)
        showCustomStep('customStepServiceType');
    }
    
    // Function to reset all custom selections
    function resetCustomSelections() {
        selectedServiceType = '';
        selectedCasket = null;
        selectedViewing = null;
        selectedFlowers = null;
        selectedAddons = [];
        totalPackagePrice = 0;
        
        // Reset UI selections
        document.querySelectorAll('.service-option').forEach(el => {
            el.classList.remove('border-navy', 'border-4');
        });
        
        document.querySelectorAll('.casket-option, .viewing-option, .flower-option').forEach(el => {
            el.classList.remove('border-yellow-600', 'border-2');
        });
        
        document.querySelectorAll('.custom-addon').forEach(el => {
            el.checked = false;
        });
        
        // Reset summary
        updateCustomSummary();
        
        // Disable continue buttons
        document.getElementById('nextToOptions').disabled = true;
        document.getElementById('proceedToBooking').disabled = true;
    }
    
    // Function to show a specific step in the custom package form
    function showCustomStep(stepId) {
        document.querySelectorAll('.custom-step').forEach(step => {
            step.classList.add('hidden');
        });
        document.getElementById(stepId).classList.remove('hidden');
    }
    
    // Service type selection
    document.querySelectorAll('.service-option').forEach(option => {
        option.addEventListener('click', function() {
            // Clear previous selection
            document.querySelectorAll('.service-option').forEach(el => {
                el.classList.remove('border-navy', 'border-4');
            });
            
            // Mark this option as selected
            this.classList.add('border-navy', 'border-4');
            
            // Store selected service type
            selectedServiceType = this.dataset.service;
            
            // Enable continue button
            document.getElementById('nextToOptions').disabled = false;
            
            // Show/hide payment sections based on service type
            updatePaymentSection();
        });
    });
    
    // Next button to go from service type to options
    document.getElementById('nextToOptions').addEventListener('click', function() {
        if (selectedServiceType) {
            showCustomStep('customStepOptions');
        }
    });
    
    // Back button to return to service type selection
    document.getElementById('backToServiceType').addEventListener('click', function() {
        showCustomStep('customStepServiceType');
    });
    
    // Casket selection
    document.querySelectorAll('.casket-option').forEach(option => {
        option.addEventListener('click', function() {
            // Clear previous selection
            document.querySelectorAll('.casket-option').forEach(el => {
                el.classList.remove('border-yellow-600', 'border-2');
            });
            
            // Mark this option as selected
            this.classList.add('border-yellow-600', 'border-2');
            
            // Store selected casket
            selectedCasket = {
                name: this.dataset.name,
                price: parseInt(this.dataset.price)
            };
            
            updateCustomSummary();
            checkRequiredSelections();
        });
    });
    
    // Viewing period selection
    document.querySelectorAll('.viewing-option').forEach(option => {
        option.addEventListener('click', function() {
            // Clear previous selection
            document.querySelectorAll('.viewing-option').forEach(el => {
                el.classList.remove('border-yellow-600', 'border-2');
            });
            
            // Mark this option as selected
            this.classList.add('border-yellow-600', 'border-2');
            
            // Store selected viewing
            selectedViewing = {
                name: this.dataset.name,
                price: parseInt(this.dataset.price)
            };
            
            updateCustomSummary();
            checkRequiredSelections();
        });
    });
    
    // Flower arrangements selection
    document.querySelectorAll('.flower-option').forEach(option => {
        option.addEventListener('click', function() {
            // Clear previous selection
            document.querySelectorAll('.flower-option').forEach(el => {
                el.classList.remove('border-yellow-600', 'border-2');
            });
            
            // Mark this option as selected
            this.classList.add('border-yellow-600', 'border-2');
            
            // Store selected flowers
            selectedFlowers = {
                name: this.dataset.name,
                price: parseInt(this.dataset.price)
            };
            
            updateCustomSummary();
            checkRequiredSelections();
        });
    });
    
    // Additional services checkboxes
    document.querySelectorAll('.custom-addon').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Update selected addons list
            selectedAddons = [];
            document.querySelectorAll('.custom-addon:checked').forEach(checked => {
                selectedAddons.push({
                    name: checked.dataset.name,
                    price: parseInt(checked.value)
                });
            });
            
            updateCustomSummary();
        });
    });
    
    // Payment term change for lifeplan
    document.getElementById('customPaymentTerm').addEventListener('change', function() {
        updateCustomSummary();
    });
    
    // Function to update payment section based on service type
    function updatePaymentSection() {
        if (selectedServiceType === 'traditional') {
            document.getElementById('customTraditionalPayment').classList.remove('hidden');
            document.getElementById('customLifeplanPayment').classList.add('hidden');
        } else if (selectedServiceType === 'lifeplan') {
            document.getElementById('customTraditionalPayment').classList.add('hidden');
            document.getElementById('customLifeplanPayment').classList.remove('hidden');
        }
    }
    
    // Function to check if required selections are made
    function checkRequiredSelections() {
        // Enable proceed button only if all required selections are made
        const requiredSelectionsComplete = selectedCasket && selectedViewing && selectedFlowers;
        document.getElementById('proceedToBooking').disabled = !requiredSelectionsComplete;
    }
    
    // Function to update the custom package summary
    function updateCustomSummary() {
        const summaryElement = document.getElementById('customSelectionsSummary');
        let summaryHTML = '';
        totalPackagePrice = 0;
        
        // Add casket info if selected
        if (selectedCasket) {
            summaryHTML += `<div class="flex justify-between text-sm">
                <span>${selectedCasket.name}</span>
                <span>₱${selectedCasket.price.toLocaleString()}</span>
            </div>`;
            totalPackagePrice += selectedCasket.price;
        }
        
        // Add viewing period info if selected
        if (selectedViewing) {
            summaryHTML += `<div class="flex justify-between text-sm">
                <span>${selectedViewing.name}</span>
                <span>₱${selectedViewing.price.toLocaleString()}</span>
            </div>`;
            totalPackagePrice += selectedViewing.price;
        }
        
        // Add flower arrangements info if selected
        if (selectedFlowers) {
            summaryHTML += `<div class="flex justify-between text-sm">
                <span>${selectedFlowers.name}</span>
                <span>₱${selectedFlowers.price.toLocaleString()}</span>
            </div>`;
            totalPackagePrice += selectedFlowers.price;
        }
        
        // Add additional services if any
        if (selectedAddons.length > 0) {
            selectedAddons.forEach(addon => {
                summaryHTML += `<div class="flex justify-between text-sm">
                    <span>${addon.name}</span>
                    <span>₱${addon.price.toLocaleString()}</span>
                </div>`;
                totalPackagePrice += addon.price;
            });
        }
        
        // Update summary section
        if (summaryHTML) {
            summaryElement.innerHTML = summaryHTML;
        } else {
            summaryElement.innerHTML = '<p class="text-gray-500 italic">No items selected yet</p>';
        }
        
        // Update total price display
        document.getElementById('customTotalPrice').textContent = `₱${totalPackagePrice.toLocaleString()}`;
        
        // Update payment-specific displays
        if (selectedServiceType === 'traditional') {
            const downpayment = Math.ceil(totalPackagePrice * 0.3);
            document.getElementById('customDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
        } else if (selectedServiceType === 'lifeplan') {
            const months = parseInt(document.getElementById('customPaymentTerm').value);
            const monthlyPayment = Math.ceil(totalPackagePrice / months);
            document.getElementById('customMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;
        }
    }
    
    // Proceed to booking button click event
    document.getElementById('proceedToBooking').addEventListener('click', function() {
        // Store package details in sessionStorage for the booking form to use
        sessionStorage.setItem('selectedPackageName', 'Custom Memorial Package');
        sessionStorage.setItem('selectedPackagePrice', totalPackagePrice.toString());
        sessionStorage.setItem('selectedServiceType', selectedServiceType);
        
        // Create feature list from selections
        const features = [];
        if (selectedCasket) features.push(`<i class="fas fa-check-circle mr-2 text-yellow-600"></i> ${selectedCasket.name}`);
        if (selectedViewing) features.push(`<i class="fas fa-check-circle mr-2 text-yellow-600"></i> ${selectedViewing.name}`);
        if (selectedFlowers) features.push(`<i class="fas fa-check-circle mr-2 text-yellow-600"></i> ${selectedFlowers.name}`);
        selectedAddons.forEach(addon => {
            features.push(`<i class="fas fa-check-circle mr-2 text-yellow-600"></i> ${addon.name}`);
        });
        
        sessionStorage.setItem('selectedPackageFeatures', JSON.stringify(features));
        
        // Close the custom package modal
        document.getElementById('customPackageModal').classList.add('hidden');
        
        // Open the appropriate booking modal based on service type
        if (selectedServiceType === 'traditional') {
            openTraditionalModal();
        } else if (selectedServiceType === 'lifeplan') {
            // Directly open lifeplan modal since it's stored in the existing code
            document.getElementById('lifeplanModal').classList.remove('hidden');
            
            // Make sure the lifeplan modal has the correct package details
            document.getElementById('lifeplanPackageName').textContent = 'Custom Memorial Package';
            document.getElementById('lifeplanPackagePrice').textContent = `₱${totalPackagePrice.toLocaleString()}`;
            
            // Update features list
            const featuresList = document.getElementById('lifeplanPackageFeatures');
            featuresList.innerHTML = '';
            features.forEach(feature => {
                featuresList.innerHTML += `<li class="flex items-center text-sm text-gray-700">${feature}</li>`;
            });
            
            // Update the form's hidden fields with package info
            document.getElementById('lifeplanSelectedPackageName').value = 'Custom Memorial Package';
            document.getElementById('lifeplanSelectedPackagePrice').value = totalPackagePrice.toString();
            
            // Calculate monthly payment (default: 5 years / 60 months)
            const months = parseInt(document.getElementById('customPaymentTerm').value) || 60;
            const monthlyPayment = Math.ceil(totalPackagePrice / months);
            document.getElementById('lifeplanMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;
            document.getElementById('lifeplanSelectedMonthlyPayment').value = monthlyPayment.toString();
        }
    });
    
    // Function to open traditional booking modal
    function openTraditionalModal() {
        document.getElementById('traditionalModal').classList.remove('hidden');
        
        // Update traditional modal with package details
        document.getElementById('traditionalPackageName').textContent = 'Custom Memorial Package';
        document.getElementById('traditionalPackagePrice').textContent = `₱${totalPackagePrice.toLocaleString()}`;
        
        // Calculate downpayment (30%)
        const downpayment = Math.ceil(totalPackagePrice * 0.3);
        document.getElementById('traditionalDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
        document.getElementById('traditionalSelectedDownpayment').value = downpayment.toString();
        
        // Update features list
        const features = JSON.parse(sessionStorage.getItem('selectedPackageFeatures') || '[]');
        const featuresList = document.getElementById('traditionalPackageFeatures');
        featuresList.innerHTML = '';
        features.forEach(feature => {
            featuresList.innerHTML += `<li class="flex items-center text-sm text-gray-700">${feature}</li>`;
        });
        
        // Update the form's hidden fields with package info
        document.getElementById('traditionalSelectedPackageName').value = 'Custom Memorial Package';
        document.getElementById('traditionalSelectedPackagePrice').value = totalPackagePrice.toString();
    }
    
    // Close custom package modal button
    document.getElementById('closeCustomPackageModal').addEventListener('click', function() {
        document.getElementById('customPackageModal').classList.add('hidden');
    });
    
    // Close traditional modal button
    document.getElementById('closeTraditionalModal').addEventListener('click', function() {
        document.getElementById('traditionalModal').classList.add('hidden');
    });
    
    // Close lifeplan modal button
    document.getElementById('closeLifeplanModal').addEventListener('click', function() {
        document.getElementById('lifeplanModal').classList.add('hidden');
    });
    
    // Form submission handling for traditional booking
    document.getElementById('traditionalBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form fields
        const form = this;
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('border-red-500');
            } else {
                field.classList.remove('border-red-500');
            }
        });
        
        if (isValid) {
            // Show loading state
            document.getElementById('traditionalSubmitBtn').disabled = true;
            document.getElementById('traditionalSubmitBtn').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            
            // Collect form data
            const formData = new FormData(form);
            
            // Send form data to server using fetch API
            fetch('/api/book-traditional', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    document.getElementById('traditionalModal').classList.add('hidden');
                    document.getElementById('bookingSuccessModal').classList.remove('hidden');
                    document.getElementById('bookingReference').textContent = data.reference;
                } else {
                    // Show error message
                    alert('Booking failed: ' + data.message);
                    document.getElementById('traditionalSubmitBtn').disabled = false;
                    document.getElementById('traditionalSubmitBtn').innerHTML = 'Confirm Booking';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
                document.getElementById('traditionalSubmitBtn').disabled = false;
                document.getElementById('traditionalSubmitBtn').innerHTML = 'Confirm Booking';
            });
        }
    });
    
    // Form submission handling for lifeplan booking
    document.getElementById('lifeplanBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form fields
        const form = this;
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('border-red-500');
            } else {
                field.classList.remove('border-red-500');
            }
        });
        
        if (isValid) {
            // Show loading state
            document.getElementById('lifeplanSubmitBtn').disabled = true;
            document.getElementById('lifeplanSubmitBtn').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            
            // Collect form data
            const formData = new FormData(form);
            
            // Send form data to server using fetch API
            fetch('/api/book-lifeplan', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    document.getElementById('lifeplanModal').classList.add('hidden');
                    document.getElementById('bookingSuccessModal').classList.remove('hidden');
                    document.getElementById('bookingReference').textContent = data.reference;
                } else {
                    // Show error message
                    alert('Booking failed: ' + data.message);
                    document.getElementById('lifeplanSubmitBtn').disabled = false;
                    document.getElementById('lifeplanSubmitBtn').innerHTML = 'Confirm Booking';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
                document.getElementById('lifeplanSubmitBtn').disabled = false;
                document.getElementById('lifeplanSubmitBtn').innerHTML = 'Confirm Booking';
            });
        }
    });
    
    // Close success modal button
    document.getElementById('closeSuccessModal').addEventListener('click', function() {
        document.getElementById('bookingSuccessModal').classList.add('hidden');
        // Reload page to reset everything
        window.location.reload();
    });
    
    // Initialize date pickers if needed
    if (document.getElementById('traditionalDeathDate')) {
        flatpickr("#traditionalDeathDate", {
            dateFormat: "Y-m-d",
            maxDate: "today"
        });
    }
    
    if (document.getElementById('traditionalServiceDate')) {
        flatpickr("#traditionalServiceDate", {
            dateFormat: "Y-m-d",
            minDate: "today"
        });
    }
    

});