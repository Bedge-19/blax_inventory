/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./app/Views/**/*.php",
    "./public/js/**/*.js"
  ],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "tertiary-fixed": "#c9e6ff",
        "surface-container-highest": "#e0e3e5",
        "inverse-on-surface": "#eff1f3",
        "on-tertiary-fixed-variant": "#004c6e",
        "secondary": "#545f73",
        "outline": "#737686",
        "on-background": "#191c1e",
        "surface-variant": "#e0e3e5",
        "secondary-container": "#d5e0f8",
        "surface": "#f7f9fb",
        "on-secondary-container": "#586377",
        "on-tertiary-container": "#e4f2ff",
        "surface-container-low": "#f2f4f6",
        "primary": "#004ac6",
        "secondary-fixed-dim": "#bcc7de",
        "on-tertiary": "#ffffff",
        "tertiary": "#005a82",
        "on-error-container": "#93000a",
        "background": "#f7f9fb",
        "on-tertiary-fixed": "#001e2f",
        "surface-container": "#eceef0",
        "on-error": "#ffffff",
        "surface-tint": "#0053db",
        "surface-bright": "#f7f9fb",
        "error-container": "#ffdad6",
        "tertiary-container": "#0074a6",
        "on-primary": "#ffffff",
        "on-secondary": "#ffffff",
        "inverse-surface": "#2d3133",
        "on-primary-fixed-variant": "#003ea8",
        "primary-fixed": "#dbe1ff",
        "on-primary-container": "#eeefff",
        "primary-container": "#2563eb",
        "on-surface": "#191c1e",
        "on-secondary-fixed": "#111c2d",
        "outline-variant": "#c3c6d7",
        "error": "#ba1a1a",
        "inverse-primary": "#b4c5ff",
        "secondary-fixed": "#d8e3fb",
        "surface-dim": "#d8dadc",
        "surface-container-lowest": "#ffffff",
        "surface-container-high": "#e6e8ea",
        "on-secondary-fixed-variant": "#3c475a",
        "primary-fixed-dim": "#b4c5ff",
        "on-surface-variant": "#434655",
        "tertiary-fixed-dim": "#89ceff",
        "on-primary-fixed": "#00174b"
      },
      borderRadius: {
        "DEFAULT": "0.25rem",
        "lg": "0.5rem",
        "xl": "0.75rem",
        "full": "9999px"
      },
      spacing: {
        "xs": "0.25rem",
        "sm": "0.5rem",
        "base": "0.5rem",
        "md": "1rem",
        "lg": "1.5rem",
        "gutter": "1.5rem",
        "margin": "2rem",
        "xl": "2.5rem",
        "xxl": "3.5rem",
        "container-max": "1200px",
        "columns": "12"
      },
      fontFamily: {
        "headline-md": ["Inter", "sans-serif"],
        "headline-lg": ["Inter", "sans-serif"],
        "title-lg": ["Inter", "sans-serif"],
        "label-sm": ["Inter", "sans-serif"],
        "body-lg": ["Inter", "sans-serif"],
        "display": ["Inter", "sans-serif"],
        "body-md": ["Inter", "sans-serif"],
        "button": ["Inter", "sans-serif"]
      },
      fontSize: {
        "headline-md": ["1.5rem", { lineHeight: "2rem", letterSpacing: "-0.01em", fontWeight: "600" }],
        "headline-lg": ["2rem", { lineHeight: "2.5rem", letterSpacing: "-0.02em", fontWeight: "700" }],
        "title-lg": ["1.25rem", { lineHeight: "1.75rem", fontWeight: "600" }],
        "label-sm": ["0.75rem", { lineHeight: "1rem", letterSpacing: "0.01em", fontWeight: "500" }],
        "body-lg": ["1.125rem", { lineHeight: "1.75rem", fontWeight: "400" }],
        "display": ["2.5rem", { lineHeight: "3rem", letterSpacing: "-0.02em", fontWeight: "700" }],
        "body-md": ["1rem", { lineHeight: "1.5rem", fontWeight: "400" }],
        "button": ["0.875rem", { lineHeight: "1.25rem", fontWeight: "500" }]
      }
    }
  },
  plugins: [
    require("@tailwindcss/forms"),
    require("@tailwindcss/container-queries")
  ]
};
