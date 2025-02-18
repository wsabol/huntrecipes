import HuntRecipes from '../HuntRecipes';
import axios from 'axios'
import $ from "jquery";
import Tags from 'bootstrap5-tags'

export default function() {
    const $search_form = $('#search-form');
    let Ingredients = [];

    const loadFormInputs = function() {
        let loads = [];

        loads.push(
            axios.get('/api/v1/chef/list.php')
                .then(response => {
                    response = response.data;
                    console.log(response);

                    for (const item of response.data) {
                        $('#chef_id').append(
                            $('<option>').attr('value', item.id).text(item.name)
                        );
                    }
                })
        );

        loads.push(
            axios.get('/api/v1/course/list.php')
                .then(response => {
                    response = response.data;
                    console.log(response);

                    for (const item of response.data) {
                        $('#course_id').append(
                            $('<option>').attr('value', item.id).text(item.name)
                        );
                    }
                })
        )

        loads.push(
            axios.get('/api/v1/cuisine/list.php')
                .then(response => {
                    response = response.data;
                    console.log(response);

                    for (const item of response.data) {
                        $('#cuisine_id').append(
                            $('<option>').attr('value', item.id).text(item.name)
                        );
                    }
                })
        )

        loads.push(
            axios.get('/api/v1/recipe-type/list.php')
                .then(response => {
                    response = response.data;
                    console.log(response);

                    for (const item of response.data) {
                        $('#recipe_type_id').append(
                            $('<option>').attr('value', item.id).text(item.name)
                        );
                    }
                })
        )

        loads.push(
            axios.get('/api/v1/ingredient/top.php')
                .then(response => {
                    response = response.data;
                    console.log(response);
                    Ingredients = response.data;
                })
        )

        return Promise.all(loads);
    };

    const getRecipes = function() {
        let payload = $search_form.serializeObject();
        console.log(payload);

        return new Promise((res, rej) => {
            axios.get('/api/v1/recipe/list.php?' + $.param(payload))
                .then(response => {
                    response = response.data;
                    console.log(response);
                    res(response.data);
                })
                .catch(rej)
        })
    };

    const ResultsContainer = {
        self: $("#results-container"),

        empty: () => {
            $("#results-container").empty()
        },

        loading_on: () => {
            $('#loading-alert').fadeIn()
            $("#results-container").addClass('loading')
        },

        loading_off: () => {
            $('#loading-alert').fadeOut()
            $("#results-container").removeClass('loading')
        },
    }

    const runSearch = function() {
        ResultsContainer.loading_on()

        getRecipes()
            .then(recipes => {

                ResultsContainer.loading_off()
                ResultsContainer.empty()

                for (const recipe of recipes) {
                    ResultsContainer.self.append(
                        $('<div class="col">').append(HuntRecipes.buildRecipeCard(recipe))
                    )
                }

            })
            .catch(error => {
                console.error(error.response.data.message);
                alert('Something went wrong: ' + error.response.data.message)
            })
    }

    function init() {
        const params = currentQueryParams();
        $('#keyword').val(params.q || '')

        loadFormInputs()
            .then(() => {

                $('#chef_id').val(params.chef_id || 0)
                $('#course_id').val(params.course_id || 0)
                $('#cuisine_id').val(params.cuisine_id || 0)
                $('#recipe_type_id').val(params.recipe_type_id || 0)
                $('#ingredients').append(
                    Ingredients.map(ig => $('<option>').attr('value', ig.name).text(ig.name))
                ).change(function(){
                    runSearch();
                })

                Tags.init('#ingredients')
                runSearch();
            })

        $('#search-btn').click(() => runSearch())
    }

    init()
}
