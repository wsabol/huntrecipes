var $ = require('jquery');
window.jQuery = $;
// require('icheck');
require('bootstrap');

import 'datatables';
import 'datatables.net-bs'

import './_common';
import Account from './pages/account'
import AccountIdentify from './pages/account-identify'
import AccountRecover from './pages/account-recover'
import AccountRecoverSignIn from './pages/account-recover-sign-in'
import Chef from './pages/chef'
import Contact from './pages/contact'
import Home from './pages/home'
import Join from './pages/join'
import Recipes from './pages/recipes'
import RecipeEdit from './pages/recipe-edit'
import RecipeSingle from './pages/recipe-single'
import SignIn from './pages/sign-in'
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

                case '/account/':
                    Account();
                    break;

                case '/account/identify/':
                    AccountIdentify();
                    break;

                case '/account/join/':
                    Join();
                    break;

                case '/account/recover/':
                    AccountRecover();
                    break;

                case '/account/recover/sign-in/':
                    AccountRecoverSignIn();
                    break;

                case '/account/sign-in/':
                    SignIn();
                    break;

                case '/home/':
                    Home();
                    break;

                case '/recipes/':
                    Recipes();
                    break;

                case '/recipes/recipe/edit/':
                    RecipeEdit();

                case '/welcome/':
                    Welcome();
                    break;

                case '/contact/':
                    Contact();
                    break;

                case '/chef/':
                    Chef();
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
