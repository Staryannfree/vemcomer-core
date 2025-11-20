import globals from "globals";

export default [
  {
    ignores: ["node_modules/**", "vendor/**", "**/*.min.js"],
  },
  {
    files: ["assets/**/*.js"],
    languageOptions: {
      sourceType: "module",
      globals: {
        ...globals.browser,
        jQuery: "readonly",
        L: "readonly",
        VC_EXPLORE_MAP: "readonly",
        VC_RESTAURANT_MAP: "readonly",
        VC_RESTAURANTS_MAP: "readonly",
        VC_KDS: "readonly",
        VemComer: "readonly"
      }
    },
    rules: {
      "no-unused-vars": ["warn", { argsIgnorePattern: "^_" }],
      "no-undef": "error",
      eqeqeq: ["error", "always"],
      curly: ["error", "all"]
    }
  }
];
