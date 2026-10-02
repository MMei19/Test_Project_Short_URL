import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    // qrcode.react อาจถูก resolve จาก node_modules ชั้นบนในเครื่องพัฒนา
    // บังคับให้ทุกแพ็กเกจใช้ React instance เดียว ป้องกัน Invalid Hook Call
    dedupe: ['react', 'react-dom'],
  },
  server: { proxy: { '/api': 'http://localhost:8000' } },
})
