import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// ADD THIS LINE
window.axios.defaults.headers.common['ngrok-skip-browser-warning'] = 'any-value';