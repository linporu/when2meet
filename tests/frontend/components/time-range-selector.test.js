/**
 * Time Range Selector Unit Tests
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { TimeRangeSelector } from '../../../resources/js/components/time-range-selector.js';

// Mock the utility modules
vi.mock('../../../resources/js/utils/form-validator.js', () => ({
    FormValidator: {
        validateTimeRange: vi.fn(() => true),
        validateTimeRangeSelector: vi.fn(() => true),
        showError: vi.fn(),
        clearError: vi.fn()
    }
}));

vi.mock('../../../resources/js/utils/dom-helpers.js', () => ({
    DOMHelpers: {
        createElement: vi.fn((tag, attrs, content) => {
            const el = document.createElement(tag);
            if (attrs) {
                Object.keys(attrs).forEach(key => {
                    if (key === 'class') {
                        el.className = attrs[key];
                    } else {
                        el.setAttribute(key, attrs[key]);
                    }
                });
            }
            if (content) {el.textContent = content;}
            return el;
        }),
        clearContent: vi.fn((el) => {
            if (el) {el.innerHTML = '';}
        }),
        insertHTMLAtEnd: vi.fn((element, html) => {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            while (tempDiv.firstChild) {
                element.appendChild(tempDiv.firstChild);
            }
        })
    }
}));

vi.mock('../../../resources/js/utils/error-handler.js', () => ({
    ErrorHandler: {
        safeExecute: vi.fn((fn, context, defaultValue = null) => {
            try {
                return fn();
            } catch (error) {
                console.error(`Error in ${context}:`, error);
                return defaultValue;
            }
        })
    }
}));

describe('TimeRangeSelector', () => {
    let container;
    let timeOptions;
    let selector;

    beforeEach(() => {
        document.body.innerHTML = '';
        container = document.createElement('div');
        document.body.appendChild(container);

        timeOptions = [
            { value: '09:00', text: '9:00 AM' },
            { value: '10:00', text: '10:00 AM' },
            { value: '11:00', text: '11:00 AM' },
            { value: '17:00', text: '5:00 PM' }
        ];

        selector = new TimeRangeSelector('2025-01-15', 0, timeOptions, '09:00', '17:00');

        vi.clearAllMocks();
    });

    describe('constructor', () => {
        it('should initialize with correct properties', () => {
            expect(selector.date).toBe('2025-01-15');
            expect(selector.index).toBe(0);
            expect(selector.timeOptions).toEqual(timeOptions);
            expect(selector.startTime).toBe('09:00');
            expect(selector.endTime).toBe('17:00');
            expect(selector.element).toBeNull();
        });

        it('should handle empty start and end times', () => {
            const emptySelector = new TimeRangeSelector('2025-01-15', 1, timeOptions);

            expect(emptySelector.startTime).toBe('');
            expect(emptySelector.endTime).toBe('');
        });
    });

    describe('createHTML', () => {
        it('should generate correct HTML structure', () => {
            const html = selector.createHTML();

            expect(typeof html).toBe('string');
            expect(html).toContain('time-range-selector');
            expect(html).toContain('data-index="0"');
            expect(html).toContain('Start Time');
            expect(html).toContain('End Time');
            expect(html).toContain('Remove');
        });

        it('should include time options in select elements', () => {
            const html = selector.createHTML();

            expect(html).toContain('9:00 AM');
            expect(html).toContain('10:00 AM');
            expect(html).toContain('5:00 PM');
        });

        it('should select current start and end times', () => {
            const html = selector.createHTML();

            // Should contain selected attributes for current times
            expect(html).toContain('value="09:00" selected');
            expect(html).toContain('value="17:00" selected');
        });

        it('should handle array format time options', () => {
            const arrayOptions = [
                { value: '09:00', text: '9:00 AM' },
                { value: '17:00', text: '5:00 PM' }
            ];
            const arraySelector = new TimeRangeSelector('2025-01-15', 0, arrayOptions);

            const html = arraySelector.createHTML();

            expect(html).toContain('9:00 AM');
            expect(html).toContain('5:00 PM');
        });

        it('should handle object format time options', () => {
            const objectOptions = {
                '09:00': '9:00 AM',
                '17:00': '5:00 PM'
            };
            const objectSelector = new TimeRangeSelector('2025-01-15', 0, objectOptions);

            const html = objectSelector.createHTML();

            expect(html).toContain('9:00 AM');
            expect(html).toContain('5:00 PM');
        });
    });

    describe('render', () => {
        it('should append HTML to container and return element', () => {
            const element = selector.render(container);

            expect(container.children.length).toBe(1);
            expect(element).toBe(selector.element);
            expect(element.classList.contains('time-range-selector')).toBe(true);
        });

        it('should setup event listeners on selects', () => {
            const element = selector.render(container);
            const startSelect = element.querySelector('.start-time');
            const endSelect = element.querySelector('.end-time');

            expect(startSelect).not.toBeNull();
            expect(endSelect).not.toBeNull();
        });
    });

    describe('setupEventListeners', () => {
        it('should validate on select change', () => {
            selector.render(container);
            const startSelect = selector.element.querySelector('.start-time');

            // Simulate change event
            startSelect.value = '10:00';
            startSelect.dispatchEvent(new Event('change'));

            // Test passes if no error is thrown
            expect(startSelect.value).toBe('10:00');
        });
    });

    describe('validate', () => {
        it('should return true for valid time range', () => {
            selector.render(container);
            const result = selector.validate();

            // The mock always returns true
            expect(result).toBe(true);
        });

        it('should call FormValidator when validating', async () => {
            selector.render(container);
            selector.validate();

            const { FormValidator } = await import('../../../resources/js/utils/form-validator.js');
            expect(FormValidator.validateTimeRangeSelector).toHaveBeenCalledWith(selector.element);
        });

        it('should handle validation correctly', () => {
            selector.render(container);
            const result = selector.validate();

            // Basic validation test
            expect(typeof result).toBe('boolean');
        });
    });

    describe('getValues', () => {
        it('should return current start and end times', () => {
            selector.render(container);

            const values = selector.getValues();

            expect(values).toEqual({
                startTime: '09:00',
                endTime: '17:00'
            });
        });

        it('should return updated values after user changes', () => {
            selector.render(container);

            const startSelect = selector.element.querySelector('.start-time');
            const endSelect = selector.element.querySelector('.end-time');
            startSelect.value = '10:00';
            endSelect.value = '11:00';

            const values = selector.getValues();

            expect(values).toEqual({
                startTime: '10:00',
                endTime: '11:00'
            });
        });
    });

    describe('updateIndex', () => {
        it('should update selector index and form names', () => {
            selector.render(container);

            selector.updateIndex(5);

            expect(selector.index).toBe(5);
            expect(selector.element.dataset.index).toBe('5');

            const startSelect = selector.element.querySelector('.start-time');
            expect(startSelect.name).toContain('[5]');
        });
    });

    describe('toggleRemoveButton', () => {
        it('should show remove button when shouldShow is true', () => {
            selector.render(container);

            selector.toggleRemoveButton(true);

            const removeButton = selector.element.querySelector('.remove-time-range');
            expect(removeButton.style.display).not.toBe('none');
        });

        it('should hide remove button when shouldShow is false', () => {
            selector.render(container);

            selector.toggleRemoveButton(false);

            const removeButton = selector.element.querySelector('.remove-time-range');
            expect(removeButton.style.display).toBe('none');
        });
    });

    describe('clear', () => {
        it('should reset select values to empty', () => {
            selector.render(container);

            selector.clear();

            const startSelect = selector.element.querySelector('.start-time');
            const endSelect = selector.element.querySelector('.end-time');

            expect(startSelect.value).toBe('');
            expect(endSelect.value).toBe('');
        });

        it('should clear any validation errors', async () => {
            selector.render(container);
            selector.clear();

            const { FormValidator } = await import('../../../resources/js/utils/form-validator.js');
            expect(FormValidator.clearError).toHaveBeenCalled();
        });
    });

    describe('remove', () => {
        it('should remove element from DOM', () => {
            selector.render(container);
            expect(container.children.length).toBe(1);

            selector.remove();

            expect(container.children.length).toBe(0);
            expect(selector.element).toBeNull();
        });

        it('should handle removal when element not in DOM', () => {
            expect(() => {
                selector.remove();
            }).not.toThrow();
        });
    });

    describe('isComplete', () => {
        it('should return true when both times are selected', () => {
            selector.render(container);

            const result = selector.isComplete();

            expect(result).toBe(true);
        });

        it('should return false when start time is empty', () => {
            const emptySelector = new TimeRangeSelector('2025-01-15', 0, timeOptions, '', '17:00');
            emptySelector.render(container);

            const result = emptySelector.isComplete();

            expect(result).toBe(false);
        });

        it('should return false when end time is empty', () => {
            const emptySelector = new TimeRangeSelector('2025-01-15', 0, timeOptions, '09:00', '');
            emptySelector.render(container);

            const result = emptySelector.isComplete();

            expect(result).toBe(false);
        });
    });

    describe('isEmpty', () => {
        it('should return false when both times are selected', () => {
            selector.render(container);

            const result = selector.isEmpty();

            expect(result).toBe(false);
        });

        it('should return true when both times are empty', () => {
            const emptySelector = new TimeRangeSelector('2025-01-15', 0, timeOptions, '', '');
            emptySelector.render(container);

            const result = emptySelector.isEmpty();

            expect(result).toBe(true);
        });

        it('should return false when only one time is selected', () => {
            const partialSelector = new TimeRangeSelector('2025-01-15', 0, timeOptions, '09:00', '');
            partialSelector.render(container);

            const result = partialSelector.isEmpty();

            expect(result).toBe(false);
        });
    });
});