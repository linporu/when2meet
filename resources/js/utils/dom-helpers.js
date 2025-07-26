/**
 * DOM Helper Utilities
 * Common DOM manipulation and query helpers
 */

export class DOMHelpers {
    /**
     * Safe element query selector
     */
    static querySelector(selector, parent = document) {
        return parent.querySelector(selector);
    }

    /**
     * Safe element query selector all
     */
    static querySelectorAll(selector, parent = document) {
        return parent.querySelectorAll(selector);
    }

    /**
     * Check if element exists
     */
    static exists(selector, parent = document) {
        return parent.querySelector(selector) !== null;
    }

    /**
     * Add event listener with delegation
     */
    static addDelegatedListener(parent, selector, event, handler) {
        parent.addEventListener(event, (e) => {
            if (e.target.matches(selector) || e.target.closest(selector)) {
                const target = e.target.matches(selector)
                    ? e.target
                    : e.target.closest(selector);
                handler.call(target, e, target);
            }
        });
    }

    /**
     * Create element with attributes and content
     */
    static createElement(tag, attributes = {}, content = '') {
        const element = document.createElement(tag);

        Object.entries(attributes).forEach(([key, value]) => {
            if (key === 'className') {
                element.className = value;
            } else if (key === 'innerHTML') {
                element.innerHTML = value;
            } else if (key === 'textContent') {
                element.textContent = value;
            } else {
                element.setAttribute(key, value);
            }
        });

        if (content) {
            element.textContent = content;
        }

        return element;
    }

    /**
     * Insert HTML after element
     */
    static insertHTMLAfter(element, html) {
        element.insertAdjacentHTML('afterend', html);
    }

    /**
     * Insert HTML before element
     */
    static insertHTMLBefore(element, html) {
        element.insertAdjacentHTML('beforebegin', html);
    }

    /**
     * Insert HTML at end of element
     */
    static insertHTMLAtEnd(element, html) {
        element.insertAdjacentHTML('beforeend', html);
    }

    /**
     * Insert HTML at beginning of element
     */
    static insertHTMLAtStart(element, html) {
        element.insertAdjacentHTML('afterbegin', html);
    }

    /**
     * Remove element safely
     */
    static removeElement(element) {
        if (element && element.parentNode) {
            element.parentNode.removeChild(element);
        }
    }

    /**
     * Get closest parent with selector
     */
    static getClosest(element, selector) {
        return element.closest(selector);
    }

    /**
     * Show element
     */
    static show(element) {
        if (element) {
            element.style.display = '';
        }
    }

    /**
     * Hide element
     */
    static hide(element) {
        if (element) {
            element.style.display = 'none';
        }
    }

    /**
     * Toggle element visibility
     */
    static toggle(element) {
        if (element) {
            const isHidden = element.style.display === 'none' ||
                           getComputedStyle(element).display === 'none';
            element.style.display = isHidden ? '' : 'none';
        }
    }

    /**
     * Add CSS class
     */
    static addClass(element, className) {
        if (element) {
            element.classList.add(className);
        }
    }

    /**
     * Remove CSS class
     */
    static removeClass(element, className) {
        if (element) {
            element.classList.remove(className);
        }
    }

    /**
     * Toggle CSS class
     */
    static toggleClass(element, className) {
        if (element) {
            element.classList.toggle(className);
        }
    }

    /**
     * Has CSS class
     */
    static hasClass(element, className) {
        return element ? element.classList.contains(className) : false;
    }

    /**
     * Set multiple attributes
     */
    static setAttributes(element, attributes) {
        if (element) {
            Object.entries(attributes).forEach(([key, value]) => {
                element.setAttribute(key, value);
            });
        }
    }

    /**
     * Get element data attribute
     */
    static getData(element, key) {
        return element ? element.dataset[key] : null;
    }

    /**
     * Set element data attribute
     */
    static setData(element, key, value) {
        if (element) {
            element.dataset[key] = value;
        }
    }

    /**
     * Clear element content
     */
    static clearContent(element) {
        if (element) {
            element.innerHTML = '';
        }
    }

    /**
     * Generate select options HTML
     */
    static generateSelectOptions(options, selectedValue = '') {
        if (Array.isArray(options)) {
            return options.map(option => {
                const value = option.value || option;
                const text = option.text || option.textContent || option;
                const selected = value === selectedValue ? 'selected' : '';
                return `<option value="${value}" ${selected}>${text}</option>`;
            }).join('');
        } else {
            return Object.entries(options).map(([value, text]) => {
                const selected = value === selectedValue ? 'selected' : '';
                return `<option value="${value}" ${selected}>${text}</option>`;
            }).join('');
        }
    }

    /**
     * Debounce function execution
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Wait for DOM to be ready
     */
    static ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }
}