const DEFAULT_CROP_SIZE = 420;

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

const emitInput = (element) => {
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
};

const initCropper = (root) => {
    if (root.dataset.avatarCropperReady === '1') {
        return;
    }

    const scope = root.closest('form') || root;
    const input = root.querySelector('[data-avatar-crop-input]');
    const tool = scope.querySelector('[data-avatar-crop-tool]');
    const stage = scope.querySelector('[data-avatar-crop-stage]');
    const image = scope.querySelector('[data-avatar-crop-image]');
    const zoomInput = scope.querySelector('[data-avatar-crop-zoom]');
    const output = scope.querySelector('[data-avatar-crop-output]');
    const cropSize = Number.parseInt(root.dataset.avatarCropSize || DEFAULT_CROP_SIZE, 10) || DEFAULT_CROP_SIZE;

    if (!input || !tool || !stage || !image || !zoomInput || !output) {
        return;
    }

    root.dataset.avatarCropperReady = '1';

    const state = {
        image: null,
        baseScale: 1,
        zoom: 1,
        x: 0,
        y: 0,
        dragging: false,
        startX: 0,
        startY: 0,
        originX: 0,
        originY: 0,
    };

    const bounds = () => {
        if (!state.image) {
            return { maxX: 0, maxY: 0 };
        }

        const scale = state.baseScale * state.zoom;
        const width = state.image.naturalWidth * scale;
        const height = state.image.naturalHeight * scale;

        return {
            maxX: Math.max(0, (width - cropSize) / 2),
            maxY: Math.max(0, (height - cropSize) / 2),
        };
    };

    const paint = () => {
        const { maxX, maxY } = bounds();
        state.x = clamp(state.x, -maxX, maxX);
        state.y = clamp(state.y, -maxY, maxY);

        image.style.transform = `translate(-50%, -50%) translate(${state.x}px, ${state.y}px) scale(${state.baseScale * state.zoom})`;
    };

    const renderOutput = () => {
        if (!state.image) {
            return;
        }

        paint();

        const canvas = document.createElement('canvas');
        canvas.width = cropSize;
        canvas.height = cropSize;

        const context = canvas.getContext('2d');
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, cropSize, cropSize);

        const scale = state.baseScale * state.zoom;
        const width = state.image.naturalWidth * scale;
        const height = state.image.naturalHeight * scale;
        const left = (cropSize - width) / 2 + state.x;
        const top = (cropSize - height) / 2 + state.y;

        context.drawImage(state.image, left, top, width, height);
        output.value = canvas.toDataURL('image/jpeg', 0.86);
        emitInput(output);
    };

    input.addEventListener('change', () => {
        const file = input.files?.[0];

        if (!file) {
            tool.hidden = true;
            output.value = '';
            emitInput(output);
            return;
        }

        const reader = new FileReader();

        reader.onload = () => {
            const nextImage = new Image();

            nextImage.onload = () => {
                state.image = nextImage;
                state.baseScale = Math.max(cropSize / nextImage.naturalWidth, cropSize / nextImage.naturalHeight);
                state.zoom = 1;
                state.x = 0;
                state.y = 0;
                zoomInput.value = '1';
                image.src = nextImage.src;
                tool.hidden = false;
                paint();
                renderOutput();
            };

            nextImage.src = String(reader.result || '');
        };

        reader.readAsDataURL(file);
    });

    zoomInput.addEventListener('input', () => {
        state.zoom = Number.parseFloat(zoomInput.value) || 1;
        renderOutput();
    });

    stage.addEventListener('pointerdown', (event) => {
        if (!state.image) {
            return;
        }

        state.dragging = true;
        state.startX = event.clientX;
        state.startY = event.clientY;
        state.originX = state.x;
        state.originY = state.y;
        stage.setPointerCapture(event.pointerId);
    });

    stage.addEventListener('pointermove', (event) => {
        if (!state.dragging) {
            return;
        }

        state.x = state.originX + event.clientX - state.startX;
        state.y = state.originY + event.clientY - state.startY;
        paint();
    });

    const endDrag = () => {
        if (!state.dragging) {
            return;
        }

        state.dragging = false;
        renderOutput();
    };

    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);
};

const initAvatarCroppers = () => {
    document.querySelectorAll('[data-avatar-cropper]').forEach(initCropper);
};

document.addEventListener('DOMContentLoaded', initAvatarCroppers);
document.addEventListener('livewire:navigated', initAvatarCroppers);
document.addEventListener('livewire:init', () => {
    window.Livewire?.hook('morph.updated', () => {
        window.setTimeout(initAvatarCroppers, 80);
    });
});
