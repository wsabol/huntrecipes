import $ from "jquery";
import ImageGenModalAdmin from './ImageGenModalAdmin';

class ImageGenModal extends ImageGenModalAdmin {
    constructor(modal_selector, on_submit = null, postData = {}) {
        super(modal_selector, on_submit, postData)
        $(this.mdl_image_gen._element).find('.chk-prompt-new').parent().hide()
        this.generatePhoto();
    }
}

export default ImageGenModal;
