import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    darkMode: "class", // ✅ enables dark mode via class

    theme: {
        extend: {},
    },
    plugins: [forms], // ✅ include forms if you're using @tailwindcss/forms
};
