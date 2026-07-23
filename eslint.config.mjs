import { FlatCompat } from "@eslint/eslintrc";

const compat = new FlatCompat({ baseDirectory: import.meta.dirname });

const config = [
  ...compat.extends("next/core-web-vitals", "next/typescript"),
  {
    ignores: [
      "node_modules/**",
      ".next/**",
      "out/**",
      "playwright-report/**",
      "test-results/**",
      "next-env.d.ts",
      // The WordPress marketing plugin has its own toolchain, ESLint config,
      // and browser-JS globals — linted from within plugin/, not by the
      // Next.js config here.
      "plugin/**",
    ],
  },
];

export default config;
