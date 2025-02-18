import $ from "jquery";
import axios from 'axios'

export default function() {
    const user_data = $('#email').data()
    const $recover_alert = $('#alert-account-recover')

    $('#btnSendOTP').click(function(e){
        const $this = $(this);

        if ($this.hasClass('disabled')) {
            return false;
        }

        $recover_alert.hide();

        $this.addClass('disabled')
            .html('<i class="fa-solid fa-circle-notch fa-spin"></i>')

        axios.post('/api/v1/account/user/sign-in-otp.php', user_data)
            .then(response => {
                response = response.data;
                console.log(response);
                const user = response.data.user;
                const otp = response.data.otp;

                window.location = '/account/recover/sign-in/?' + $.param({
                    email: user.email,
                    otp: otp.id,
                })

            })
            .catch(error => {

                $this.removeClass('disabled')
                    .html('Continue')

                $recover_alert.text(error.response.data.message).show()
                console.error(error.response.data.message);
            })

        return false
    })
}
