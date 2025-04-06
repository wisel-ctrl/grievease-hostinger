document.addEventListener('DOMContentLoaded', function() {
    // Select the custom cremation button and modal
    const customCremationButton = document.querySelector('.customcremationpckg');
    const customCremationModal = document.getElementById('customCremationModal');
    const closeModalButtons = document.querySelectorAll('.closeModalBtn');
    
    // Step navigation buttons
    const nextToServiceTypeBtn = document.getElementById('nextToServiceType');
    const nextToOptionsBtn = document.getElementById('cremationnextToOptions');
    const cremationProceedToBookingBtn = document.getElementById('cremationProceedToBooking');
    const backToCremationTypeBtn = document.getElementById('backToCremationType');
    const backToServiceTypeBtn = document.getElementById('backToServiceType');
    const backToCremationOptionsBtn = document.getElementById('backToCremationOptions');
    
    // Step containers
    const cremationStepType = document.getElementById('cremationStepType');
    const cremationStepServiceType = document.getElementById('cremationStepServiceType');
    const cremationStepOptions = document.getElementById('cremationStepOptions');
    const cremationStepBooking = document.getElementById('cremationStepBooking');
    
    // Option-specific containers
    const directCremationOptions = document.getElementById('directCremationOptions');
    const traditionalCremationOptions = document.getElementById('traditionalCremationOptions');
    const cremationImmediatePayment = document.getElementById('cremationImmediatePayment');
    const cremationLifeplanPayment = document.getElementById('cremationLifeplanPayment');
    const cremationBookingFormContainer = document.getElementById('cremationBookingFormContainer');
    
    // Selection tracking variables
    let selectedCremationType = null;
    let selectedServiceType = null;
    let selectedOptions = {
        container: null,
        urn: null,
        casket: null,
        viewing: null,
        flowers: null,
        addons: []
    };
    let totalPrice = 0;
    
    // Open the modal when the custom cremation button is clicked
    if (customCremationButton) {
        customCremationButton.addEventListener('click', function() {
            if (customCremationModal) {
                customCremationModal.classList.remove('hidden');
                resetForm();
            }
        });
    }
    
    // Close the modal when close buttons are clicked
    closeModalButtons.forEach(button => {
        button.addEventListener('click', function() {
            customCremationModal.classList.add('hidden');
        });
    });
    
    // Close modal when clicking outside the content area
    customCremationModal.addEventListener('click', function(e) {
        if (e.target === customCremationModal) {
            customCremationModal.classList.add('hidden');
        }
    });
    
    // Cremation Type Selection
    const cremationTypeOptions = document.querySelectorAll('.cremation-type-option');
    cremationTypeOptions.forEach(option => {
        option.addEventListener('click', function() {
            cremationTypeOptions.forEach(opt => opt.classList.remove('border-navy', 'shadow-lg'));
            this.classList.add('border-navy', 'shadow-lg');
            selectedCremationType = this.getAttribute('data-cremation-type');
            nextToServiceTypeBtn.disabled = false;
        });
    });
    
    // Service Type Selection
const serviceOptions = document.querySelectorAll('.service-option');
serviceOptions.forEach(option => {
    option.addEventListener('click', function() {
        serviceOptions.forEach(opt => opt.classList.remove('border-navy', 'shadow-lg'));
        this.classList.add('border-navy', 'shadow-lg');
        selectedServiceType = this.getAttribute('data-service');
        nextToOptionsBtn.disabled = false; // This enables the "Continue to Package Options" button
    });
});
    
    // Step Navigation
    if (nextToServiceTypeBtn) {
        nextToServiceTypeBtn.addEventListener('click', function() {
            cremationStepType.classList.add('hidden');
            cremationStepServiceType.classList.remove('hidden');
        });
    }
    
    if (backToCremationTypeBtn) {
        backToCremationTypeBtn.addEventListener('click', function() {
            cremationStepServiceType.classList.add('hidden');
            cremationStepType.classList.remove('hidden');
        });
    }
    
    if (nextToOptionsBtn) {
        nextToOptionsBtn.addEventListener('click', function() {
            cremationStepServiceType.classList.add('hidden');
            cremationStepOptions.classList.remove('hidden');
            
            // Show options based on cremation type
            if (selectedCremationType === 'direct') {
                directCremationOptions.classList.remove('hidden');
                traditionalCremationOptions.classList.add('hidden');
            } else {
                traditionalCremationOptions.classList.remove('hidden');
                directCremationOptions.classList.add('hidden');
            }
            
            // Show payment info based on service type
            if (selectedServiceType === 'immediate') {
                cremationImmediatePayment.classList.remove('hidden');
                cremationLifeplanPayment.classList.add('hidden');
            } else {
                cremationLifeplanPayment.classList.remove('hidden');
                cremationImmediatePayment.classList.add('hidden');
            }
        });
    }
    
    if (backToServiceTypeBtn) {
        backToServiceTypeBtn.addEventListener('click', function() {
            cremationStepOptions.classList.add('hidden');
            cremationStepServiceType.classList.remove('hidden');
        });
    }
    
    if (cremationProceedToBookingBtn) {
        cremationProceedToBookingBtn.addEventListener('click', function() {
            cremationStepOptions.classList.add('hidden');
            cremationStepBooking.classList.remove('hidden');
            
            // Load appropriate booking form
            if (selectedServiceType === 'immediate') {
                // Traditional booking form is for immediate service
                const traditionalForm = document.getElementById('traditionalBookingForm');
                if (traditionalForm) {
                    cremationBookingFormContainer.innerHTML = traditionalForm.outerHTML;
                    
                    // Update form IDs to avoid duplicates
                    const bookingForm = cremationBookingFormContainer.querySelector('form');
                    bookingForm.id = 'cremationBookingForm';
                    
                    // Set values in the form
                    const formTotalPrice = cremationBookingFormContainer.querySelector('#traditionalTotalPrice');
                    const formDownpayment = cremationBookingFormContainer.querySelector('#traditionalDownpayment');
                    const formAmountDue = cremationBookingFormContainer.querySelector('#traditionalAmountDue');
                    
                    if (formTotalPrice) formTotalPrice.textContent = '₱' + totalPrice.toLocaleString();
                    if (formDownpayment) formDownpayment.textContent = '₱' + (totalPrice * 0.3).toLocaleString();
                    if (formAmountDue) formAmountDue.textContent = '₱' + (totalPrice * 0.3).toLocaleString();
                    
                    // Set up form submission
                    setupFormSubmission(bookingForm);
                }
            } else {
                // Lifeplan booking form
                const lifeplanForm = document.getElementById('lifeplanBookingForm');
                if (lifeplanForm) {
                    cremationBookingFormContainer.innerHTML = lifeplanForm.outerHTML;
                    
                    // Update form IDs to avoid duplicates
                    const bookingForm = cremationBookingFormContainer.querySelector('form');
                    bookingForm.id = 'cremationBookingForm';
                    
                    // Set values in the form
                    const formTotalPrice = cremationBookingFormContainer.querySelector('#lifeplanTotalPrice');
                    const formMonthlyPayment = cremationBookingFormContainer.querySelector('#lifeplanMonthlyPayment');
                    const paymentTermSelect = cremationBookingFormContainer.querySelector('#lifeplanPaymentTerm');
                    const paymentTermDisplay = cremationBookingFormContainer.querySelector('#lifeplanPaymentTermDisplay');
                    
                    if (formTotalPrice) formTotalPrice.textContent = '₱' + totalPrice.toLocaleString();
                    
                    // Set up payment term change handler
                    if (paymentTermSelect) {
                        paymentTermSelect.addEventListener('change', function() {
                            const months = parseInt(this.value);
                            let paymentText = '';
                            
                            switch(months) {
                                case 60: paymentText = '5 Years (60 Monthly Payments)'; break;
                                case 36: paymentText = '3 Years (36 Monthly Payments)'; break;
                                case 24: paymentText = '2 Years (24 Monthly Payments)'; break;
                                case 12: paymentText = '1 Year (12 Monthly Payments)'; break;
                            }
                            
                            if (paymentTermDisplay) paymentTermDisplay.textContent = paymentText;
                            const monthlyAmount = totalPrice / months;
                            if (formMonthlyPayment) formMonthlyPayment.textContent = '₱' + monthlyAmount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        });
                        
                        // Trigger change to set initial values
                        paymentTermSelect.dispatchEvent(new Event('change'));
                    }
                    
                    // Set up form submission
                    setupFormSubmission(bookingForm);
                }
            }
        });
    }
    
    if (backToCremationOptionsBtn) {
        backToCremationOptionsBtn.addEventListener('click', function() {
            cremationStepBooking.classList.add('hidden');
            cremationStepOptions.classList.remove('hidden');
        });
    }
    
    // Container Option Selection
    const containerOptions = document.querySelectorAll('.container-option');
    containerOptions.forEach(option => {
        option.addEventListener('click', function() {
            containerOptions.forEach(opt => opt.classList.remove('border-navy', 'shadow-md'));
            this.classList.add('border-navy', 'shadow-md');
            selectedOptions.container = {
                name: this.getAttribute('data-name'),
                price: parseInt(this.getAttribute('data-price'))
            };
            updateSummary();
        });
    });
    
    // Urn Option Selection
    const urnOptions = document.querySelectorAll('.urn-option');
    urnOptions.forEach(option => {
        option.addEventListener('click', function() {
            urnOptions.forEach(opt => opt.classList.remove('border-navy', 'shadow-md'));
            this.classList.add('border-navy', 'shadow-md');
            selectedOptions.urn = {
                name: this.getAttribute('data-name'),
                price: parseInt(this.getAttribute('data-price'))
            };
            updateSummary();
        });
    });
    
    // Casket Option Selection (for traditional)
    const casketOptions = document.querySelectorAll('.casket-option');
    casketOptions.forEach(option => {
        option.addEventListener('click', function() {
            casketOptions.forEach(opt => opt.classList.remove('border-navy', 'shadow-md'));
            this.classList.add('border-navy', 'shadow-md');
            selectedOptions.casket = {
                name: this.getAttribute('data-name'),
                price: parseInt(this.getAttribute('data-price'))
            };
            updateSummary();
        });
    });
    
    // Viewing Period Selection (for traditional)
    const viewingOptions = document.querySelectorAll('.viewing-option');
    viewingOptions.forEach(option => {
        option.addEventListener('click', function() {
            viewingOptions.forEach(opt => opt.classList.remove('border-navy', 'shadow-md'));
            this.classList.add('border-navy', 'shadow-md');
            selectedOptions.viewing = {
                name: this.getAttribute('data-name'),
                price: parseInt(this.getAttribute('data-price'))
            };
            updateSummary();
        });
    });
    
    // Flower Arrangement Selection (for traditional)
    const flowerOptions = document.querySelectorAll('.flower-option');
    flowerOptions.forEach(option => {
        option.addEventListener('click', function() {
            flowerOptions.forEach(opt => opt.classList.remove('border-navy', 'shadow-md'));
            this.classList.add('border-navy', 'shadow-md');
            selectedOptions.flowers = {
                name: this.getAttribute('data-name'),
                price: parseInt(this.getAttribute('data-price'))
            };
            updateSummary();
        });
    });
    
    // Additional Services Selection
    const addonOptions = document.querySelectorAll('.cremation-addon');
    addonOptions.forEach(option => {
        option.addEventListener('change', function() {
            const addonName = this.getAttribute('data-name');
            const addonPrice = parseInt(this.value);
            
            if (this.checked) {
                selectedOptions.addons.push({
                    name: addonName,
                    price: addonPrice
                });
            } else {
                selectedOptions.addons = selectedOptions.addons.filter(addon => addon.name !== addonName);
            }
            
            updateSummary();
        });
    });
    
    // Payment term selection for Lifeplan
    const cremationPaymentTerm = document.getElementById('cremationPaymentTerm');
    if (cremationPaymentTerm) {
        cremationPaymentTerm.addEventListener('change', function() {
            updateSummary();
        });
    }
    
    // Update package summary and pricing
    function updateSummary() {
        const summaryElement = document.getElementById('cremationSelectionsSummary');
        const totalPriceElement = document.getElementById('cremationTotalPrice');
        const downpaymentElement = document.getElementById('cremationDownpayment');
        const monthlyPaymentElement = document.getElementById('cremationMonthlyPayment');
        
        let summaryHTML = '';
        totalPrice = 0;
        
        // Add cremation type to summary
        if (selectedCremationType === 'direct') {
            summaryHTML += '<p class="font-medium">Direct Cremation</p>';
            
            // Add container to summary if selected
            if (selectedOptions.container) {
                summaryHTML += `<p>- ${selectedOptions.container.name}: ₱${selectedOptions.container.price.toLocaleString()}</p>`;
                totalPrice += selectedOptions.container.price;
            }
        } else if (selectedCremationType === 'traditional') {
            summaryHTML += '<p class="font-medium">Traditional Cremation with Wake</p>';
            
            // Add casket to summary if selected
            if (selectedOptions.casket) {
                summaryHTML += `<p>- ${selectedOptions.casket.name}: ₱${selectedOptions.casket.price.toLocaleString()}</p>`;
                totalPrice += selectedOptions.casket.price;
            }
            
            // Add viewing period to summary if selected
            if (selectedOptions.viewing) {
                summaryHTML += `<p>- ${selectedOptions.viewing.name}: ₱${selectedOptions.viewing.price.toLocaleString()}</p>`;
                totalPrice += selectedOptions.viewing.price;
            }
            
            // Add flowers to summary if selected
            if (selectedOptions.flowers) {
                summaryHTML += `<p>- ${selectedOptions.flowers.name}: ₱${selectedOptions.flowers.price.toLocaleString()}</p>`;
                totalPrice += selectedOptions.flowers.price;
            }
        }
        
        // Add urn to summary if selected
        if (selectedOptions.urn) {
            summaryHTML += `<p>- ${selectedOptions.urn.name}: ₱${selectedOptions.urn.price.toLocaleString()}</p>`;
            totalPrice += selectedOptions.urn.price;
        }
        
        // Add additional services to summary
        if (selectedOptions.addons.length > 0) {
            summaryHTML += '<p class="font-medium mt-2">Additional Services:</p>';
            selectedOptions.addons.forEach(addon => {
                summaryHTML += `<p>- ${addon.name}: ₱${addon.price.toLocaleString()}</p>`;
                totalPrice += addon.price;
            });
        }
        
        // Update summary element
        if (summaryElement) {
            if (summaryHTML === '') {
                summaryElement.innerHTML = '<p class="text-gray-500 italic">No items selected yet</p>';
            } else {
                summaryElement.innerHTML = summaryHTML;
            }
        }
        
        // Update total price
        if (totalPriceElement) {
            totalPriceElement.textContent = '₱' + totalPrice.toLocaleString();
        }
        
        // Update downpayment for immediate service
        if (downpaymentElement) {
            const downpayment = totalPrice * 0.3;
            downpaymentElement.textContent = '₱' + downpayment.toLocaleString();
        }
        
        // Update monthly payment for lifeplan
        if (monthlyPaymentElement && cremationPaymentTerm) {
            const months = parseInt(cremationPaymentTerm.value);
            const monthlyPayment = totalPrice / months;
            monthlyPaymentElement.textContent = '₱' + monthlyPayment.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        
        // Enable/disable proceed button based on selections
        const hasMinimumSelections = 
            (selectedCremationType === 'direct' && selectedOptions.container && selectedOptions.urn) ||
            (selectedCremationType === 'traditional' && selectedOptions.casket && selectedOptions.viewing && selectedOptions.urn);
        
        if (cremationProceedToBookingBtn) {
            cremationProceedToBookingBtn.disabled = !hasMinimumSelections;
        }
    }
    
    // Set up form submission
    function setupFormSubmission(form) {
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Here you would typically send the form data to a server
                // For this example, we'll just show an alert
                alert('Thank you for your booking! Our team will contact you shortly.');
                
                // Close the modal
                customCremationModal.classList.add('hidden');
            });
        }
    }
    
    // Reset the form to initial state
    function resetForm() {
        // Reset selections
        selectedCremationType = null;
        selectedServiceType = null;
        selectedOptions = {
            container: null,
            urn: null,
            casket: null,
            viewing: null,
            flowers: null,
            addons: []
        };
        totalPrice = 0;
        
        // Reset UI - remove selection highlights
        document.querySelectorAll('.cremation-type-option, .service-option, .container-option, .urn-option, .casket-option, .viewing-option, .flower-option')
            .forEach(element => {
                element.classList.remove('border-navy', 'shadow-lg', 'shadow-md');
            });
        
        // Uncheck all addon checkboxes
        document.querySelectorAll('.cremation-addon').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Reset to first step
        cremationStepType.classList.remove('hidden');
        cremationStepServiceType.classList.add('hidden');
        cremationStepOptions.classList.add('hidden');
        cremationStepBooking.classList.add('hidden');
        
        // Disable next buttons
    nextToServiceTypeBtn.disabled = true;
    nextToOptionsBtn.disabled = true; // This ensures it's disabled when the modal opens
    cremationProceedToBookingBtn.disabled = true;
        
        // Reset summary
        const summaryElement = document.getElementById('cremationSelectionsSummary');
        if (summaryElement) {
            summaryElement.innerHTML = '<p class="text-gray-500 italic">No items selected yet</p>';
        }
        
        // Reset total price and payment displays
        const totalPriceElement = document.getElementById('cremationTotalPrice');
        if (totalPriceElement) {
            totalPriceElement.textContent = '₱0';
        }
        
        const downpaymentElement = document.getElementById('cremationDownpayment');
        if (downpaymentElement) {
            downpaymentElement.textContent = '₱0';
        }
        
        const monthlyPaymentElement = document.getElementById('cremationMonthlyPayment');
        if (monthlyPaymentElement) {
            monthlyPaymentElement.textContent = '₱0';
        }
    }
});