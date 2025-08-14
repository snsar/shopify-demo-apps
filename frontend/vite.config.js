import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  base: "/frontend/",
  build: {
    outDir: "dist",
    assetsDir: "assets",
    sourcemap: false,
    minify: 'terser',
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['react', 'react-dom'],
          polaris: ['@shopify/polaris'],
          appbridge: ['@shopify/app-bridge-react']
        }
      }
    }
  }
});
