import js from "@eslint/js";
import globals from "globals";
import tseslint from "typescript-eslint";

export default [
    {
        files: ["**/*.{js,mjs,cjs,ts,mts,cts}"],
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
            },
        },
    },
    js.configs.recommended,
    ...tseslint.configs.recommended,
    {
        files: ["assets/**/*.{ts,tsx,js,jsx}"],
        languageOptions: {
            parserOptions: {
                project: "./tsconfig.json",
            },
        },
        rules: {
            // Możesz dodać własne reguły tutaj
            "@typescript-eslint/no-unused-vars": "warn",
            "@typescript-eslint/no-explicit-any": "warn",
        },
    },
    {
        ignores: [
            "node_modules/**",
            "public/build/**",
            "var/**",
            "vendor/**",
            "webpack.config.js",
        ],
    },
];
