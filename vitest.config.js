import { defineConfig } from 'vitest/config'

export default defineConfig({
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./tests/frontend/setup.js'],
    include: ['tests/frontend/**/*.test.js'],
    exclude: ['node_modules', 'vendor', 'tests/Unit', 'tests/Feature'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: [
        'node_modules/',
        'vendor/',
        'tests/',
        'vite.config.js',
        'vitest.config.js'
      ]
    }
  }
})