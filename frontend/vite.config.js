import { fileURLToPath, URL } from 'node:url'

import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // Lee las variables de entorno del archivo activo (.env.development /
  // .env.production). VITE_PORT define el puerto dinámico de dev y preview.
  const env = loadEnv(mode, process.cwd(), '')
  const port = Number(env.VITE_PORT) || 5173

  return {
    plugins: [
      vue(),
      vueDevTools(),
    ],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      port,
      host: true,
    },
    preview: {
      port,
      host: true,
    },
  }
})