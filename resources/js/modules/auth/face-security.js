const MODEL_PATH = '/vendor/face-api';

let modelsReady = null;
let faceapiModule = null;

const loadModels = async () => {
    if (!document.querySelector('[data-face-security]')) {
        return null;
    }

    if (!faceapiModule) {
        faceapiModule = await import('@vladmandic/face-api');
    }

    if (!modelsReady) {
        modelsReady = Promise.all([
            faceapiModule.nets.tinyFaceDetector.loadFromUri(MODEL_PATH),
            faceapiModule.nets.faceLandmark68TinyNet.loadFromUri(MODEL_PATH),
            faceapiModule.nets.faceRecognitionNet.loadFromUri(MODEL_PATH),
        ]);
    }

    await modelsReady;

    return faceapiModule;
};

const setStatus = (root, message) => {
    const status = root.querySelector('[data-face-status]');

    if (status) {
        status.textContent = message;
    }
};

const startCamera = async (root) => {
    const video = root.querySelector('[data-face-video]');

    const stream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: 'user',
            width: { ideal: 480 },
            height: { ideal: 360 },
        },
        audio: false,
    });

    video.srcObject = stream;
    await video.play();
    loadModels();
    setStatus(root, 'Camara lista. Mantente de frente y presiona validar.');
};

const captureImage = (root) => {
    const video = root.querySelector('[data-face-video]');
    const canvas = root.querySelector('[data-face-canvas]');

    if (!video || !canvas || !video.videoWidth) {
        return null;
    }

    const width = Math.min(video.videoWidth, 420);
    const height = Math.round((video.videoHeight / video.videoWidth) * width);

    canvas.width = width;
    canvas.height = height;

    const context = canvas.getContext('2d');
    context.translate(width, 0);
    context.scale(-1, 1);
    context.drawImage(video, 0, 0, width, height);

    return canvas.toDataURL('image/jpeg', 0.72);
};

const captureDescriptor = async (root) => {
    const video = root.querySelector('[data-face-video]');
    const captureButton = root.querySelector('[data-face-capture]');

    if (!video?.srcObject) {
        await startCamera(root);
    }

    captureButton?.setAttribute('disabled', 'disabled');
    root.classList.add('is-reading');

    try {
        const faceapi = await loadModels();
        const imageData = captureImage(root);

        setStatus(root, 'Rumi esta revisando tu rostro...');

        const detection = await faceapi
            .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.48 }))
            .withFaceLandmarks(true)
            .withFaceDescriptor();

        if (!detection) {
            setStatus(root, 'No encontre un rostro claro. Mejora la luz e intenta otra vez.');
            await window.Livewire?.find(root.dataset.livewireId)?.call('recordFailedAttempt', imageData);
            return;
        }

        const action = root.dataset.faceMode === 'enroll' ? 'saveDescriptor' : 'verifyDescriptor';
        const component = window.Livewire?.find(root.dataset.livewireId);

        if (!component) {
            setStatus(root, 'No se pudo conectar con el sistema. Recarga la pagina.');
            return;
        }

        setStatus(root, 'Validando acceso...');
        await component.call(action, JSON.stringify(Array.from(detection.descriptor)), imageData);
    } finally {
        root.classList.remove('is-reading');
        captureButton?.removeAttribute('disabled');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-face-security]')) {
        loadModels();
    }
});

document.addEventListener('click', async (event) => {
    const startButton = event.target.closest('[data-face-start]');
    const captureButton = event.target.closest('[data-face-capture]');
    const root = event.target.closest('[data-face-security]');

    if (!root || (!startButton && !captureButton)) {
        return;
    }

    try {
        if (startButton) {
            await startCamera(root);
        }

        if (captureButton) {
            await captureDescriptor(root);
        }
    } catch (error) {
        root.classList.remove('is-reading');
        setStatus(root, error?.message || 'No se pudo usar la camara en este navegador.');
    }
});
