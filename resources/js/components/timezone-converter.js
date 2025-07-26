/**
 * Timezone Conversion Component
 * Converts UTC time to the user's local time with enhanced error handling
 */

import { ErrorHandler } from '../utils/error-handler.js';

export class TimezoneConverter {
    constructor() {
        this.userTimezone = this.detectUserTimezone();
        this.timezoneOffset = this.getTimezoneOffset();
    }

    /**
     * Detects the user's timezone automatically.
     */
    detectUserTimezone() {
        return ErrorHandler.safeExecute(() => {
            return Intl.DateTimeFormat().resolvedOptions().timeZone;
        }, 'Timezone Detection', 'UTC');
    }

    /**
     * Gets the timezone offset display string (e.g., GMT+8).
     */
    getTimezoneOffset() {
        return ErrorHandler.safeExecute(() => {
            const now = new Date();
            const offsetMinutes = now.getTimezoneOffset();
            const offsetHours = Math.abs(offsetMinutes / 60);
            const sign = offsetMinutes <= 0 ? '+' : '-';
            return `GMT${sign}${offsetHours}`;
        }, 'Timezone Offset Calculation', 'GMT+0');
    }

    /**
     * Converts a UTC time string to the local time.
     * @param {string} utcTimeString - The UTC time string (format: "01:00:00" or "01:00").
     * @returns {string} The local time string (format: "09:00").
     */
    convertUtcToLocal(utcTimeString) {
        return ErrorHandler.safeExecute(() => {
            // Normalize the time string to ensure it's in HH:MM:SS format
            const normalizedTime = this.normalizeTimeString(utcTimeString);

            // Create a UTC date object (the date is arbitrary, we only care about the time)
            const utcDate = new Date(`1970-01-01T${normalizedTime}Z`);

            // Convert to local time and format it
            return utcDate.toLocaleTimeString('en-GB', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit'
            });
        }, 'UTC to Local Conversion', utcTimeString);
    }

    /**
     * Converts local time back to UTC
     * @param {string} localTimeString - The local time string
     * @returns {string} The UTC time string
     */
    convertLocalToUtc(localTimeString) {
        return ErrorHandler.safeExecute(() => {
            const normalized = this.normalizeTimeString(localTimeString);

            // Create a proper local date object
            const [hours, minutes] = normalized.split(':').map(Number);
            const localDate = new Date();
            localDate.setHours(hours, minutes, 0, 0);

            // Convert to UTC and format as HH:MM
            const utcHours = localDate.getUTCHours().toString().padStart(2, '0');
            const utcMinutes = localDate.getUTCMinutes().toString().padStart(2, '0');
            return `${utcHours}:${utcMinutes}`;
        }, 'Local to UTC Conversion', localTimeString);
    }

    /**
     * Normalizes a time string format.
     * @param {string} timeString - The time string.
     * @returns {string} The normalized time string (HH:MM:SS).
     */
    normalizeTimeString(timeString) {
        return ErrorHandler.safeExecute(() => {
            if (!timeString) {return '00:00:00';}

            // Remove whitespace
            timeString = timeString.trim();

            // If the format is HH:MM, add seconds
            if (timeString.match(/^\d{1,2}:\d{2}$/)) {
                timeString += ':00';
            }

            // Ensure the hour is two digits
            if (timeString.match(/^\d:\d{2}:\d{2}$/)) {
                timeString = '0' + timeString;
            }

            return timeString;
        }, 'Time String Normalization', timeString || '00:00:00');
    }

    /**
     * Convert UTC time options to local time format
     */
    convertTimeOptionsToLocal(timeOptions) {
        return ErrorHandler.safeExecute(() => {
            if (Array.isArray(timeOptions)) {
                return timeOptions;
            }

            const convertedOptions = {};
            for (const [utcTime, displayText] of Object.entries(timeOptions)) {
                const localTime = this.convertUtcToLocal(utcTime);
                // Create a more readable format for local time display
                const localDisplayTime = new Date(`1970-01-01T${this.normalizeTimeString(utcTime)}Z`)
                    .toLocaleTimeString('en-US', {
                        hour12: true,
                        hour: 'numeric',
                        minute: '2-digit'
                    });
                convertedOptions[localTime] = localDisplayTime;
            }
            return convertedOptions;
        }, 'Time Options Conversion', timeOptions);
    }

    /**
     * Initializes timezone conversion for the page.
     */
    initializePageTimezone() {
        return ErrorHandler.safeExecute(() => {
            // Find all elements that require timezone conversion
            const timezoneElements = document.querySelectorAll('.timezone-display');

            timezoneElements.forEach((element) => {
                const utcStart = element.getAttribute('data-utc-start');
                const utcEnd = element.getAttribute('data-utc-end');

                if (utcStart && utcEnd) {
                    const localStart = this.convertUtcToLocal(utcStart);
                    const localEnd = this.convertUtcToLocal(utcEnd);

                    // Update timezone label (find the timezone-label in the same container)
                    const container = element.closest('.rounded-lg');
                    const timezoneLabel = container?.querySelector('.timezone-label');
                    if (timezoneLabel) {
                        timezoneLabel.textContent = this.timezoneOffset;
                    }

                    // Update time display content only (no HTML generation)
                    element.textContent = `${localStart} - ${localEnd}`;
                }
            });

            console.log(`Initialized ${timezoneElements.length} timezone display elements`);
        }, 'Page Timezone Initialization', false);
    }

    /**
     * Get timezone information
     */
    getTimezoneInfo() {
        return {
            timezone: this.userTimezone,
            offset: this.timezoneOffset,
            offsetMinutes: new Date().getTimezoneOffset()
        };
    }

    /**
     * Format time for display with timezone info
     */
    formatTimeWithTimezone(utcTimeString) {
        const localTime = this.convertUtcToLocal(utcTimeString);
        return `${localTime} (${this.timezoneOffset})`;
    }

    /**
     * Check if two times are in the same day after timezone conversion
     */
    isSameDay(utcTime1, utcTime2) {
        return ErrorHandler.safeExecute(() => {
            const date1 = new Date(`1970-01-01T${this.normalizeTimeString(utcTime1)}Z`);
            const date2 = new Date(`1970-01-01T${this.normalizeTimeString(utcTime2)}Z`);

            return date1.toDateString() === date2.toDateString();
        }, 'Same Day Check', true);
    }
}