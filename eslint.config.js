import js from '@eslint/js'

export default [
  js.configs.recommended,
  {
    files: ['resources/js/**/*.js', 'tests/frontend/**/*.js'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: {
        // Browser globals
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
        fetch: 'readonly',
        navigator: 'readonly',
        process: 'readonly',
        global: 'readonly',
        Event: 'readonly',
        setTimeout: 'readonly',
        clearTimeout: 'readonly',
        getComputedStyle: 'readonly',
        require: 'readonly',
        Intl: 'readonly',
        
        // Vitest globals
        describe: 'readonly',
        it: 'readonly',
        test: 'readonly',
        expect: 'readonly',
        beforeEach: 'readonly',
        afterEach: 'readonly',
        beforeAll: 'readonly',
        afterAll: 'readonly',
        vi: 'readonly'
      }
    },
    rules: {
      'no-unused-vars': 'warn',
      'no-console': 'warn',
      'prefer-const': 'error',
      'no-var': 'error',
      'eqeqeq': 'error',
      'curly': 'error',
      'semi': ['error', 'always'],
      'quotes': ['error', 'single', { 'allowTemplateLiterals': true }],
      'indent': ['error', 4],
      'no-trailing-spaces': 'error',
      'comma-dangle': ['error', 'never']
    }
  },
  {
    // Ignore patterns
    ignores: [
      'node_modules/',
      'vendor/',
      'public/build/',
      'storage/',
      'bootstrap/cache/'
    ]
  }
]