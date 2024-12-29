import $ from "jquery";
import axios from 'axios'
import HuntRecipes from '../HuntRecipes';

export default function() {

    const $favorites_container = $('#favorites-container')
    const user_id = +$favorites_container.attr('data-user-id') || 0;

    const $no_favorites = $('<div class="alert alert-info col-sm-12 col-md-12 col-lg-12">').append("This chef does not have any favorites at the time")

    function loadFavorites() {
        $favorites_container.empty()

        if (user_id <= 0) {
            $favorites_container.append($no_favorites)
            return
        }

        $favorites_container.addClass('loading')

        axios.get('/api/v1/account/favorites/?user_id=' + user_id)
            .then(response => {
                response = response.data;
                console.log(response);

                $favorites_container.removeClass('loading').empty()

                for (let recipe of response.data) {
                    $favorites_container.append(
                        $('<div class="col">').append(HuntRecipes.buildRecipeCard(recipe, response => {
                            if (!response) {
                                return
                            }
                            if (user_id > 0 && user_id === HuntRecipes.current_user_id()) {
                                loadFavorites()
                            }
                        }))
                    )
                }

                if (response.data.length === 0) {
                    $favorites_container.append($no_favorites)
                }
            })
    }

    function init() {

        loadFavorites()

    }

    init()

}
