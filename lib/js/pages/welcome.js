import HuntRecipes from '../HuntRecipes';
import axios from 'axios'
import $ from "jquery";

export default function() {
    const $form = $('#welcome-form');

    const loadFormInputs = function() {
        axios.get('/api/v1/cuisine/list.php')
            .then(response => {
                response = response.data;
                console.log(response);

                for (const item of response.data) {
                    $('[name=cuisine_id]', $form).append(
                        $('<option>').attr('value', item.id).text(item.name)
                    );
                }
            })
    };

    function init() {
        loadFormInputs()
    }

    init()
}
