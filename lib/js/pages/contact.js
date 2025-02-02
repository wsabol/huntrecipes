import $ from "jquery";
import axios from 'axios'

export default function() {
    const $alert = $('#alert-contact')
    const $form = $('#frm-contact')
    const $success_alert = $('#alert-success')
    const $contact_wrapper = $('#div-contact-wrapper')

    hideContactAlert()

    $form.submit(function(e){
        e.preventDefault()

        if ($('[type="submit"]', $form).hasClass('disabled')) {
            return false;
        }

        let payload = $form.serializeObject();

        $('[type="submit"]', $form)
            .addClass('disabled')
            .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

        axios.post('/api/v1/contact/', payload)
            .then(response => {
                response = response.data;
                console.log(response);

                // remove form
                $contact_wrapper.hide();

                // show mail success
                $success_alert.fadeIn();

            })
            .catch(error => {

                $('[type="submit"]', $form)
                    .removeClass('disabled')
                    .html('Send Message')

                console.error(error.response.data.message);
                showContactAlert(error.response.data.message)
            })

        return false
    })

    function showContactAlert(message) {
        $alert.text(message).show()
    }

    function hideContactAlert() {
        $alert.hide();
    }

}
