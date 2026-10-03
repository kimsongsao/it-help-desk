/**
 * Square avatar cropper: drag to position, slider or mouse wheel to zoom.
 *
 * The crop is rendered in the browser (which already applies photo orientation) and the
 * resulting PNG replaces the chosen file, so the server receives exactly what was framed.
 */
import { Modal } from '@tabler/core/dist/js/tabler.esm.min.js';

const STAGE_SIZE = 320;
const OUTPUT_SIZE = 512;

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

export function initAvatarCropper() {
    const input = document.querySelector('[data-avatar-input]');
    const modalElement = document.getElementById('avatar-cropper');

    if (!input || !modalElement) {
        return;
    }

    const stage = modalElement.querySelector('[data-crop-stage]');
    const image = modalElement.querySelector('[data-crop-image]');
    const zoomInput = modalElement.querySelector('[data-crop-zoom]');
    const applyButton = modalElement.querySelector('[data-crop-apply]');
    const avatar = document.querySelector('[data-avatar-preview]');
    const fileName = document.querySelector('[data-avatar-name]');
    const modal = Modal.getOrCreateInstance(modalElement);

    let state = null;
    let applied = false;

    const render = () => {
        image.style.width = `${state.width * state.scale}px`;
        image.style.height = `${state.height * state.scale}px`;
        image.style.transform = `translate(${state.x}px, ${state.y}px)`;
    };

    const setScale = (scale, anchorX = STAGE_SIZE / 2, anchorY = STAGE_SIZE / 2) => {
        const next = clamp(scale, state.minScale, state.minScale * 4);
        // Keep the point under the anchor still while zooming.
        state.x = anchorX - ((anchorX - state.x) / state.scale) * next;
        state.y = anchorY - ((anchorY - state.y) / state.scale) * next;
        state.scale = next;
        state.x = clamp(state.x, STAGE_SIZE - state.width * next, 0);
        state.y = clamp(state.y, STAGE_SIZE - state.height * next, 0);
        zoomInput.value = String(next / state.minScale);
        render();
    };

    const open = (file) => {
        const url = URL.createObjectURL(file);
        image.onload = () => {
            const minScale = Math.max(STAGE_SIZE / image.naturalWidth, STAGE_SIZE / image.naturalHeight);
            state = {
                file,
                width: image.naturalWidth,
                height: image.naturalHeight,
                minScale,
                scale: minScale,
                x: (STAGE_SIZE - image.naturalWidth * minScale) / 2,
                y: (STAGE_SIZE - image.naturalHeight * minScale) / 2,
            };
            zoomInput.value = '1';
            applied = false;
            render();
            modal.show();
        };
        image.src = url;
    };

    input.addEventListener('change', () => {
        const [file] = input.files;
        if (file && file.type.startsWith('image/') && !file.name.startsWith('avatar-cropped')) {
            open(file);
        }
    });

    zoomInput.addEventListener('input', () => setScale(state.minScale * Number(zoomInput.value)));

    stage.addEventListener('wheel', (event) => {
        event.preventDefault();
        const rect = stage.getBoundingClientRect();
        setScale(state.scale * (event.deltaY < 0 ? 1.1 : 0.9), event.clientX - rect.left, event.clientY - rect.top);
    }, { passive: false });

    stage.addEventListener('pointerdown', (event) => {
        stage.setPointerCapture(event.pointerId);
        const start = { pointerX: event.clientX, pointerY: event.clientY, x: state.x, y: state.y };

        const move = (moveEvent) => {
            state.x = clamp(start.x + moveEvent.clientX - start.pointerX, STAGE_SIZE - state.width * state.scale, 0);
            state.y = clamp(start.y + moveEvent.clientY - start.pointerY, STAGE_SIZE - state.height * state.scale, 0);
            render();
        };
        const stop = () => {
            stage.removeEventListener('pointermove', move);
            stage.removeEventListener('pointerup', stop);
        };

        stage.addEventListener('pointermove', move);
        stage.addEventListener('pointerup', stop);
    });

    stage.addEventListener('keydown', (event) => {
        const step = event.shiftKey ? 20 : 5;
        const moves = { ArrowLeft: [step, 0], ArrowRight: [-step, 0], ArrowUp: [0, step], ArrowDown: [0, -step] };
        if (moves[event.key]) {
            event.preventDefault();
            state.x = clamp(state.x + moves[event.key][0], STAGE_SIZE - state.width * state.scale, 0);
            state.y = clamp(state.y + moves[event.key][1], STAGE_SIZE - state.height * state.scale, 0);
            render();
        }
    });

    applyButton.addEventListener('click', () => {
        const canvas = document.createElement('canvas');
        canvas.width = OUTPUT_SIZE;
        canvas.height = OUTPUT_SIZE;
        const size = STAGE_SIZE / state.scale;
        canvas.getContext('2d').drawImage(image, -state.x / state.scale, -state.y / state.scale, size, size, 0, 0, OUTPUT_SIZE, OUTPUT_SIZE);

        canvas.toBlob((blob) => {
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'avatar-cropped.png', { type: 'image/png' }));
            input.files = transfer.files;

            avatar.style.backgroundImage = `url('${canvas.toDataURL('image/png')}')`;
            avatar.textContent = '';
            fileName.textContent = state.file.name;
            fileName.classList.remove('d-none');
            applied = true;
            modal.hide();
        }, 'image/png');
    });

    modalElement.addEventListener('hidden.bs.modal', () => {
        if (!applied) {
            input.value = '';
        }
    });
}
