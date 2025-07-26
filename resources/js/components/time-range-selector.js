/**
 * Time Range Selector Component
 * Handles individual time range selection logic and HTML generation
 */

import { FormValidator } from '../utils/form-validator.js';
import { DOMHelpers } from '../utils/dom-helpers.js';
import { ErrorHandler } from '../utils/error-handler.js';

export class TimeRangeSelector {
    constructor(date, index, timeOptions, startTime = '', endTime = '') {
        this.date = date;
        this.index = index;
        this.timeOptions = timeOptions;
        this.startTime = startTime;
        this.endTime = endTime;
        this.element = null;
    }

    /**
     * Create HTML for the time range selector
     */
    createHTML() {
        return ErrorHandler.safeExecute(() => {
            // Convert timeOptions object to array format if needed
            const timeOptionsArray = Array.isArray(this.timeOptions)
                ? this.timeOptions
                : Object.entries(this.timeOptions).map(([value, text]) => ({ value, text }));

            // Generate options HTML for start time select
            const startOptionsHTML = this.generateOptionsHTML(timeOptionsArray, this.startTime);

            // Generate options HTML for end time select
            const endOptionsHTML = this.generateOptionsHTML(timeOptionsArray, this.endTime);

            return `
                <div class="time-range-selector mb-3 rounded border border-gray-200 bg-gray-50 p-3" data-index="${this.index}">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                        <div class="flex-1">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Start Time <span class="text-red-500">*</span></label>
                            <select name="availability[${this.date}][${this.index}][start_time]" 
                                    class="time-select start-time w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    data-date="${this.date}" data-index="${this.index}">
                                <option value="">Select start time</option>
                                ${startOptionsHTML}
                            </select>
                        </div>
                        <div class="flex items-center justify-center text-gray-500">
                            <span class="text-sm font-medium">to</span>
                        </div>
                        <div class="flex-1">
                            <label class="mb-1 block text-sm font-medium text-gray-700">End Time <span class="text-red-500">*</span></label>
                            <select name="availability[${this.date}][${this.index}][end_time]" 
                                    class="time-select end-time w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    data-date="${this.date}" data-index="${this.index}">
                                <option value="">Select end time</option>
                                ${endOptionsHTML}
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="button" 
                                    class="remove-time-range rounded bg-red-500 px-3 py-2 text-sm font-medium text-white hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                    data-date="${this.date}" data-index="${this.index}">
                                Remove
                            </button>
                        </div>
                    </div>
                    <div class="error-message mt-2 hidden text-sm text-red-600"></div>
                </div>
            `;
        }, 'Time Range Selector HTML Creation', '');
    }

    /**
     * Generate options HTML for select elements
     */
    generateOptionsHTML(options, selectedValue) {
        return ErrorHandler.safeExecute(() => {
            return options.map(option => {
                // Handle both DOM option elements and processed objects
                const value = option.value || option;
                const text = option.text || option.textContent || option;
                const selected = value === selectedValue ? 'selected' : '';
                return `<option value="${value}" ${selected}>${text}</option>`;
            }).join('');
        }, 'Options HTML Generation', '');
    }

    /**
     * Render the time range selector to a container
     */
    render(container) {
        return ErrorHandler.safeExecute(() => {
            const html = this.createHTML();
            DOMHelpers.insertHTMLAtEnd(container, html);

            // Store reference to the created element
            this.element = container.lastElementChild;

            // Setup event listeners
            this.setupEventListeners();

            return this.element;
        }, 'Time Range Selector Render');
    }

    /**
     * Setup event listeners for the time range selector
     */
    setupEventListeners() {
        if (!this.element) {return;}

        ErrorHandler.safeExecute(() => {
            const startSelect = this.element.querySelector('.start-time');
            const endSelect = this.element.querySelector('.end-time');

            // Add change event listeners for validation
            if (startSelect) {
                startSelect.addEventListener('change', () => this.validate());
            }

            if (endSelect) {
                endSelect.addEventListener('change', () => this.validate());
            }
        }, 'Time Range Selector Event Listeners Setup');
    }

    /**
     * Validate the time range selector
     */
    validate() {
        if (!this.element) {return false;}

        return ErrorHandler.safeExecute(() => {
            return FormValidator.validateTimeRangeSelector(this.element);
        }, 'Time Range Selector Validation', false);
    }

    /**
     * Get the selected values
     */
    getValues() {
        if (!this.element) {return { startTime: '', endTime: '' };}

        return ErrorHandler.safeExecute(() => {
            const startSelect = this.element.querySelector('.start-time');
            const endSelect = this.element.querySelector('.end-time');

            return {
                startTime: startSelect ? startSelect.value : '',
                endTime: endSelect ? endSelect.value : ''
            };
        }, 'Get Time Range Values', { startTime: '', endTime: '' });
    }

    /**
     * Set the selected values
     */
    setValues(startTime, endTime) {
        if (!this.element) {return;}

        ErrorHandler.safeExecute(() => {
            const startSelect = this.element.querySelector('.start-time');
            const endSelect = this.element.querySelector('.end-time');

            if (startSelect) {
                startSelect.value = startTime || '';
            }

            if (endSelect) {
                endSelect.value = endTime || '';
            }

            // Update instance properties
            this.startTime = startTime || '';
            this.endTime = endTime || '';
        }, 'Set Time Range Values');
    }

    /**
     * Update the selector index and form names
     */
    updateIndex(newIndex) {
        ErrorHandler.safeExecute(() => {
            this.index = newIndex;

            if (!this.element) {return;}

            // Update data attribute
            this.element.dataset.index = newIndex;

            // Update form field names and data attributes
            const startSelect = this.element.querySelector('.start-time');
            const endSelect = this.element.querySelector('.end-time');
            const removeButton = this.element.querySelector('.remove-time-range');

            if (startSelect) {
                startSelect.name = `availability[${this.date}][${newIndex}][start_time]`;
                startSelect.dataset.index = newIndex;
            }

            if (endSelect) {
                endSelect.name = `availability[${this.date}][${newIndex}][end_time]`;
                endSelect.dataset.index = newIndex;
            }

            if (removeButton) {
                removeButton.dataset.index = newIndex;
            }
        }, 'Update Time Range Index');
    }

    /**
     * Show/hide the remove button
     */
    toggleRemoveButton(show = true) {
        if (!this.element) {return;}

        ErrorHandler.safeExecute(() => {
            const removeButton = this.element.querySelector('.remove-time-range');
            if (removeButton) {
                removeButton.style.display = show ? 'block' : 'none';
            }
        }, 'Toggle Remove Button');
    }

    /**
     * Remove the time range selector from DOM
     */
    remove() {
        ErrorHandler.safeExecute(() => {
            if (this.element && this.element.parentNode) {
                this.element.parentNode.removeChild(this.element);
                this.element = null;
            }
        }, 'Remove Time Range Selector');
    }

    /**
     * Check if the time range is empty
     */
    isEmpty() {
        const values = this.getValues();
        return !values.startTime && !values.endTime;
    }

    /**
     * Check if the time range is complete (both start and end times selected)
     */
    isComplete() {
        const values = this.getValues();
        return values.startTime && values.endTime;
    }

    /**
     * Clear the selected values
     */
    clear() {
        this.setValues('', '');
    }

    /**
     * Get the DOM element
     */
    getElement() {
        return this.element;
    }

    /**
     * Clone this time range selector with new index
     */
    clone(newIndex) {
        return new TimeRangeSelector(
            this.date,
            newIndex,
            this.timeOptions,
            '',  // Start with empty values for clone
            ''
        );
    }
}