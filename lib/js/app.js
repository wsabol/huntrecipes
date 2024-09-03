var $ = require('jquery');
window.jQuery = $;
require('icheck');
require('bootstrap');

import 'datatables';
import 'datatables.net-bs'

import './_common';
import HuntRecipes from './HuntRecipes'
import Login from './login'
import Home from './home'

// style
import 'bootstrap/dist/css/bootstrap.min.css'
import 'font-awesome/css/font-awesome.min.css'
import 'icheck/skins/all.css'
// import '../css/AdminLTE/AdminLTE.css'
// import '../css/AdminLTE/skin-green-light.css'
import '../css/style.css'

document.addEventListener("DOMContentLoaded", function() {

    const HuntRecipesApp = {
        init() {
            window.jQuery = $;

            HuntRecipes.init();
            HuntRecipes.tree('.sidebar');

            const pathname = window.location.pathname;

            switch (pathname) {

                case '/offline/':
                    break;

                case '/login/':
                    Login();
                    break;

                case '/home/':
                    Home();
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
