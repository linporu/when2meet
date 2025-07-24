// Event Pages JavaScript
import { TimezoneConverter } from './timezone.js';

document.addEventListener('DOMContentLoaded', function() {
    // Initialize event form functionality
    initEventForm();
    
    // Initialize timezone display functionality
    initTimezoneDisplay();
});

function initEventForm() {
    const form = document.getElementById('event-form');
    if (!form) return;

    // Form validation
    const nameInput = document.getElementById('event_name');
    const dateInput = document.getElementById('date');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');

    // Real-time validation
    if (nameInput) {
        nameInput.addEventListener('blur', validateEventName);
    }

    if (dateInput) {
        dateInput.addEventListener('change', validateDate);
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
    }

    if (startTimeInput && endTimeInput) {
        startTimeInput.addEventListener('change', validateTimeRange);
        endTimeInput.addEventListener('change', validateTimeRange);
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });
}

function validateEventName() {
    const nameInput = document.getElementById('event_name');
    const value = nameInput.value.trim();
    
    if (value.length < 1) {
        showFieldError(nameInput, 'Please enter an event name');
        return false;
    } else if (value.length > 255) {
        showFieldError(nameInput, 'Event name cannot exceed 255 characters');
        return false;
    }
    
    clearFieldError(nameInput);
    return true;
}

function validateDate() {
    const dateInput = document.getElementById('date');
    const value = dateInput.value;
    const today = new Date().toISOString().split('T')[0];
    
    if (!value) {
        showFieldError(dateInput, 'Please select a date');
        return false;
    } else if (value < today) {
        showFieldError(dateInput, 'Cannot select a past date');
        return false;
    }
    
    clearFieldError(dateInput);
    return true;
}

function validateTimeRange() {
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const startTime = startTimeInput.value;
    const endTime = endTimeInput.value;
    
    if (startTime && endTime && startTime >= endTime) {
        showFieldError(endTimeInput, 'End time must be later than start time');
        return false;
    }
    
    clearFieldError(startTimeInput);
    clearFieldError(endTimeInput);
    return true;
}

function validateForm() {
    const isNameValid = validateEventName();
    const isDateValid = validateDate();
    const isTimeRangeValid = validateTimeRange();
    
    return isNameValid && isDateValid && isTimeRangeValid;
}

function showFieldError(input, message) {
    clearFieldError(input);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-600 text-sm mt-1';
    errorDiv.textContent = message;
    errorDiv.id = input.id + '_error';
    
    input.parentNode.appendChild(errorDiv);
    input.classList.add('border-red-500');
}

function clearFieldError(input) {
    const errorDiv = document.getElementById(input.id + '_error');
    if (errorDiv) {
        errorDiv.remove();
    }
    input.classList.remove('border-red-500');
}

/**
 * Initializes timezone display functionality
 * Handles timezone conversion display on the page
 */
function initTimezoneDisplay() {
    // Check if there are timezone conversion elements on the page
    const timezoneElements = document.querySelectorAll('.timezone-display');
    if (timezoneElements.length === 0) return;
    
    try {
        // Create a TimezoneConverter instance
        const converter = new TimezoneConverter();
        
        // Initialize page timezone conversion
        converter.initializePageTimezone();
        
        console.log(`Initialized ${timezoneElements.length} timezone display elements`);
    } catch (error) {
        console.error('Timezone display initialization failed:', error);
        
        // Fallback: display original UTC time
        timezoneElements.forEach(element => {
            const utcStart = element.getAttribute('data-utc-start');
            const utcEnd = element.getAttribute('data-utc-end');
            if (utcStart && utcEnd) {
                // Update timezone label to show UTC
                const container = element.closest('.rounded-lg');
                const timezoneLabel = container?.querySelector('.timezone-label');
                if (timezoneLabel) {
                    timezoneLabel.textContent = 'UTC';
                }
                
                // Update time display only (no HTML generation)
                element.textContent = `${utcStart.substring(0, 5)} - ${utcEnd.substring(0, 5)}`;
            }
        });
    }
}