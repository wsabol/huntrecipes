import HuntRecipes from '../HuntRecipes';
import axios from 'axios'
import $ from "jquery";

export default function() {
    const $RecipeOfTheDay = $('#divRecipeOfTheDay')
    const $ChefOfTheDay = $('#divChefOfTheDay')
    const $TopRecipes = $('#top-recipes-container')

    function loadRecipeOfTheDay() {
        axios.get('/api/v1/recipe/recipe-of-the-day.php')
            .then(response => {
                response = response.data;
                console.log(response);

                const recipe = response.data;
                $('.card-img-top', $RecipeOfTheDay).attr('src', recipe.image_filename)
                $('.recipe-title', $RecipeOfTheDay).text(recipe.title)
                $('.card-text', $RecipeOfTheDay).text([recipe.recipe_type, recipe.cuisine, recipe.course].filter(x => !!x).join(' | '));
                $('.btn-link-to-recipes', $RecipeOfTheDay)
                    .attr('href', '/recipes/recipe/?id=' + recipe.id)
                    .removeClass('disabled')
            })
    }

    function loadChefOfTheDay() {
        axios.get('/api/v1/chef/chef-of-the-day.php')
            .then(response => {
                response = response.data;
                console.log(response);

                const chef = response.data;
                $('.card-img-top', $ChefOfTheDay).attr('src', chef.image_filename)
                $('.chef-name', $ChefOfTheDay).text(chef.name)
                $('.btn-link-to-chef', $ChefOfTheDay)
                    .text('See ' + (chef.is_male ? 'his' : 'her') + ' recipes')
                    .attr('href', '/chef/?id=' + chef.id)
                    .removeClass('disabled')

                if (!!chef.wisdom) {
                    $('.chef-quote-wrapper', $ChefOfTheDay).show()
                    $('.chef-quote', $ChefOfTheDay).text(chef.wisdom);
                }
            })
    }

    function loadTopRecipes() {
        axios.get('/api/v1/recipe/top.php')
            .then(response => {
                response = response.data;
                console.log(response);

                for (const item of response.data) {
                    $TopRecipes.append(
                        $('<div class="col">').append(HuntRecipes.buildRecipeCard(item))
                    )
                }
            })
    }

    function init() {
        loadRecipeOfTheDay()
        loadChefOfTheDay()
        loadTopRecipes()
    }

    init();
}
