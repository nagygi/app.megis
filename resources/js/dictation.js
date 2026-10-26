/**
 * The microphone next to a text field (08 · 8.1, 10 · 10.1, 24): records in
 * the browser, uploads through Livewire, and asks the component to
 * transcribe; the transcript arrives in the field, editable.
 */
export default (config) => ({
    recording: false,
    uploading: false,
    seconds: 0,
    error: null,
    notice: null,
    recorder: null,
    stream: null,
    timer: null,

    get supported() {
        return !! (navigator.mediaDevices && window.MediaRecorder);
    },

    get timeLabel() {
        const minutes = Math.floor(this.seconds / 60);
        const seconds = String(this.seconds % 60).padStart(2, '0');

        return `${minutes}:${seconds}`;
    },

    // A transcript that did not fit was condensed: the component says so.
    applied(event) {
        if (event.target === this.$wire.$el && event.detail.field === config.field) {
            this.notice = event.detail.notice;
        }
    },

    async toggle() {
        if (this.recording) {
            this.stop();

            return;
        }

        await this.start();
    },

    async start() {
        this.error = null;
        this.notice = null;

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (exception) {
            this.error = config.texts.denied;

            return;
        }

        const mimeType = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4']
            .find((type) => window.MediaRecorder.isTypeSupported(type));

        this.recorder = new MediaRecorder(this.stream, mimeType ? { mimeType } : undefined);
        const chunks = [];

        this.recorder.ondataavailable = (event) => event.data.size > 0 && chunks.push(event.data);
        this.recorder.onstop = () => {
            this.stream?.getTracks().forEach((track) => track.stop());
            this.upload(new Blob(chunks, { type: this.recorder.mimeType || 'audio/webm' }));
        };

        this.recorder.start(1000);
        this.recording = true;
        this.seconds = 0;
        this.timer = setInterval(() => {
            this.seconds++;

            if (this.seconds >= config.max) {
                this.stop();
            }
        }, 1000);
    },

    stop() {
        clearInterval(this.timer);
        this.recording = false;

        if (this.recorder && this.recorder.state === 'recording') {
            this.recorder.stop();
        }
    },

    upload(blob) {
        const extension = blob.type.includes('mp4') ? 'm4a' : 'webm';
        const file = new File([blob], `dictation.${extension}`, { type: blob.type });

        this.uploading = true;
        this.$wire.upload(
            'dictationAudio',
            file,
            () => {
                this.uploading = false;
                this.$wire.dictate(config.field, this.seconds);
            },
            () => {
                this.uploading = false;
                this.error = config.texts.failed;
            },
        );
    },
});
