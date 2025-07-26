/**
 * Error Handler Utilities
 * Centralized error handling and logging
 */

export class ErrorHandler {
    /**
     * Log error to console with context
     */
    static logError(error, context = '', data = null) {
        const timestamp = new Date().toISOString();
        const errorMessage = error instanceof Error ? error.message : error;
        const stack = error instanceof Error ? error.stack : '';

        console.error(`[${timestamp}] ${context}: ${errorMessage}`, {
            error: errorMessage,
            stack,
            context,
            data
        });
    }

    /**
     * Handle and log form validation errors
     */
    static handleValidationError(error, formElement = null) {
        this.logError(error, 'Form Validation Error', {
            form: formElement ? formElement.id : 'unknown',
            action: formElement ? formElement.action : 'unknown'
        });
    }

    /**
     * Handle and log AJAX/fetch errors
     */
    static handleAjaxError(error, url = '', method = 'GET') {
        this.logError(error, 'AJAX Error', {
            url,
            method,
            status: error.status || 'unknown'
        });
    }

    /**
     * Handle and log time conversion errors
     */
    static handleTimeConversionError(error, timeString = '', operation = '') {
        this.logError(error, 'Time Conversion Error', {
            timeString,
            operation
        });
        
        // Return fallback value
        return timeString;
    }

    /**
     * Handle and log DOM manipulation errors
     */
    static handleDOMError(error, element = null, operation = '') {
        this.logError(error, 'DOM Error', {
            element: element ? element.tagName + (element.id ? '#' + element.id : '') : 'unknown',
            operation
        });
    }

    /**
     * Handle and log component initialization errors
     */
    static handleComponentError(error, componentName = '', method = '') {
        this.logError(error, 'Component Error', {
            component: componentName,
            method
        });
    }

    /**
     * Safe execution wrapper
     */
    static safeExecute(fn, errorContext = '', fallbackValue = null) {
        try {
            return fn();
        } catch (error) {
            this.logError(error, errorContext);
            return fallbackValue;
        }
    }

    /**
     * Safe async execution wrapper
     */
    static async safeExecuteAsync(fn, errorContext = '', fallbackValue = null) {
        try {
            return await fn();
        } catch (error) {
            this.logError(error, errorContext);
            return fallbackValue;
        }
    }

    /**
     * Show user-friendly error message
     */
    static showUserError(message, container = null) {
        const errorElement = document.createElement('div');
        errorElement.className = 'bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded mb-4';
        errorElement.textContent = message;

        if (container) {
            container.insertBefore(errorElement, container.firstChild);
        } else {
            // Try to find a suitable container
            const form = document.querySelector('form');
            const card = document.querySelector('.card, .max-w-2xl, .max-w-4xl');
            const main = document.querySelector('main');
            
            const targetContainer = form || card || main || document.body;
            targetContainer.insertBefore(errorElement, targetContainer.firstChild);
        }

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorElement.parentNode) {
                errorElement.parentNode.removeChild(errorElement);
            }
        }, 5000);
    }

    /**
     * Clear user error messages
     */
    static clearUserErrors(container = null) {
        const selector = '.bg-red-50';
        const errors = container 
            ? container.querySelectorAll(selector)
            : document.querySelectorAll(selector);
            
        errors.forEach(error => {
            if (error.parentNode) {
                error.parentNode.removeChild(error);
            }
        });
    }

    /**
     * Validate and handle network errors
     */
    static handleNetworkError(error, operation = 'Network operation') {
        if (error.name === 'NetworkError' || !navigator.onLine) {
            this.showUserError('Network error. Please check your connection and try again.');
            this.logError(error, 'Network Error', {
                online: navigator.onLine,
                operation
            });
        } else {
            this.showUserError('An unexpected error occurred. Please try again.');
            this.logError(error, 'Unexpected Error', { operation });
        }
    }

    /**
     * Development mode error handler (more verbose)
     */
    static handleDevError(error, context = '') {
        if (process.env.NODE_ENV === 'development') {
            console.group(`🚨 Development Error: ${context}`);
            console.error('Error:', error);
            console.error('Stack:', error.stack);
            console.trace('Call stack');
            console.groupEnd();
        } else {
            this.logError(error, context);
        }
    }
}