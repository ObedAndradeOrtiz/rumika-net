const MODEL_PATH = '/vendor/face-api';

let faceapiModule = null;
let modelsReady = null;
let leafletReady = null;
let lastKnownLocation = null;

const LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
const LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';

const hasAttendanceRoot = () => Boolean(document.querySelector('[data-attendance-face]'));

const loadModels = async () => {
    if (!hasAttendanceRoot()) {
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
    const status = root.querySelector('[data-attendance-status]');

    if (status) {
        status.textContent = message;
    }
};

const setButtonBusy = (button, busy, label = 'Validando...') => {
    if (!button) {
        return;
    }

    if (busy) {
        if (!button.dataset.originalText) {
            button.dataset.originalText = button.textContent.trim();
        }

        button.textContent = label;
        button.setAttribute('disabled', 'disabled');
        return;
    }

    if (button.dataset.originalText) {
        button.textContent = button.dataset.originalText;
        delete button.dataset.originalText;
    }

    button.removeAttribute('disabled');
};

const getLocation = () => new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
        reject(new Error('Este navegador no permite leer ubicacion.'));
        return;
    }

    const timeoutId = window.setTimeout(() => {
        if (lastKnownLocation) {
            resolve(lastKnownLocation);
            return;
        }

        reject(new Error('La ubicacion esta demorando. Revisa que el GPS este activo e intenta de nuevo.'));
    }, 9000);

    navigator.geolocation.getCurrentPosition(
        (position) => {
            window.clearTimeout(timeoutId);
            lastKnownLocation = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
            };
            resolve(lastKnownLocation);
        },
        () => {
            window.clearTimeout(timeoutId);
            reject(new Error('Permite la ubicacion para registrar asistencia.'));
        },
        {
            enableHighAccuracy: true,
            timeout: 8000,
            maximumAge: 30000,
        },
    );
});

const stopCamera = (root) => {
    const video = root.querySelector('[data-attendance-video]');

    if (!video?.srcObject) {
        return;
    }

    video.srcObject.getTracks().forEach((track) => track.stop());
    video.srcObject = null;
};

const errorMessage = (error) => {
    const validationMessage = error?.response?.data?.errors
        ? Object.values(error.response.data.errors).flat()[0]
        : null;

    return validationMessage
        || error?.response?.data?.message
        || error?.message
        || 'No se pudo completar la marcacion.';
};

const loadLeaflet = () => {
    if (window.L) {
        return Promise.resolve(window.L);
    }

    if (leafletReady) {
        return leafletReady;
    }

    leafletReady = new Promise((resolve, reject) => {
        if (!document.querySelector(`link[href="${LEAFLET_CSS}"]`)) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = LEAFLET_CSS;
            document.head.appendChild(link);
        }

        const script = document.createElement('script');
        script.src = LEAFLET_JS;
        script.async = true;
        script.onload = () => window.L ? resolve(window.L) : reject(new Error('No se pudo cargar el mapa.'));
        script.onerror = () => reject(new Error('No se pudo cargar el mapa.'));
        document.head.appendChild(script);
    });

    return leafletReady;
};

const numericValue = (input, fallback) => {
    const value = Number.parseFloat(String(input?.value || '').replace(',', '.'));

    return Number.isFinite(value) ? value : fallback;
};

const syncInput = (input, value) => {
    if (!input) {
        return;
    }

    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
};

const initBranchMaps = async () => {
    const mapRoots = Array.from(document.querySelectorAll('[data-branch-map]:not([data-map-ready])'));

    if (mapRoots.length === 0) {
        return;
    }

    const L = await loadLeaflet();

    mapRoots.forEach((mapRoot) => {
        const root = mapRoot.closest('[data-branch-geofence]');
        const latitudeInput = root?.querySelector('[data-branch-latitude]');
        const longitudeInput = root?.querySelector('[data-branch-longitude]');
        const radiusInput = root?.querySelector('[data-branch-radius]');
        const defaultLat = Number.parseFloat(mapRoot.dataset.defaultLat || '-17.783327');
        const defaultLng = Number.parseFloat(mapRoot.dataset.defaultLng || '-63.182140');
        const lat = numericValue(latitudeInput, defaultLat);
        const lng = numericValue(longitudeInput, defaultLng);
        const radius = Math.max(20, numericValue(radiusInput, 120));

        mapRoot.dataset.mapReady = '1';

        const map = L.map(mapRoot, {
            zoomControl: true,
            attributionControl: false,
        }).setView([lat, lng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        const circle = L.circle([lat, lng], {
            radius,
            color: '#0f766e',
            fillColor: '#99f6e4',
            fillOpacity: 0.22,
            weight: 2,
        }).addTo(map);

        const setPoint = (point) => {
            const nextLat = Number(point.lat).toFixed(7);
            const nextLng = Number(point.lng).toFixed(7);

            marker.setLatLng(point);
            circle.setLatLng(point);
            syncInput(latitudeInput, nextLat);
            syncInput(longitudeInput, nextLng);
        };

        map.on('click', (event) => setPoint(event.latlng));
        marker.on('dragend', () => setPoint(marker.getLatLng()));

        radiusInput?.addEventListener('input', () => {
            circle.setRadius(Math.max(20, numericValue(radiusInput, 120)));
        });

        latitudeInput?.addEventListener('input', () => {
            const nextLat = numericValue(latitudeInput, null);
            const nextLng = numericValue(longitudeInput, null);

            if (nextLat !== null && nextLng !== null) {
                marker.setLatLng([nextLat, nextLng]);
                circle.setLatLng([nextLat, nextLng]);
                map.panTo([nextLat, nextLng]);
            }
        });

        longitudeInput?.addEventListener('input', () => {
            const nextLat = numericValue(latitudeInput, null);
            const nextLng = numericValue(longitudeInput, null);

            if (nextLat !== null && nextLng !== null) {
                marker.setLatLng([nextLat, nextLng]);
                circle.setLatLng([nextLat, nextLng]);
                map.panTo([nextLat, nextLng]);
            }
        });

        setTimeout(() => map.invalidateSize(), 180);
    });
};

const startCamera = async (root) => {
    const video = root.querySelector('[data-attendance-video]');

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
    loadModels().catch(() => null);
    getLocation().catch(() => null);
    setStatus(root, 'Camara lista. Rumi validara rostro y ubicacion.');
};

const captureImage = (root) => {
    const video = root.querySelector('[data-attendance-video]');
    const canvas = root.querySelector('[data-attendance-canvas]');

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

const captureAttendance = async (root) => {
    const video = root.querySelector('[data-attendance-video]');
    const button = root.querySelector('[data-attendance-capture]');

    if (root.classList.contains('is-reading')) {
        return;
    }

    if (!video?.srcObject) {
        await startCamera(root);
    }

    setButtonBusy(button, true, 'Validando...');
    root.classList.add('is-reading');

    try {
        setStatus(root, 'Leyendo ubicacion y preparando validacion...');
        const [faceapi, location] = await Promise.all([loadModels(), getLocation()]);

        if (!faceapi) {
            throw new Error('No se pudo cargar la validacion facial. Recarga la pagina e intenta de nuevo.');
        }

        const imageData = captureImage(root);

        if (!imageData) {
            throw new Error('La camara aun no esta lista. Espera unos segundos e intenta de nuevo.');
        }

        setStatus(root, 'Rumi esta revisando tu rostro...');
        const detection = await faceapi
            .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.48 }))
            .withFaceLandmarks(true)
            .withFaceDescriptor();

        if (!detection) {
            setStatus(root, 'No encontre un rostro claro. Mejora la luz e intenta otra vez.');
            return;
        }

        const component = window.Livewire?.find(root.dataset.livewireId);

        if (!component) {
            setStatus(root, 'No se pudo conectar con Rumika. Recarga la pagina.');
            return;
        }

        setStatus(root, 'Validando asistencia...');
        setButtonBusy(button, true, 'Guardando...');
        await component.call(
            'submitPunch',
            JSON.stringify(Array.from(detection.descriptor)),
            imageData,
            location.latitude,
            location.longitude,
        );

        setStatus(root, 'Marcacion guardada correctamente.');
        stopCamera(root);
    } finally {
        root.classList.remove('is-reading');
        setButtonBusy(button, false);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (hasAttendanceRoot()) {
        loadModels();
    }

    initBranchMaps();
});

document.addEventListener('livewire:navigated', initBranchMaps);
document.addEventListener('livewire:init', () => {
    window.Livewire?.hook('morph.updated', () => {
        window.setTimeout(initBranchMaps, 80);
    });
});

document.addEventListener('click', async (event) => {
    const locationButton = event.target.closest('[data-branch-location-button]');

    if (locationButton) {
        const root = locationButton.closest('[data-branch-geofence]');
        const latitudeInput = root?.querySelector('[data-branch-latitude]');
        const longitudeInput = root?.querySelector('[data-branch-longitude]');

        try {
            locationButton.setAttribute('disabled', 'disabled');
            const location = await getLocation();

            if (latitudeInput && longitudeInput) {
                latitudeInput.value = location.latitude.toFixed(7);
                longitudeInput.value = location.longitude.toFixed(7);
                latitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
                longitudeInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        } catch (error) {
            window.alert(error?.message || 'No se pudo obtener tu ubicacion.');
        } finally {
            locationButton.removeAttribute('disabled');
        }

        return;
    }

    const startButton = event.target.closest('[data-attendance-start]');
    const captureButton = event.target.closest('[data-attendance-capture]');
    const root = event.target.closest('[data-attendance-face]');

    if (!root || (!startButton && !captureButton)) {
        return;
    }

    try {
        if (startButton) {
            await startCamera(root);
        }

        if (captureButton) {
            await captureAttendance(root);
        }
    } catch (error) {
        root.classList.remove('is-reading');
        setStatus(root, errorMessage(error));
    }
});
