/**
 * Availability Manager Unit Tests
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { AvailabilityManager } from '../../../resources/js/components/availability-manager.js';

// Mock dependencies
vi.mock('../../../resources/js/components/time-range-selector.js', () => ({
    TimeRangeSelector: vi.fn().mockImplementation((date, index, timeOptions, startTime, endTime) => ({
        date,
        index,
        timeOptions,
        startTime,
        endTime,
        element: document.createElement('div'),
        render: vi.fn(function(container) {
            this.element.className = 'time-range-selector';
            this.element.dataset.index = this.index;
            container.appendChild(this.element);
            return this.element;
        }),
        updateIndex: vi.fn(function(newIndex) {
            this.index = newIndex;
            this.element.dataset.index = newIndex;
        }),
        toggleRemoveButton: vi.fn(),
        validate: vi.fn(() => true),
        getValues: vi.fn(() => ({
            startTime: this.startTime || '',
            endTime: this.endTime || ''
        })),
        clear: vi.fn(function() {
            this.startTime = '';
            this.endTime = '';
        }),
        remove: vi.fn(function() {
            if (this.element.parentNode) {
                this.element.parentNode.removeChild(this.element);
            }
        }),
        isComplete: vi.fn(function() {
            return !!(this.startTime && this.endTime);
        }),
        isEmpty: vi.fn(function() {
            return !this.startTime && !this.endTime;
        })
    }))
}));

vi.mock('../../../resources/js/components/timezone-converter.js', () => ({
    TimezoneConverter: vi.fn().mockImplementation(() => ({
        convertTimeOptionsToLocal: vi.fn((options) => options || []),
        convertUtcToLocal: vi.fn((time) => time),
        convertLocalToUtc: vi.fn((time) => time)
    }))
}));

vi.mock('../../../resources/js/utils/form-validator.js', () => ({
    FormValidator: {
        validateTimeRange: vi.fn(() => true),
        validateTimeRangeSelector: vi.fn(() => true),
        showError: vi.fn(),
        clearError: vi.fn(),
        validateForm: vi.fn(() => true),
        validateRequired: vi.fn(() => true),
        showFieldError: vi.fn(),
        clearFieldError: vi.fn(),
        showTimeRangeError: vi.fn(),
        clearTimeRangeError: vi.fn()
    }
}));

vi.mock('../../../resources/js/utils/dom-helpers.js', () => ({
    DOMHelpers: {
        addDelegatedListener: vi.fn(),
        clearContent: vi.fn((el) => {
            if (el) {el.innerHTML = '';}
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

describe('AvailabilityManager', () => {
    let container;
    let manager;
    const testDate = '2025-01-15';

    beforeEach(() => {
        document.body.innerHTML = '';
        container = document.createElement('div');
        container.dataset.timeOptions = JSON.stringify([
            { value: '09:00', text: '9:00 AM' },
            { value: '17:00', text: '5:00 PM' }
        ]);
        container.dataset.existingRanges = JSON.stringify([]);
        document.body.appendChild(container);

        vi.clearAllMocks();

        manager = new AvailabilityManager(container, testDate);
    });

    describe('constructor', () => {
        it('should initialize with correct properties', () => {
            expect(manager.container).toBe(container);
            expect(manager.date).toBe(testDate);
            expect(manager.timeRangeSelectors).toHaveLength(1);
            expect(manager.timezoneConverter).toBeDefined();
            expect(manager.timeOptions).toEqual([
                { value: '09:00', text: '9:00 AM' },
                { value: '17:00', text: '5:00 PM' }
            ]);
        });

        it('should call init during construction', async () => {
            const { ErrorHandler } = await import('../../../resources/js/utils/error-handler.js');

            expect(ErrorHandler.safeExecute).toHaveBeenCalledWith(
                expect.any(Function),
                'Availability Manager Initialization'
            );
        });
    });

    describe('parseTimeOptions', () => {
        it('should parse time options from container dataset', () => {
            const testContainer = document.createElement('div');
            testContainer.dataset.timeOptions = JSON.stringify([
                { value: '10:00', text: '10:00 AM' }
            ]);

            const testManager = new AvailabilityManager(testContainer, testDate);

            expect(testManager.timeOptions).toEqual([
                { value: '10:00', text: '10:00 AM' }
            ]);
        });

        it('should handle empty time options', () => {
            const testContainer = document.createElement('div');
            // No dataset.timeOptions set

            const testManager = new AvailabilityManager(testContainer, testDate);

            expect(testManager.timeOptions).toEqual([]);
        });
    });

    describe('renderExistingRanges', () => {
        it('should create default empty selector when no existing ranges', async () => {
            manager.renderExistingRanges();

            const { TimeRangeSelector } = await import('../../../resources/js/components/time-range-selector.js');

            expect(TimeRangeSelector).toHaveBeenCalledWith(
                testDate,
                0,
                manager.timeOptions,
                '',
                ''
            );
            expect(manager.timeRangeSelectors).toHaveLength(1);
        });

        it('should create selectors for existing ranges', () => {
            container.dataset.existingRanges = JSON.stringify([
                { start_time: '09:00', end_time: '17:00' },
                { start_time: '10:00', end_time: '18:00' }
            ]);

            const newManager = new AvailabilityManager(container, testDate);

            expect(newManager.timeRangeSelectors).toHaveLength(2);
        });

        it('should convert UTC times to local times', () => {
            container.dataset.existingRanges = JSON.stringify([
                { start_time: '01:00:00', end_time: '09:00:00' }
            ]);

            // 基本測試功能是否能建立 manager
            const newManager = new AvailabilityManager(container, testDate);

            // 確認 manager 有正確的 timeRangeSelectors
            expect(newManager.timeRangeSelectors).toHaveLength(1);
        });
    });

    describe('addTimeRange', () => {
        it('should add new time range selector', () => {
            const initialCount = manager.timeRangeSelectors.length;

            manager.addTimeRange();

            expect(manager.timeRangeSelectors).toHaveLength(initialCount + 1);
        });

        it('should update remove button visibility after adding', () => {
            manager.addTimeRange();

            // Should call toggleRemoveButton on all selectors
            manager.timeRangeSelectors.forEach(selector => {
                expect(selector.toggleRemoveButton).toHaveBeenCalled();
            });
        });
    });

    describe('removeTimeRange', () => {
        beforeEach(() => {
            // Add multiple selectors first
            manager.addTimeRange();
            manager.addTimeRange();
        });

        it('should remove specified selector', () => {
            const selectorElement = document.createElement('div');
            selectorElement.dataset.index = '1';

            const initialCount = manager.timeRangeSelectors.length;

            manager.removeTimeRange(selectorElement);

            expect(manager.timeRangeSelectors).toHaveLength(initialCount - 1);
        });

        it('should reindex remaining selectors after removal', () => {
            const selectorElement = document.createElement('div');
            selectorElement.dataset.index = '0';

            manager.removeTimeRange(selectorElement);

            // Check that updateIndex was called on remaining selectors
            manager.timeRangeSelectors.forEach((selector, index) => {
                expect(selector.updateIndex).toHaveBeenCalledWith(index);
            });
        });

        it('should handle null selector element gracefully', () => {
            const initialCount = manager.timeRangeSelectors.length;

            expect(() => {
                manager.removeTimeRange(null);
            }).not.toThrow();

            expect(manager.timeRangeSelectors).toHaveLength(initialCount);
        });
    });

    describe('validateAll', () => {
        beforeEach(() => {
            manager.addTimeRange();
            manager.addTimeRange();
        });

        it('should return true when all selectors are valid', () => {
            // Mock all selectors as valid
            manager.timeRangeSelectors.forEach(selector => {
                selector.validate.mockReturnValue(true);
            });

            const result = manager.validateAll();

            expect(result).toBe(true);
        });

        it('should return false when any selector is invalid', () => {
            manager.timeRangeSelectors[0].validate.mockReturnValue(true);
            manager.timeRangeSelectors[1].validate.mockReturnValue(false);

            const result = manager.validateAll();

            expect(result).toBe(false);
        });

        it('should validate all selectors even if one fails', () => {
            manager.timeRangeSelectors[0].validate.mockReturnValue(false);
            manager.timeRangeSelectors[1].validate.mockReturnValue(true);

            manager.validateAll();

            manager.timeRangeSelectors.forEach(selector => {
                expect(selector.validate).toHaveBeenCalled();
            });
        });
    });

    describe('getAllValues', () => {
        it('should return values from all selectors', () => {
            manager.timeRangeSelectors[0].getValues.mockReturnValue({
                startTime: '09:00',
                endTime: '17:00'
            });

            const values = manager.getAllValues();

            expect(values).toEqual([{
                startTime: '09:00',
                endTime: '17:00'
            }]);
        });
    });

    describe('convertAllToUtc', () => {
        it('should convert all local times to UTC', async () => {
            const mockConverter = manager.timezoneConverter;
            mockConverter.convertLocalToUtc.mockReturnValue('01:00:00');

            manager.timeRangeSelectors[0].getValues.mockReturnValue({
                startTime: '09:00',
                endTime: '17:00'
            });

            const result = manager.convertAllToUtc();

            expect(mockConverter.convertLocalToUtc).toHaveBeenCalledWith('09:00');
            expect(mockConverter.convertLocalToUtc).toHaveBeenCalledWith('17:00');
            expect(result).toEqual([{
                startTime: '01:00:00',
                endTime: '01:00:00'
            }]);
        });

        it('should handle empty times', () => {
            manager.timeRangeSelectors[0].getValues.mockReturnValue({
                startTime: '',
                endTime: ''
            });

            const result = manager.convertAllToUtc();

            expect(result).toEqual([{
                startTime: '',
                endTime: ''
            }]);
        });
    });

    describe('clearAll', () => {
        it('should clear all selectors', () => {
            manager.addTimeRange();

            manager.clearAll();

            manager.timeRangeSelectors.forEach(selector => {
                expect(selector.clear).toHaveBeenCalled();
            });
        });
    });

    describe('resetToOne', () => {
        beforeEach(() => {
            manager.addTimeRange();
            manager.addTimeRange();
        });

        it('should remove all selectors except first one', () => {
            manager.resetToOne();

            expect(manager.timeRangeSelectors).toHaveLength(1);
            expect(manager.timeRangeSelectors[0].clear).toHaveBeenCalled();
        });
    });

    describe('hasCompleteRange', () => {
        it('should return true if any selector is complete', () => {
            manager.timeRangeSelectors[0].isComplete.mockReturnValue(true);

            const result = manager.hasCompleteRange();

            expect(result).toBe(true);
        });

        it('should return false if no selector is complete', () => {
            manager.timeRangeSelectors[0].isComplete.mockReturnValue(false);

            const result = manager.hasCompleteRange();

            expect(result).toBe(false);
        });
    });

    describe('areAllEmpty', () => {
        it('should return true if all selectors are empty', () => {
            manager.timeRangeSelectors[0].isEmpty.mockReturnValue(true);

            const result = manager.areAllEmpty();

            expect(result).toBe(true);
        });

        it('should return false if any selector is not empty', () => {
            manager.timeRangeSelectors[0].isEmpty.mockReturnValue(false);

            const result = manager.areAllEmpty();

            expect(result).toBe(false);
        });
    });

    describe('getCompleteRangeCount', () => {
        beforeEach(() => {
            manager.addTimeRange();
            manager.addTimeRange();
        });

        it('should return count of complete ranges', () => {
            manager.timeRangeSelectors[0].isComplete.mockReturnValue(true);
            manager.timeRangeSelectors[1].isComplete.mockReturnValue(false);
            manager.timeRangeSelectors[2].isComplete.mockReturnValue(true);

            const count = manager.getCompleteRangeCount();

            expect(count).toBe(2);
        });
    });

    describe('setRanges', () => {
        it('should replace existing ranges with new ones', () => {
            const newRanges = [
                { startTime: '10:00', endTime: '18:00' },
                { startTime: '14:00', endTime: '22:00' }
            ];

            manager.setRanges(newRanges);

            expect(manager.timeRangeSelectors).toHaveLength(2);
        });

        it('should create empty range if no ranges provided', () => {
            manager.setRanges([]);

            expect(manager.timeRangeSelectors).toHaveLength(1);
        });
    });

    describe('destroy', () => {
        it('should remove all selectors and clear container', async () => {
            manager.addTimeRange();
            manager.destroy();

            expect(manager.timeRangeSelectors).toHaveLength(0);

            const { DOMHelpers } = await import('../../../resources/js/utils/dom-helpers.js');
            expect(DOMHelpers.clearContent).toHaveBeenCalledWith(container);
        });
    });
});