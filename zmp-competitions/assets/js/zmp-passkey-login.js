(function () {
    'use strict';

    var OPTIONS_ACTION = 'zmp_passkey_login_options';
    var FINISH_ACTION = 'zmp_passkey_login_finish';
    var config = window.zmpPasskeyLogin || {};
    var container = document.querySelector('.zmp-passkey-login');
    var button = document.getElementById('zmp-passkey-login');
    var status = document.getElementById('zmp-passkey-login-status');
    var supported = window.isSecureContext === true && !!(window.PublicKeyCredential
        && navigator.credentials && typeof navigator.credentials.get === 'function'
        && typeof window.fetch === 'function' && typeof window.FormData === 'function');

    function message(key) {
        return config.messages && typeof config.messages[key] === 'string'
            ? config.messages[key]
            : 'Passkey sign-in could not be completed. Try again or use your password.';
    }

    function setStatus(key) {
        if (status) {
            status.textContent = message(key);
        }
    }

    function decodeBase64Url(value) {
        if (typeof value !== 'string' || !/^[A-Za-z0-9_-]+$/.test(value)) {
            throw new Error('invalid-option');
        }
        var base64 = value.replace(/-/g, '+').replace(/_/g, '/');
        while (base64.length % 4) {
            base64 += '=';
        }
        var binary = window.atob(base64);
        var bytes = new Uint8Array(binary.length);
        for (var i = 0; i < binary.length; i += 1) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }

    function encodeBase64Url(value) {
        if (!(value instanceof ArrayBuffer)) {
            throw new Error('invalid-response');
        }
        var bytes = new Uint8Array(value);
        var binary = '';
        for (var i = 0; i < bytes.length; i += 1) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
    }

    function buildRequestOptions(options) {
        if (!options || typeof options !== 'object' || typeof options.challenge !== 'string'
            || typeof options.rpId !== 'string' || options.userVerification !== 'required'
            || (options.allowCredentials && options.allowCredentials.length)) {
            throw new Error('invalid-options');
        }
        var publicKey = {
            challenge: decodeBase64Url(options.challenge),
            rpId: options.rpId,
            userVerification: 'required'
        };
        if (typeof options.timeout === 'number' && options.timeout > 0) {
            publicKey.timeout = options.timeout;
        }
        if (options.extensions && typeof options.extensions === 'object') {
            publicKey.extensions = options.extensions;
        }
        return publicKey;
    }

    function serializeAssertion(credential) {
        if (!credential || credential.type !== 'public-key' || !credential.response
            || !credential.rawId || typeof credential.response.clientDataJSON === 'undefined'
            || typeof credential.response.authenticatorData === 'undefined'
            || typeof credential.response.signature === 'undefined') {
            throw new Error('invalid-assertion');
        }
        return {
            id: credential.id,
            rawId: encodeBase64Url(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: encodeBase64Url(credential.response.clientDataJSON),
                authenticatorData: encodeBase64Url(credential.response.authenticatorData),
                signature: encodeBase64Url(credential.response.signature),
                userHandle: credential.response.userHandle
                    ? encodeBase64Url(credential.response.userHandle)
                    : null
            },
            clientExtensionResults: typeof credential.getClientExtensionResults === 'function'
                ? credential.getClientExtensionResults()
                : {}
        };
    }

    function post(action, values) {
        if (typeof config.endpoint !== 'string' || typeof action !== 'string') {
            return Promise.reject(new Error('request-unavailable'));
        }
        var endpoint;
        try {
            endpoint = new URL(config.endpoint, window.location.href);
        } catch (error) {
            return Promise.reject(new Error('request-unavailable'));
        }
        if (endpoint.origin !== window.location.origin || endpoint.protocol !== window.location.protocol) {
            return Promise.reject(new Error('request-unavailable'));
        }

        var data = new FormData();
        data.append('action', action);
        Object.keys(values).forEach(function (key) {
            data.append(key, values[key]);
        });
        return window.fetch(endpoint.href, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' },
            body: data
        }).then(function (response) {
            return response.json().then(function (body) {
                if (!response.ok || !body || body.success !== true || !body.data) {
                    throw new Error('request-failed');
                }
                return body.data;
            });
        });
    }

    function beginLogin() {
        if (!button || button.disabled) {
            return;
        }
        if (!window.isSecureContext) {
            setStatus('insecure');
            return;
        }
        if (!supported) {
            setStatus('unsupported');
            return;
        }

        button.disabled = true;
        if (container) {
            container.setAttribute('aria-busy', 'true');
        }
        setStatus('working');

        post(config.optionsAction || OPTIONS_ACTION, {}).then(function (data) {
            if (!data || typeof data.challenge_id !== 'string' || !data.options) {
                throw new Error('invalid-options');
            }
            setStatus('continue');
            return window.navigator.credentials.get({ publicKey: buildRequestOptions(data.options) })
                .then(function (credential) {
                    return post(config.finishAction || FINISH_ACTION, {
                        challenge_id: data.challenge_id,
                        credential: JSON.stringify(serializeAssertion(credential))
                    });
                });
        }).then(function (result) {
            if (!result || result.authenticated !== true || typeof result.redirect !== 'string') {
                throw new Error('invalid-result');
            }
            var redirect = new URL(result.redirect, window.location.href);
            if (redirect.origin !== window.location.origin || redirect.protocol !== window.location.protocol) {
                throw new Error('invalid-redirect');
            }
            window.location.assign(redirect.href);
        }).catch(function (error) {
            setStatus(error && error.name === 'NotAllowedError' ? 'canceled' : 'failed');
            button.disabled = false;
            if (container) {
                container.setAttribute('aria-busy', 'false');
            }
        });
    }

    if (button) {
        button.addEventListener('click', beginLogin);
        if (!supported) {
            button.disabled = true;
            setStatus(window.isSecureContext ? 'unsupported' : 'insecure');
        }
    }
}());
