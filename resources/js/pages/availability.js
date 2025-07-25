// Availability Form JavaScript
export class AvailabilityForm {
    constructor() {
        this.form = null;
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

        console.log("Availability form initialized");
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
            if (!this.validateAvailabilityForm()) {
                e.preventDefault();
            }
        });
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

        // Get time options from the first existing selector
        const firstSelector = existingRanges[0];
        const startTimeSelect = firstSelector.querySelector(".start-time");
        const timeOptions = Array.from(startTimeSelect.options).slice(1); // Skip the first empty option

        // Create new time range selector HTML
        const newTimeRangeHTML = this.createTimeRangeSelectorHTML(
            date,
            newIndex,
            timeOptions,
        );

        // Create a temporary div to hold the HTML
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = newTimeRangeHTML;
        const newTimeRange = tempDiv.firstElementChild;

        // Append to container
        container.appendChild(newTimeRange);

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
    createTimeRangeSelectorHTML(date, index, timeOptions) {
        const optionsHTML = timeOptions
            .map(
                (option) =>
                    `<option value="${option.value}">${option.textContent}</option>`,
            )
            .join("");

        return `
            <div class="time-range-selector mb-3 rounded border border-gray-200 bg-gray-50 p-3" data-index="${index}">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="flex-1">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Start Time</label>
                        <select name="availability[${date}][${index}][start_time]" 
                                class="time-select start-time w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                data-date="${date}" data-index="${index}">
                            <option value="">Select start time</option>
                            ${optionsHTML}
                        </select>
                    </div>
                    <div class="flex items-center justify-center text-gray-500">
                        <span class="text-sm font-medium">to</span>
                    </div>
                    <div class="flex-1">
                        <label class="mb-1 block text-sm font-medium text-gray-700">End Time</label>
                        <select name="availability[${date}][${index}][end_time]" 
                                class="time-select end-time w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                data-date="${date}" data-index="${index}">
                            <option value="">Select end time</option>
                            ${optionsHTML}
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

        // Validate if both times are selected
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
