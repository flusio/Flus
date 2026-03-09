import globals from "globals";
import { defineConfig } from "eslint/config";
import pluginJs from "@eslint/js";

export default defineConfig([
    {
        languageOptions: {
            globals: globals.browser,
        }
    },
    pluginJs.configs.recommended,
]);
