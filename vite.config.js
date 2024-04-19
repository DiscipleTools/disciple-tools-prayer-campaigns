import { defineConfig } from 'vite'
export default defineConfig({

  build: {
    lib: {
      name: 'DiscipleToolsPrayerCampaigns',
      entry: 'parts/main.js',
    },
    rollupOptions: {
      output: {
        dir: `dist`,
        entryFileNames:  `[name]-bundle.js`,
        chunkFileNames: `[name]-bundle.js`,
        assetFileNames: `[name].[ext]`
      }
    },
  },
})