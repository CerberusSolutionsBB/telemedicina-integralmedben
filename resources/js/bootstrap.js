import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.refreshCSRFToken = (token) => {
    if (!token) {
        return;
    }

    let csrfMeta = document.head.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) {
        csrfMeta = document.createElement("meta");
        csrfMeta.name = "csrf-token";
        document.head.appendChild(csrfMeta);
    }
    csrfMeta.setAttribute("content", token);
    window.axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
};

const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}
