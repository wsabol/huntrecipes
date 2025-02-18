import $ from "jquery";
import axios from 'axios'

export default function() {
    const $form = $('#frm-user-account-search')
    const $search_alert = $('#alert-account-search')

    $form.submit(function(e){
        e.preventDefault()

        if ($('[type="submit"]', $form).hasClass('disabled')) {
            return false;
        }

        let payload = $form.serializeObject();
        $search_alert.hide();

        $('[type="submit"]', $form)
            .addClass('disabled')
            .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

        axios.get('/api/v1/account/user/identify.php?email=' + payload.email)
            .then(response => {
                response = response.data;
                console.log(response);
                window.location = '/account/recover/?email=' + response.data.email;

            })
            .catch(error => {

                $('[type="submit"]', $form)
                    .removeClass('disabled')
                    .html('Search')

                $search_alert.text(error.response.data.message).show()
                console.error(error.response.data.message);
            })

        return false
    })
}
