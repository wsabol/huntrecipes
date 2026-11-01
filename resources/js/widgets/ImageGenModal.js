import ImageGenModalAdmin from './ImageGenModalAdmin';

class ImageGenModal extends ImageGenModalAdmin {
    constructor(modal_selector, on_submit = null, postData = {}) {
        super(modal_selector, on_submit, postData)
    }
}

export default ImageGenModal;
