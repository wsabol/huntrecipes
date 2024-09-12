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
                    $favorites_container.append(
                        $('<div class="col">').append(HuntRecipes.buildRecipeCard(recipe))
                    )
                }
            })
    }

    function init() {

        loadFavorites()

        $settings_form.submit(function(evt) {
            evt.preventDefault();

            if ($('[type="submit"]', $settings_form).hasClass('disabled')) {
                return false;
            }

            let payload = $settings_form.serializeObject();

            $('[type="submit"]', $settings_form)
                .addClass('disabled')
                .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

            axios.post('/api/v1/account/user/', payload)
                .then(response => {
                    window.location = '/account/?goto=settings';

                })
                .catch(error => {

                    $('[type="submit"]', $settings_form)
                        .removeClass('disabled')
                        .html('Update Account')

                    console.error(error.response.data.message);
                    $settings_alert.show().text(error.response.data.message)
                })
        })

        // $chef_form.submit(function(evt) {
        //     evt.preventDefault();
        //
        //     if ($('[type="submit"]', $chef_form).hasClass('disabled')) {
        //         return false;
        //     }
        //
        //     let payload = $chef_form.serializeObject();
        //
        //     $('[type="submit"]', $settings_form$chef_form)
        //         .addClass('disabled')
        //         .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')
        //
        //     axios.post('/api/v1/account/chef/', payload)
        //         .then(response => {
        //             window.location = '/account/?goto=settings';
        //
        //         })
        //         .catch(error => {
        //
        //             $('[type="submit"]', $settings_form)
        //                 .removeClass('disabled')
        //                 .html('Update Account')
        //
        //             console.error(error.response.data.message);
        //             $settings_alert.show().text(error.response.data.message)
        //         })
        // })
    }

    init()

}
