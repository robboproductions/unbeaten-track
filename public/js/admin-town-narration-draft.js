/**
 * Town narration script: Draft Script with Claude.
 * Expects window.__UT_TOWN_NARRATION_DRAFT before this file loads.
 */
(function () {
    'use strict';

    function aiCfg() {
        var cfg = window.__UT_TOWN_NARRATION_DRAFT || {};
        return cfg.ai || {};
    }

    function csrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') || '' : '';
    }

    function xsrfToken() {
        var parts = (document.cookie || '').split(';');
        for (var i = 0; i < parts.length; i++) {
            var p = parts[i].replace(/^\s+/, '');
            if (p.indexOf('XSRF-TOKEN=') === 0) {
                try {
                    return decodeURIComponent(p.slice('XSRF-TOKEN='.length));
                } catch (e) {
                    return p.slice('XSRF-TOKEN='.length);
                }
            }
        }
        return '';
    }

    function setMessage(text, isError) {
        var el = document.getElementById('town_narration_ai_message');
        if (!el) {
            return;
        }
        if (!text) {
            el.hidden = true;
            el.textContent = '';
            el.classList.remove('town-about-ai-message--error');
            return;
        }
        el.hidden = false;
        el.textContent = text;
        el.classList.toggle('town-about-ai-message--error', !!isError);
    }

    function parseJsonResponse(res) {
        var ct = res.headers.get('content-type') || '';
        if (ct.indexOf('application/json') !== -1) {
            return res.json();
        }
        return res.text().then(function (t) {
            var err = new Error(t || res.statusText);
            err.status = res.status;
            throw err;
        });
    }

    function boot() {
        var btn = document.getElementById('town_narration_ai_btn');
        var ta = document.getElementById('town_narration_script');
        var ai = aiCfg();
        if (!btn || !ta || !ai.url) {
            return;
        }

        btn.addEventListener('click', function () {
            setMessage('Asking Claude…', false);
            btn.disabled = true;
            var headers = {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            };
            var tok = csrfToken();
            if (tok) {
                headers['X-CSRF-TOKEN'] = tok;
            }
            var xsrf = xsrfToken();
            if (xsrf) {
                headers['X-XSRF-TOKEN'] = xsrf;
            }
            fetch(ai.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: headers,
                body: '{}',
            })
                .then(function (res) {
                    return parseJsonResponse(res).then(function (data) {
                        return { ok: res.ok, status: res.status, data: data };
                    });
                })
                .then(function (out) {
                    if (out.ok && out.data && typeof out.data.script === 'string') {
                        ta.value = out.data.script;
                        setMessage('Draft inserted. Review and edit before saving.', false);
                        ta.focus();
                    } else {
                        var msg =
                            (out.data && out.data.message) ||
                            'Could not get a draft (' + out.status + ').';
                        setMessage(msg, true);
                    }
                })
                .catch(function (err) {
                    var m = err && err.message ? err.message : 'Request failed.';
                    if (m.length > 220) {
                        m = m.slice(0, 220) + '…';
                    }
                    setMessage(m || 'Network error. Try again.', true);
                })
                .finally(function () {
                    btn.disabled = false;
                });
        });
    }

    if (document.getElementById('town_narration_script')) {
        boot();
    }
})();
