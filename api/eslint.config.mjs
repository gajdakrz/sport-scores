import tsPlugin from "@typescript-eslint/eslint-plugin";
import tsParser from "@typescript-eslint/parser";

export default [
    {
        // reguły i parser tylko dla TS
        files: ["**/*.ts"],
        languageOptions: {
            parser: tsParser,
            parserOptions: {
                ecmaVersion: 2020,
                sourceType: "module",
                project: "./tsconfig.json", // jeśli TS config jest w api/
            },
        },
        plugins: {
            "@typescript-eslint": tsPlugin,
        },
        rules: {
            // podstawowe zalecane reguły
            ...tsPlugin.configs.recommended.rules,
        },
    },
];
