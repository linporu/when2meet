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
}

// Reset all mocks before each test
beforeEach(() => {
    vi.clearAllMocks();
});