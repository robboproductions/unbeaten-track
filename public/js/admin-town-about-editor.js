/**
 * TinyMCE for town "About" + optional Claude draft button (admin town form).
 * Expects window.__UT_TOWN_ABOUT_EDITOR to be set before this file loads.
 */
(function () {
    'use strict';

    function cfg() {
        return window.__UT_TOWN_ABOUT_EDITOR || {};
    }

    function aiCfg() {
        return cfg().ai || {};
    }

    function csrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') || '' : '';
    }

    /** Laravel also accepts the XSRF-TOKEN cookie value in X-XSRF-TOKEN. */
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

    function setAiMessage(text, isError) {
        var el = document.getElementById('town_about_ai_message');
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

    function bootAi() {
        var btn = document.getElementById('town_about_ai_btn');
        var ai = aiCfg();
        if (!btn || !ai.url) {
            return;
        }

        btn.addEventListener('click', function () {
            setAiMessage('Asking Claude…', false);
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
                    if (out.ok && out.data && typeof out.data.html === 'string') {
                        var ed = typeof tinymce !== 'undefined' ? tinymce.get('town_about_html') : null;
                        if (ed) {
                            ed.setContent(out.data.html);
                        } else {
                            var ta = document.getElementById('town_about_html');
                            if (ta) {
                                ta.value = out.data.html;
                            }
                        }
                        setAiMessage('Draft inserted — review and edit before saving.', false);
                    } else {
                        var msg =
                            (out.data && out.data.message) ||
                            'Could not get a draft (' + out.status + ').';
                        setAiMessage(msg, true);
                    }
                })
                .catch(function (err) {
                    var m = err && err.message ? err.message : 'Request failed.';
                    if (m.length > 220) {
                        m = m.slice(0, 220) + '…';
                    }
                    setAiMessage(m || 'Network error. Try again.', true);
                })
                .finally(function () {
                    btn.disabled = false;
                });
        });
    }

    function bootEditor() {
        var ta = document.getElementById('town_about_html');
        if (!ta || typeof tinymce === 'undefined') {
            return;
        }

        var initResult;
        try {
            initResult = tinymce.init({
                selector: '#town_about_html',
                license_key: 'gpl',
                promotion: false,
                branding: false,
                height: 360,
                min_height: 280,
                menubar: false,
                statusbar: true,
                plugins: 'lists link autoresize',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link removeformat',
                block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3',
                content_style:
                    'body { font-family: Inter, system-ui, -apple-system, sans-serif; font-size: 14px; line-height: 1.5; color: #1a1f1a; }',
                relative_urls: false,
                convert_urls: true,
                link_default_target: '_blank',
                link_default_protocol: 'https',
            });
        } catch (e) {
            return;
        }

        Promise.resolve(initResult)
            .then(function () {
                var form = ta.closest('form');
                if (form) {
                    form.addEventListener('submit', function () {
                        if (typeof tinymce !== 'undefined') {
                            tinymce.triggerSave();
                        }
                    });
                }
            })
            .catch(function () {});
    }

    if (document.getElementById('town_about_html')) {
        bootAi();
        bootEditor();
    }
})();
