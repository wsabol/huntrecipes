import $ from "jquery";
import axios from 'axios'

export default function() {
    const $alert = $('#alert-sign-up')
    const $form = $('#frm-user-sign-up')

    hideSignupAlert()

    $form.submit(function(e){
        e.preventDefault()

        if ($('[type="submit"]', $form).hasClass('disabled')) {
            return false;
        }

        let payload = $form.serializeObject();

        $('[type="submit"]', $form)
            .addClass('disabled')
            .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

        axios.post('/api/v1/account/user/register.php', payload)
            .then(response => {
                response = response.data;
                console.log(response);

                window.location = '/sign-in/';

            })
            .catch(error => {

                $('[type="submit"]', $form)
                    .removeClass('disabled')
                    .html('Sign Up')

                console.error(error.response.data.message);
                showSignupAlert(error.response.data.message)
            })

        return false
    })

    function showSignupAlert(message) {
        $alert.text(message).show()
    }

    function hideSignupAlert() {
        $alert.hide();
    }

}
