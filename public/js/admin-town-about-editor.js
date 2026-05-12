/**
 * TinyMCE for town "About" + optional AI draft button (admin town form).
 * Expects window.__UT_TOWN_ABOUT_EDITOR from _town_form_fields.
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

    function bootAi() {
        var btn = document.getElementById('town_about_ai_btn');
        var ai = aiCfg();
        if (!btn || !ai.url) {
            return;
        }

        btn.addEventListener('click', function () {
            setAiMessage('Drafting…', false);
            btn.disabled = true;
            fetch(ai.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: '{}',
            })
                .then(function (res) {
                    return res.json().then(function (data) {
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
                            'Could not generate a draft (' + out.status + ').';
                        setAiMessage(msg, true);
                    }
                })
                .catch(function () {
                    setAiMessage('Network error. Try again.', true);
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

        tinymce
            .init({
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
            })
            .then(function () {
                var form = ta.closest('form');
                if (form) {
                    form.addEventListener('submit', function () {
                        if (typeof tinymce !== 'undefined') {
                            tinymce.triggerSave();
                        }
                    });
                }
            });
    }

    if (document.getElementById('town_about_html')) {
        bootEditor();
        bootAi();
    }
})();
