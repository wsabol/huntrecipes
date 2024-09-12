import $ from "jquery";

export default (function() {
    const current_user_id = +$('body').data('current-user-id') || 0

    return {
        current_user_id: () => current_user_id,

        buildRecipeCard: (recipe) => {
            let $card = $('<div class="recipe-card card mb-5">')

            let $figure = $('<figure>').append([
                $('<img>').attr({
                    src: recipe.image_filename,
                    class: 'card-img-top',
                    alt: recipe.title
                }),
                $('<figcaption>').append([
                    $('<a>').attr('href', recipe.link).append([
                        $('<i class="icon icon-themeenergy_eye2">'),
                        $('<span>').text('View recipe')
                    ])
                ]),
            ])

            let $card_body = $('<div class="card-body">').append([
                $('<h5 class="card-title">').text(recipe.title),
                $('<div class="card-actions">').append(
                    $('<div class="likes">').addClass(recipe.is_liked ? 'liked' : '').append([
                        $('<i class="fa fa-heart">'),
                        $('<span class="favorite-count">').text(recipe.likes_count || 0)
                    ])
                ),
            ])

            $card.append($figure, $card_body)
            return $card;
        },

        buildChefCard: (chef) => {
            let $card = $('<div class="recipe-card card mb-5">')

            let $figure = $('<figure>').append([
                $('<img>').attr({
                    src: recipe.image_filename,
                    class: 'card-img-top',
                    alt: recipe.title
                }),
                $('<figcaption>').append([
                    $('<a>').attr('href', recipe.link).append([
                        $('<i class="icon icon-themeenergy_eye2">'),
                        $('<span>').text('View recipe')
                    ])
                ]),
            ])

            let $card_body = $('<div class="card-body">').append([
                $('<h5 class="card-title">').text(recipe.title),
                $('<div class="card-actions">').append(
                    $('<div class="likes">').addClass(recipe.is_liked ? 'liked' : '').append([
                        $('<i class="fa fa-heart">'),
                        $('<span class="favorite-count">').text(recipe.likes_count || 0)
                    ])
                ),
            ])

            $card.append($figure, $card_body)
            return $card;
        }
    }
})()
