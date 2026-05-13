/**
 * Admin POI create/edit — MapLibre preview, draggable pin, geocode (MapTiler via Laravel).
 * Expects window.__UT_ADMIN_POI_MAP from the POI form sidebar.
 */
(function () {
    'use strict';

    function cfg() {
        return window.__UT_ADMIN_POI_MAP || {};
    }

    function csrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') || '' : '';
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

    function formatCoord(n) {
        return Number(n).toFixed(7);
    }

    function writePoiInputsFromLngLat(lng, lat) {
        var latEl = document.getElementById('poi_latitude');
        var lngEl = document.getElementById('poi_longitude');
        if (latEl) {
            latEl.value = formatCoord(lat);
        }
        if (lngEl) {
            lngEl.value = formatCoord(lng);
        }
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

    function applyRevertFieldValues() {
        var r = cfg().revert || {};
        var latEl = document.getElementById('poi_latitude');
        var lngEl = document.getElementById('poi_longitude');
        if (latEl) {
            latEl.value = revertValueToString(r.latitude);
        }
        if (lngEl) {
            lngEl.value = revertValueToString(r.longitude);
        }
        var statusEl = document.getElementById('poi_publication_status');
        if (statusEl && Object.prototype.hasOwnProperty.call(r, 'status')) {
            statusEl.value = r.status || 'draft';
        }
    }

    function bindRevert() {
        var rev = document.getElementById('poi_map_revert_btn');
        if (!rev) {
            return;
        }
        rev.addEventListener('click', function (e) {
            e.preventDefault();
            applyRevertFieldValues();

            var inst = window.__UT_ADMIN_POI_MAP_INSTANCE;
            if (inst && typeof inst.revertPreview === 'function') {
                inst.revertPreview();
            }
        });
    }

    function buildPoiGeocodeQuery() {
        var nameEl = document.getElementById('poi_name');
        var townSel = document.getElementById('poi_town_id');
        var stateSel = document.getElementById('poi_state');
        var name = nameEl ? String(nameEl.value).trim() : '';
        if (!name) {
            return '';
        }
        var parts = [name];
        if (townSel && townSel.selectedIndex >= 0) {
            var townText = String(townSel.options[townSel.selectedIndex].text || '').trim();
            if (townText) {
                parts.push(townText);
            }
        }
        var st = stateSel ? String(stateSel.value || '').trim() : '';
        if (st) {
            parts.push(st);
        }
        parts.push('Australia');
        return parts.join(', ');
    }

    function geocodeErrorMessage(data, status) {
        if (data && typeof data.message === 'string' && data.message) {
            return data.message;
        }
        if (data && data.errors && data.errors.q && data.errors.q[0]) {
            return String(data.errors.q[0]);
        }
        if (status === 404) {
            return 'No matching places found.';
        }
        return 'Lookup failed.';
    }

    function bindGeocode() {
        var btn = document.getElementById('poi_map_geocode_btn');
        if (!btn) {
            return;
        }
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var c = cfg();
            if (!c.geocodeUrl) {
                window.alert('Geocoding is not configured.');
                return;
            }
            var q = buildPoiGeocodeQuery();
            if (!q) {
                window.alert('Enter a POI name first.');
                return;
            }
            var prevDisabled = btn.disabled;
            btn.disabled = true;
            fetch(c.geocodeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ q: q }),
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { res: res, data: data };
                    });
                })
                .then(function (o) {
                    if (o.res.ok && o.data && o.data.ok === true) {
                        writePoiInputsFromLngLat(o.data.longitude, o.data.latitude);
                        var inst = window.__UT_ADMIN_POI_MAP_INSTANCE;
                        if (inst && typeof inst.showFromInputs === 'function') {
                            inst.showFromInputs();
                        }
                    } else {
                        window.alert(geocodeErrorMessage(o.data, o.res.status));
                    }
                })
                .catch(function () {
                    window.alert('Lookup failed (network error).');
                })
                .finally(function () {
                    btn.disabled = prevDisabled;
                });
        });
    }

    function initMap() {
        var c = cfg();
        if (!c.enabled || !c.styleUrl || !c.proxyUrl) {
            return;
        }

        var el = document.getElementById('poi-admin-map');
        if (!el) {
            return;
        }

        var lat0 = parseCoord(c.initialLat);
        var lng0 = parseCoord(c.initialLng);
        var zoom = typeof c.defaultZoom === 'number' ? c.defaultZoom : 5;
        var center = lat0 !== null && lng0 !== null ? [lng0, lat0] : [133.7751, -25.2744];

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

        var marker = new maplibregl.Marker({ color: '#2d5016', draggable: true });
        if (lat0 !== null && lng0 !== null) {
            marker.setLngLat([lng0, lat0]).addTo(map);
        }

        marker.on('dragend', function () {
            var ll = marker.getLngLat();
            writePoiInputsFromLngLat(ll.lng, ll.lat);
        });

        map.on('click', function (ev) {
            marker.setLngLat(ev.lngLat).addTo(map);
            writePoiInputsFromLngLat(ev.lngLat.lng, ev.lngLat.lat);
        });

        function showFromInputs() {
            var latEl = document.getElementById('poi_latitude');
            var lngEl = document.getElementById('poi_longitude');
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
                zoom: Math.max(map.getZoom(), 13),
                essential: true,
            });
        }

        function revertPreview() {
            var latEl = document.getElementById('poi_latitude');
            var lngEl = document.getElementById('poi_longitude');
            var lat = latEl ? parseCoord(latEl.value) : null;
            var lng = lngEl ? parseCoord(lngEl.value) : null;

            if (lat !== null && lng !== null) {
                marker.setLngLat([lng, lat]).addTo(map);
                map.flyTo({
                    center: [lng, lat],
                    zoom: Math.max(map.getZoom(), 12),
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

        var btn = document.getElementById('poi_map_show_btn');
        if (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                showFromInputs();
            });
        }

        window.__UT_ADMIN_POI_MAP_INSTANCE = {
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
        bindGeocode();
        bootMap();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
