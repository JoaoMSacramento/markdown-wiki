/**
 * SPDX-FileCopyrightText: 2026 João Sacramento <jlmsacramento@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [vue()],

    define: {
        appName: JSON.stringify('native_wiki'),
        appVersion: JSON.stringify('0.1.0'),
    },

    build: {
        outDir: 'js',
        emptyOutDir: true,

        rollupOptions: {
            input: 'src/main.js',

            output: {
                format: 'iife',
                inlineDynamicImports: true,
                entryFileNames: 'native_wiki.js',
                assetFileNames: 'native_wiki.[ext]',
            },
        },
    },
})