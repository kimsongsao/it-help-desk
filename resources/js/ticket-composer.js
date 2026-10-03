/**
 * Ticket composer: a textarea with voice dictation, a voice note recorder and
 * drag & drop attachments. Files are kept in real <input type="file"> elements
 * (via DataTransfer) so the form submits normally without any upload API.
 */
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const MAX_RECORDING_SECONDS = 300;

function formatBytes(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function formatSeconds(seconds) {
    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
}

function iconFor(file) {
    if (file.type.startsWith('image/')) return 'ti-photo';
    if (file.type.startsWith('audio/')) return 'ti-music';
    if (file.type === 'application/pdf') return 'ti-file-type-pdf';
    return 'ti-file';
}

function setFiles(input, files) {
    const transfer = new DataTransfer();
    files.forEach((file) => transfer.items.add(file));
    input.files = transfer.files;
}

function initComposer(root) {
    const i18n = JSON.parse(root.dataset.i18n);
    const maxFiles = Number(root.dataset.maxFiles);
    const maxBytes = Number(root.dataset.maxSize) * 1024;
    const textarea = root.querySelector('textarea');
    const fileInput = root.querySelector('[data-file-input]');
    const voiceInput = root.querySelector('[data-voice-input]');
    const fileList = root.querySelector('.composer-files');
    const voicePanel = root.querySelector('.composer-voice');
    const errorBox = root.parentElement.querySelector('[data-composer-error]');
    const dictateButton = root.querySelector('[data-dictate]');
    const recordButton = root.querySelector('[data-record]');
    let files = [];

    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.classList.toggle('d-none', !message);
    };

    /* ---------- Attachments ---------- */

    const renderFiles = () => {
        setFiles(fileInput, files);
        fileList.replaceChildren(...files.map((file, index) => {
            const chip = document.createElement('div');
            chip.className = 'file-chip';
            chip.innerHTML = `<i class="ti ${iconFor(file)} text-secondary"></i><span></span><small class="text-secondary"></small>
                <button type="button" class="btn btn-sm btn-ghost-secondary btn-icon rounded-circle" aria-label="${i18n.remove}"><i class="ti ti-x"></i></button>`;
            chip.querySelector('span').textContent = file.name;
            chip.querySelector('small').textContent = formatBytes(file.size);
            chip.querySelector('button').addEventListener('click', () => {
                files.splice(index, 1);
                renderFiles();
            });
            return chip;
        }));
    };

    const addFiles = (incoming) => {
        showError('');
        for (const file of incoming) {
            if (files.length >= maxFiles) {
                showError(i18n.tooMany);
                break;
            }
            if (file.size > maxBytes) {
                showError(i18n.tooLarge.replace(':name', file.name));
                continue;
            }
            files.push(file);
        }
        renderFiles();
    };

    root.querySelector('[data-attach]').addEventListener('click', () => {
        const picker = document.createElement('input');
        picker.type = 'file';
        picker.multiple = true;
        picker.accept = fileInput.accept;
        picker.addEventListener('change', () => addFiles(picker.files));
        picker.click();
    });

    root.addEventListener('dragover', (event) => {
        if (event.dataTransfer.types.includes('Files')) {
            event.preventDefault();
            root.classList.add('is-dragging');
        }
    });
    root.addEventListener('dragleave', (event) => {
        if (!root.contains(event.relatedTarget)) {
            root.classList.remove('is-dragging');
        }
    });
    root.addEventListener('drop', (event) => {
        event.preventDefault();
        root.classList.remove('is-dragging');
        addFiles(event.dataTransfer.files);
    });
    textarea.addEventListener('paste', (event) => {
        if (event.clipboardData.files.length) {
            event.preventDefault();
            addFiles(event.clipboardData.files);
        }
    });

    /* ---------- Dictation (speech to text) ---------- */

    if (!SpeechRecognition) {
        dictateButton.classList.add('d-none');
    } else {
        let recognition = null;

        const stopDictation = () => {
            recognition?.stop();
        };

        dictateButton.addEventListener('click', () => {
            if (recognition) {
                stopDictation();
                return;
            }

            recognition = new SpeechRecognition();
            recognition.lang = root.dataset.speechLang;
            recognition.continuous = true;
            recognition.interimResults = false;

            recognition.addEventListener('result', (event) => {
                const transcript = Array.from(event.results)
                    .slice(event.resultIndex)
                    .filter((result) => result.isFinal)
                    .map((result) => result[0].transcript.trim())
                    .join(' ');

                if (transcript) {
                    const separator = textarea.value && !/\s$/.test(textarea.value) ? ' ' : '';
                    textarea.value += separator + transcript;
                    textarea.dispatchEvent(new Event('input'));
                }
            });
            recognition.addEventListener('error', (event) => {
                showError(event.error === 'not-allowed' ? i18n.micDenied : i18n.dictationFailed);
            });
            recognition.addEventListener('end', () => {
                recognition = null;
                dictateButton.classList.remove('is-listening');
                dictateButton.querySelector('[data-label]').textContent = i18n.dictate;
            });

            showError('');
            recognition.start();
            dictateButton.classList.add('is-listening');
            dictateButton.querySelector('[data-label]').textContent = i18n.stopDictation;
            textarea.focus();
        });
    }

    /* ---------- Voice note recording ---------- */

    if (!window.MediaRecorder || !navigator.mediaDevices?.getUserMedia) {
        recordButton.classList.add('d-none');
        return;
    }

    let recorder = null;
    let timer = null;

    const clearVoiceNote = () => {
        voiceInput.value = '';
        voicePanel.replaceChildren();
        recordButton.classList.remove('d-none');
    };

    const showVoiceNote = (blob) => {
        const extension = blob.type.includes('mp4') ? 'm4a' : (blob.type.includes('ogg') ? 'ogg' : 'webm');
        setFiles(voiceInput, [new File([blob], `voice-note.${extension}`, { type: blob.type })]);

        const audio = document.createElement('audio');
        audio.controls = true;
        audio.className = 'voice-player';
        audio.src = URL.createObjectURL(blob);

        const discard = document.createElement('button');
        discard.type = 'button';
        discard.className = 'btn btn-sm btn-ghost-danger';
        discard.innerHTML = `<i class="ti ti-trash icon"></i> ${i18n.discard}`;
        discard.addEventListener('click', clearVoiceNote);

        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex flex-wrap align-items-center gap-2 w-100';
        wrapper.append(audio, discard);
        voicePanel.replaceChildren(wrapper);
    };

    const startRecording = async () => {
        let stream;
        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch {
            showError(i18n.micDenied);
            return;
        }

        showError('');
        const mimeType = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg']
            .find((type) => MediaRecorder.isTypeSupported(type));
        const chunks = [];
        recorder = new MediaRecorder(stream, mimeType ? { mimeType } : undefined);
        recorder.addEventListener('dataavailable', (event) => chunks.push(event.data));
        recorder.addEventListener('stop', () => {
            stream.getTracks().forEach((track) => track.stop());
            window.clearInterval(timer);
            recorder = null;
            showVoiceNote(new Blob(chunks, { type: chunks[0]?.type || mimeType || 'audio/webm' }));
        });
        recorder.start();

        let seconds = 0;
        recordButton.classList.add('d-none');
        voicePanel.innerHTML = `<div class="d-flex align-items-center gap-2 w-100">
            <span class="recording-dot"></span><span class="fw-medium">${i18n.recording}</span>
            <span class="text-secondary font-monospace" data-timer>0:00</span>
            <button type="button" class="btn btn-sm btn-danger ms-auto" data-stop><i class="ti ti-player-stop-filled icon"></i> ${i18n.stop}</button>
        </div>`;
        voicePanel.querySelector('[data-stop]').addEventListener('click', () => recorder?.stop());
        timer = window.setInterval(() => {
            seconds += 1;
            voicePanel.querySelector('[data-timer]').textContent = formatSeconds(seconds);
            if (seconds >= MAX_RECORDING_SECONDS) {
                recorder?.stop();
            }
        }, 1000);
    };

    recordButton.addEventListener('click', startRecording);
}

export function initComposers() {
    document.querySelectorAll('[data-composer]').forEach(initComposer);
}
