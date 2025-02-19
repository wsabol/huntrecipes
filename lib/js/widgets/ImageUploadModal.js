import $ from "jquery";
import Modal from 'bootstrap/js/src/modal'
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';
import axios from 'axios';
import HuntRecipes from '../HuntRecipes';

class ImageUploadModal {
    constructor(modal_selector, api_endpoint, options = {}) {
        this.api_endpoint = api_endpoint
        this.options = options ?? {};
        this.options.data_label = options.data_label ?? 'file';
        this.options.file_name = options.file_name ?? 'photo.jpg';
        this.options.callback = options.callback ?? null;
        this.options.post_data = options.post_data ?? {};
        this.cropper = null;

        if (typeof this.options.callback !== 'function') {
            this.options.callback = null;
        }

        const mdl = $(modal_selector)[0];
        this.mdl_photo_upload = new Modal(mdl, {
            focus: false,
            backdrop: 'static'
        })

        const $container = $(mdl)
        let self = this;

        mdl.addEventListener('hidden.bs.modal', function() {
            if (self.cropper) {
                self.cropper.destroy();
                self.cropper = null;
            }
            $('#photoInput', $container).val('');
            $('.uploadState', $container).show()
            $('.croppingState', $container).hide()
            $('.cropButton', $container).hide()
        });

        $('#photoInput', $container).change(function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                const $cropImage = $('.cropImage', $container);

                reader.onload = function(event) {
                    // Show cropping interface
                    $('.uploadState', $container).hide()
                    $('.croppingState', $container).show()
                    $('.cropButton', $container).show()

                    // Set up image
                    $cropImage.attr('src', event.target.result);

                    // Initialize cropper
                    if (self.cropper) {
                        self.cropper.destroy();
                    }

                    self.cropper = new Cropper($cropImage[0], {
                        aspectRatio: 1,
                        viewMode: 1,
                        dragMode: 'move',
                        autoCropArea: 1,
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: false,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                };

                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Handle crop and upload
        $('.cropButton', $container).click(function() {
            if (self.cropper) {
                // Get cropped canvas
                const canvas = self.cropper.getCroppedCanvas({
                    width: 400,    // Final image width
                    height: 400,   // Final image height
                });

                // Convert to blob
                canvas.toBlob(function(blob) {
                    const file_name = self.options.file_name

                    if (self.api_endpoint === 'blob') {
                        const file = new File([blob], 'profile.jpg', { type: 'image/jpeg' });
                        self.options.callback.call(self, file)
                        return;
                    }
                    // Create form data for upload

                    const formData = new FormData();
                    formData.append('current_user_id', HuntRecipes.current_user_id())
                    formData.append(self.options.data_label, blob, file_name);

                    for (const [k, v] of Object.entries(self.options.post_data)) {
                        formData.append(k, v)
                    }

                    // Upload the image
                    axios.post(self.api_endpoint, formData)
                        .then(response => {
                            response = response.data
                            console.log(response)

                            // Handle success
                            self.mdl_photo_upload.hide()

                            // callback
                            if (typeof self.options.callback === 'function') {
                                self.options.callback.call(self, response.data)
                            }
                        })
                        .catch(error => {
                            console.error('Error uploading photo:', error.response.data.message);
                            alert('There was an error uploading your photo. Please try again.');
                        })
                }, 'image/jpeg', 0.9)
            }
        })
    }

    show() {
        this.mdl_photo_upload.show()
    }

    hide() {
        this.mdl_photo_upload.hide()
    }
}

export default ImageUploadModal;
