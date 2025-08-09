/**
 * Form Validation Utilities
 * Common validation logic and error handling for forms
 */

export class FormValidator {
    /**
     * Show error message for a form field
     */
    static showFieldError(input, message) {
        this.clearFieldError(input);

        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-600 text-sm mt-1';
        errorDiv.textContent = message;
        errorDiv.id = input.id + '_error';

        input.parentNode.appendChild(errorDiv);
        input.classList.add('border-red-500');
    }

    /**
     * Clear error message for a form field
     */
    static clearFieldError(input) {
        const errorDiv = document.getElementById(input.id + '_error');
        if (errorDiv) {
            errorDiv.remove();
        }
        input.classList.remove('border-red-500');
    }

    /**
     * Show error for a time range selector
     */
    static showTimeRangeError(timeRangeSelector, message) {
        const errorContainer =
            timeRangeSelector.querySelector('.error-message');
        const startSelect = timeRangeSelector.querySelector('.start-time');
        const endSelect = timeRangeSelector.querySelector('.end-time');

        errorContainer.textContent = message;
        errorContainer.classList.remove('hidden');

        startSelect.classList.add('border-red-500');
        endSelect.classList.add('border-red-500');
    }

    /**
     * Clear error for a time range selector
     */
    static clearTimeRangeError(timeRangeSelector) {
        const errorContainer =
            timeRangeSelector.querySelector('.error-message');
        const startSelect = timeRangeSelector.querySelector('.start-time');
        const endSelect = timeRangeSelector.querySelector('.end-time');

        errorContainer.textContent = '';
        errorContainer.classList.add('hidden');

        startSelect.classList.remove('border-red-500');
        endSelect.classList.remove('border-red-500');
    }

    /**
     * Validate required field
     */
    static validateRequired(input, fieldName = 'This field') {
        const value = input.value.trim();

        if (!value) {
            this.showFieldError(input, `${fieldName} is required`);
            return false;
        }

        this.clearFieldError(input);
        return true;
    }

    /**
     * Validate text length
     */
    static validateLength(
        input,
        minLength = 0,
        maxLength = null,
        fieldName = 'This field'
    ) {
        const value = input.value.trim();

        if (value.length < minLength) {
            this.showFieldError(
                input,
                `${fieldName} must be at least ${minLength} characters`
            );
            return false;
        }

        if (maxLength && value.length > maxLength) {
            this.showFieldError(
                input,
                `${fieldName} cannot exceed ${maxLength} characters`
            );
            return false;
        }

        this.clearFieldError(input);
        return true;
    }

    /**
     * Validate date field with strict validation
     * Requirements: not empty, year <= 2200, valid month/day, not before today
     */
    static validateDate(input, allowPastDates = false) {
        const value = input.value;

        // 1. Cannot be empty
        if (!value) {
            this.showFieldError(input, 'Please enter a valid date');
            return false;
        }

        // Strict format validation: YYYY-MM-DD only
        const datePattern = /^(\d{4})-(\d{2})-(\d{2})$/;
        const match = value.match(datePattern);

        if (!match) {
            this.showFieldError(input, 'Please enter a valid date');
            return false;
        }

        const year = parseInt(match[1]);
        const month = parseInt(match[2]);
        const day = parseInt(match[3]);

        // 2. Year must be within 2200
        if (year > 2200) {
            this.showFieldError(input, 'Please enter a valid date');
            return false;
        }

        // 3. Month must be 1-12
        if (month < 1 || month > 12) {
            this.showFieldError(input, 'Please enter a valid date');
            return false;
        }

        // 4. Day must be 1 to max day of the month (considering leap years)
        const daysInMonth = this._getDaysInMonth(year, month);
        if (day < 1 || day > daysInMonth) {
            this.showFieldError(input, 'Please enter a valid date');
            return false;
        }

        // 5. Date cannot be before today (today is allowed)
        if (!allowPastDates) {
            const today = new Date();
            const inputDate = new Date(year, month - 1, day); // month is 0-indexed in Date constructor

            // Compare only the date part (ignore time)
            const todayDateOnly = new Date(
                today.getFullYear(),
                today.getMonth(),
                today.getDate()
            );

            if (inputDate < todayDateOnly) {
                // Only dates before today are considered past dates
                this.showFieldError(input, 'Please enter a valid date');
                return false;
            }
        }

        this.clearFieldError(input);
        return true;
    }

    /**
     * Validate time range (both times required, start time must be before end time)
     */
    static validateTimeRange(startTimeInput, endTimeInput) {
        const startTime = startTimeInput.value.trim();
        const endTime = endTimeInput.value.trim();

        // Clear previous errors
        this.clearFieldError(startTimeInput);
        this.clearFieldError(endTimeInput);

        // Both times are required for a valid time range
        if (!startTime && !endTime) {
            this.showFieldError(
                startTimeInput,
                'Please select both start and end times'
            );
            return false;
        }

        if (!startTime) {
            this.showFieldError(startTimeInput, 'Please select start time');
            return false;
        }

        if (!endTime) {
            this.showFieldError(endTimeInput, 'Please select end time');
            return false;
        }

        // Validate time logic - start must be before end
        if (startTime >= endTime) {
            this.showFieldError(
                endTimeInput,
                'End time must be later than start time'
            );
            return false;
        }

        return true;
    }

    /**
     * Validate participant name
     */
    static validateParticipantName(input) {
        const value = input.value.trim();

        if (!value) {
            this.showFieldError(input, 'Please enter your name');
            return false;
        }

        this.clearFieldError(input);
        return true;
    }

    /**
     * Validate event name
     */
    static validateEventName(input) {
        const value = input.value.trim();

        if (value.length < 1) {
            this.showFieldError(input, 'Please enter an event name');
            return false;
        } else if (value.length > 255) {
            this.showFieldError(
                input,
                'Event name cannot exceed 255 characters'
            );
            return false;
        }

        this.clearFieldError(input);
        return true;
    }

    /**
     * Validate a single time range selector
     */
    static validateTimeRangeSelector(timeRangeSelector) {
        const startSelect = timeRangeSelector.querySelector('.start-time');
        const endSelect = timeRangeSelector.querySelector('.end-time');

        const startTime = startSelect.value;
        const endTime = endSelect.value;

        // Clear previous errors
        this.clearTimeRangeError(timeRangeSelector);

        // Check if both times are empty
        if (!startTime && !endTime) {
            this.showTimeRangeError(
                timeRangeSelector,
                'Please select both start time and end time'
            );
            return false;
        }

        // Check if only one time is selected
        if (!startTime && endTime) {
            this.showTimeRangeError(
                timeRangeSelector,
                'Please select start time'
            );
            return false;
        }

        if (startTime && !endTime) {
            this.showTimeRangeError(
                timeRangeSelector,
                'Please select end time'
            );
            return false;
        }

        // Both times are selected, validate time logic
        if (startTime && endTime) {
            if (startTime >= endTime) {
                this.showTimeRangeError(
                    timeRangeSelector,
                    'End time must be later than start time'
                );
                return false;
            }
        }

        return true;
    }

    /**
     * General showError method (alias for showFieldError)
     */
    static showError(element, message) {
        if (element.classList.contains('time-range-selector')) {
            this.showTimeRangeError(element, message);
        } else {
            this.showFieldError(element, message);
        }
    }

    /**
     * General clearError method (alias for clearFieldError)
     */
    static clearError(element) {
        if (element.classList.contains('time-range-selector')) {
            this.clearTimeRangeError(element);
        } else {
            this.clearFieldError(element);
        }
    }

    /**
     * Validate entire form with validation rules
     */
    static validateForm(form, rules = {}) {
        let isValid = true;

        // Clear all previous errors
        form.querySelectorAll('.border-red-500').forEach((el) => {
            el.classList.remove('border-red-500');
        });
        form.querySelectorAll('[id$="_error"]').forEach((el) => {
            el.remove();
        });

        // Validate each field based on rules
        for (const [fieldName, fieldRules] of Object.entries(rules)) {
            const input = form.querySelector(`[name="${fieldName}"]`);
            if (!input) {
                continue;
            }

            for (const rule of fieldRules) {
                if (
                    rule === 'required' &&
                    !this.validateRequired(input, fieldName)
                ) {
                    isValid = false;
                    break;
                }
                // Add more rule types as needed
            }
        }

        // Validate time range selectors
        const timeRangeSelectors = form.querySelectorAll(
            '.time-range-selector'
        );
        timeRangeSelectors.forEach((selector) => {
            if (!this.validateTimeRangeSelector(selector)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Helper method to get days in a specific month/year
     * Handles leap years correctly
     */
    static _getDaysInMonth(year, month) {
        const daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        // Handle February in leap years
        if (month === 2 && this._isLeapYear(year)) {
            return 29;
        }

        return daysInMonth[month - 1];
    }

    /**
     * Helper method to check if a year is a leap year
     */
    static _isLeapYear(year) {
        return (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
    }
}
