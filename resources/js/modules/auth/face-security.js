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

    await loadModels();

    const stream = await navigator.mediaDevices.getUserMedia({
        video: {
            facingMode: 'user',
            width: { ideal: 720 },
            height: { ideal: 540 },
        },
        audio: false,
    });

    video.srcObject = stream;
    await video.play();
    setStatus(root, 'Camara lista. Mantente de frente y presiona validar.');
};

const captureDescriptor = async (root) => {
    const video = root.querySelector('[data-face-video]');

    if (!video?.srcObject) {
        await startCamera(root);
    }

    const faceapi = await loadModels();

    root.classList.add('is-reading');
    setStatus(root, 'Rumi esta revisando tu rostro...');

    const detection = await faceapi
        .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
        .withFaceLandmarks(true)
        .withFaceDescriptor();

    root.classList.remove('is-reading');

    if (!detection) {
        setStatus(root, 'No encontre un rostro claro. Mejora la luz e intenta otra vez.');
        return;
    }

    const action = root.dataset.faceMode === 'enroll' ? 'saveDescriptor' : 'verifyDescriptor';
    const component = window.Livewire?.find(root.dataset.livewireId);

    if (!component) {
        setStatus(root, 'No se pudo conectar con el sistema. Recarga la pagina.');
        return;
    }

    setStatus(root, 'Validando acceso...');
    await component.call(action, JSON.stringify(Array.from(detection.descriptor)));
};

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
