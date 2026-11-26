(function () {
    'use strict';

    var presidentConfig = window.zmpPresidentSubjectStepUp || {};
    var juryConfig = window.zmpJuryResultStepUp || {};
    var forms = document.querySelectorAll('.zmp-president-stepup-form, .zmp-jury-stepup-form');
    var supported = window.isSecureContext === true && !!(window.PublicKeyCredential
        && window.navigator && window.navigator.credentials
        && typeof window.navigator.credentials.get === 'function'
        && typeof window.fetch === 'function' && typeof window.FormData === 'function');

    function message(key, form) {
        var config = form && form.classList.contains('zmp-president-stepup-form') ? presidentConfig : juryConfig;
        return config.messages && typeof config.messages[key] === 'string'
            ? config.messages[key]
            : 'Passkey verification could not be completed. The decision was not submitted.';
    }

    function setStatus(form, key) {
        var status = form.querySelector('.zmp-president-stepup-status');
        if (status) {
            status.textContent = message(key, form);
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

    function requestOptions(options) {
        if (!options || typeof options !== 'object' || typeof options.challenge !== 'string'
            || typeof options.rpId !== 'string' || options.userVerification !== 'required'
            || options.rpId.toLowerCase() !== window.location.hostname.toLowerCase()
            || (options.allowCredentials && !Array.isArray(options.allowCredentials))) {
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
        if (Array.isArray(options.allowCredentials) && options.allowCredentials.length) {
            publicKey.allowCredentials = options.allowCredentials.map(function (descriptor) {
                if (!descriptor || descriptor.type !== 'public-key' || typeof descriptor.id !== 'string') {
                    throw new Error('invalid-credentials');
                }
                return { type: 'public-key', id: decodeBase64Url(descriptor.id) };
            });
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

    function postOptions(values, form) {
        var config = form && form.classList.contains('zmp-president-stepup-form') ? presidentConfig : juryConfig;
        if (typeof config.endpoint !== 'string' || typeof config.optionsAction !== 'string') {
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
        data.append('action', config.optionsAction);
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

    function controls(form) {
        return Array.prototype.slice.call(form.querySelectorAll('button[type="submit"], button:not([type])'));
    }

    function unlock(form) {
        form.dataset.stepUpBusy = 'false';
        form.setAttribute('aria-busy', 'false');
        controls(form).forEach(function (button) {
            button.disabled = false;
        });
    }

    function submitWithProof(form, submitter) {
        var decision = submitter && submitter.value;
        var proposal = form.querySelector('input[name="proposal_id"]');
        var nonce = form.querySelector('input[name="_wpnonce"]');
        var select = form.querySelector('select[name="canonical_subject_id"]');
        var challengeField = form.querySelector('input[name="step_up_challenge_id"]');
        var assertionField = form.querySelector('input[name="step_up_credential"]');
        if (!proposal || !nonce || !select || !challengeField || !assertionField
            || ['approve_new', 'approve_alias', 'reject'].indexOf(decision) === -1) {
            setStatus(form, 'failed');
            return;
        }
        if (decision === 'approve_alias' && (!/^[1-9][0-9]*$/.test(select.value))) {
            setStatus(form, 'failed');
            return;
        }
        var canonicalId = decision === 'approve_alias' ? select.value : '0';

        form.dataset.stepUpBusy = 'true';
        form.dataset.stepUpBypassOnce = 'false';
        form.setAttribute('aria-busy', 'true');
        controls(form).forEach(function (button) {
            if (button !== submitter) {
                button.disabled = true;
            }
        });
        setStatus(form, 'working');

        postOptions({
            decision_action: 'president_subject_decision',
            proposal_id: proposal.value,
            decision: decision,
            canonical_subject_id: canonicalId,
            route_nonce: nonce.value
        }, form).then(function (result) {
            if (!result || typeof result.challenge_id !== 'string' || !result.options) {
                throw new Error('invalid-options');
            }
            setStatus(form, 'continue');
            return window.navigator.credentials.get({ publicKey: requestOptions(result.options) })
                .then(function (credential) {
                    return { challengeId: result.challenge_id, assertion: serializeAssertion(credential) };
                });
        }).then(function (proof) {
            challengeField.value = proof.challengeId;
            assertionField.value = JSON.stringify(proof.assertion);
            select.value = canonicalId;
            form.dataset.stepUpBypassOnce = 'true';
            if (typeof form.requestSubmit !== 'function') {
                throw new Error('request-submit-unavailable');
            }
            form.requestSubmit(submitter);
        }).catch(function (error) {
            challengeField.value = '';
            assertionField.value = '';
            setStatus(form, error && error.name === 'NotAllowedError' ? 'canceled' : 'failed');
            unlock(form);
        });
    }

    function submitJuryResultWithProof(form, submitter) {
        var nonce = form.querySelector('input[name="_wpnonce"]');
        var challengeField = form.querySelector('input[name="step_up_challenge_id"]');
        var assertionField = form.querySelector('input[name="step_up_credential"]');
        var names = ['category_id', 'entry_id', 'phase', 'final_points', 'qualified_next_stage', 'decision_basis', 'decision_note'];
        if (!nonce || !challengeField || !assertionField || names.filter(function (name) { return name !== 'qualified_next_stage'; }).some(function (name) { return !form.querySelector('[name="' + name + '"]'); })) {
            setStatus(form, 'failed'); return;
        }
        var values = { _wpnonce: nonce.value };
        names.forEach(function (name) {
            var field = form.querySelector('[name="' + name + '"]');
            if (!field && name === 'qualified_next_stage') { values[name] = '0'; return; }
            values[name] = field.type === 'checkbox' ? (field.checked ? '1' : '0') : field.value;
        });
        form.dataset.stepUpBusy = 'true'; form.dataset.stepUpBypassOnce = 'false';
        form.setAttribute('aria-busy', 'true'); controls(form).forEach(function (button) { if (button !== submitter) button.disabled = true; });
        setStatus(form, 'working');
        postOptions(values, form).then(function (result) {
            if (!result || typeof result.challenge_id !== 'string' || !result.options) throw new Error('invalid-options');
            setStatus(form, 'continue');
            return window.navigator.credentials.get({ publicKey: requestOptions(result.options) }).then(function (credential) {
                return { challengeId: result.challenge_id, assertion: serializeAssertion(credential) };
            });
        }).then(function (proof) {
            challengeField.value = proof.challengeId; assertionField.value = JSON.stringify(proof.assertion);
            form.dataset.stepUpBypassOnce = 'true';
            if (typeof form.requestSubmit !== 'function') throw new Error('request-submit-unavailable');
            form.requestSubmit(submitter);
        }).catch(function (error) {
            challengeField.value = ''; assertionField.value = '';
            setStatus(form, error && error.name === 'NotAllowedError' ? 'canceled' : 'failed'); unlock(form);
        });
    }

    forms.forEach(function (form) {
        form.dataset.stepUpBusy = 'false';
        form.dataset.stepUpBypassOnce = 'false';
        form.addEventListener('submit', function (event) {
            if (form.dataset.stepUpBypassOnce === 'true') {
                form.dataset.stepUpBypassOnce = 'false';
                return;
            }
            event.preventDefault();
            if (form.dataset.stepUpBusy === 'true') {
                return;
            }
            if (!window.isSecureContext || !supported) {
                setStatus(form, 'unsupported');
                return;
            }
            if (form.classList.contains('zmp-jury-stepup-form')) {
                submitJuryResultWithProof(form, event.submitter || null);
            } else {
                submitWithProof(form, event.submitter || null);
            }
        });
    });
}());
