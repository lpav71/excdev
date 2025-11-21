import '../scss/app.scss';
import { createApp } from 'vue';
import axios from 'axios';

// Set up axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Content-Type'] = 'application/json';
axios.defaults.headers.common['Accept'] = 'application/json';

// Get CSRF token from meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Import Vue components
import Login from './components/balance/Login.vue';
import Dashboard from './components/balance/Dashboard.vue';
import OperationsHistory from './components/balance/OperationsHistory.vue';

// Initialize Vue apps based on element IDs
document.addEventListener('DOMContentLoaded', () => {
    const loginApp = document.getElementById('login-app');
    if (loginApp) {
        createApp(Login).mount('#login-app');
    }

    const dashboardApp = document.getElementById('dashboard-app');
    if (dashboardApp) {
        createApp(Dashboard).mount('#dashboard-app');
    }

    const operationsApp = document.getElementById('operations-app');
    if (operationsApp) {
        createApp(OperationsHistory).mount('#operations-app');
    }
});

