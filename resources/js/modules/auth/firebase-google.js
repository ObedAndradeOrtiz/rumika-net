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
        || Object.values(error.response?.data?.errors || {})?.flat()?.[0]
        || (error.response?.status === 419 ? 'Tu sesion expiro. Recarga la pagina y vuelve a continuar con Google.' : null)
        || (error.response?.status >= 500 ? 'No pudimos completar el registro. Intenta nuevamente en unos momentos.' : null)
        || error.response?.data?.message
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
    }).catch(() => {});

    firebaseContext = { auth, provider };

    return firebaseContext;
};

const bindGoogleAuthButtons = () => {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-firebase-google]');
        if (!button) return;
        if (button.disabled) return;

        const form = button.closest('[data-google-registration]');
        const errorTarget = document.querySelector(button.dataset.errorTarget || '[data-google-error]');
        const originalText = button.querySelector('[data-google-label]')?.textContent;
        const termsCheckbox = (form || document).querySelector('[data-terms-checkbox]');

        if (termsCheckbox && !termsCheckbox.checked) {
            if (errorTarget) {
                errorTarget.hidden = false;
                errorTarget.textContent = 'Debes aceptar los terminos y la politica de privacidad para crear tu cuenta.';
            }

            termsCheckbox.focus();
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
            const context = firebaseReady();
            if (!context) {
                throw new Error('Falta configurar Firebase para crear cuentas con Google.');
            }

            // Open Google synchronously from the click so mobile browsers allow it.
            const credential = await signInWithPopup(context.auth, context.provider);
            const idToken = await credential.user.getIdToken();
            const payload = {
                id_token: idToken,
            };

            if (termsCheckbox) {
                payload.terms_accepted = termsCheckbox.checked;
            }

            button.querySelector('[data-google-label]').textContent = 'Preparando tu cuenta...';
            const response = await window.axios.post(button.dataset.authUrl, payload, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                timeout: 30000,
            });

            window.location.href = response.data.redirect || '/dashboard';
        } catch (error) {
            const message = firebaseAuthMessage(error);

            if (errorTarget) {
                errorTarget.hidden = false;
                errorTarget.textContent = message;
                errorTarget.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }

            button.disabled = false;
            button.classList.remove('is-loading');

            if (button.querySelector('[data-google-label]') && originalText) {
                button.querySelector('[data-google-label]').textContent = originalText;
            }
        }
    });
};

// Delegation also handles pages restored from Livewire's navigation cache.
bindGoogleAuthButtons();

document.addEventListener('change', (event) => {
    if (!event.target.matches('[data-terms-checkbox]') || !event.target.checked) return;

    const errorTarget = event.target.closest('[data-google-registration]')?.querySelector('[data-google-error]');
    if (errorTarget) {
        errorTarget.hidden = true;
        errorTarget.textContent = '';
    }
});
