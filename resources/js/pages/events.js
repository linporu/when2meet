/**
 * Event Pages JavaScript - Refactored
 * Now uses modular components and utilities for better maintainability
 */

import { TimezoneConverter } from "../components/timezone-converter.js";
import { AvailabilityForm } from "./availability.js";
import { FormValidator } from "../utils/form-validator.js";
import { DOMHelpers } from "../utils/dom-helpers.js";
import { ErrorHandler } from "../utils/error-handler.js";

document.addEventListener("DOMContentLoaded", function () {
    // Initialize all page functionality
    initEventForm();
    initTimezoneDisplay();
    initAvailabilityForm();
});

/**
 * Initialize event form functionality
 */
function initEventForm() {
    const form = DOMHelpers.querySelector("#event-form");
    if (!form) return;

    ErrorHandler.safeExecute(() => {
        setupFormValidation(form);
        setupFormSubmission(form);
        console.log("Event form initialized");
    }, 'Event Form Initialization');
}

/**
 * Setup form validation with real-time feedback
 */
function setupFormValidation(form) {
    const nameInput = DOMHelpers.querySelector("#event_name");
    const dateInput = DOMHelpers.querySelector("#date");
    const startTimeInput = DOMHelpers.querySelector("#start_time");
    const endTimeInput = DOMHelpers.querySelector("#end_time");

    // Real-time validation
    if (nameInput) {
        nameInput.addEventListener("blur", () => validateEventName(nameInput));
    }

    if (dateInput) {
        dateInput.addEventListener("change", () => validateDate(dateInput));
        // Set minimum date to today
        const today = new Date().toISOString().split("T")[0];
        dateInput.min = today;
    }

    if (startTimeInput && endTimeInput) {
        startTimeInput.addEventListener("change", () => validateTimeRange(startTimeInput, endTimeInput));
        endTimeInput.addEventListener("change", () => validateTimeRange(startTimeInput, endTimeInput));
    }
}

/**
 * Setup form submission with comprehensive validation
 */
function setupFormSubmission(form) {
    form.addEventListener("submit", function (e) {
        if (!validateEventForm()) {
            e.preventDefault();
        }
    });
}

/**
 * Validate event name using FormValidator utility
 */
function validateEventName(nameInput) {
    return ErrorHandler.safeExecute(() => {
        return FormValidator.validateEventName(nameInput);
    }, 'Event Name Validation', false);
}

/**
 * Validate date using FormValidator utility
 */
function validateDate(dateInput) {
    return ErrorHandler.safeExecute(() => {
        return FormValidator.validateDate(dateInput, false);
    }, 'Date Validation', false);
}

/**
 * Validate time range using FormValidator utility
 */
function validateTimeRange(startTimeInput, endTimeInput) {
    return ErrorHandler.safeExecute(() => {
        return FormValidator.validateTimeRange(startTimeInput, endTimeInput);
    }, 'Time Range Validation', false);
}

/**
 * Validate the entire event form
 */
function validateEventForm() {
    return ErrorHandler.safeExecute(() => {
        const nameInput = DOMHelpers.querySelector("#event_name");
        const dateInput = DOMHelpers.querySelector("#date");
        const startTimeInput = DOMHelpers.querySelector("#start_time");
        const endTimeInput = DOMHelpers.querySelector("#end_time");

        const isNameValid = validateEventName(nameInput);
        const isDateValid = validateDate(dateInput);
        const isTimeRangeValid = validateTimeRange(startTimeInput, endTimeInput);

        return isNameValid && isDateValid && isTimeRangeValid;
    }, 'Event Form Validation', false);
}

/**
 * Initialize timezone display functionality
 * Handles timezone conversion display on the page
 */
function initTimezoneDisplay() {
    return ErrorHandler.safeExecute(() => {
        // Check if there are timezone conversion elements on the page
        const timezoneElements = document.querySelectorAll(".timezone-display");
        if (timezoneElements.length === 0) return;

        // Create a TimezoneConverter instance
        const converter = new TimezoneConverter();

        // Initialize page timezone conversion
        converter.initializePageTimezone();

        console.log(`Initialized ${timezoneElements.length} timezone display elements`);
    }, 'Timezone Display Initialization');
}

/**
 * Initialize availability form functionality
 */
function initAvailabilityForm() {
    return ErrorHandler.safeExecute(() => {
        const availabilityForm = new AvailabilityForm();
        availabilityForm.initialize();
    }, 'Availability Form Initialization');
}
