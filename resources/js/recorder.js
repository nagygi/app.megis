/**
 * The browser recorder of the Értéktükör (06 · 6.1, ET2): a timer with a
 * ring filling up over the maximum length, an encouraging line that changes
 * with time, a warning before the end, and a supporting question after a
 * few seconds of silence. The finished recording is uploaded through Livewire.
 * The page's own button (x-recorder-submit) follows the state and asks for
 * the sending with "recorder-submit"; pressed during the recording, it stops it.
 */
export default (config) => ({
    state: 'idle',
    seconds: 0,
    error: null,
    notice: null,
    questionIndex: -1,
    speaking: true,
    uploadProgress: 0,
    blob: null,
    recorder: null,
    stream: null,
    timer: null,
    startedAt: null,
    audioContext: null,
    submitAfterStop: false,

    init() {
        this.$watch('state', (state) => this.$dispatch('recorder-state', state));
    },

    get supported() {
        return !! (navigator.mediaDevices && window.MediaRecorder);
    },

    get timeLabel() {
        const minutes = Math.floor(this.seconds / 60);
        const seconds = String(this.seconds % 60).padStart(2, '0');

        return `${minutes}:${seconds}`;
    },

    get progress() {
        return Math.min(1, this.seconds / config.max);
    },

    get motivation() {
        if (this.seconds >= 180) {
            return config.texts.threeMinutes;
        }

        return this.seconds >= 60 ? config.texts.oneMinute : config.texts.start;
    },

    get question() {
        if (this.questionIndex < 0 || config.questions.length === 0) {
            return null;
        }

        return config.questions[this.questionIndex % config.questions.length];
    },

    get isShort() {
        return this.seconds < config.recommended;
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

        this.recorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                chunks.push(event.data);
            }

            this.tick();
        };
        this.recorder.onstop = () => {
            this.blob = new Blob(chunks, { type: this.recorder.mimeType || 'audio/webm' });
            this.state = 'recorded';
            this.release();

            if (this.submitAfterStop) {
                this.submitAfterStop = false;
                this.submit();
            }
        };

        this.recorder.start(1000);
        this.state = 'recording';
        this.startedAt = Date.now();
        this.seconds = 0;
        this.questionIndex = -1;
        this.speaking = true;
        this.timer = setInterval(() => this.tick(), 1000);

        this.watchSilence();
    },

    /**
     * The time comes from the clock, not from counting: a background tab's
     * timer is slowed down to once a minute, the recording is not. The
     * recorder's own data, every second, checks it too, so the recording
     * stops at the maximum in a background tab as well.
     */
    tick() {
        if (this.state !== 'recording') {
            return;
        }

        this.seconds = Math.min(config.max, Math.floor((Date.now() - this.startedAt) / 1000));

        if (this.seconds >= config.max) {
            this.notice = config.texts.maxReached;
            this.stop();
        }
    },

    stop() {
        clearInterval(this.timer);

        if (this.recorder && this.recorder.state === 'recording') {
            this.recorder.stop();
        }
    },

    restart() {
        this.submitAfterStop = false;
        this.blob = null;
        this.seconds = 0;
        this.notice = null;
        this.error = null;
        this.state = 'idle';
    },

    /**
     * After the configured seconds of silence the next question appears; when
     * the speaker goes on, it stays in place, faded.
     */
    watchSilence() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;

        if (! AudioContext || config.questions.length === 0) {
            return;
        }

        this.audioContext = new AudioContext();
        const analyser = this.audioContext.createAnalyser();
        analyser.fftSize = 2048;
        this.audioContext.createMediaStreamSource(this.stream).connect(analyser);

        const samples = new Uint8Array(analyser.fftSize);
        let silentSince = null;

        const listen = () => {
            if (this.state !== 'recording') {
                return;
            }

            analyser.getByteTimeDomainData(samples);
            const level = Math.sqrt(samples.reduce((sum, sample) => sum + ((sample - 128) / 128) ** 2, 0) / samples.length);
            const now = performance.now();

            if (level < 0.02) {
                silentSince ??= now;

                if (this.speaking && now - silentSince > config.silence * 1000) {
                    this.questionIndex++;
                    this.speaking = false;
                }
            } else {
                silentSince = null;
                this.speaking = true;
            }

            requestAnimationFrame(listen);
        };

        listen();
    },

    release() {
        this.stream?.getTracks().forEach((track) => track.stop());
        this.audioContext?.close();
        this.audioContext = null;
    },

    async submit() {
        // "Kész" during the recording: the recording goes once it is whole.
        if (this.state === 'recording') {
            this.submitAfterStop = true;
            this.stop();

            return;
        }

        if (! this.blob || this.state === 'uploading') {
            return;
        }

        this.error = null;
        this.uploadProgress = 0;
        this.state = 'uploading';

        // The rest (the e-mail part) is checked first, so a long recording is not uploaded in vain.
        if (config.check && (await this.$wire.$call(config.check)) !== true) {
            this.state = 'recorded';

            return;
        }

        const extension = this.blob.type.includes('mp4') ? 'm4a' : 'webm';
        const file = new File([this.blob], `felvetel.${extension}`, { type: this.blob.type });

        this.$wire.upload(
            'recording',
            file,
            () => this.$wire.submitRecording(this.seconds).then(() => {
                if (this.state === 'uploading') {
                    this.state = 'recorded';
                }
            }),
            () => {
                this.state = 'recorded';
                this.error = config.texts.uploadFailed;
            },
            (event) => (this.uploadProgress = event.detail.progress),
        );
    },
});
