module.exports = {
    root: true,
    env: {
        node: true,
        browser: true,
    },
    extends: [
        'plugin:vue/essential',
        '@vue/airbnb',
    ],
    rules: {
        // allow async-await
        'generator-star-spacing': 0,
        // allow debugger during development
        'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'off',
        'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
        indent: 0,
        'no-trailing-space': 0,
        'max-len': 0,
        'no-param-reassign': 0,
    },
    plugins: [
        'vue',
    ],
    parserOptions: {
        parser: 'babel-eslint',
    },
};
