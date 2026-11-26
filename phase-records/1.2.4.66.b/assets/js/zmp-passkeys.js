(function () {
    'use strict';

    var profileConfig = window.zmpPasskeyProfile || {};
    var supportedOrigin = window.isSecureContext === true;
    var hasCredentialApi = !!(window.PublicKeyCredential && navigator.credentials
        && typeof navigator.credentials.create === 'function');

    var locale = String(document.documentElement.lang || navigator.language || 'mk').toLowerCase();
    var messages = locale.indexOf('sq') === 0 ? {
        enterPassword: 'Shkruani fjalëkalimin aktual për të filluar.',
        preparing: 'Po përgatitet regjistrimi me passkey…',
        continueInBrowser: 'Ndiqni kërkesën e sigurisë në shfletues.',
        success: 'Passkey u shtua me sukses në këtë llogari.',
        canceled: 'Regjistrimi u anulua. Provoni përsëri kur të jeni gati.',
        unsupported: 'Ky shfletues ose kjo lidhje nuk mbështet regjistrimin me passkey.',
        insecure: 'Për të menaxhuar passkey-n, hapni këtë faqe përmes një lidhjeje të sigurt.',
        failed: 'Regjistrimi nuk mund të përfundohej. Kontrolloni fjalëkalimin dhe provoni përsëri.',
        diagnostic: {
            optionsHttpHeaderAbsent: 'Diagnostika: mungon titulli i plugin-it (HTTP {status}; ridrejtim: {redirect}; destinacioni: {route}; përgjigjja: {contentType}).',
            optionsHttpCategory: 'Diagnostika (HTTP {status}): {category}',
            optionsResponseMetadata: {
                redirectYes: 'po', redirectNo: 'jo', routeLogin: 'faqja e hyrjes së WordPress-it',
                routeAdminPost: 'admin-post.php', routeSameOrigin: 'faqe tjetër në të njëjtin sajt',
                routeExternal: 'destinacion jashtë sajtit', routeUnknown: 'i panjohur',
                contentJson: 'JSON', contentHtml: 'HTML', contentOther: 'tjetër'
            },
            optionsCategories: {
                auth_required: 'WordPress nuk e njohu sesionin e kyçur.',
                capability_denied: 'Llogaria nuk ka lejen e nevojshme të WordPress-it.',
                request_invalid: 'Serveri refuzoi metodën ose formatin e kërkesës.',
                action_mismatch: 'Veprimi i regjistrimit nuk përputhej me përpunuesin.',
                nonce_missing: 'Kontrolli i sigurisë së kërkesës mungonte.',
                nonce_invalid: 'Kontrolli i sigurisë së kërkesës nuk kaloi.',
                options_handler_rejected: 'Përpunuesi i opsioneve të regjistrimit e refuzoi kërkesën.',
                options_handler_succeeded: 'Përpunuesi i opsioneve të regjistrimit u krye me sukses.'
            },
            optionsNetwork: 'Diagnostika: kërkesa për opsionet e serverit nuk arriti të përfundojë.',
            optionsInvalid: 'Diagnostika: opsionet e regjistrimit nuk mund të lexoheshin ose përpunoheshin.',
            browserError: 'Diagnostika: u ndal kërkesa WebAuthn e shfletuesit ({name}).',
            browserEmpty: 'Diagnostika: shfletuesi nuk ktheu një kredencial WebAuthn.',
            serialization: 'Diagnostika: përgjigjja WebAuthn nuk mund të përgatitej për serverin.',
            finishHttp: 'Diagnostika: dërgimi i konfirmimit te serveri dështoi (HTTP {status}).',
            finishNetwork: 'Diagnostika: konfirmimi nuk arriti te serveri.',
            finishInvalid: 'Diagnostika: serveri nuk e konfirmoi regjistrimin.'
        },
        removePassword: 'Shkruani fjalëkalimin aktual për të hequr passkey-n.',
        removing: 'Po hiqet passkey…',
        removed: 'Passkey u hoq nga kjo llogari.',
        removeFailed: 'Passkey nuk mund të hiqej. Kontrolloni fjalëkalimin dhe provoni përsëri.'
    } : {
        enterPassword: 'Внесете ја тековната лозинка за да започнете.',
        preparing: 'Се подготвува регистрацијата со passkey…',
        continueInBrowser: 'Следете го безбедносното барање во прелистувачот.',
        success: 'Passkey е успешно додаден на оваа сметка.',
        canceled: 'Регистрацијата е откажана. Обидете се повторно кога ќе бидете подготвени.',
        unsupported: 'Овој прелистувач или врска не поддржува passkey регистрација.',
        insecure: 'За управување со passkey, отворете ја страницата преку безбедна врска.',
        failed: 'Регистрацијата не може да се заврши. Проверете ја лозинката и обидете се повторно.',
        diagnostic: {
            optionsHttpHeaderAbsent: 'Дијагностика: недостасува plugin header (HTTP {status}; пренасочување: {redirect}; дестинација: {route}; одговор: {contentType}).',
            optionsHttpCategory: 'Дијагностика (HTTP {status}): {category}',
            optionsResponseMetadata: {
                redirectYes: 'да', redirectNo: 'не', routeLogin: 'WordPress најава',
                routeAdminPost: 'admin-post.php', routeSameOrigin: 'друга страница на истиот сајт',
                routeExternal: 'надворешна дестинација', routeUnknown: 'непозната',
                contentJson: 'JSON', contentHtml: 'HTML', contentOther: 'друго'
            },
            optionsCategories: {
                auth_required: 'WordPress не ја препозна најавената сесија.',
                capability_denied: 'Сметката го нема потребното WordPress право.',
                request_invalid: 'Серверот го одби методот или форматот на барањето.',
                action_mismatch: 'Акцијата за регистрација не се совпадна со обработувачот.',
                nonce_missing: 'Недостасуваше безбедносната проверка на барањето.',
                nonce_invalid: 'Безбедносната проверка на барањето не помина.',
                options_handler_rejected: 'PHP обработувачот на опциите за регистрација го одби барањето.',
                options_handler_succeeded: 'PHP обработувачот на опциите за регистрација заврши успешно.'
            },
            optionsNetwork: 'Дијагностика: барањето за серверските опции не заврши.',
            optionsInvalid: 'Дијагностика: опциите за регистрација не можеа да се прочитаат или обработат.',
            browserError: 'Дијагностика: WebAuthn барањето во прелистувачот запре ({name}).',
            browserEmpty: 'Дијагностика: прелистувачот не врати WebAuthn credential.',
            serialization: 'Дијагностика: WebAuthn одговорот не можеше да се подготви за серверот.',
            finishHttp: 'Дијагностика: испраќањето на потврдата до серверот не успеа (HTTP {status}).',
            finishNetwork: 'Дијагностика: потврдата не стигна до серверот.',
            finishInvalid: 'Дијагностика: серверот не ја потврди регистрацијата.'
        },
        removePassword: 'Внесете ја тековната лозинка за да го отстраните passkey.',
        removing: 'Passkey се отстранува…',
        removed: 'Passkey е отстранет од оваа сметка.',
        removeFailed: 'Passkey не може да се отстрани. Проверете ја лозинката и обидете се повторно.'
    };

    function decodeBase64Url(value) {
        if (typeof value !== 'string' || !/^[A-Za-z0-9_-]+$/.test(value)) {
            throw new Error('invalid-encoding');
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

    function encodeBase64Url(buffer) {
        var bytes = new Uint8Array(buffer);
        var binary = '';
        var chunkSize = 0x8000;
        for (var offset = 0; offset < bytes.length; offset += chunkSize) {
            var slice = bytes.subarray(offset, Math.min(offset + chunkSize, bytes.length));
            binary += String.fromCharCode.apply(null, slice);
        }
        return window.btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
    }

    function parseCreationOptions(optionsJson) {
        if (window.PublicKeyCredential
            && typeof window.PublicKeyCredential.parseCreationOptionsFromJSON === 'function') {
            return window.PublicKeyCredential.parseCreationOptionsFromJSON(optionsJson);
        }

        var options = Object.assign({}, optionsJson);
        options.challenge = decodeBase64Url(optionsJson.challenge);
        options.user = Object.assign({}, optionsJson.user, {
            id: decodeBase64Url(optionsJson.user.id)
        });
        if (Array.isArray(optionsJson.excludeCredentials)) {
            options.excludeCredentials = optionsJson.excludeCredentials.map(function (descriptor) {
                return Object.assign({}, descriptor, { id: decodeBase64Url(descriptor.id) });
            });
        }
        return options;
    }

    function credentialToJson(credential) {
        if (!credential || credential.type !== 'public-key' || !credential.response) {
            throw new Error('invalid-credential');
        }

        var result;
        if (typeof credential.toJSON === 'function') {
            result = credential.toJSON();
        } else {
            var response = credential.response;
            if (!response.clientDataJSON || !response.attestationObject) {
                throw new Error('invalid-attestation');
            }
            var responseJson = {
                clientDataJSON: encodeBase64Url(response.clientDataJSON),
                attestationObject: encodeBase64Url(response.attestationObject)
            };
            if (typeof response.getTransports === 'function') {
                responseJson.transports = response.getTransports();
            }
            if (typeof response.getAuthenticatorData === 'function') {
                var authenticatorData = response.getAuthenticatorData();
                if (authenticatorData) {
                    responseJson.authenticatorData = encodeBase64Url(authenticatorData);
                }
            }
            if (typeof response.getPublicKey === 'function') {
                var publicKey = response.getPublicKey();
                if (publicKey) {
                    responseJson.publicKey = encodeBase64Url(publicKey);
                }
            }
            if (typeof response.getPublicKeyAlgorithm === 'function') {
                responseJson.publicKeyAlgorithm = response.getPublicKeyAlgorithm();
            }

            result = {
                id: credential.id,
                rawId: encodeBase64Url(credential.rawId),
                type: credential.type,
                response: responseJson,
                clientExtensionResults: typeof credential.getClientExtensionResults === 'function'
                    ? credential.getClientExtensionResults()
                    : {}
            };
            if (credential.authenticatorAttachment) {
                result.authenticatorAttachment = credential.authenticatorAttachment;
            }
        }

        if (!result || result.type !== 'public-key' || !result.response
            || typeof result.response.clientDataJSON !== 'string'
            || typeof result.response.attestationObject !== 'string') {
            throw new Error('invalid-attestation');
        }
        return result;
    }

    function postFormData(formData) {
        if (typeof profileConfig.endpoint !== 'string' || profileConfig.endpoint === '') {
            return Promise.reject(new Error('request-unavailable'));
        }
        return window.fetch(profileConfig.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' },
            body: formData
        }).then(function (response) {
            return response.json().then(function (body) {
                if (!response.ok || !body || body.success !== true || !body.data) {
                    throw new Error('request-failed');
                }
                return body.data;
            });
        });
    }

    function enrollmentError(stage, reason, status) {
        var error = new Error('passkey-enrollment-diagnostic');
        error.zmpEnrollmentDiagnostic = {
            stage: stage,
            reason: reason,
            status: typeof status === 'number' && status >= 100 && status <= 599 ? status : 0,
            category: null
        };
        return error;
    }

    function safeOptionsDiagnosticCategory(response) {
        var allowedCategories = ['auth_required', 'capability_denied', 'request_invalid', 'action_mismatch',
            'nonce_missing', 'nonce_invalid', 'options_handler_rejected', 'options_handler_succeeded'];
        if (!response || !response.headers || typeof response.headers.get !== 'function') {
            return null;
        }
        var category = response.headers.get('X-ZMP-Passkey-Diagnostic');
        return allowedCategories.indexOf(category) !== -1 ? category : null;
    }

    function safeOptionsResponseMetadata(response) {
        var route = 'unknown';
        var contentType = 'other';
        var redirected = !!(response && response.redirected === true);
        try {
            var currentUrl = new URL(window.location.href);
            var responseUrl = new URL(response.url, currentUrl.href);
            if (responseUrl.origin !== currentUrl.origin) {
                route = 'external';
            } else if (/\/wp-login\.php$/i.test(responseUrl.pathname)) {
                route = 'login';
            } else if (/\/admin-post\.php$/i.test(responseUrl.pathname)) {
                route = 'adminPost';
            } else {
                route = 'sameOrigin';
            }
        } catch (error) {
            route = 'unknown';
        }
        try {
            var rawContentType = response.headers.get('Content-Type');
            var mimeType = typeof rawContentType === 'string'
                ? rawContentType.split(';')[0].trim().toLowerCase()
                : '';
            if (/^(application|text)\/(json|[a-z0-9.+-]+\+json)$/.test(mimeType)) {
                contentType = 'json';
            } else if (mimeType === 'text/html' || mimeType === 'application/xhtml+xml') {
                contentType = 'html';
            }
        } catch (error) {
            contentType = 'other';
        }
        return { redirected: redirected, route: route, contentType: contentType };
    }

    function postEnrollmentFormData(formData, stage) {
        if (typeof profileConfig.endpoint !== 'string' || profileConfig.endpoint === '') {
            return Promise.reject(enrollmentError(stage, 'network'));
        }
        return window.fetch(profileConfig.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' },
            body: formData
        }).then(function (response) {
            if (!response.ok) {
                var error = enrollmentError(stage, 'http', response.status);
                if (stage === 'options') {
                    error.zmpEnrollmentDiagnostic.category = safeOptionsDiagnosticCategory(response);
                    if (!error.zmpEnrollmentDiagnostic.category) {
                        error.zmpEnrollmentDiagnostic.metadata = safeOptionsResponseMetadata(response);
                    }
                }
                throw error;
            }
            return response.json().then(function (body) {
                if (!body || body.success !== true || !body.data) {
                    throw enrollmentError(stage, 'invalid');
                }
                return body.data;
            }, function () {
                throw enrollmentError(stage, 'invalid');
            });
        }, function () {
            throw enrollmentError(stage, 'network');
        });
    }

    function safeWebAuthnErrorName(error) {
        var allowedNames = ['AbortError', 'ConstraintError', 'InvalidStateError', 'NotAllowedError',
            'NotSupportedError', 'SecurityError', 'TypeError', 'UnknownError'];
        return error && allowedNames.indexOf(error.name) !== -1 ? error.name : 'UnknownError';
    }

    function enrollmentDiagnosticMessage(error) {
        var diagnostic = error && error.zmpEnrollmentDiagnostic;
        if (diagnostic) {
            if (diagnostic.reason === 'http') {
                if (diagnostic.stage === 'options') {
                    var categoryMessage = diagnostic.category
                        ? messages.diagnostic.optionsCategories[diagnostic.category]
                        : null;
                    return categoryMessage
                        ? messages.diagnostic.optionsHttpCategory
                            .replace('{status}', String(diagnostic.status))
                            .replace('{category}', categoryMessage)
                        : messages.diagnostic.optionsHttpHeaderAbsent
                            .replace('{status}', String(diagnostic.status))
                            .replace('{redirect}', diagnostic.metadata && diagnostic.metadata.redirected
                                ? messages.diagnostic.optionsResponseMetadata.redirectYes
                                : messages.diagnostic.optionsResponseMetadata.redirectNo)
                            .replace('{route}', messages.diagnostic.optionsResponseMetadata[
                                diagnostic.metadata ? 'route' + diagnostic.metadata.route.charAt(0).toUpperCase()
                                    + diagnostic.metadata.route.slice(1) : 'routeUnknown'])
                            .replace('{contentType}', messages.diagnostic.optionsResponseMetadata[
                                diagnostic.metadata ? 'content' + diagnostic.metadata.contentType.charAt(0).toUpperCase()
                                    + diagnostic.metadata.contentType.slice(1) : 'contentOther']);
                }
                return messages.diagnostic[diagnostic.stage + 'Http'].replace('{status}', String(diagnostic.status));
            }
            if (diagnostic.reason === 'network') {
                return messages.diagnostic[diagnostic.stage + 'Network'];
            }
            return messages.diagnostic[diagnostic.stage + 'Invalid'];
        }
        if (error && error.zmpEnrollmentBrowserError) {
            return messages.diagnostic.browserError.replace('{name}', safeWebAuthnErrorName(error));
        }
        if (error && error.zmpEnrollmentBrowserEmpty) {
            return messages.diagnostic.browserEmpty;
        }
        if (error && error.zmpEnrollmentSerialization) {
            return messages.diagnostic.serialization;
        }
        return null;
    }

    function setStatus(profile, message) {
        var status = profile.querySelector('.zmp-passkey-profile__status');
        if (!status) {
            status = document.createElement('p');
            status.className = 'zmp-passkey-profile__status';
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');
            status.setAttribute('aria-atomic', 'true');
            profile.appendChild(status);
        }
        status.textContent = message;
    }

    function getNamedValue(profile, name, fallback) {
        var field = profile.querySelector('[name="' + name + '"]');
        return field && typeof field.value === 'string' && field.value !== '' ? field.value : (fallback || '');
    }

    function isCancellation(error) {
        return !!error && (error.name === 'NotAllowedError' || error.name === 'AbortError');
    }

    function handleEnrollment(profile, button) {
        if (profile.getAttribute('aria-busy') === 'true') {
            return;
        }
        if (!supportedOrigin || !hasCredentialApi) {
            setStatus(profile, messages.unsupported);
            return;
        }

        var passwordField = profile.querySelector('[name="current_password"]');
        var password = passwordField && typeof passwordField.value === 'string' ? passwordField.value : '';
        if (!password) {
            setStatus(profile, messages.enterPassword);
            if (passwordField) {
                passwordField.focus();
            }
            return;
        }

        var previousDisabled = button.disabled;
        profile.setAttribute('aria-busy', 'true');
        button.disabled = true;
        setStatus(profile, messages.preparing);

        var optionsRequest = new FormData();
        optionsRequest.append('action', profileConfig.optionsAction || 'zmp_passkey_registration_options');
        optionsRequest.append('_zmp_passkey_nonce', getNamedValue(profile, '_zmp_passkey_nonce', profileConfig.optionsNonce));
        optionsRequest.append('current_password', password);

        var optionsPromise = postEnrollmentFormData(optionsRequest, 'options');
        if (passwordField) {
            passwordField.value = '';
        }

        optionsPromise.then(function (responseData) {
            if (!responseData || typeof responseData.challenge_id !== 'string' || !responseData.options) {
                throw enrollmentError('options', 'invalid');
            }
            setStatus(profile, messages.continueInBrowser);
            var publicKeyOptions;
            try {
                publicKeyOptions = parseCreationOptions(responseData.options);
            } catch (error) {
                throw enrollmentError('options', 'invalid');
            }
            return navigator.credentials.create({ publicKey: publicKeyOptions })
                .then(function (credential) {
                    if (!credential) {
                        var emptyCredentialError = new Error('passkey-enrollment-diagnostic');
                        emptyCredentialError.zmpEnrollmentBrowserEmpty = true;
                        throw emptyCredentialError;
                    }
                    var serialized;
                    try {
                        serialized = JSON.stringify(credentialToJson(credential));
                    } catch (error) {
                        var serializationError = new Error('passkey-enrollment-diagnostic');
                        serializationError.zmpEnrollmentSerialization = true;
                        throw serializationError;
                    }
                    var finishRequest = new FormData();
                    finishRequest.append('action', profileConfig.finishAction || 'zmp_passkey_registration_finish');
                    finishRequest.append('_zmp_passkey_finish_nonce', getNamedValue(profile, '_zmp_passkey_finish_nonce', profileConfig.finishNonce));
                    finishRequest.append('challenge_id', responseData.challenge_id);
                    finishRequest.append('payload_json', serialized);
                    return postEnrollmentFormData(finishRequest, 'finish').then(function (finishData) {
                        if (!finishData || finishData.registered !== true) {
                            throw enrollmentError('finish', 'invalid');
                        }
                        setStatus(profile, messages.success);
                    });
                });
        }).catch(function (error) {
            var diagnosticsEnabled = profileConfig.debugDiagnostics === true
                || profileConfig.debugDiagnostics === '1';
            var diagnosticMessage = diagnosticsEnabled ? enrollmentDiagnosticMessage(error) : null;
            if (diagnosticMessage) {
                setStatus(profile, diagnosticMessage);
            } else if (diagnosticsEnabled && error && ['AbortError', 'ConstraintError', 'InvalidStateError', 'NotAllowedError',
                'NotSupportedError', 'SecurityError', 'TypeError', 'UnknownError'].indexOf(error.name) !== -1) {
                var browserError = new Error('passkey-enrollment-diagnostic');
                browserError.name = error.name;
                browserError.zmpEnrollmentBrowserError = true;
                setStatus(profile, enrollmentDiagnosticMessage(browserError));
            } else if (isCancellation(error)) {
                setStatus(profile, messages.canceled);
            } else {
                setStatus(profile, messages.failed);
            }
        }).finally(function () {
            if (passwordField) {
                passwordField.value = '';
            }
            button.disabled = previousDisabled;
            profile.setAttribute('aria-busy', 'false');
        });
    }

    function handleRemoval(profile, button) {
        if (profile.getAttribute('aria-busy') === 'true' || button.disabled) {
            return;
        }
        if (!supportedOrigin) {
            setStatus(profile, messages.insecure);
            return;
        }

        var credentialId = button.getAttribute('data-credential-id');
        var passwordField = profile.querySelector('[name="current_password"]');
        var password = passwordField && typeof passwordField.value === 'string' ? passwordField.value : '';
        if (!credentialId || !password) {
            setStatus(profile, messages.removePassword);
            if (passwordField) {
                passwordField.focus();
            }
            return;
        }

        profile.setAttribute('aria-busy', 'true');
        button.disabled = true;
        setStatus(profile, messages.removing);

        var request = new FormData();
        request.append('action', profileConfig.removeAction || 'zmp_passkey_remove');
        request.append('_zmp_passkey_remove_nonce', getNamedValue(profile, '_zmp_passkey_remove_nonce', profileConfig.removeNonce));
        request.append('credential_id', credentialId);
        request.append('current_password', password);
        var removalPromise = postFormData(request);
        if (passwordField) {
            passwordField.value = '';
        }

        removalPromise.then(function (responseData) {
            if (!responseData || responseData.removed !== true) {
                throw new Error('removal-incomplete');
            }
            var row = button.closest('.zmp-passkey-profile__credential');
            if (row && row.parentNode) {
                row.parentNode.removeChild(row);
            }
            button.removeAttribute('data-credential-id');
            setStatus(profile, messages.removed);
        }).catch(function () {
            setStatus(profile, messages.removeFailed);
        }).finally(function () {
            if (passwordField) {
                passwordField.value = '';
            }
            button.disabled = false;
            profile.setAttribute('aria-busy', 'false');
        });
    }

    function initializeProfile(profile) {
        var button = profile.querySelector('.zmp-passkey-profile__begin');
        var status = profile.querySelector('.zmp-passkey-profile__status');
        if (status) {
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');
            status.setAttribute('aria-atomic', 'true');
        }

        if (button) {
            if (!supportedOrigin || !hasCredentialApi) {
                button.disabled = true;
                setStatus(profile, messages.unsupported);
            } else {
                button.addEventListener('click', function () {
                    handleEnrollment(profile, button);
                });
            }
        }

        var removalButtons = profile.querySelectorAll('.zmp-passkey-profile__remove');
        for (var i = 0; i < removalButtons.length; i += 1) {
            removalButtons[i].addEventListener('click', function (event) {
                handleRemoval(profile, event.currentTarget);
            });
        }
    }

    function initialize() {
        var profiles = document.querySelectorAll('.zmp-passkey-profile');
        for (var i = 0; i < profiles.length; i += 1) {
            initializeProfile(profiles[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
}());
