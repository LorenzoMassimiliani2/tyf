import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Send session cookies (when Vite/dev server on altra porta) and usa i nomi cookie/header di Laravel per CSRF
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Prefer XSRF-TOKEN cookie to avoid stale meta tokens after session regeneration
