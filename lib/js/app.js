var $ = require('jquery');
window.jQuery = $;
// require('icheck');
require('bootstrap');

import 'datatables';
import 'datatables.net-bs'

import './_common';
import Account from './pages/account'
import Home from './pages/home'
import Recipes from './pages/recipes'
import RecipeSingle from './pages/recipe-single'
import Signin from './pages/sign-in'
import Welcome from './pages/welcome'

// style
import '../scss/app.scss'

document.addEventListener("DOMContentLoaded", function() {

    const HuntRecipesApp = {
        init() {
            window.jQuery = $;

            const pathname = window.location.pathname;

            switch (pathname) {

                case '/offline/':
                    break;

                case '/sign-in/':
                    Signin();
                    break;

                case '/account/':
                    Account();
                    break;

                case '/home/':
                    Home();
                    break;

                case '/recipes/':
                    Recipes();
                    break;

                case '/welcome/':
                    Welcome();
                    break;

                case '/recipes/recipe/':
                    RecipeSingle();
                    break;

                default:
                    throw "nav not handled " + pathname

            }
        },
    };

    HuntRecipesApp.init()

    // const checkWorkerActive = async () => {
    //     // Get registration object
    //     const swRegistration = await navigator.serviceWorker.getRegistration();
    //     return !!swRegistration;
    // }
    //
    // const registerWorker = async () => {
    //     try {
    //         // Define the serviceworker and an optional options object.
    //         const worker = navigator.serviceWorker;
    //
    //         // Register the worker and return the registration object to the calling function
    //         return await worker.register('/serviceworker.js');
    //     } catch (e) {
    //         console.error(e);
    //     }
    // }
    //
    // checkWorkerActive()
    //     .then(registered => {
    //         if (!registered) registerWorker()
    //     })

});

// When the DOM is done loading, call these functions:
// window.addEventListener('load', () => {
//
//     const checkOnline = () => {
//         const isOnline = navigator.onLine;
//         const indicator = $('.online-status-header');
//         if (!indicator) {
//             return
//         }
//
//         const is_visible = indicator.is(':visible');
//         if (isOnline && is_visible) {
//             indicator.hide();
//         } else if (!isOnline && !is_visible) {
//             indicator.show();
//         }
//     };
//
//     checkOnline();
//     setInterval(() => checkOnline(), 2000);
// });
