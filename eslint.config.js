module.exports = {
    root: true,
    env: {
        browser: true,
        es2021: true,
        node: true,
    },
    extends: ['plugin:vue/vue3-recommended', 'eslint:recommended', 'prettier'],
    parserOptions: {
        ecmaVersion: 2021,
        sourceType: 'module',
    },
    ignorePatterns: ['vendor/', 'node_modules/', 'public/'],
    rules: {
        'vue/multi-word-component-names': 'off',
        'no-unused-vars': 'error',
    },
};
