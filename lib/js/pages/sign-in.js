import $ from "jquery";
import axios from 'axios'

export default function() {
    const $alert = $('#alert-sign-in')
    const $form = $('#frm-user-sign-in')

    hideSigninAlert()

    $form.submit(function(){

        if ($('[type="submit"]', $form).hasClass('disabled')) {
            return false;
        }

        $('[type="submit"]', $form)
            .addClass('disabled')
            .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

        let elogin = btoa($('[name="username"]', $form).val() + ';' + $('[name="password"]', $form).val());

        axios.post('/api/v1/auth/sign-in.php', {
            'elogin': elogin,
            'rememberme': ($('[name=rememberme]', $form).is(':checked') ? 1 : 0)
        })
            .then(response => {
                response = response.data;
                console.log(response);

                let new_url = $('#request_uri').val() || '';
                if (new_url.length === 0) {
                    new_url = '/home/';
                }

                window.location = new_url;

            })
            .catch(error => {

                $('[type="submit"]', $form)
                    .removeClass('disabled')
                    .html('Sign in')

                console.error(error.response.data.message);
                showSigninAlert(error.response.data.message)
            })

        return false
    })

    function showSigninAlert(message) {
        $alert.text(message).show()
    }

    function hideSigninAlert() {
        $alert.hide();
    }

}
