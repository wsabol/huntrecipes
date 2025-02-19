import $ from "jquery";
import Modal from 'bootstrap/js/src/modal'
import axios from 'axios';

class ImageGenModal {
    constructor(modal_selector, on_submit = null, postData = {}) {
        this.on_submit = null;
        if (typeof on_submit === 'function') {
            this.on_submit = on_submit;
        }

        this.postData = postData ?? {};

        const mdl = $(modal_selector)[0];
        this.mdl_image_gen = new Modal(mdl, {
            focus: false,
            backdrop: 'static',
            keyboard: false
        })

        const $container = $(mdl)
        this.loadingState = $('.loadingState', $container);
        this.resultsState = $('.resultsState', $container);
        this.blurbState = $('.blurbState', $container);
        this.generatedImage = $('.generatedImage', $container);
        this.generatedBlurb = $('.generatedBlurb', $container);
        this.regenerateButton = $('.regenerateButton', $container);
        this.usePhotoButton = $('.usePhotoButton', $container);
        this.alertDiv = $('.alert', $container);
        this.chkPromptNew = $('.chk-prompt-new', $container);

        let self = this;

        mdl.addEventListener('hidden.bs.modal', function() {
            self.loadingState.show();
            self.resultsState.hide();
            self.blurbState.hide();
            self.regenerateButton.hide();
            self.usePhotoButton.hide();
            self.generatedImage.attr('src', '');
            self.generatedBlurb.val('');
        });

        // Regenerate button handler
        this.regenerateButton.click(function(){
            if (self.regenerateButton.hasClass('disabled')) {
                return false;
            }
            self.generatePhoto()
        });

        // edit prompt event
        this.chkPromptNew.change(() => {
            const state = self.chkPromptNew.prop('checked')
            self.generatedBlurb.prop('disabled', state)
        })

        // Use photo button handler
        this.usePhotoButton.click(function() {
            if (self.usePhotoButton.hasClass('disabled')) {
                return false;
            }

            const selectedPhoto = {
                path: self.generatedImage.attr('src'),
            };

            if (typeof self.on_submit !== 'function') {
                self.on_submit.call(self, selectedPhoto)
            }
        });
    }

    // Function to generate photo
    generatePhoto() {
        let self = this;

        let payload = {...this.postData}
        if (!self.chkPromptNew.prop('checked')) {
            payload.image_prompt = self.generatedBlurb.val();
        }

        // Show loading state
        this.loadingState.show();
        this.resultsState.hide();
        this.blurbState.disable();
        this.regenerateButton.hide();
        this.usePhotoButton.hide();
        this.alertDiv.disable()

        return axios.post('/api/v1/recipe/generate-photo.php', payload)
            .then(response => {
                response = response.data;

                self.alertDiv.hide()
                self.generatedImage.attr('src', response.data.generated_image);
                self.generatedBlurb.val(response.data.image_prompt);

                // Show results
                self.alertDiv.hide()
                self.loadingState.hide();
                self.resultsState.show();
                self.blurbState.enable();
                self.regenerateButton.show();
                self.usePhotoButton.show();
            })
            .catch(error => {
                self.loadingState.hide();
                self.blurbState.enable();
                self.regenerateButton.show();
                console.log('Error generating photo: ' + error.response.data.message);
                self.setErrorMessage(error.response.data.message)
            })
    }

    show() {
        this.alertDiv.hide()
        this.mdl_image_gen.show()
        this.chkPromptNew.prop('checked', true).change()
        this.generatePhoto();
        this.usePhotoButton.removeClass('disabled')
    }

    hide() {
        this.mdl_image_gen.hide()
    }

    setErrorMessage(message) {
        this.alertDiv.removeClass('alert-success')
            .addClass('alert-error')
            .text(message)
            .enable()
    }
}

export default ImageGenModal;
