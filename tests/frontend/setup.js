/**
 * Vitest Setup File
 * Global configuration for frontend tests
 */

import { vi } from 'vitest';

// Mock global objects that might be needed in components
global.console = {
    ...console,
    // Suppress console.warn during tests unless explicitly needed
    warn: vi.fn(),
    error: vi.fn(),
    log: vi.fn()
};

// Mock DOM helper methods that might not be available in jsdom
if (typeof window !== 'undefined') {
    // Mock any browser-specific APIs if needed
    window.scrollTo = vi.fn();

    // Mock localStorage if needed
    Object.defineProperty(window, 'localStorage', {
        value: {
            getItem: vi.fn(),
            setItem: vi.fn(),
            removeItem: vi.fn(),
            clear: vi.fn()
        },
        writable: true
    });

    // Mock window.location if needed
    Object.defineProperty(window, 'location', {
        value: {
            href: 'http://localhost:3000',
            origin: 'http://localhost:3000',
            pathname: '/',
            search: '',
            hash: ''
        },
        writable: true
    });

    // Ensure better DOM support
    if (!window.HTMLElement.prototype.insertAdjacentHTML) {
        window.HTMLElement.prototype.insertAdjacentHTML = function(position, html) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;

            switch (position) {
            case 'beforebegin':
                this.parentNode.insertBefore(tempDiv.firstChild, this);
                break;
            case 'afterbegin':
                this.insertBefore(tempDiv.firstChild, this.firstChild);
                break;
            case 'beforeend':
                while (tempDiv.firstChild) {
                    this.appendChild(tempDiv.firstChild);
                }
                break;
            case 'afterend':
                this.parentNode.insertBefore(tempDiv.firstChild, this.nextSibling);
                break;
            }
        };
    }
}

// Reset all mocks before each test
beforeEach(() => {
    vi.clearAllMocks();
});