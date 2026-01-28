// @ts-check
import { defineConfig } from "astro/config";

import react from "@astrojs/react";
import sitemap from "@astrojs/sitemap";
import node from "@astrojs/node";
import tailwindcss from "@tailwindcss/vite";
import path from "path";
import { fileURLToPath } from "url";
import { env } from "node:process";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const backendHost = env.BACKEND_HOST ?? "127.0.0.1";
const backendPort = env.BACKEND_PORT ?? "8081";
const backendTarget = env.BACKEND_URL ?? `http://${backendHost}:${backendPort}`;

// https://astro.build/config
export default defineConfig({
  output: "server",
  integrations: [react(), sitemap()],
  server: {
    port: 3000,
  },
  vite: {
    plugins: [tailwindcss()],
    resolve: {
      alias: {
        "@": path.resolve(__dirname, "./src"),
      },
    },
    server: {
      proxy: {
        "/api": backendTarget,
      },
    },
  },
  adapter: node({
    mode: "standalone",
  }),
});
