import { initializeApp } from 'firebase/app';
import { getAnalytics, isSupported } from 'firebase/analytics';
import { getAuth, GoogleAuthProvider, signInWithPopup } from 'firebase/auth';

let firebaseContext = null;

const firebaseAuthMessage = (error) => {
    const messages = {
        'auth/unauthorized-domain': 'Este dominio no esta autorizado en Firebase. Agrega rumika.guislaincorp.com y www.rumika.guislaincorp.com en Authentication > Settings > Authorized domains.',
        'auth/operation-not-allowed': 'El proveedor Google no esta habilitado en Firebase Authentication.',
        'auth/popup-blocked': 'El navegador bloqueo la ventana de Google. Permite ventanas emergentes para este sitio.',
        'auth/popup-closed-by-user': 'Se cerro la ventana de Google antes de terminar el ingreso.',
        'auth/cancelled-popup-request': 'Ya hay una ventana de Google abierta. Cierra la anterior e intenta de nuevo.',
        'auth/network-request-failed': 'No se pudo conectar con Firebase. Revisa internet o bloqueo del navegador.',
        'auth/invalid-api-key': 'La API key de Firebase no es valida en este entorno.',
        'auth/auth-domain-config-required': 'Falta configurar FIREBASE_AUTH_DOMAIN para el login con Google.',
    };

    return messages[error.code]
        || error.response?.data?.message
        || Object.values(error.response?.data?.errors || {})?.flat()?.[0]
        || error.message
        || 'No se pudo iniciar sesion con Google. Intenta de nuevo.';
};

const firebaseReady = () => {
    if (firebaseContext) {
        return firebaseContext;
    }

    const firebaseConfig = {
        apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
        authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
        projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
        storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
        messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
        appId: import.meta.env.VITE_FIREBASE_APP_ID,
        measurementId: import.meta.env.VITE_FIREBASE_MEASUREMENT_ID,
    };

    if (! firebaseConfig.apiKey || ! firebaseConfig.authDomain || ! firebaseConfig.projectId) {
        return null;
    }

    const app = initializeApp(firebaseConfig);
    const auth = getAuth(app);
    const provider = new GoogleAuthProvider();

    provider.setCustomParameters({
        prompt: 'select_account',
    });

    isSupported().then((supported) => {
        if (supported && firebaseConfig.measurementId) {
            getAnalytics(app);
        }
    });

    firebaseContext = { auth, provider };

    return firebaseContext;
};

const bindGoogleAuthButtons = () => {
    const googleButtons = document.querySelectorAll('[data-firebase-google]');

    if (! googleButtons.length) {
        return;
    }

    const context = firebaseReady();

    googleButtons.forEach((button) => {
        if (button.dataset.firebaseBound === 'true') {
            return;
        }

        button.dataset.firebaseBound = 'true';

        button.addEventListener('click', async () => {
            const errorTarget = document.querySelector(button.dataset.errorTarget || '[data-google-error]');
            const originalText = button.querySelector('[data-google-label]')?.textContent;
            const termsCheckbox = document.querySelector('[data-terms-checkbox]');

            if (! context) {
                if (errorTarget) {
                    errorTarget.hidden = false;
                    errorTarget.textContent = 'Falta configurar Firebase para crear cuentas con Google.';
                }

                return;
            }

            if (termsCheckbox && !termsCheckbox.checked) {
                if (errorTarget) {
                    errorTarget.hidden = false;
                    errorTarget.textContent = 'Debes aceptar los terminos y la politica de privacidad para crear tu cuenta.';
                }

                return;
            }

            button.disabled = true;
            button.classList.add('is-loading');

            if (button.querySelector('[data-google-label]')) {
                button.querySelector('[data-google-label]').textContent = 'Conectando con Google...';
            }

            if (errorTarget) {
                errorTarget.hidden = true;
                errorTarget.textContent = '';
            }

            try {
                const credential = await signInWithPopup(context.auth, context.provider);
                const idToken = await credential.user.getIdToken();
                const payload = {
                    id_token: idToken,
                };

                if (termsCheckbox) {
                    payload.terms_accepted = termsCheckbox.checked;
                }

                const response = await window.axios.post(button.dataset.authUrl, payload);

                window.location.href = response.data.redirect || '/dashboard';
            } catch (error) {
                const message = firebaseAuthMessage(error);

                if (errorTarget) {
                    errorTarget.hidden = false;
                    errorTarget.textContent = message;
                }

                button.disabled = false;
                button.classList.remove('is-loading');

                if (button.querySelector('[data-google-label]') && originalText) {
                    button.querySelector('[data-google-label]').textContent = originalText;
                }
            }
        });
    });
};

document.addEventListener('DOMContentLoaded', bindGoogleAuthButtons);
document.addEventListener('livewire:navigated', bindGoogleAuthButtons);
