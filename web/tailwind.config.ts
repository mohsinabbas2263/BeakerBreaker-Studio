import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./app/**/*.{ts,tsx}",
    "./components/**/*.{ts,tsx}",
    "./lib/**/*.{ts,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        blue:     "#4FC3F7",
        "blue-d": "#29B6F6",
        green:    "#4CAF82",
        "green-d":"#2E7D57",
        orange:   "#FF8A50",
        "orange-d":"#FF6D00",
        purple:   "#9575CD",
        yellow:   "#FFD54F",
        sky:      "#E8F4FD",
        mint:     "#C8F7E4",
        lemon:    "#FFF9C4",
        coral:    "#FFD6C8",
        ink:      "#2D3748",
        sub:      "#718096",
        muted:    "#A0AEC0",
        red:      "#F56565",
      },
      fontFamily: {
        fredoka: ["Fredoka", "sans-serif"],
        nunito:  ["Nunito", "sans-serif"],
      },
      fontWeight: {
        "400": "400",
        "600": "600",
        "700": "700",
        "800": "800",
        "900": "900",
      },
      borderRadius: {
        card: "18px",
      },
      boxShadow: {
        card: "0 8px 32px rgba(0,0,0,.10)",
        blue: "0 8px 24px rgba(79,195,247,.4)",
      },
      transitionTimingFunction: {
        bouncy: "cubic-bezier(.68,-0.55,.27,1.55)",
      },
    },
  },
  plugins: [],
};

export default config;
