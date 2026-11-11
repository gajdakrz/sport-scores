import tsPlugin from "@typescript-eslint/eslint-plugin";
import tsParser from "@typescript-eslint/parser";

export default [
    {
        // rules and parser for TS files only
        files: ["**/*.ts"],
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                ecmaVersion: 2020,
                sourceType: "module",
                project: "./tsconfig.json", // tsconfig.json lives in api/
            },
        },
        plugins: {
            "@typescript-eslint": tsPlugin,
        },
        rules: {
            // basic recommended rules
            ...tsPlugin.configs.recommended.rules,
        },
    },
];
