import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        publicKey: String,
        checkoutUrl: String,
        packageId: Number,
    };

    static targets = ['submitButton', 'loadingSpinner', 'buttonText', 'errorMessage'];

    stripe = null;
    isProcessing = false;

    connect() {
        this.initializeStripe();
    }

    async initializeStripe() {
        if (!this.publicKeyValue) {
            console.error('Stripe public key is required');
            return;
        }

        if (typeof Stripe === 'undefined') {
            await this.loadStripeScript();
        }

        this.stripe = Stripe(this.publicKeyValue);
    }

    loadStripeScript() {
        return new Promise((resolve, reject) => {
            if (document.querySelector('script[src*="stripe"]')) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3/';
            script.async = true;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    async checkout(event) {
        event.preventDefault();

        if (this.isProcessing) {
            return;
        }

        this.setLoading(true);
        this.hideError();

        try {
            const response = await fetch(this.checkoutUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Une erreur est survenue');
            }

            if (data.url) {
                window.location.href = data.url;
            } else if (data.sessionId) {
                const result = await this.stripe.redirectToCheckout({
                    sessionId: data.sessionId,
                });

                if (result.error) {
                    throw new Error(result.error.message);
                }
            }
        } catch (error) {
            console.error('Payment error:', error);
            this.showError(error.message || 'Une erreur est survenue lors du paiement');
        } finally {
            this.setLoading(false);
        }
    }

    setLoading(loading) {
        this.isProcessing = loading;

        if (this.hasSubmitButtonTarget) {
            this.submitButtonTarget.disabled = loading;
        }

        if (this.hasLoadingSpinnerTarget) {
            this.loadingSpinnerTarget.classList.toggle('hidden', !loading);
        }

        if (this.hasButtonTextTarget) {
            this.buttonTextTarget.classList.toggle('hidden', loading);
        }
    }

    showError(message) {
        if (this.hasErrorMessageTarget) {
            this.errorMessageTarget.textContent = message;
            this.errorMessageTarget.classList.remove('hidden');
        }
    }

    hideError() {
        if (this.hasErrorMessageTarget) {
            this.errorMessageTarget.classList.add('hidden');
        }
    }
}
