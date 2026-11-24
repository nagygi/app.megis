/**
 * The browser recorder of the Értéktükör (06 · 6.1, ET2): a timer with a
 * ring filling up over the maximum length, an encouraging line that changes
 * with time, a warning before the end, and a supporting question after a
 * few seconds of silence. A level meter shows that the microphone hears the
 * speaker. The recording can be paused and continued ("Szünet", "Folytatom");
 * "Befejeztem" finishes it. The finished recording is uploaded through Livewire.
 * The page's own button (x-recorder-submit) follows the state and asks for
 * the sending with "recorder-submit"; it only sends a finished recording.
 */
export default (config) => ({
    state: 'idle',
    seconds: 0,
    level: 0,
    error: null,
    notice: null,
    questionIndex: -1,
    speaking: true,
    uploadProgress: 0,
    blob: null,
    recorder: null,
    stream: null,
    timer: null,
    // The recorded time: the finished parts, and the running one since segmentStartedAt.
    recordedBefore: 0,
    segmentStartedAt: null,
    audioContext: null,

    init() {
        this.$watch('state', (state) => this.$dispatch('recorder-state', state));
    },

    /**
     * The recorder left the page (the user chose to write instead): the
     * microphone is let go, the unsent recording with it.
     */
    destroy() {
        clearInterval(this.timer);

        if (this.recorder && this.recorder.state !== 'inactive') {
            this.recorder.onstop = null;
            this.recorder.stop();
        }

        this.release();
    },

    get supported() {
        return !! (navigator.mediaDevices && window.MediaRecorder);
    },

    get canPause() {
        return !! (window.MediaRecorder && typeof window.MediaRecorder.prototype.pause === 'function');
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

    /**
     * The height of a level meter bar, 0.15–1: the middle ones move the most.
     */
    bar(index) {
        const weights = [0.55, 0.85, 1, 0.85, 0.55];

        return Math.max(0.15, Math.min(1, this.level * weights[index]));
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
        };

        this.recorder.start(1000);
        this.state = 'recording';
        this.recordedBefore = 0;
        this.segmentStartedAt = Date.now();
        this.seconds = 0;
        this.questionIndex = -1;
        this.speaking = true;
        this.timer = setInterval(() => this.tick(), 1000);

        this.listen();
    },

    /**
     * The time comes from the clock, not from counting: a background tab's
     * timer is slowed down to once a minute, the recording is not. The
     * recorder's own data, every second, checks it too, so the recording
     * stops at the maximum in a background tab as well. A pause does not count.
     */
    tick() {
        if (this.state !== 'recording') {
            return;
        }

        this.seconds = this.recordedSeconds();

        if (this.seconds >= config.max) {
            this.notice = config.texts.maxReached;
            this.stop();
        }
    },

    recordedSeconds() {
        return Math.min(config.max, Math.floor((this.recordedBefore + Date.now() - this.segmentStartedAt) / 1000));
    },

    /**
     * "Szünet": the recording waits; the time and the silence do not count.
     */
    pause() {
        if (this.state !== 'recording' || this.recorder?.state !== 'recording' || ! this.canPause) {
            return;
        }

        this.seconds = this.recordedSeconds();
        this.recorder.pause();
        this.recordedBefore += Date.now() - this.segmentStartedAt;
        this.state = 'paused';
        this.level = 0;
    },

    /**
     * "Folytatom": the same recording goes on.
     */
    resume() {
        if (this.state !== 'paused' || this.recorder?.state !== 'paused') {
            return;
        }

        this.recorder.resume();
        this.segmentStartedAt = Date.now();
        this.speaking = true;
        this.state = 'recording';
    },

    /**
     * "Befejeztem": the recording is finished, running or paused.
     */
    stop() {
        if (this.state === 'recording') {
            this.seconds = this.recordedSeconds();
        }

        clearInterval(this.timer);

        if (this.recorder && this.recorder.state !== 'inactive') {
            this.recorder.stop();
        }
    },

    restart() {
        this.blob = null;
        this.seconds = 0;
        this.notice = null;
        this.error = null;
        this.state = 'idle';
    },

    /**
     * Listens to the microphone while recording: the level moves the meter,
     * and after the configured seconds of silence the next question appears;
     * when the speaker goes on, it stays in place, faded.
     */
    listen() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;

        if (! AudioContext) {
            return;
        }

        this.audioContext = new AudioContext();
        this.audioContext.resume?.();
        const analyser = this.audioContext.createAnalyser();
        analyser.fftSize = 2048;
        this.audioContext.createMediaStreamSource(this.stream).connect(analyser);

        const samples = new Uint8Array(analyser.fftSize);
        let silentSince = null;
        let shownAt = 0;

        const frame = () => {
            if (this.state !== 'recording' && this.state !== 'paused') {
                this.level = 0;

                return;
            }

            if (this.state === 'paused') {
                silentSince = null;
                requestAnimationFrame(frame);

                return;
            }

            const now = performance.now();
            analyser.getByteTimeDomainData(samples);
            const rms = Math.sqrt(samples.reduce((sum, sample) => sum + ((sample - 128) / 128) ** 2, 0) / samples.length);

            // The meter is redrawn a dozen times a second, not on every frame.
            if (now - shownAt > 80) {
                this.level = Math.min(1, rms * 8);
                shownAt = now;
            }

            if (rms < 0.02) {
                silentSince ??= now;

                if (config.questions.length > 0 && this.speaking && now - silentSince > config.silence * 1000) {
                    this.questionIndex++;
                    this.speaking = false;
                }
            } else {
                silentSince = null;
                this.speaking = true;
            }

            requestAnimationFrame(frame);
        };

        frame();
    },

    release() {
        this.stream?.getTracks().forEach((track) => track.stop());
        this.stream = null;
        this.audioContext?.close();
        this.audioContext = null;
        this.level = 0;
    },

    async submit() {
        // Only a finished recording goes: "Kész" waits for "Befejeztem".
        if (! this.blob || this.state !== 'recorded') {
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
