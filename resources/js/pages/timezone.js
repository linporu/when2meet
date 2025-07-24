/**
 * Timezone Conversion Utility
 * Converts UTC time to the user's local time.
 */

class TimezoneConverter {
    constructor() {
        this.userTimezone = this.detectUserTimezone();
        this.timezoneOffset = this.getTimezoneOffset();
    }

    /**
     * Detects the user's timezone automatically.
     */
    detectUserTimezone() {
        try {
            return Intl.DateTimeFormat().resolvedOptions().timeZone;
        } catch (error) {
            console.warn("Could not detect timezone, falling back to UTC:", error);
            return "UTC";
        }
    }

    /**
     * Gets the timezone offset display string (e.g., GMT+8).
     */
    getTimezoneOffset() {
        const now = new Date();
        const offsetMinutes = now.getTimezoneOffset();
        const offsetHours = Math.abs(offsetMinutes / 60);
        const sign = offsetMinutes <= 0 ? "+" : "-";
        return `GMT${sign}${offsetHours}`;
    }

    /**
     * Converts a UTC time string to the local time.
     * @param {string} utcTimeString - The UTC time string (format: "01:00:00" or "01:00").
     * @returns {string} The local time string (format: "09:00").
     */
    convertUtcToLocal(utcTimeString) {
        try {
            // Normalize the time string to ensure it's in HH:MM:SS format
            const normalizedTime = this.normalizeTimeString(utcTimeString);

            // Create a UTC date object (the date is arbitrary, we only care about the time)
            const utcDate = new Date(`1970-01-01T${normalizedTime}Z`);

            // Convert to local time and format it
            return utcDate.toLocaleTimeString("en-GB", {
                hour12: false,
                hour: "2-digit",
                minute: "2-digit",
            });
        } catch (error) {
            console.error("Time conversion failed:", error, utcTimeString);
            return utcTimeString; // Return the original time as a fallback
        }
    }

    /**
     * Normalizes a time string format.
     * @param {string} timeString - The time string.
     * @returns {string} The normalized time string (HH:MM:SS).
     */
    normalizeTimeString(timeString) {
        if (!timeString) return "00:00:00";

        // Remove whitespace
        timeString = timeString.trim();

        // If the format is HH:MM, add seconds
        if (timeString.match(/^\d{1,2}:\d{2}$/)) {
            timeString += ":00";
        }

        // Ensure the hour is two digits
        if (timeString.match(/^\d:\d{2}:\d{2}$/)) {
            timeString = "0" + timeString;
        }

        return timeString;
    }

    /**
     * Initializes timezone conversion for the page.
     */
    initializePageTimezone() {
        // Find all elements that require timezone conversion
        const timezoneElements = document.querySelectorAll(".timezone-display");

        timezoneElements.forEach((element) => {
            const utcStart = element.getAttribute("data-utc-start");
            const utcEnd = element.getAttribute("data-utc-end");

            if (utcStart && utcEnd) {
                const localStart = this.convertUtcToLocal(utcStart);
                const localEnd = this.convertUtcToLocal(utcEnd);

                // Update the display content
                element.innerHTML = `${localStart} - ${localEnd} <small class="text-gray-500">(${this.timezoneOffset})</small>`;
            }
        });
    }
}

// Export the TimezoneConverter class for use in other modules
// Note: This module does not initialize automatically; it must be manually initialized in the page controller.
export { TimezoneConverter };
