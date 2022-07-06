import Alpine from 'alpinejs'
import axios from 'axios';

window.Alpine = Alpine
window.axios = axios;
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Accept webpacks hot module replacement.
if (module.hot) {
    module.hot.accept()
}