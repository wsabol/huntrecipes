import $ from "jquery";
import axios from 'axios'
import Modal from 'bootstrap/js/src/modal'
import HuntRecipes from '../HuntRecipes';

export default function() {
    const $settings_form = $('#frm-settings')
    const $settings_alert = $('#alert-settings')

    const $chef_form = $('#frm-chef')
    const $chef_alert = $('#alert-chef')

    const $favorites_container = $('#favorites-container')
    const $recipes_container = $('#recipes-container')

    const $chef_app_form = $('#frm-chef-app')
    const $chef_app_alert = $('#alert-chef-app')
    const $btn_chef_app = $('.btn-start-application')

    const $reset_pwd_alert = $('#alert-reset-pwd')
    const $btn_reset_pwd = $('.btn-reset-pwd')

    let $mdl_chef_app = new Modal(document.getElementById('mdl-chef-app'), {
        focus: false,
        backdrop: 'static'
    })

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

    function loadChefRecipes() {

        $recipes_container.addClass('loading')

        axios.get('/api/v1/chef/recipes.php?include_drafts=1&user_id=' + HuntRecipes.current_user_id())
            .then(response => {
                response = response.data;
                console.log(response);

                $recipes_container.removeClass('loading').empty()

                for (let recipe of response.data) {
                    let $card = HuntRecipes.buildRecipeCard(recipe, response => {
                        if (!response) {
                            return
                        }
                        loadFavorites()
                    })

                    $recipes_container.append($('<div class="col">').append($card))
                }
            })
    }

    function init() {

        loadFavorites()
        loadChefRecipes()

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

            $settings_alert.disable()

            let payload = $settings_form.serializeObject();

            $('[type="submit"]', $settings_form)
                .addClass('disabled')
                .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

            axios.put('/api/v1/account/user/', payload)
                .then(response => {

                    let data = (response || {}).data.data;
                    if (data.name) $('[name="name"]', $settings_form).val(data.name)
                    if (data.email) $('[name="email"]', $settings_form).val(data.email)

                    if (data.is_new_email) {
                        $('#my-account-content').removeClass((i, c) => (c.match(/email-alert-\S*/g) || []).join(' '))
                            .addClass('email-alert-email')
                    }

                    $settings_alert.removeClass('alert-error')
                        .addClass('alert-success')
                        .text('Account Updated!')
                        .enable()

                })
                .catch(error => {

                    let data = (error.response || {}).data.data;
                    if (data.name) $('[name="name"]', $settings_form).val(data.name)
                    if (data.email) $('[name="email"]', $settings_form).val(data.email)

                    console.error(error.response.data.message);
                    $settings_alert.removeClass('alert-success')
                        .addClass('alert-error')
                        .text(error.response.data.message)
                        .enable()
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

            $chef_alert.disable()

            let payload = $chef_form.serializeObject();

            $('[type="submit"]', $chef_form)
                .addClass('disabled')
                .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

            axios.put('/api/v1/account/chef/', payload)
                .then(response => {
                    $chef_alert.removeClass('alert-error')
                        .addClass('alert-success')
                        .text('Chef Profile Updated!')
                        .enable()

                })
                .catch(error => {
                    console.error(error.response.data.message);
                    $chef_alert.removeClass('alert-success')
                        .addClass('alert-error')
                        .text(error.response.data.message)
                        .enable()
                })
                .finally(()=> {
                    $('[type="submit"]', $chef_form)
                        .removeClass('disabled')
                        .html('Update Chef Profile')
                })
        })

        $('.btn-verify-email').click(function(evt) {

            $('.btn-verify-email').addClass('disabled')

            axios.post('/api/v1/account/user/verify.php', {
                user_id: HuntRecipes.current_user_id(),
            })
                .then(response => {
                    $('#my-account-content').removeClass((i, c) => (c.match(/email-alert-\S*/g) || []).join(' '))
                        .addClass('email-alert-email-sent')
                })
                .finally(() => {
                    $('.btn-verify-email').removeClass('disabled')
                        .html('Send Verification Email')
                })
        })

        $btn_reset_pwd.click(function(evt) {

            $btn_reset_pwd.addClass('disabled')

            axios.post('/api/v1/account/user/reset-password.php', {
                user_id: HuntRecipes.current_user_id(),
            })
                .then(response => {
                    $btn_reset_pwd.hide()
                    $reset_pwd_alert.fadeIn();
                })
        })

        $btn_chef_app.click(function(){
            $('[name=relationship]', $chef_app_form).val('')
            $('[name=already_exists]', $chef_app_form).prop('checked', false)
            $('[name=story]', $chef_app_form).val('')
            $mdl_chef_app.show();
        })

        $mdl_chef_app._element.addEventListener('hide.bs.modal', function(e){
            const data = $chef_app_form.serializeObject()
            data.relationship = data.relationship || ''
            data.story = data.story || ''

            if (data.relationship || data.story || typeof data.already_exists !== 'undefined' ) {
                const c = confirm('Closing this form will discard your changes. Continue?')
                if (!c) {
                    e.preventDefault()
                    return false
                }
            }
            $btn_chef_app.focus()
            return true;
        });

        $chef_app_form.submit(function(e){
            e.preventDefault()
            if ($('[type="submit"]', $chef_app_form).hasClass('disabled')) {
                return false;
            }

            let payload = $chef_app_form.serializeObject();
            payload.story = payload.story.trim()

            $chef_app_alert.disable()

            $('[type="submit"]', $chef_app_form)
                .addClass('disabled')
                .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

            axios.post('/api/v1/chef/application/', payload)
                .then(response => {
                    response = response.data;
                    console.log(response);

                    window.location = '/account/?goto=chef';

                })
                .catch(error => {

                    $('[type="submit"]', $chef_app_form)
                        .removeClass('disabled')
                        .html('Submit Application')

                    console.error(error.response.data.message);
                    $chef_app_alert.removeClass((i, c) => (c.match(/alert-/g) || []).join(' '))
                        .addClass('alert-error')
                        .text(error.response.data.message)
                        .enable()
                })

            return false
        });

        $('.btn-withdraw-app').click(function(){
            const $this = $('.btn-withdraw-app');
            const user_id = $(this).data('user-id')
            const app_id = $(this).data('chef-application-id')
            console.log(user_id, app_id)

            const c = confirm("Are you sure you want to withdraw your chef application? We'd hate to lose a potential family chef! \n" +
                "\n" +
                "If you withdraw now, you're always welcome to apply again if you'd like to join later. \n" +
                "\n" +
                "Would you still like to withdraw your application?")

            if (!c) {
                return false;
            }

            $chef_alert.disable()

            $this.addClass('disabled')
                .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

            axios.delete('/api/v1/chef/application/?' + $.param({
                user_id: user_id,
                chef_application_id: app_id
            }))
                .then(response => {
                    response = response.data;
                    console.log(response);

                    window.location = '/account/?goto=chef';

                })
                .catch(error => {

                    $this.removeClass('disabled')
                        .html('Withdraw Application')

                    console.error(error.response.data.message);
                    $chef_alert.removeClass((i, c) => (c.match(/alert-/g) || []).join(' '))
                        .addClass('alert-error')
                        .text(error.response.data.message)
                        .enable()
                })
        })
    }

    init()

}
