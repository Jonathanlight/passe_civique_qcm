import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['progress', 'timer', 'answer', 'submitBtn'];
    static values = {
        timeLimit: Number,
        questionId: Number,
        multiple: Number
    };

    connect() {
        this.selectedAnswers = [];
        if (this.hasTimerTarget && this.timeLimitValue > 0) {
            this.startTimer();
        }
        this.updateSubmitButton();
    }

    startTimer() {
        this.remainingTime = this.timeLimitValue * 60;
        this.updateTimerDisplay();

        this.timerInterval = setInterval(() => {
            this.remainingTime--;
            this.updateTimerDisplay();

            if (this.remainingTime <= 0) {
                this.timeUp();
            }
        }, 1000);
    }

    updateTimerDisplay() {
        if (!this.hasTimerTarget) return;

        const minutes = Math.floor(this.remainingTime / 60);
        const seconds = this.remainingTime % 60;
        this.timerTarget.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

        if (this.remainingTime <= 60) {
            this.timerTarget.classList.add('warning');
        }
    }

    timeUp() {
        clearInterval(this.timerInterval);
        this.element.querySelector('form').submit();
    }

    selectAnswer(event) {
        event.preventDefault();
        event.stopPropagation();

        const option = event.currentTarget;
        const answerId = option.dataset.answerId;
        const isMultiple = this.multipleValue === 1;
        const input = option.querySelector('input');

        if (isMultiple) {
            // Toggle selection for multiple choice
            const isCurrentlySelected = option.classList.contains('selected');

            if (isCurrentlySelected) {
                option.classList.remove('selected');
                input.checked = false;
                const index = this.selectedAnswers.indexOf(answerId);
                if (index > -1) {
                    this.selectedAnswers.splice(index, 1);
                }
            } else {
                option.classList.add('selected');
                input.checked = true;
                this.selectedAnswers.push(answerId);
            }
        } else {
            // Single choice - deselect all others first
            this.answerTargets.forEach(el => {
                el.classList.remove('selected');
                const elInput = el.querySelector('input');
                if (elInput) elInput.checked = false;
            });

            option.classList.add('selected');
            input.checked = true;
            this.selectedAnswers = [answerId];
        }

        this.updateSubmitButton();
    }

    updateSubmitButton() {
        if (this.hasSubmitBtnTarget) {
            this.submitBtnTarget.disabled = this.selectedAnswers.length === 0;
        }
    }

    disconnect() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
    }
}
