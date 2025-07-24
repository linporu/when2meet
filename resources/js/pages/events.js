// Event Pages JavaScript
import '../pages/timezone.js';

document.addEventListener('DOMContentLoaded', function() {
    // Initialize event form functionality
    initEventForm();
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
        showFieldError(nameInput, '請輸入活動名稱');
        return false;
    } else if (value.length > 255) {
        showFieldError(nameInput, '活動名稱不能超過 255 個字符');
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
        showFieldError(dateInput, '請選擇日期');
        return false;
    } else if (value < today) {
        showFieldError(dateInput, '不能選擇過去的日期');
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
        showFieldError(endTimeInput, '結束時間必須晚於開始時間');
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