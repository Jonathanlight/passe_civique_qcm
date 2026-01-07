import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        timeout: { type: Number, default: 5000 }
    };

    connect() {
        this.autoHideTimeout = setTimeout(() => {
            this.dismiss();
        }, this.timeoutValue);
    }

    dismiss() {
        this.element.style.animation = 'slideOutRight 0.3s ease-out forwards';

        setTimeout(() => {
            this.element.remove();
        }, 300);
    }

    disconnect() {
        if (this.autoHideTimeout) {
            clearTimeout(this.autoHideTimeout);
        }
    }
}
