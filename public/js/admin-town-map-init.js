/**
 * Admin town create/edit — MapLibre preview (optional) + coordinate revert.
 * Expects window.__UT_ADMIN_TOWN_MAP from the town form sidebar.
 */
(function () {
    'use strict';

    function cfg() {
        return window.__UT_ADMIN_TOWN_MAP || {};
    }

    function proxyTransform(url) {
        var c = cfg();
        if (!url || !c.proxyUrl) {
            return { url: url };
        }
        if (url.indexOf('api.maptiler.com') === -1 && url.indexOf('tiles.maptiler.com') === -1) {
            return { url: url };
        }
        var clean = String(url).replace(/([&?])key=[^&#]*/g, '');
        clean = clean.replace(/[?&]$/, '');
        try {
            var b64 = btoa(unescape(encodeURIComponent(clean)));
            return { url: c.proxyUrl + '?target=' + encodeURIComponent(b64) };
        } catch (e) {
            return { url: url };
        }
    }

    function parseCoord(raw) {
        if (raw === undefined || raw === null || raw === '') {
            return null;
        }
        var n = parseFloat(String(raw).trim().replace(',', '.'));
        return Number.isFinite(n) ? n : null;
    }

    function waitForMaplibre(cb) {
        if (typeof maplibregl !== 'undefined') {
            cb();
            return;
        }
        var n = 0;
        var id = window.setInterval(function () {
            n += 1;
            if (typeof maplibregl !== 'undefined') {
                window.clearInterval(id);
                cb();
            } else if (n > 200) {
                window.clearInterval(id);
            }
        }, 30);
    }

    function revertValueToString(v) {
        if (v === null || v === undefined) {
            return '';
        }
        return String(v);
    }

    /**
     * Reset lat/lng/population inputs to the values from when this page was built (same source as the Blade `value=` attributes).
     */
    function applyRevertFieldValues() {
        var r = cfg().revert || {};
        var latEl = document.getElementById('town_latitude');
        var lngEl = document.getElementById('town_longitude');
        var popEl = document.getElementById('town_population');
        if (latEl) {
            latEl.value = revertValueToString(r.latitude);
        }
        if (lngEl) {
            lngEl.value = revertValueToString(r.longitude);
        }
        if (popEl) {
            popEl.value = revertValueToString(r.population_approx);
        }
        var statusEl = document.getElementById('town_publication_status');
        if (statusEl && Object.prototype.hasOwnProperty.call(r, 'status')) {
            statusEl.value = r.status === 'published' ? 'published' : 'draft';
        }
        var verEl = document.getElementById('town_verification_status');
        if (verEl && Object.prototype.hasOwnProperty.call(r, 'verification_status')) {
            verEl.value = r.verification_status || 'unverified';
        }
    }

    function bindRevert() {
        var rev = document.getElementById('town_map_revert_btn');
        if (!rev) {
            return;
        }
        rev.addEventListener('click', function (e) {
            e.preventDefault();
            applyRevertFieldValues();

            var inst = window.__UT_ADMIN_TOWN_MAP_INSTANCE;
            if (inst && typeof inst.revertPreview === 'function') {
                inst.revertPreview();
            }
        });
    }

    function initMap() {
        var c = cfg();
        if (!c.enabled || !c.styleUrl || !c.proxyUrl) {
            return;
        }

        var el = document.getElementById('town-admin-map');
        if (!el) {
            return;
        }

        var lat0 = parseCoord(c.initialLat);
        var lng0 = parseCoord(c.initialLng);
        var zoom = typeof c.defaultZoom === 'number' ? c.defaultZoom : 4;
        var center =
            lat0 !== null && lng0 !== null ? [lng0, lat0] : [133.7751, -25.2744];

        var map = new maplibregl.Map({
            container: el,
            style: c.styleUrl,
            center: center,
            zoom: zoom,
            attributionControl: true,
            transformRequest: function (url) {
                return proxyTransform(url);
            },
        });

        map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

        var marker = new maplibregl.Marker({ color: '#2d5016' });
        if (lat0 !== null && lng0 !== null) {
            marker.setLngLat([lng0, lat0]).addTo(map);
        }

        function showFromInputs() {
            var latEl = document.getElementById('town_latitude');
            var lngEl = document.getElementById('town_longitude');
            var lat = latEl ? parseCoord(latEl.value) : null;
            var lng = lngEl ? parseCoord(lngEl.value) : null;
            if (lat === null || lng === null) {
                window.alert('Enter a valid latitude and longitude first.');
                return;
            }
            if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                window.alert('Coordinates are out of range.');
                return;
            }
            marker.setLngLat([lng, lat]).addTo(map);
            map.flyTo({
                center: [lng, lat],
                zoom: Math.max(map.getZoom(), 12),
                essential: true,
            });
        }

        function revertPreview() {
            var latEl = document.getElementById('town_latitude');
            var lngEl = document.getElementById('town_longitude');
            var lat = latEl ? parseCoord(latEl.value) : null;
            var lng = lngEl ? parseCoord(lngEl.value) : null;

            if (lat !== null && lng !== null) {
                marker.setLngLat([lng, lat]).addTo(map);
                map.flyTo({
                    center: [lng, lat],
                    zoom: Math.max(map.getZoom(), 11),
                    essential: true,
                });
            } else {
                marker.remove();
                map.flyTo({
                    center: [133.7751, -25.2744],
                    zoom: 4,
                    essential: true,
                });
            }
        }

        var btn = document.getElementById('town_map_show_btn');
        if (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                showFromInputs();
            });
        }

        window.__UT_ADMIN_TOWN_MAP_INSTANCE = {
            map: map,
            marker: marker,
            showFromInputs: showFromInputs,
            revertPreview: revertPreview,
        };
    }

    function bootMap() {
        var c = cfg();
        if (!c.enabled) {
            return;
        }
        waitForMaplibre(initMap);
    }

    function boot() {
        bindRevert();
        bootMap();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
