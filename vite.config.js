import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";
import laravel from "laravel-vite-plugin";

export default defineConfig(({ mode }) => {
    const envDir = "../../../";

    Object.assign(process.env, loadEnv(mode, envDir));

    return {
        build: {
            emptyOutDir: true,
        },

        envDir,

        server: {
            host: process.env.VITE_HOST || "localhost",
            port: process.env.VITE_PORT || 5173,
        },

        plugins: [
            vue(),

            laravel({
                hotFile: "../../../public/scalapay-default-vite.hot",
                publicDirectory: "../../../public",
                buildDirectory: "themes/scalapay/default/build",
                input: [
                    "src/Resources/assets/js/app.js",
                ],
                refresh: true,
            }),
        ],
    };
});