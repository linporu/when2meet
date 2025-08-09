/**
 * Form Validator Unit Tests
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { FormValidator } from '../../../resources/js/utils/form-validator.js';

describe('FormValidator', () => {
    let container;

    beforeEach(() => {
        document.body.innerHTML = '';
        container = document.createElement('div');
        document.body.appendChild(container);
        vi.clearAllMocks();
    });

    describe('validateTimeRange', () => {
        it('should return true for valid time range', () => {
            const startInput = document.createElement('input');
            const endInput = document.createElement('input');
            startInput.value = '09:00';
            endInput.value = '17:00';
            startInput.id = 'start_time';
            endInput.id = 'end_time';
            container.appendChild(startInput);
            container.appendChild(endInput);

            const result = FormValidator.validateTimeRange(startInput, endInput);
            expect(result).toBe(true);
        });

        it('should return false when start time equals end time', () => {
            const startInput = document.createElement('input');
            const endInput = document.createElement('input');
            startInput.value = '09:00';
            endInput.value = '09:00';
            startInput.id = 'start_time';
            endInput.id = 'end_time';
            container.appendChild(startInput);
            container.appendChild(endInput);

            const result = FormValidator.validateTimeRange(startInput, endInput);
            expect(result).toBe(false);
        });

        it('should return false when start time is after end time', () => {
            const startInput = document.createElement('input');
            const endInput = document.createElement('input');
            startInput.value = '17:00';
            endInput.value = '09:00';
            startInput.id = 'start_time';
            endInput.id = 'end_time';
            container.appendChild(startInput);
            container.appendChild(endInput);

            const result = FormValidator.validateTimeRange(startInput, endInput);
            expect(result).toBe(false);
        });

        it('should return false when start time is empty', () => {
            const startInput = document.createElement('input');
            const endInput = document.createElement('input');
            startInput.value = '';
            endInput.value = '17:00';
            startInput.id = 'start_time';
            endInput.id = 'end_time';
            container.appendChild(startInput);
            container.appendChild(endInput);

            const result = FormValidator.validateTimeRange(startInput, endInput);
            expect(result).toBe(false); // Incomplete time range should be invalid
            expect(document.getElementById('start_time_error')).not.toBeNull();
        });

        it('should return false when end time is empty', () => {
            const startInput = document.createElement('input');
            const endInput = document.createElement('input');
            startInput.value = '09:00';
            endInput.value = '';
            startInput.id = 'start_time';
            endInput.id = 'end_time';
            container.appendChild(startInput);
            container.appendChild(endInput);

            const result = FormValidator.validateTimeRange(startInput, endInput);
            expect(result).toBe(false); // Incomplete time range should be invalid
            expect(document.getElementById('end_time_error')).not.toBeNull();
        });

        it('should return false when both times are empty', () => {
            const startInput = document.createElement('input');
            const endInput = document.createElement('input');
            startInput.value = '';
            endInput.value = '';
            startInput.id = 'start_time';
            endInput.id = 'end_time';
            container.appendChild(startInput);
            container.appendChild(endInput);

            const result = FormValidator.validateTimeRange(startInput, endInput);
            expect(result).toBe(false); // Empty time range should be invalid
            expect(document.getElementById('start_time_error')).not.toBeNull();
        });

        it('should handle edge case times correctly', () => {
            const startInput1 = document.createElement('input');
            const endInput1 = document.createElement('input');
            startInput1.value = '00:00';
            endInput1.value = '23:59';
            startInput1.id = 'start_time1';
            endInput1.id = 'end_time1';
            container.appendChild(startInput1);
            container.appendChild(endInput1);

            const result1 = FormValidator.validateTimeRange(startInput1, endInput1);
            expect(result1).toBe(true);

            const startInput2 = document.createElement('input');
            const endInput2 = document.createElement('input');
            startInput2.value = '23:59';
            endInput2.value = '00:00';
            startInput2.id = 'start_time2';
            endInput2.id = 'end_time2';
            container.appendChild(startInput2);
            container.appendChild(endInput2);

            const result2 = FormValidator.validateTimeRange(startInput2, endInput2);
            expect(result2).toBe(false);
        });
    });

    describe('validateRequired', () => {
        it('should return true for non-empty string', () => {
            const input = document.createElement('input');
            input.value = 'test value';
            input.id = 'test_input';
            container.appendChild(input);

            const result = FormValidator.validateRequired(input);
            expect(result).toBe(true);
        });

        it('should return false for empty string', () => {
            const input = document.createElement('input');
            input.value = '';
            input.id = 'test_input';
            container.appendChild(input);

            const result = FormValidator.validateRequired(input);
            expect(result).toBe(false);
        });

        it('should return false for whitespace only', () => {
            const input = document.createElement('input');
            input.value = '   ';
            input.id = 'test_input';
            container.appendChild(input);

            const result = FormValidator.validateRequired(input);
            expect(result).toBe(false);
        });

        it('should handle empty input elements', () => {
            const input = document.createElement('input');
            input.id = 'test_input';
            container.appendChild(input);

            const result = FormValidator.validateRequired(input);
            expect(result).toBe(false);
        });
    });

    describe('showError', () => {
        it('should add error class to element', () => {
            const input = document.createElement('input');
            input.className = 'form-control';
            input.id = 'test_input';
            container.appendChild(input);

            FormValidator.showError(input, 'Error message');

            expect(input.classList.contains('border-red-500')).toBe(true);
        });

        it('should create and display error message', () => {
            const input = document.createElement('input');
            input.id = 'test-input';
            container.appendChild(input);

            FormValidator.showError(input, 'Test error message');

            const errorElement = document.getElementById('test-input_error');
            expect(errorElement).not.toBeNull();
            expect(errorElement.textContent).toBe('Test error message');
            expect(errorElement.classList.contains('text-red-600')).toBe(true);
        });

        it('should update existing error message', () => {
            const input = document.createElement('input');
            input.id = 'test-input';
            container.appendChild(input);

            FormValidator.showError(input, 'First error');
            FormValidator.showError(input, 'Second error');

            const errorElements = document.querySelectorAll('#test-input_error');
            expect(errorElements.length).toBe(1);
            expect(errorElements[0].textContent).toBe('Second error');
        });

        it('should handle element without id gracefully', () => {
            const input = document.createElement('input');
            container.appendChild(input);

            expect(() => {
                FormValidator.showError(input, 'Error message');
            }).not.toThrow();

            expect(input.classList.contains('border-red-500')).toBe(true);
        });
    });

    describe('clearError', () => {
        it('should remove error class from element', () => {
            const input = document.createElement('input');
            input.className = 'form-control border-red-500';

            FormValidator.clearError(input);

            expect(input.classList.contains('border-red-500')).toBe(false);
        });

        it('should remove error message element', () => {
            const input = document.createElement('input');
            input.id = 'test-input';
            container.appendChild(input);

            // First show error
            FormValidator.showError(input, 'Test error');
            expect(document.getElementById('test-input_error')).not.toBeNull();

            // Then clear error
            FormValidator.clearError(input);
            expect(document.getElementById('test-input_error')).toBeNull();
        });

        it('should handle element without existing error gracefully', () => {
            const input = document.createElement('input');
            input.id = 'test-input';
            container.appendChild(input);

            expect(() => {
                FormValidator.clearError(input);
            }).not.toThrow();
        });

        it('should handle element without id gracefully', () => {
            const input = document.createElement('input');
            input.className = 'form-control border-red-500';
            container.appendChild(input);

            expect(() => {
                FormValidator.clearError(input);
            }).not.toThrow();

            expect(input.classList.contains('border-red-500')).toBe(false);
        });
    });

    describe('validateDate', () => {
        it('should return true for valid future date', () => {
            const input = document.createElement('input');
            const futureDate = new Date();
            futureDate.setDate(futureDate.getDate() + 1);
            input.value = futureDate.toISOString().split('T')[0];
            input.id = 'test_date';
            container.appendChild(input);

            const result = FormValidator.validateDate(input);
            expect(result).toBe(true);
        });

        it('should return true for today when allowPastDates is true', () => {
            const input = document.createElement('input');
            const today = new Date().toISOString().split('T')[0];
            input.value = today;
            input.id = 'test_date';
            container.appendChild(input);

            const result = FormValidator.validateDate(input, true);
            expect(result).toBe(true);
        });

        it('should return false for empty date', () => {
            const input = document.createElement('input');
            input.value = '';
            input.id = 'test_date';
            container.appendChild(input);

            const result = FormValidator.validateDate(input);
            expect(result).toBe(false);
            expect(document.getElementById('test_date_error').textContent).toBe('Please enter a valid date');
        });

        it('should return false for dates with year outside reasonable range', () => {
            const input = document.createElement('input');
            input.id = 'test_date';
            container.appendChild(input);

            const invalidYearDates = [
                '1800-01-01', // Too old
                '10000-01-01' // Too far in future (but HTML won't accept this anyway)
            ];

            invalidYearDates.forEach(invalidDate => {
                input.value = invalidDate;
                const result = FormValidator.validateDate(input);
                expect(result).toBe(false);
                expect(document.getElementById('test_date_error').textContent).toBe('Please enter a valid date');
            });
        });

        it('should return false for completely invalid date strings', () => {
            const input = document.createElement('input');
            input.id = 'test_date';
            container.appendChild(input);

            const invalidDates = [
                'not-a-date',
                'abc-def-ghi',
                '2023-13-01', // HTML date input typically rejects this
                '2023-02-30'  // HTML date input typically rejects this
            ];

            invalidDates.forEach(invalidDate => {
                input.value = invalidDate;
                const result = FormValidator.validateDate(input);
                expect(result).toBe(false);
                expect(document.getElementById('test_date_error').textContent).toBe('Please enter a valid date');
            });
        });

        it('should return false for past date when allowPastDates is false', () => {
            const input = document.createElement('input');
            const pastDate = new Date();
            pastDate.setDate(pastDate.getDate() - 1);
            input.value = pastDate.toISOString().split('T')[0];
            input.id = 'test_date';
            container.appendChild(input);

            const result = FormValidator.validateDate(input);
            expect(result).toBe(false);
            expect(document.getElementById('test_date_error').textContent).toBe('Please enter a valid date');
        });

        it('should return true for past date when allowPastDates is true', () => {
            const input = document.createElement('input');
            const pastDate = new Date();
            pastDate.setDate(pastDate.getDate() - 1);
            input.value = pastDate.toISOString().split('T')[0];
            input.id = 'test_date';
            container.appendChild(input);

            const result = FormValidator.validateDate(input, true);
            expect(result).toBe(true);
        });

        it('should prioritize year validation over past date validation', () => {
            const input = document.createElement('input');
            // This has an invalid year that should be caught before past date check
            input.value = '1800-01-01'; // Old date, but should fail year check first
            input.id = 'test_date';
            container.appendChild(input);

            const result = FormValidator.validateDate(input);
            expect(result).toBe(false);
            // Should show year range error, not past date error
            expect(document.getElementById('test_date_error').textContent).toBe('Please enter a valid date');
        });

        it('should handle edge case dates correctly', () => {
            const input = document.createElement('input');
            input.id = 'test_date';
            container.appendChild(input);

            // Valid date formats (within reasonable year range)
            const validDates = [
                '2025-01-01',
                '2025-12-31',
                '2024-02-29', // Leap year
                '1900-01-01', // Edge case: minimum year
                '9999-12-31'  // Edge case: maximum year
            ];

            validDates.forEach(validDate => {
                input.value = validDate;
                const result = FormValidator.validateDate(input, true);
                expect(result).toBe(true);
            });
        });

        it('should clear error when validation passes', () => {
            const input = document.createElement('input');
            input.id = 'test_date';
            container.appendChild(input);

            // First fail validation
            input.value = 'invalid-date';
            FormValidator.validateDate(input);
            expect(document.getElementById('test_date_error')).not.toBeNull();

            // Then pass validation
            const futureDate = new Date();
            futureDate.setDate(futureDate.getDate() + 1);
            input.value = futureDate.toISOString().split('T')[0];
            const result = FormValidator.validateDate(input);

            expect(result).toBe(true);
            expect(document.getElementById('test_date_error')).toBeNull();
        });
    });

    describe('validateForm', () => {
        it('should validate form with required fields', () => {
            container.innerHTML = `
                <form>
                    <input id="name" data-required="true" value="John Doe">
                    <input id="email" data-required="true" value="john@example.com">
                </form>
            `;
            const form = container.querySelector('form');

            const result = FormValidator.validateForm(form);

            expect(result).toBe(true);
        });

        it('should fail validation for empty required fields', () => {
            container.innerHTML = `
                <form>
                    <input name="name" id="name" value="">
                    <input name="email" id="email" value="john@example.com">
                </form>
            `;
            const form = container.querySelector('form');
            const rules = {
                name: ['required'],
                email: ['required']
            };

            const result = FormValidator.validateForm(form, rules);

            expect(result).toBe(false);
            expect(document.getElementById('name_error')).not.toBeNull();
        });

        it('should validate time range fields', () => {
            container.innerHTML = `
                <form>
                    <select name="start_time" data-time-range="end_time">
                        <option value="09:00" selected>9:00 AM</option>
                    </select>
                    <select name="end_time">
                        <option value="17:00" selected>5:00 PM</option>
                    </select>
                </form>
            `;
            const form = container.querySelector('form');

            const result = FormValidator.validateForm(form);

            expect(result).toBe(true);
        });

        it('should fail validation for invalid time range', () => {
            container.innerHTML = `
                <form>
                    <div class="time-range-selector">
                        <select name="start_time" class="start-time">
                            <option value="17:00" selected>5:00 PM</option>
                        </select>
                        <select name="end_time" class="end-time">
                            <option value="09:00" selected>9:00 AM</option>
                        </select>
                        <div class="error-message hidden"></div>
                    </div>
                </form>
            `;
            const form = container.querySelector('form');

            const result = FormValidator.validateForm(form);

            expect(result).toBe(false);
        });

        it('should handle form without validation rules', () => {
            container.innerHTML = `
                <form>
                    <input name="optional" value="some value">
                </form>
            `;
            const form = container.querySelector('form');

            const result = FormValidator.validateForm(form);

            expect(result).toBe(true);
        });
    });
});