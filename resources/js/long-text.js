/**
 * Leírom · Elmondom · Feltöltöm over a longer text field (x-form.long-text):
 * the way the text arrives. The transcript or the file's text goes into the
 * field (WithDictation), and the field shows again, editable.
 */
export default (config) => ({
    mode: 'write',
    length: 0,
    reading: false,
    error: null,
    notice: null,

    init() {
        this.length = this.$refs.field.value.length;
    },

    get canRecord() {
        return !! (navigator.mediaDevices && window.MediaRecorder);
    },

    // Livewire dispatches from the component's root after the morph: the
    // field already holds the text. Another component's field of the same
    // name (one feedback form a chapter) is not this one.
    applied(event) {
        if (event.target !== this.$wire.$el || event.detail.field !== config.field) {
            return;
        }

        this.notice = event.detail.notice;
        this.mode = 'write';
        this.length = this.$refs.field.value.length;
    },

    read(event) {
        const input = event.target;
        const file = input.files[0];

        if (! file) {
            return;
        }

        this.error = null;
        this.notice = null;
        this.reading = true;

        const done = () => {
            this.reading = false;
            input.value = '';
        };

        this.$wire.upload(
            'dictationFile',
            file,
            () => this.$wire.readFile(config.field).finally(done),
            () => {
                done();
                this.error = config.failed;
            },
        );
    },
});
