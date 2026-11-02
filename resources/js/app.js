import axios from 'axios';

import 'bootstrap/js/src/collapse';
import 'bootstrap/js/src/dropdown';
import './_common';
import HuntRecipes from './HuntRecipes';

window.axios = axios;
window.HuntRecipes = HuntRecipes;

const meta = name => document.querySelector(`meta[name="${name}"]`)?.content || '';
axios.defaults.headers.common['X-CSRF-TOKEN'] = meta('csrf-token');
axios.defaults.headers.common.Accept = 'application/json';

document.addEventListener('DOMContentLoaded', async () => {
    if (document.querySelector('[data-auth-screen], [data-email-link-finish], [data-account-email]')) {
        const { default: initializeAuth } = await import('./auth');
        initializeAuth();
    }

    const path = window.location.pathname.replace(/\/+$/, '') || '/';
    let page;

    if (path === '/welcome') page = import('./pages/welcome');
    else if (path === '/account') page = import('./pages/account');
    else if (path === '/about') page = import('./pages/about');
    else if (path === '/admin/chef-applications') page = import('./pages/admin-chef-approval');
    else if (path === '/home') page = import('./pages/home');
    else if (path === '/recipes') page = import('./pages/recipes');
    else if (path === '/recipes/create' || /^\/recipes\/\d+\/edit$/.test(path)) page = import('./pages/recipe-edit');
    else if (/^\/recipes\/[a-z0-9-]+$/.test(path)) page = import('./pages/recipe-single');
    else if (/^\/chefs\/\d+$/.test(path)) page = import('./pages/chef');
    else if (path === '/contact') page = import('./pages/contact');

    if (page) (await page).default();
});
