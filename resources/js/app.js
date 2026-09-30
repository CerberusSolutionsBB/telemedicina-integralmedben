import "../css/app.css";
import "./bootstrap";

import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

const appName = import.meta.env.VITE_APP_NAME || "";

createInertiaApp({
    title: (title) => (appName ? `${title} - ${appName}` : title),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue"),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        return app.mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});

router.on("success", (event) => {
    const csrfToken = event.detail.page.props.csrf_token;
    window.refreshCSRFToken(csrfToken);
});
