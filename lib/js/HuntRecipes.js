import $ from "jquery";
import axios from 'axios';
import Modal from 'bootstrap/js/src/modal'

export default (function() {
    const current_user_id = +$('body').data('current-user-id') || 0

    let signUpModal = new Modal(document.getElementById('mdl-account-cta'), {
        focus: false
    })

    const setRecipeLiked = (recipe_id, liked) => {
        return axios.patch('/api/v1/recipe/', {
            action: 'set-favorite-recipe',
            recipe_id: recipe_id,
            user_id: current_user_id,
            status: liked
        })
    };

    const toggleRecipeCardLiked = ($card) => {
        if (current_user_id === 0) {
            signUpModal.show()
            return Promise.resolve(false);
        }

        const liked = !$card.find('.likes').hasClass('liked');
        const recipe = $card.data('recipe');
        $card.toggleClass('liked', liked);
        $card.find('i').toggleClass('fa-solid fa-regular');

        return setRecipeLiked(recipe.id, liked)
            .then(response => {
                response = response.data;
                console.log(response)
                $card.find('.favorite-count').text(response.data.likes_count);
                return response.data;
            })
    };

    return {
        current_user_id: () => current_user_id,

        buildRecipeCard: (recipe, likeCallback = null) => {
            if (!likeCallback) {
                likeCallback = function(){}
            }

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

            let $title = $('<h5 class="card-title">').append(recipe.title);
            if (!recipe.published_flag) {
                $title.append([
                    '&nbsp;',
                    $('<span class="badge bg-primary">').text('DRAFT')
                ])
            }

            let $card_body = $('<div class="card-body">').append([
                $title,
                $('<div class="card-actions">').append(
                    $('<div class="likes">').addClass(recipe.is_liked ? 'liked' : '').append([
                        $('<i class="' + (recipe.is_liked ? 'fa-solid' : 'fa-regular') + ' fa-heart">'),
                        $('<span class="favorite-count">').text(recipe.likes_count ?? 0)
                    ]).click(function(){
                        toggleRecipeCardLiked($(this)).then(likeCallback)
                    })
                ),
            ])

            $card.append($figure, $card_body)
            $card.data('recipe', recipe)
            return $card
        },

        setRecipeLiked: setRecipeLiked,

        toggleRecipeCardLiked: toggleRecipeCardLiked,

        signUpModal: signUpModal
    }
})()
