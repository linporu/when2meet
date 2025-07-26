/**
 * Error Handler Unit Tests
 * Tests for the basic ErrorHandler functionality
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ErrorHandler } from '../../../resources/js/utils/error-handler.js';

describe('ErrorHandler', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('safeExecute', () => {
        it('should execute function successfully and return result', () => {
            const testFunction = () => 'success result';
            const result = ErrorHandler.safeExecute(testFunction, 'Test Operation');

            expect(result).toBe('success result');
        });

        it('should return default value when function throws error', () => {
            const testFunction = () => {
                throw new Error('Test error');
            };
            const defaultValue = 'default';
            const result = ErrorHandler.safeExecute(testFunction, 'Test Operation', defaultValue);

            expect(result).toBe(defaultValue);
        });

        it('should return null when no default value provided and error occurs', () => {
            const testFunction = () => {
                throw new Error('Test error');
            };
            const result = ErrorHandler.safeExecute(testFunction, 'Test Operation');

            expect(result).toBeNull();
        });

        it('should handle functions with parameters', () => {
            const testFunction = (a, b) => a + b;
            const result = ErrorHandler.safeExecute(() => testFunction(5, 3), 'Math Operation');

            expect(result).toBe(8);
        });
    });

    describe('logError', () => {
        it('should log error with context', () => {
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
            const testError = new Error('Test error message');

            ErrorHandler.logError(testError, 'Test Context');

            expect(consoleSpy).toHaveBeenCalledWith(
                expect.stringContaining('Test Context: Test error message'),
                expect.any(Object)
            );

            consoleSpy.mockRestore();
        });

        it('should handle string errors', () => {
            const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

            ErrorHandler.logError('String error message', 'Test Context');

            expect(consoleSpy).toHaveBeenCalledWith(
                expect.stringContaining('Test Context: String error message'),
                expect.any(Object)
            );

            consoleSpy.mockRestore();
        });
    });
});