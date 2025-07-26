/**
 * Basic Tests to verify test environment setup
 */

import { describe, it, expect } from 'vitest';

describe('Test Environment', () => {
    it('should run basic assertions', () => {
        expect(1 + 1).toBe(2);
        expect('hello').toBe('hello');
        expect(true).toBe(true);
    });

    it('should have access to DOM', () => {
        const element = document.createElement('div');
        element.textContent = 'Test content';

        expect(element.tagName).toBe('DIV');
        expect(element.textContent).toBe('Test content');
    });

    it('should handle arrays and objects', () => {
        const arr = [1, 2, 3];
        const obj = { name: 'test', value: 42 };

        expect(arr).toHaveLength(3);
        expect(obj.name).toBe('test');
        expect(obj.value).toBe(42);
    });
});

describe('JavaScript Modules', () => {
    it('should support ES6 modules', () => {
        // Test that module imports work
        expect(typeof describe).toBe('function');
        expect(typeof it).toBe('function');
        expect(typeof expect).toBe('function');
    });

    it('should support async/await', async () => {
        const promise = new Promise(resolve => {
            setTimeout(() => resolve('async result'), 10);
        });

        const result = await promise;
        expect(result).toBe('async result');
    });
});