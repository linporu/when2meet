/**
 * Availability Form JavaScript - Refactored
 * Now uses modular components for better maintainability
 */

import { AvailabilityManager } from '../components/availability-manager.js';
import { TimezoneConverter } from '../components/timezone-converter.js';
import { FormValidator } from '../utils/form-validator.js';
import { DOMHelpers } from '../utils/dom-helpers.js';
import { ErrorHandler } from '../utils/error-handler.js';

export class AvailabilityForm {
    constructor() {
        this.form = null;
        this.timezoneConverter = new TimezoneConverter();
        this.availabilityManagers = [];
    }

    /**
     * Initialize availability form functionality
     */
    initialize() {
        return ErrorHandler.safeExecute(() => {
            this.form = DOMHelpers.querySelector('#availability-form');
            if (!this.form) {return;}

            // Initialize availability managers for each date
            this.initializeAvailabilityManagers();

            // Initialize form submission handling
            this.initFormSubmission();

            console.log('Availability form initialized with modular components');
        }, 'Availability Form Initialization');
    }

    /**
     * Initialize availability managers for each time range list
     */
    initializeAvailabilityManagers() {
        ErrorHandler.safeExecute(() => {
            const timeRangeLists = this.form.querySelectorAll('.time-range-list');

            timeRangeLists.forEach(timeRangeList => {
                const container = timeRangeList.querySelector('.time-ranges-container');
                const date = timeRangeList.dataset.date;

                if (container && date) {
                    const manager = new AvailabilityManager(container, date);
                    this.availabilityManagers.push(manager);
                }
            });
        }, 'Initialize Availability Managers');
    }

    /**
     * Initialize form submission validation
     */
    initFormSubmission() {
        if (!this.form) {return;}

        ErrorHandler.safeExecute(() => {
            this.form.addEventListener('submit', (e) => {
                e.preventDefault();

                if (!this.validateAvailabilityForm()) {
                    return;
                }

                // Convert local times to UTC using hidden fields
                this.convertTimesToUtcForSubmission();

                // Submit the form normally (maintains Laravel flow)
                this.form.submit();
            });
        }, 'Form Submission Setup');
    }

    /**
     * Convert all local times to UTC for form submission
     */
    convertTimesToUtcForSubmission() {
        ErrorHandler.safeExecute(() => {
            const timeSelects = this.form.querySelectorAll('.time-select');

            timeSelects.forEach(select => {
                if (select.value) {
                    const utcTime = this.timezoneConverter.convertLocalToUtc(select.value);

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
        }, 'Convert Times to UTC');
    }

    /**
     * Validate the entire availability form
     */
    validateAvailabilityForm() {
        return ErrorHandler.safeExecute(() => {
            const participantNameInput = this.form.querySelector('input[name="participant_name"]');
            let isValid = true;

            // Validate participant name
            if (!FormValidator.validateParticipantName(participantNameInput)) {
                isValid = false;
            }

            // Validate all availability managers
            this.availabilityManagers.forEach(manager => {
                if (!manager.validateAll()) {
                    isValid = false;
                }
            });

            return isValid;
        }, 'Validate Availability Form', false);
    }

    /**
     * Get all availability managers
     */
    getAvailabilityManagers() {
        return this.availabilityManagers;
    }

    /**
     * Get availability manager for a specific date
     */
    getAvailabilityManager(date) {
        return this.availabilityManagers.find(manager => manager.getDate() === date);
    }

    /**
     * Clear all availability selections
     */
    clearAllAvailability() {
        ErrorHandler.safeExecute(() => {
            this.availabilityManagers.forEach(manager => {
                manager.clearAll();
            });
        }, 'Clear All Availability');
    }

    /**
     * Reset all availability to default state (one empty range per date)
     */
    resetAllAvailability() {
        ErrorHandler.safeExecute(() => {
            this.availabilityManagers.forEach(manager => {
                manager.resetToOne();
            });
        }, 'Reset All Availability');
    }

    /**
     * Get summary of all availability selections
     */
    getAvailabilitySummary() {
        return ErrorHandler.safeExecute(() => {
            return this.availabilityManagers.map(manager => ({
                date: manager.getDate(),
                ranges: manager.getAllValues(),
                completeRanges: manager.getCompleteRangeCount(),
                hasCompleteRange: manager.hasCompleteRange()
            }));
        }, 'Get Availability Summary', []);
    }

    /**
     * Check if form has any complete availability ranges
     */
    hasAnyAvailability() {
        return ErrorHandler.safeExecute(() => {
            return this.availabilityManagers.some(manager => manager.hasCompleteRange());
        }, 'Check Any Availability', false);
    }

    /**
     * Destroy the availability form and clean up resources
     */
    destroy() {
        ErrorHandler.safeExecute(() => {
            this.availabilityManagers.forEach(manager => {
                manager.destroy();
            });
            this.availabilityManagers = [];
        }, 'Destroy Availability Form');
    }
}
