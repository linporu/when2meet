/**
 * DOM Helpers Unit Tests
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import { DOMHelpers } from '../../../resources/js/utils/dom-helpers.js';

describe('DOMHelpers', () => {
    let container;

    beforeEach(() => {
        document.body.innerHTML = '';
        container = document.createElement('div');
        container.id = 'test-container';
        document.body.appendChild(container);
        vi.clearAllMocks();
    });

    describe('clearContent', () => {
        it('should clear all content from element', () => {
            container.innerHTML = '<p>Test content</p><span>More content</span>';

            DOMHelpers.clearContent(container);

            expect(container.innerHTML).toBe('');
        });

        it('should handle null element gracefully', () => {
            expect(() => {
                DOMHelpers.clearContent(null);
            }).not.toThrow();
        });

        it('should handle undefined element gracefully', () => {
            expect(() => {
                DOMHelpers.clearContent(undefined);
            }).not.toThrow();
        });
    });

    describe('addDelegatedListener', () => {
        it('should add event listener to parent for child selector', () => {
            container.innerHTML = '<button class="test-btn">Click me</button>';
            const button = container.querySelector('.test-btn');
            const handler = vi.fn();

            DOMHelpers.addDelegatedListener(container, '.test-btn', 'click', handler);

            button.click();

            expect(handler).toHaveBeenCalledOnce();
            expect(handler).toHaveBeenCalledWith(
                expect.any(Event),
                button
            );
        });

        it('should not trigger handler for non-matching elements', () => {
            container.innerHTML = '<button class="other-btn">Click me</button>';
            const button = container.querySelector('.other-btn');
            const handler = vi.fn();

            DOMHelpers.addDelegatedListener(container, '.test-btn', 'click', handler);

            button.click();

            expect(handler).not.toHaveBeenCalled();
        });

        it('should handle multiple matching elements', () => {
            container.innerHTML = `
                <button class="test-btn" data-id="1">Button 1</button>
                <button class="test-btn" data-id="2">Button 2</button>
            `;
            const buttons = container.querySelectorAll('.test-btn');
            const handler = vi.fn();

            DOMHelpers.addDelegatedListener(container, '.test-btn', 'click', handler);

            buttons[0].click();
            buttons[1].click();

            expect(handler).toHaveBeenCalledTimes(2);
            expect(handler).toHaveBeenNthCalledWith(1, expect.any(Event), buttons[0]);
            expect(handler).toHaveBeenNthCalledWith(2, expect.any(Event), buttons[1]);
        });

        it('should handle nested elements correctly', () => {
            container.innerHTML = `
                <div class="parent">
                    <button class="test-btn">
                        <span>Click text</span>
                    </button>
                </div>
            `;
            const span = container.querySelector('span');
            const button = container.querySelector('.test-btn');
            const handler = vi.fn();

            DOMHelpers.addDelegatedListener(container, '.test-btn', 'click', handler);

            span.click(); // Click on nested span should trigger button handler

            expect(handler).toHaveBeenCalledOnce();
            expect(handler).toHaveBeenCalledWith(
                expect.any(Event),
                button
            );
        });

        it('should handle null parent gracefully', () => {
            const handler = vi.fn();

            expect(() => {
                DOMHelpers.addDelegatedListener(null, '.test-btn', 'click', handler);
            }).not.toThrow();
        });
    });

    describe('createElement', () => {
        it('should create element with tag name', () => {
            const element = DOMHelpers.createElement('div');

            expect(element.tagName).toBe('DIV');
        });

        it('should create element with attributes', () => {
            const element = DOMHelpers.createElement('input', {
                type: 'text',
                id: 'test-input',
                class: 'form-control'
            });

            expect(element.tagName).toBe('INPUT');
            expect(element.type).toBe('text');
            expect(element.id).toBe('test-input');
            expect(element.className).toBe('form-control');
        });

        it('should create element with content', () => {
            const element = DOMHelpers.createElement('p', {}, 'Test content');

            expect(element.textContent).toBe('Test content');
        });

        it('should create element with HTML content', () => {
            const element = DOMHelpers.createElement('div');
            element.innerHTML = '<span>HTML content</span>';

            expect(element.querySelector('span')).not.toBeNull();
            expect(element.querySelector('span').textContent).toBe('HTML content');
        });
    });

    describe('findClosest', () => {
        it('should find closest matching ancestor', () => {
            container.innerHTML = `
                <div class="parent">
                    <div class="child">
                        <span class="target">Target</span>
                    </div>
                </div>
            `;
            const target = container.querySelector('.target');

            const result = DOMHelpers.findClosest(target, '.parent');

            expect(result).toBe(container.querySelector('.parent'));
        });

        it('should return null if no match found', () => {
            container.innerHTML = '<span class="target">Target</span>';
            const target = container.querySelector('.target');

            const result = DOMHelpers.findClosest(target, '.nonexistent');

            expect(result).toBeNull();
        });

        it('should return element itself if it matches', () => {
            container.innerHTML = '<div class="target">Target</div>';
            const target = container.querySelector('.target');

            const result = DOMHelpers.findClosest(target, '.target');

            expect(result).toBe(target);
        });
    });
});