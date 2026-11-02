import $ from "jquery";
import axios from 'axios'
import Modal from 'bootstrap/js/src/modal'
import 'bootstrap/js/src/tab'
import HuntRecipes from '../HuntRecipes';
import ImageUploadModal from '../widgets/ImageUploadModal'

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

    let $mdl_chef_app = new Modal(document.getElementById('mdl-chef-app'), {
        focus: false,
        backdrop: 'static'
    })

    let photo_upload = new ImageUploadModal(
        '#photo-upload-modal',
        '/api/v1/me/photo',
        {
            data_label: 'profile_photo',
            image_size: 608,
            file_name: 'profile_' + HuntRecipes.current_user_id() + '.jpg',
            onclose: function(){
                $('.btn-new-picture').removeClass('disabled')
            },
            callback: function(data){
                const user = data?.data || data
                $('#profile-picture').attr('src', user.profile_picture_lg)
                this.hide()
            }
        }
    )

    function loadFavorites() {

        $favorites_container.addClass('loading')

        axios.get('/api/v1/me/favorites')
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

        axios.get('/api/v1/me/recipes')
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
        if ($recipes_container.length && !$('#recipes-tab-pane .alert-info, #recipes-tab-pane .alert-error').length) {
            loadChefRecipes()
        }

        $('.btn-logout').click(function(e) {
            e.preventDefault()

            if (!confirm('Are you sure you want to logout?')) {
                return false;
            }

            const $this = $(this)
            $this.addClass('disabled').html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

            axios.delete('/api/v1/auth/session')
                .then(async () => {
                    try {
                        await window.clearHuntRecipesFirebaseAuth?.();
                    } finally {
                        window.location.href = '/welcome';
                    }
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

            axios.patch('/api/v1/me', payload)
                .then(response => {

                    let data = (response || {}).data.data;
                    if (data.name) $('[name="name"]', $settings_form).val(data.name)

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

            axios.patch('/api/v1/me/chef', payload)
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

            axios.post('/api/v1/chef-applications', payload)
                .then(response => {
                    response = response.data;
                    console.log(response);

                    window.location = '/account?goto=chef';

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
            const app_id = $(this).data('chef-application-id')

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

            axios.delete('/api/v1/chef-applications/' + app_id)
                .then(response => {
                    response = response.data;
                    console.log(response);

                    window.location = '/account?goto=chef';

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

        $('.btn-new-picture').click(function(){
            $('.btn-new-picture').addClass('disabled')
            photo_upload.show()
        })
    }

    init()

}
