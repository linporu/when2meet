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

        const errorDiv = document.createElement("div");
        errorDiv.className = "text-red-600 text-sm mt-1";
        errorDiv.textContent = message;
        errorDiv.id = input.id + "_error";

        input.parentNode.appendChild(errorDiv);
        input.classList.add("border-red-500");
    }

    /**
     * Clear error message for a form field
     */
    static clearFieldError(input) {
        const errorDiv = document.getElementById(input.id + "_error");
        if (errorDiv) {
            errorDiv.remove();
        }
        input.classList.remove("border-red-500");
    }

    /**
     * Show error for a time range selector
     */
    static showTimeRangeError(timeRangeSelector, message) {
        const errorContainer = timeRangeSelector.querySelector(".error-message");
        const startSelect = timeRangeSelector.querySelector(".start-time");
        const endSelect = timeRangeSelector.querySelector(".end-time");

        errorContainer.textContent = message;
        errorContainer.classList.remove("hidden");

        startSelect.classList.add("border-red-500");
        endSelect.classList.add("border-red-500");
    }

    /**
     * Clear error for a time range selector
     */
    static clearTimeRangeError(timeRangeSelector) {
        const errorContainer = timeRangeSelector.querySelector(".error-message");
        const startSelect = timeRangeSelector.querySelector(".start-time");
        const endSelect = timeRangeSelector.querySelector(".end-time");

        errorContainer.textContent = "";
        errorContainer.classList.add("hidden");

        startSelect.classList.remove("border-red-500");
        endSelect.classList.remove("border-red-500");
    }

    /**
     * Validate required field
     */
    static validateRequired(input, fieldName = "This field") {
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
    static validateLength(input, minLength = 0, maxLength = null, fieldName = "This field") {
        const value = input.value.trim();
        
        if (value.length < minLength) {
            this.showFieldError(input, `${fieldName} must be at least ${minLength} characters`);
            return false;
        }
        
        if (maxLength && value.length > maxLength) {
            this.showFieldError(input, `${fieldName} cannot exceed ${maxLength} characters`);
            return false;
        }
        
        this.clearFieldError(input);
        return true;
    }

    /**
     * Validate date field
     */
    static validateDate(input, allowPastDates = false) {
        const value = input.value;
        
        if (!value) {
            this.showFieldError(input, "Please select a date");
            return false;
        }
        
        if (!allowPastDates) {
            const today = new Date().toISOString().split("T")[0];
            if (value < today) {
                this.showFieldError(input, "Cannot select a past date");
                return false;
            }
        }
        
        this.clearFieldError(input);
        return true;
    }

    /**
     * Validate time range (start time must be before end time)
     */
    static validateTimeRange(startTimeInput, endTimeInput) {
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;

        if (startTime && endTime && startTime >= endTime) {
            this.showFieldError(endTimeInput, "End time must be later than start time");
            return false;
        }

        this.clearFieldError(startTimeInput);
        this.clearFieldError(endTimeInput);
        return true;
    }

    /**
     * Validate participant name
     */
    static validateParticipantName(input) {
        const value = input.value.trim();

        if (!value) {
            this.showFieldError(input, "Please enter your name");
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
            this.showFieldError(input, "Please enter an event name");
            return false;
        } else if (value.length > 255) {
            this.showFieldError(input, "Event name cannot exceed 255 characters");
            return false;
        }

        this.clearFieldError(input);
        return true;
    }

    /**
     * Validate a single time range selector
     */
    static validateTimeRangeSelector(timeRangeSelector) {
        const startSelect = timeRangeSelector.querySelector(".start-time");
        const endSelect = timeRangeSelector.querySelector(".end-time");

        const startTime = startSelect.value;
        const endTime = endSelect.value;

        // Clear previous errors
        this.clearTimeRangeError(timeRangeSelector);

        // Check if both times are empty
        if (!startTime && !endTime) {
            this.showTimeRangeError(
                timeRangeSelector,
                "Please select both start time and end time"
            );
            return false;
        }

        // Check if only one time is selected
        if (!startTime && endTime) {
            this.showTimeRangeError(
                timeRangeSelector,
                "Please select start time"
            );
            return false;
        }

        if (startTime && !endTime) {
            this.showTimeRangeError(
                timeRangeSelector,
                "Please select end time"
            );
            return false;
        }

        // Both times are selected, validate time logic
        if (startTime && endTime) {
            if (startTime >= endTime) {
                this.showTimeRangeError(
                    timeRangeSelector,
                    "End time must be later than start time"
                );
                return false;
            }
        }

        return true;
    }
}