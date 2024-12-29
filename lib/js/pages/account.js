import $ from "jquery";
import axios from 'axios'
import HuntRecipes from '../HuntRecipes';

export default function() {
    const $settings_form = $('#frm-settings')
    const $settings_alert = $('#alert-settings')

    const $chef_form = $('#frm-chef')
    const $chef_alert = $('#alert-chef')

    const $favorites_container = $('#favorites-container')

    function loadFavorites() {

        $favorites_container.addClass('loading')

        axios.get('/api/v1/account/favorites/?user_id=' + HuntRecipes.current_user_id())
            .then(response => {
                response = response.data;
                console.log(response);

                $favorites_container.removeClass('loading').empty()

                for (let recipe of response.data) {
                    let $card = HuntRecipes.buildRecipeCard(recipe, response => {
                        if (!response) {
                            return
                        }
                        loadFavorites()
                    })

                    $favorites_container.append($('<div class="col">').append($card))
                }
            })
    }

    function init() {

        loadFavorites()

        $('.btn-logout').click(function(e) {
            e.preventDefault()

            if (!confirm('Are you sure you want to logout?')) {
                return false;
            }

            const $this = $(this)
            $this.addClass('disabled').html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

            axios.post('/api/v1/auth/sign-out.php')
                .then(() => {
                    window.location.href = '/welcome/';
                })
                .catch(error => {
                    $this.removeClass('disabled').html('Sign Out')
                    console.error(error.response.data.message);
                    alert(error.response.data.message)
                })
        });

        $settings_form.submit(function(evt) {
            evt.preventDefault();

            if ($('[type="submit"]', $settings_form).hasClass('disabled')) {
                return false;
            }

            $settings_alert
                .hide()
                .removeClass('alert-error alert-success')

            let payload = $settings_form.serializeObject();

            $('[type="submit"]', $settings_form)
                .addClass('disabled')
                .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

            axios.put('/api/v1/account/user/', payload)
                .then(response => {
                    $settings_alert
                        .addClass('alert-success')
                        .text('Account Updated!')
                        .show()

                })
                .catch(error => {
                    console.error(error.response.data.message);
                    $settings_alert
                        .addClass('alert-error')
                        .text(error.response.data.message)
                        .show()
                })
                .finally(()=> {
                    $('[type="submit"]', $settings_form)
                        .removeClass('disabled')
                        .html('Update Account')
                })
        })

        $chef_form.submit(function(evt) {
            evt.preventDefault()

            if ($('[type="submit"]', $chef_form).hasClass('disabled')) {
                return false;
            }

            $chef_alert
                .hide()
                .removeClass('alert-error alert-success')

            let payload = $chef_form.serializeObject();

            $('[type="submit"]', $chef_form)
                .addClass('disabled')
                .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

            axios.put('/api/v1/account/chef/', payload)
                .then(response => {
                    $chef_alert
                        .addClass('alert-success')
                        .text('Chef Profile Updated!')
                        .show()

                })
                .catch(error => {
                    console.error(error.response.data.message);
                    $chef_alert
                        .addClass('alert-error')
                        .text(error.response.data.message)
                        .show()
                })
                .finally(()=> {
                    $('[type="submit"]', $chef_form)
                        .removeClass('disabled')
                        .html('Update Chef Profile')
                })
        })

        $('.btn-verify-email').click(function(evt) {

            $(this).addClass('disabled').html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

            axios.post('/api/v1/account/user/verify.php', {
                user_id: HuntRecipes.current_user_id(),
            })
                .then(response => {
                    $('#alert-verification-email').hide()
                    $('#alert-verification-email-sent').fadeIn()
                })
        })
    }

    init()

}
