import $ from "jquery";
import axios from 'axios'
import HuntRecipes from '../HuntRecipes';

export default function() {

    const chefId = $('#chef-profile').data('chef-id')
    if (!chefId) return

    function loadRecipes(container, collection, emptyMessage) {
        const $container = $(container).addClass('loading')

        axios.get('/api/v1/chefs/' + chefId + '/' + collection)
            .then(({ data: response }) => {
                $container.empty()

                for (const recipe of response.data) {
                    $container.append(
                        $('<div class="col">').append(HuntRecipes.buildRecipeCard(recipe, result => {
                            if (result) loadCollections()
                        }))
                    )
                }

                if (response.data.length === 0) {
                    $container.append($('<p class="col-12 text-muted">').text(emptyMessage))
                }
            })
            .catch(() => {
                $container.empty().append(
                    $('<div class="col-12">').append(
                        $('<div class="alert alert-warning" role="alert">').append(
                            $('<p>').text('We couldn’t load these recipes. Please try again.'),
                            $('<button type="button" class="btn btn-outline-secondary">')
                                .text('Try again').on('click', () => loadRecipes(container, collection, emptyMessage))
                        )
                    )
                )
            })
            .finally(() => $container.removeClass('loading'))
    }

    function loadCollections() {
        loadRecipes('#chef-recipes-container', 'recipes', 'This chef hasn’t shared any recipes yet.')
        loadRecipes('#favorites-container', 'favorites', 'This chef hasn’t added any favorites yet.')
    }

    loadCollections()
}
