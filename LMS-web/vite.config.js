import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
// `npm run build` writes straight into the Laravel backend's public/web, so
// `php artisan serve` serves this site at `/` (see routes/web.php). Assets are
// requested from /web/ in production; `npm run dev` still serves from `/`.
export default defineConfig(({ command }) => ({
  plugins: [react()],
  base: command === 'build' ? '/web/' : '/',
  build: {
    outDir: '../public/web',
    emptyOutDir: true,
  },
}))
