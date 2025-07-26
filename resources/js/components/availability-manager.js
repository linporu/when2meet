/**
 * Availability Manager Component
 * Manages multiple time range selectors for availability selection
 */

import { TimeRangeSelector } from './time-range-selector.js';
import { TimezoneConverter } from './timezone-converter.js';
import { FormValidator } from '../utils/form-validator.js';
import { DOMHelpers } from '../utils/dom-helpers.js';
import { ErrorHandler } from '../utils/error-handler.js';

export class AvailabilityManager {
    constructor(container, date) {
        this.container = container;
        this.date = date;
        this.timeRangeSelectors = [];
        this.timezoneConverter = new TimezoneConverter();
        this.timeOptions = this.parseTimeOptions();
        
        this.init();
    }

    /**
     * Initialize the availability manager
     */
    init() {
        ErrorHandler.safeExecute(() => {
            this.setupEventListeners();
            this.renderExistingRanges();
        }, 'Availability Manager Initialization');
    }

    /**
     * Parse time options from container data
     */
    parseTimeOptions() {
        return ErrorHandler.safeExecute(() => {
            const timeOptions = JSON.parse(this.container.dataset.timeOptions || "[]");
            return this.timezoneConverter.convertTimeOptionsToLocal(timeOptions);
        }, 'Time Options Parsing', []);
    }

    /**
     * Setup event listeners for add/remove buttons
     */
    setupEventListeners() {
        ErrorHandler.safeExecute(() => {
            // Use event delegation for add button
            DOMHelpers.addDelegatedListener(
                this.container.parentElement, 
                '.add-time-range', 
                'click', 
                (e) => {
                    e.preventDefault();
                    this.addTimeRange();
                }
            );

            // Use event delegation for remove buttons
            DOMHelpers.addDelegatedListener(
                this.container, 
                '.remove-time-range', 
                'click', 
                (e, button) => {
                    e.preventDefault();
                    const selectorElement = button.closest('.time-range-selector');
                    this.removeTimeRange(selectorElement);
                }
            );
        }, 'Event Listeners Setup');
    }

    /**
     * Render existing time ranges from server data
     */
    renderExistingRanges() {
        ErrorHandler.safeExecute(() => {
            const existingRanges = JSON.parse(this.container.dataset.existingRanges || "[]");
            
            // Clear container
            DOMHelpers.clearContent(this.container);
            this.timeRangeSelectors = [];

            if (existingRanges.length > 0) {
                // Render existing ranges
                existingRanges.forEach((range, index) => {
                    // Convert existing UTC times to local times for proper option selection
                    const localStartTime = range.start_time ? 
                        this.timezoneConverter.convertUtcToLocal(range.start_time) : "";
                    const localEndTime = range.end_time ? 
                        this.timezoneConverter.convertUtcToLocal(range.end_time) : "";
                        
                    this.createTimeRangeSelector(index, localStartTime, localEndTime);
                });
            } else {
                // Render default empty time range
                this.createTimeRangeSelector(0);
            }
            
            this.updateRemoveButtonsVisibility();
        }, 'Render Existing Ranges');
    }

    /**
     * Create a new time range selector
     */
    createTimeRangeSelector(index, startTime = "", endTime = "") {
        return ErrorHandler.safeExecute(() => {
            const selector = new TimeRangeSelector(
                this.date, 
                index, 
                this.timeOptions, 
                startTime, 
                endTime
            );

            const element = selector.render(this.container);
            this.timeRangeSelectors.push(selector);

            return selector;
        }, 'Create Time Range Selector');
    }

    /**
     * Add a new time range selector
     */
    addTimeRange() {
        ErrorHandler.safeExecute(() => {
            const newIndex = this.timeRangeSelectors.length;
            this.createTimeRangeSelector(newIndex);
            this.updateRemoveButtonsVisibility();
        }, 'Add Time Range');
    }

    /**
     * Remove a time range selector
     */
    removeTimeRange(selectorElement) {
        ErrorHandler.safeExecute(() => {
            if (!selectorElement) return;

            const index = parseInt(selectorElement.dataset.index);
            
            // Remove from array
            const selector = this.timeRangeSelectors[index];
            if (selector) {
                selector.remove();
                this.timeRangeSelectors.splice(index, 1);
            }

            // Reindex remaining selectors
            this.reindexSelectors();
            this.updateRemoveButtonsVisibility();
        }, 'Remove Time Range');
    }

    /**
     * Reindex all selectors after removal
     */
    reindexSelectors() {
        ErrorHandler.safeExecute(() => {
            this.timeRangeSelectors.forEach((selector, index) => {
                selector.updateIndex(index);
            });
        }, 'Reindex Selectors');
    }

    /**
     * Update visibility of remove buttons
     */
    updateRemoveButtonsVisibility() {
        ErrorHandler.safeExecute(() => {
            const shouldShowRemove = this.timeRangeSelectors.length > 1;
            
            this.timeRangeSelectors.forEach(selector => {
                selector.toggleRemoveButton(shouldShowRemove);
            });
        }, 'Update Remove Buttons');
    }

    /**
     * Validate all time range selectors
     */
    validateAll() {
        return ErrorHandler.safeExecute(() => {
            let isValid = true;
            
            this.timeRangeSelectors.forEach(selector => {
                if (!selector.validate()) {
                    isValid = false;
                }
            });
            
            return isValid;
        }, 'Validate All Time Ranges', false);
    }

    /**
     * Get all time range values
     */
    getAllValues() {
        return ErrorHandler.safeExecute(() => {
            return this.timeRangeSelectors.map(selector => selector.getValues());
        }, 'Get All Values', []);
    }

    /**
     * Convert all local times to UTC for form submission
     */
    convertAllToUtc() {
        return ErrorHandler.safeExecute(() => {
            return this.timeRangeSelectors.map(selector => {
                const values = selector.getValues();
                return {
                    startTime: values.startTime ? 
                        this.timezoneConverter.convertLocalToUtc(values.startTime) : '',
                    endTime: values.endTime ? 
                        this.timezoneConverter.convertLocalToUtc(values.endTime) : ''
                };
            });
        }, 'Convert All to UTC', []);
    }

    /**
     * Clear all time ranges
     */
    clearAll() {
        ErrorHandler.safeExecute(() => {
            this.timeRangeSelectors.forEach(selector => selector.clear());
        }, 'Clear All Time Ranges');
    }

    /**
     * Remove all time ranges except the first one
     */
    resetToOne() {
        ErrorHandler.safeExecute(() => {
            // Remove all selectors except the first one
            while (this.timeRangeSelectors.length > 1) {
                const lastSelector = this.timeRangeSelectors.pop();
                lastSelector.remove();
            }
            
            // Clear the remaining selector
            if (this.timeRangeSelectors.length > 0) {
                this.timeRangeSelectors[0].clear();
            }
            
            this.updateRemoveButtonsVisibility();
        }, 'Reset to One Time Range');
    }

    /**
     * Check if any time range is complete
     */
    hasCompleteRange() {
        return ErrorHandler.safeExecute(() => {
            return this.timeRangeSelectors.some(selector => selector.isComplete());
        }, 'Check Complete Range', false);
    }

    /**
     * Check if all time ranges are empty
     */
    areAllEmpty() {
        return ErrorHandler.safeExecute(() => {
            return this.timeRangeSelectors.every(selector => selector.isEmpty());
        }, 'Check All Empty', true);
    }

    /**
     * Get the count of complete time ranges
     */
    getCompleteRangeCount() {
        return ErrorHandler.safeExecute(() => {
            return this.timeRangeSelectors.filter(selector => selector.isComplete()).length;
        }, 'Get Complete Range Count', 0);
    }

    /**
     * Set time ranges from data
     */
    setRanges(ranges) {
        ErrorHandler.safeExecute(() => {
            // Clear existing ranges
            this.timeRangeSelectors.forEach(selector => selector.remove());
            this.timeRangeSelectors = [];
            DOMHelpers.clearContent(this.container);

            // Create new ranges
            ranges.forEach((range, index) => {
                this.createTimeRangeSelector(
                    index, 
                    range.startTime || '', 
                    range.endTime || ''
                );
            });

            // Ensure at least one empty range if no ranges provided
            if (ranges.length === 0) {
                this.createTimeRangeSelector(0);
            }

            this.updateRemoveButtonsVisibility();
        }, 'Set Time Ranges');
    }

    /**
     * Get container element
     */
    getContainer() {
        return this.container;
    }

    /**
     * Get date
     */
    getDate() {
        return this.date;
    }

    /**
     * Destroy the availability manager
     */
    destroy() {
        ErrorHandler.safeExecute(() => {
            this.timeRangeSelectors.forEach(selector => selector.remove());
            this.timeRangeSelectors = [];
            DOMHelpers.clearContent(this.container);
        }, 'Destroy Availability Manager');
    }
}