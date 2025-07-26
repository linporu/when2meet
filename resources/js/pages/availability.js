// Availability Form JavaScript
import { TimezoneConverter } from "./timezone.js";

export class AvailabilityForm {
    constructor() {
        this.form = null;
        this.timezoneConverter = new TimezoneConverter();
    }

    /**
     * Convert UTC time options to local time format
     */
    convertTimeOptionsToLocal(timeOptions) {
        if (Array.isArray(timeOptions)) {
            return timeOptions;
        }

        const convertedOptions = {};
        for (const [utcTime, displayText] of Object.entries(timeOptions)) {
            const localTime = this.timezoneConverter.convertUtcToLocal(utcTime);
            // Create a more readable format for local time display
            const localDisplayTime = new Date(`1970-01-01T${this.timezoneConverter.normalizeTimeString(utcTime)}Z`)
                .toLocaleTimeString("en-US", {
                    hour12: true,
                    hour: "numeric",
                    minute: "2-digit"
                });
            convertedOptions[localTime] = localDisplayTime;
        }
        return convertedOptions;
    }

    /**
     * Initialize availability form functionality
     */
    initialize() {
        this.form = document.getElementById("availability-form");
        if (!this.form) return;

        // Initialize all functionality
        this.initAddTimeRangeButtons();
        this.initRemoveTimeRangeButtons();
        this.initTimeRangeValidation();
        this.initFormSubmission();
        
        // Render initial time ranges from server data
        this.renderInitialTimeRanges();

        console.log("Availability form initialized");
    }

    /**
     * Render initial time ranges from server data
     */
    renderInitialTimeRanges() {
        const timeRangeLists = this.form.querySelectorAll(".time-range-list");
        
        timeRangeLists.forEach(timeRangeList => {
            const container = timeRangeList.querySelector(".time-ranges-container");
            const date = timeRangeList.dataset.date;
            
            // Parse data from container
            const existingRanges = JSON.parse(container.dataset.existingRanges || "[]");
            const timeOptions = JSON.parse(container.dataset.timeOptions || "[]");
            
            // Convert UTC time options to local time
            const localTimeOptions = this.convertTimeOptionsToLocal(timeOptions);
            
            // Clear container
            container.innerHTML = "";
            
            // Render time ranges
            if (existingRanges.length > 0) {
                // Render existing ranges
                existingRanges.forEach((range, index) => {
                    // Convert existing UTC times to local times for proper option selection
                    const localStartTime = range.start_time ? 
                        this.timezoneConverter.convertUtcToLocal(range.start_time) : "";
                    const localEndTime = range.end_time ? 
                        this.timezoneConverter.convertUtcToLocal(range.end_time) : "";
                        
                    const timeRangeHTML = this.createTimeRangeSelectorHTML(
                        date, 
                        index, 
                        localTimeOptions,
                        localStartTime,
                        localEndTime
                    );
                    container.insertAdjacentHTML("beforeend", timeRangeHTML);
                });
            } else {
                // Render default empty time range
                const timeRangeHTML = this.createTimeRangeSelectorHTML(
                    date, 
                    0, 
                    localTimeOptions
                );
                container.insertAdjacentHTML("beforeend", timeRangeHTML);
            }
            
            // Update remove buttons visibility
            this.updateRemoveButtonsVisibility(container);
        });
    }

    /**
     * Initialize add time range button functionality
     */
    initAddTimeRangeButtons() {
        document.addEventListener("click", (e) => {
            if (
                e.target.matches(".add-time-range") ||
                e.target.closest(".add-time-range")
            ) {
                e.preventDefault();

                const button = e.target.matches(".add-time-range")
                    ? e.target
                    : e.target.closest(".add-time-range");
                const timeRangeList = button.closest(".time-range-list");
                const date = timeRangeList.dataset.date;

                this.addNewTimeRange(timeRangeList, date);
            }
        });
    }

    /**
     * Initialize remove time range button functionality
     */
    initRemoveTimeRangeButtons() {
        document.addEventListener("click", (e) => {
            if (
                e.target.matches(".remove-time-range") ||
                e.target.closest(".remove-time-range")
            ) {
                e.preventDefault();

                const button = e.target.matches(".remove-time-range")
                    ? e.target
                    : e.target.closest(".remove-time-range");
                const timeRangeSelector = button.closest(
                    ".time-range-selector",
                );

                this.removeTimeRange(timeRangeSelector);
            }
        });
    }

    /**
     * Initialize time range validation
     */
    initTimeRangeValidation() {
        document.addEventListener("change", (e) => {
            if (e.target.matches(".time-select")) {
                this.validateTimeRangeSelector(
                    e.target.closest(".time-range-selector"),
                );
            }
        });
    }

    /**
     * Initialize form submission validation
     */
    initFormSubmission() {
        this.form.addEventListener("submit", (e) => {
            e.preventDefault();
            
            if (!this.validateAvailabilityForm()) {
                return;
            }
            
            // Convert local times to UTC using hidden fields
            const timeSelects = this.form.querySelectorAll(".time-select");
            
            timeSelects.forEach(select => {
                if (select.value) {
                    const utcTime = this.convertLocalToUtc(select.value);
                    
                    // Create hidden field with UTC time
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = select.name; // Same name as select
                    hiddenInput.value = utcTime;
                    this.form.appendChild(hiddenInput);
                    
                    // Clear original select name to avoid duplicate submission
                    select.name = '';
                }
            });
            
            // Submit the form normally (maintains Laravel flow)
            this.form.submit();
        });
    }


    /**
     * Convert local time back to UTC
     */
    convertLocalToUtc(localTimeString) {
        try {
            const normalized = this.timezoneConverter.normalizeTimeString(localTimeString);
            
            // Create a proper local date object
            const [hours, minutes] = normalized.split(':').map(Number);
            const localDate = new Date();
            localDate.setHours(hours, minutes, 0, 0);
            
            // Convert to UTC and format as HH:MM
            const utcHours = localDate.getUTCHours().toString().padStart(2, '0');
            const utcMinutes = localDate.getUTCMinutes().toString().padStart(2, '0');
            return `${utcHours}:${utcMinutes}`;
        } catch (error) {
            console.error("Local to UTC conversion failed:", error, localTimeString);
            return localTimeString; // Return original time as fallback
        }
    }

    /**
     * Add a new time range selector
     */
    addNewTimeRange(timeRangeList, date) {
        const container = timeRangeList.querySelector(".time-ranges-container");
        const existingRanges = container.querySelectorAll(
            ".time-range-selector",
        );
        const newIndex = existingRanges.length;

        // Get time options from container data
        const timeOptions = JSON.parse(container.dataset.timeOptions || "[]");
        
        // Convert UTC time options to local time
        const localTimeOptions = this.convertTimeOptionsToLocal(timeOptions);

        // Create new time range selector HTML
        const newTimeRangeHTML = this.createTimeRangeSelectorHTML(
            date,
            newIndex,
            localTimeOptions
        );

        // Append to container
        container.insertAdjacentHTML("beforeend", newTimeRangeHTML);

        // Update existing ranges to show remove buttons if there are now multiple ranges
        this.updateRemoveButtonsVisibility(container);
    }

    /**
     * Remove a time range selector
     */
    removeTimeRange(timeRangeSelector) {
        const container = timeRangeSelector.closest(".time-ranges-container");

        // Remove the selector
        timeRangeSelector.remove();

        // Reindex remaining selectors
        this.reindexTimeRangeSelectors(container);

        // Update remove buttons visibility
        this.updateRemoveButtonsVisibility(container);
    }

    /**
     * Create HTML for a new time range selector
     */
    createTimeRangeSelectorHTML(date, index, timeOptions, startTime = "", endTime = "") {
        // Convert timeOptions object to array format if needed
        const timeOptionsArray = Array.isArray(timeOptions) 
            ? timeOptions 
            : Object.entries(timeOptions).map(([value, text]) => ({ value, text }));

        // Generate options HTML for start time select
        const startOptionsHTML = timeOptionsArray
            .map(
                (option) => {
                    // Handle both DOM option elements and processed objects
                    const value = option.value || option;
                    const text = option.text || option.textContent || option;
                    const selected = value === startTime ? "selected" : "";
                    return `<option value="${value}" ${selected}>${text}</option>`;
                }
            )
            .join("");
            
        // Generate options HTML for end time select
        const endOptionsHTML = timeOptionsArray
            .map(
                (option) => {
                    // Handle both DOM option elements and processed objects
                    const value = option.value || option;
                    const text = option.text || option.textContent || option;
                    const selected = value === endTime ? "selected" : "";
                    return `<option value="${value}" ${selected}>${text}</option>`;
                }
            )
            .join("");

        return `
            <div class="time-range-selector mb-3 rounded border border-gray-200 bg-gray-50 p-3" data-index="${index}">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="flex-1">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Start Time <span class="text-red-500">*</span></label>
                        <select name="availability[${date}][${index}][start_time]" 
                                class="time-select start-time w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                data-date="${date}" data-index="${index}">
                            <option value="">Select start time</option>
                            ${startOptionsHTML}
                        </select>
                    </div>
                    <div class="flex items-center justify-center text-gray-500">
                        <span class="text-sm font-medium">to</span>
                    </div>
                    <div class="flex-1">
                        <label class="mb-1 block text-sm font-medium text-gray-700">End Time <span class="text-red-500">*</span></label>
                        <select name="availability[${date}][${index}][end_time]" 
                                class="time-select end-time w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                data-date="${date}" data-index="${index}">
                            <option value="">Select end time</option>
                            ${endOptionsHTML}
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" 
                                class="remove-time-range rounded bg-red-500 px-3 py-2 text-sm font-medium text-white hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                data-date="${date}" data-index="${index}">
                            Remove
                        </button>
                    </div>
                </div>
                <div class="error-message mt-2 hidden text-sm text-red-600"></div>
            </div>
        `;
    }

    /**
     * Reindex time range selectors after removal
     */
    reindexTimeRangeSelectors(container) {
        const selectors = container.querySelectorAll(".time-range-selector");

        selectors.forEach((selector, index) => {
            selector.dataset.index = index;

            const date = selector.querySelector(".time-select").dataset.date;
            const startSelect = selector.querySelector(".start-time");
            const endSelect = selector.querySelector(".end-time");
            const removeButton = selector.querySelector(".remove-time-range");

            // Update names and data attributes
            startSelect.name = `availability[${date}][${index}][start_time]`;
            startSelect.dataset.index = index;

            endSelect.name = `availability[${date}][${index}][end_time]`;
            endSelect.dataset.index = index;

            if (removeButton) {
                removeButton.dataset.index = index;
            }
        });
    }

    /**
     * Update visibility of remove buttons based on number of ranges
     */
    updateRemoveButtonsVisibility(container) {
        const selectors = container.querySelectorAll(".time-range-selector");
        const shouldShowRemove = selectors.length > 1;

        selectors.forEach((selector) => {
            const removeButton = selector.querySelector(".remove-time-range");
            if (removeButton) {
                if (shouldShowRemove) {
                    removeButton.style.display = "block";
                } else {
                    removeButton.style.display = "none";
                }
            }
        });
    }

    /**
     * Validate a single time range selector
     */
    validateTimeRangeSelector(timeRangeSelector) {
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
                "Please select both start time and end time",
            );
            return false;
        }

        // Check if only one time is selected
        if (!startTime && endTime) {
            this.showTimeRangeError(
                timeRangeSelector,
                "Please select start time",
            );
            return false;
        }

        if (startTime && !endTime) {
            this.showTimeRangeError(
                timeRangeSelector,
                "Please select end time",
            );
            return false;
        }

        // Both times are selected, validate time logic
        if (startTime && endTime) {
            if (startTime >= endTime) {
                this.showTimeRangeError(
                    timeRangeSelector,
                    "End time must be later than start time",
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Show error for a time range selector
     */
    showTimeRangeError(timeRangeSelector, message) {
        const errorContainer =
            timeRangeSelector.querySelector(".error-message");
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
    clearTimeRangeError(timeRangeSelector) {
        const errorContainer =
            timeRangeSelector.querySelector(".error-message");
        const startSelect = timeRangeSelector.querySelector(".start-time");
        const endSelect = timeRangeSelector.querySelector(".end-time");

        errorContainer.textContent = "";
        errorContainer.classList.add("hidden");

        startSelect.classList.remove("border-red-500");
        endSelect.classList.remove("border-red-500");
    }

    /**
     * Validate the entire availability form
     */
    validateAvailabilityForm() {
        const participantNameInput = this.form.querySelector(
            'input[name="participant_name"]',
        );

        let isValid = true;

        // Validate participant name
        if (!participantNameInput.value.trim()) {
            this.showFieldError(participantNameInput, "Please enter your name");
            isValid = false;
        } else {
            this.clearFieldError(participantNameInput);
        }

        // Validate all time range selectors
        const timeRangeSelectors = this.form.querySelectorAll(
            ".time-range-selector",
        );
        timeRangeSelectors.forEach((selector) => {
            if (!this.validateTimeRangeSelector(selector)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Show field error (reusing from events.js utility functions)
     */
    showFieldError(input, message) {
        this.clearFieldError(input);

        const errorDiv = document.createElement("div");
        errorDiv.className = "text-red-600 text-sm mt-1";
        errorDiv.textContent = message;
        errorDiv.id = input.id + "_error";

        input.parentNode.appendChild(errorDiv);
        input.classList.add("border-red-500");
    }

    /**
     * Clear field error (reusing from events.js utility functions)
     */
    clearFieldError(input) {
        const errorDiv = document.getElementById(input.id + "_error");
        if (errorDiv) {
            errorDiv.remove();
        }
        input.classList.remove("border-red-500");
    }
}
