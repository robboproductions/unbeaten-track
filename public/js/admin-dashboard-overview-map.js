/**
 * Admin dashboard — NSW & VIC overview map (towns + POIs, client-side filters).
 * Expects window.__UT_ADMIN_DASHBOARD_OVERVIEW_MAP from admin/dashboard.
 */
(function () {
    'use strict';

    function cfg() {
        return window.__UT_ADMIN_DASHBOARD_OVERVIEW_MAP || {};
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

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showLoadError(msg) {
        var el = document.getElementById('dashboard-map-load-error');
        if (!el) {
            return;
        }
        el.hidden = false;
        el.textContent = msg;
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
                showLoadError(
                    'Map library did not load. Check your network or try disabling extensions that block scripts from cdn.jsdelivr.net.'
                );
            }
        }, 30);
    }

    function boundsFromGeoJSON(geojson) {
        var feats = geojson && geojson.features ? geojson.features : [];
        if (!feats.length) {
            return null;
        }
        var minLng = Infinity;
        var minLat = Infinity;
        var maxLng = -Infinity;
        var maxLat = -Infinity;
        for (var i = 0; i < feats.length; i++) {
            var c = feats[i].geometry && feats[i].geometry.coordinates;
            if (!c || c.length < 2) {
                continue;
            }
            var lng = c[0];
            var lat = c[1];
            if (!Number.isFinite(lng) || !Number.isFinite(lat)) {
                continue;
            }
            minLng = Math.min(minLng, lng);
            maxLng = Math.max(maxLng, lng);
            minLat = Math.min(minLat, lat);
            maxLat = Math.max(maxLat, lat);
        }
        if (!Number.isFinite(minLng)) {
            return null;
        }
        if (minLng === maxLng && minLat === maxLat) {
            return { center: [minLng, minLat], zoom: 11 };
        }
        return { sw: [minLng, minLat], ne: [maxLng, maxLat] };
    }

    function selectedCategories() {
        var sel = document.getElementById('dash-categories');
        if (!sel || !sel.options) {
            return [];
        }
        var out = [];
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].selected) {
                out.push(sel.options[i].value);
            }
        }
        return out;
    }

    function readFilterState() {
        return {
            showPois: !!(document.getElementById('dash-layer-pois') && document.getElementById('dash-layer-pois').checked),
            showTowns: !!(document.getElementById('dash-layer-towns') && document.getElementById('dash-layer-towns').checked),
            state: (document.getElementById('dash-state') && document.getElementById('dash-state').value) || '',
            photos: (document.getElementById('dash-photos') && document.getElementById('dash-photos').value) || 'any',
            about: (document.getElementById('dash-about') && document.getElementById('dash-about').value) || 'any',
            newOnly: !!(document.getElementById('dash-new-only') && document.getElementById('dash-new-only').checked),
            poiPublication:
                (document.getElementById('dash-poi-pub') && document.getElementById('dash-poi-pub').value) || 'any',
            poiVerification:
                (document.getElementById('dash-poi-ver') && document.getElementById('dash-poi-ver').value) || '',
            poiNarration:
                (document.getElementById('dash-poi-narr') && document.getElementById('dash-poi-narr').value) || 'any',
            townPublication:
                (document.getElementById('dash-town-pub') && document.getElementById('dash-town-pub').value) || 'any',
            townVerification:
                (document.getElementById('dash-town-ver') && document.getElementById('dash-town-ver').value) || '',
            categories: selectedCategories(),
        };
    }

    function matchesShared(p, s) {
        if (s.state && p.state !== s.state) {
            return false;
        }
        if (s.photos === 'has' && Number(p.photosCount) <= 0) {
            return false;
        }
        if (s.photos === 'missing' && Number(p.photosCount) > 0) {
            return false;
        }
        if (s.about === 'has' && Number(p.hasAbout) !== 1) {
            return false;
        }
        if (s.about === 'missing' && Number(p.hasAbout) === 1) {
            return false;
        }
        if (s.newOnly && Number(p.isNew) !== 1) {
            return false;
        }
        return true;
    }

    function poiMatches(p, s) {
        if (!matchesShared(p, s)) {
            return false;
        }
        if (s.categories.length > 0) {
            var slugs = String(p.categorySlugs || '')
                .split(',')
                .map(function (x) {
                    return x.trim();
                })
                .filter(Boolean);
            var hit = false;
            for (var i = 0; i < s.categories.length; i++) {
                if (slugs.indexOf(s.categories[i]) !== -1) {
                    hit = true;
                    break;
                }
            }
            if (!hit) {
                return false;
            }
        }
        if (s.poiVerification && p.verificationStatus !== s.poiVerification) {
            return false;
        }
        if (s.poiPublication === 'published' && p.status !== 'published') {
            return false;
        }
        if (s.poiPublication === 'draft' && p.status !== 'draft') {
            return false;
        }
        if (s.poiPublication === 'pending' && p.status !== 'pending') {
            return false;
        }
        if (s.poiPublication === 'unpublished' && p.status === 'published') {
            return false;
        }
        if (s.poiNarration === 'has' && Number(p.hasNarration) !== 1) {
            return false;
        }
        if (s.poiNarration === 'missing' && Number(p.hasNarration) === 1) {
            return false;
        }
        return true;
    }

    function townMatches(p, s) {
        if (!matchesShared(p, s)) {
            return false;
        }
        if (s.categories.length > 0) {
            return false;
        }
        if (s.poiVerification) {
            return false;
        }
        if (s.poiNarration !== 'any') {
            return false;
        }
        if (s.poiPublication !== 'any') {
            if (s.poiPublication === 'pending') {
                return false;
            }
            if (s.poiPublication === 'published' && p.status !== 'published') {
                return false;
            }
            if (s.poiPublication === 'draft' && p.status !== 'draft') {
                return false;
            }
            if (s.poiPublication === 'unpublished' && p.status === 'published') {
                return false;
            }
        }
        if (s.townVerification && p.verificationStatus !== s.townVerification) {
            return false;
        }
        if (s.townPublication === 'published' && p.status !== 'published') {
            return false;
        }
        if (s.townPublication === 'pending' && p.status !== 'pending') {
            return false;
        }
        if (s.townPublication === 'draft' && p.status === 'published') {
            return false;
        }
        return true;
    }

    function filterCollection(fc, matcher, state) {
        var feats = fc && fc.features ? fc.features : [];
        var out = [];
        for (var i = 0; i < feats.length; i++) {
            var f = feats[i];
            var p = f.properties || {};
            if (matcher(p, state)) {
                out.push(f);
            }
        }
        return { type: 'FeatureCollection', features: out };
    }

    function updateCounts(townsFc, poisFc, visTowns, visPois) {
        var el = document.getElementById('dash-map-counts');
        if (!el) {
            return;
        }
        var tn = townsFc.features.length;
        var pn = poisFc.features.length;
        var parts = [];
        if (visPois) {
            parts.push(pn + ' POI' + (pn === 1 ? '' : 's'));
        }
        if (visTowns) {
            parts.push(tn + ' town' + (tn === 1 ? '' : 's'));
        }
        if (!visPois && !visTowns) {
            el.textContent = 'Both layers hidden.';
            return;
        }
        el.textContent = 'Showing ' + parts.join(' · ') + ' with coordinates in NSW & VIC.';
    }

    function initMap() {
        var c = cfg();
        var el = document.getElementById('dashboard-admin-overview-map');
        if (!el) {
            return;
        }
        if (!c.enabled || !c.styleUrl || !c.proxyUrl) {
            return;
        }

        var colors = c.colors || {};
        var townFill = colors.townFill || '#4a7c59';
        var poiFill = colors.poiFill || '#2e5f8a';
        var newStroke = colors.newStroke || '#c49020';

        var allTowns = c.townsGeojson && c.townsGeojson.type === 'FeatureCollection' ? c.townsGeojson : { type: 'FeatureCollection', features: [] };
        var allPois = c.poisGeojson && c.poisGeojson.type === 'FeatureCollection' ? c.poisGeojson : { type: 'FeatureCollection', features: [] };

        var centerFb = Array.isArray(c.centerFallback) && c.centerFallback.length === 2 ? c.centerFallback : [145.5, -34.2];
        var zoomFb = typeof c.zoomFallback === 'number' ? c.zoomFallback : 5.8;

        var map;
        try {
            map = new maplibregl.Map({
                container: el,
                style: c.styleUrl,
                center: centerFb,
                zoom: zoomFb,
                attributionControl: true,
                transformRequest: function (url) {
                    return proxyTransform(url);
                },
            });
        } catch (e) {
            showLoadError('Could not start map: ' + (e && e.message ? e.message : String(e)));
            return;
        }

        map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');

        map.on('error', function (e) {
            var msg = e && e.error && e.error.message ? e.error.message : 'Map error';
            showLoadError(msg);
        });

        function applyFilters() {
            if (!map || typeof map.getSource !== 'function') {
                return;
            }
            var s = readFilterState();
            var townsFc = filterCollection(allTowns, townMatches, s);
            var poisFc = filterCollection(allPois, poiMatches, s);
            if (map.getSource('dash-towns')) {
                map.getSource('dash-towns').setData(townsFc);
            }
            if (map.getSource('dash-pois')) {
                map.getSource('dash-pois').setData(poisFc);
            }
            if (map.getLayer('dash-towns-dots')) {
                map.setLayoutProperty('dash-towns-dots', 'visibility', s.showTowns ? 'visible' : 'none');
            }
            if (map.getLayer('dash-pois-dots')) {
                map.setLayoutProperty('dash-pois-dots', 'visibility', s.showPois ? 'visible' : 'none');
            }
            updateCounts(townsFc, poisFc, s.showTowns, s.showPois);
            try {
                map.resize();
            } catch (ignore) {
                /* ignore */
            }
        }

        map.on('load', function () {
            map.resize();
            var s0 = readFilterState();
            var initialTowns = filterCollection(allTowns, townMatches, s0);
            var initialPois = filterCollection(allPois, poiMatches, s0);

            try {
                map.addSource('dash-towns', { type: 'geojson', data: initialTowns });
                map.addSource('dash-pois', { type: 'geojson', data: initialPois });

                map.addLayer({
                    id: 'dash-towns-dots',
                    type: 'circle',
                    source: 'dash-towns',
                    layout: { visibility: s0.showTowns ? 'visible' : 'none' },
                    paint: {
                        'circle-radius': 7,
                        'circle-color': townFill,
                        'circle-opacity': 0.92,
                        'circle-stroke-width': ['case', ['==', ['get', 'isNew'], 1], 4, 2],
                        'circle-stroke-color': ['case', ['==', ['get', 'isNew'], 1], newStroke, '#ffffff'],
                    },
                });
                map.addLayer({
                    id: 'dash-pois-dots',
                    type: 'circle',
                    source: 'dash-pois',
                    layout: { visibility: s0.showPois ? 'visible' : 'none' },
                    paint: {
                        'circle-radius': 7,
                        'circle-color': poiFill,
                        'circle-opacity': 0.92,
                        'circle-stroke-width': ['case', ['==', ['get', 'isNew'], 1], 4, 2],
                        'circle-stroke-color': ['case', ['==', ['get', 'isNew'], 1], newStroke, '#ffffff'],
                    },
                });
            } catch (e) {
                showLoadError('Could not add map layers: ' + (e && e.message ? e.message : String(e)));
                return;
            }

            window.__UT_DASHBOARD_MAP_APPLY_FILTERS = applyFilters;

            var merged0 = { type: 'FeatureCollection', features: [] };
            if (s0.showTowns) {
                merged0.features = merged0.features.concat(initialTowns.features);
            }
            if (s0.showPois) {
                merged0.features = merged0.features.concat(initialPois.features);
            }
            var b0 = boundsFromGeoJSON(merged0);
            if (b0 && b0.sw) {
                map.fitBounds([b0.sw, b0.ne], { padding: 56, maxZoom: 11, duration: 500 });
            } else if (b0 && b0.center) {
                map.flyTo({ center: b0.center, zoom: b0.zoom, duration: 400 });
            } else {
                map.jumpTo({ center: centerFb, zoom: zoomFb });
            }

            updateCounts(initialTowns, initialPois, s0.showTowns, s0.showPois);
            map.resize();

            var popup = new maplibregl.Popup({ closeButton: true, closeOnClick: true, maxWidth: '320px' });

            function statusLabelPoi(st) {
                if (st === 'published') {
                    return 'Published';
                }
                if (st === 'pending') {
                    return 'Pending';
                }
                return 'Draft';
            }

            function statusLabelTown(st) {
                return statusLabelPoi(st);
            }

            function showPopup(props, lngLat, kind) {
                if (!props) {
                    return;
                }
                var isNew = Number(props.isNew) === 1;
                var newBadge = isNew
                    ? '<span style="display:inline-block;margin-left:6px;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;letter-spacing:0.04em;background:#f5e8c0;color:#4a3000;border:1px solid #e6c46a;">NEW</span>'
                    : '';
                var photoUrl =
                    typeof props.primaryPhotoUrl === 'string' && props.primaryPhotoUrl.trim()
                        ? props.primaryPhotoUrl.trim()
                        : '';
                var photoBlock = photoUrl
                    ? '<div style="margin-top:8px;border-radius:8px;overflow:hidden;border:1px solid var(--color-border,#dde0da);background:#f4f5f2;">' +
                      '<img src="' +
                      escapeHtml(photoUrl) +
                      '" alt="' +
                      escapeHtml(props.name || '') +
                      '" style="display:block;width:100%;max-height:168px;object-fit:cover;" loading="lazy" decoding="async" />' +
                      '</div>'
                    : '';
                var editUrl = String(props.editUrl || '#').replace(/"/g, '');
                var html;
                if (kind === 'poi') {
                    var townLine =
                        typeof props.townName === 'string' && props.townName.trim()
                            ? '<div style="font-size:11px;color:#7a8578;margin-top:4px;">' +
                              escapeHtml(props.townName.trim()) +
                              '</div>'
                            : '';
                    var cats =
                        typeof props.categoriesLabel === 'string' && props.categoriesLabel.trim()
                            ? '<div style="font-size:11px;color:#7a8578;margin-top:2px;">' +
                              escapeHtml(props.categoriesLabel.trim()) +
                              '</div>'
                            : '';
                    html =
                        '<div style="font-family:Inter,system-ui,sans-serif;padding:2px 2px 4px;">' +
                        '<div style="font-size:14px;font-weight:600;color:#2a2e28;">' +
                        '<span style="color:#2e5f8a;font-size:11px;font-weight:700;margin-right:6px;">POI</span>' +
                        escapeHtml(props.name || '') +
                        newBadge +
                        '</div>' +
                        photoBlock +
                        '<div style="font-size:12px;color:#4a5248;margin-top:4px;">' +
                        escapeHtml(props.state || '') +
                        ' · ' +
                        statusLabelPoi(props.status) +
                        '</div>' +
                        townLine +
                        cats +
                        '<div style="margin-top:10px;">' +
                        '<a href="' +
                        editUrl +
                        '" style="display:inline-block;font-size:12px;font-weight:600;color:#2d5016;text-decoration:none;">Edit POI →</a>' +
                        '</div>' +
                        '</div>';
                } else {
                    var region = props.region
                        ? '<div style="font-size:11px;color:#7a8578;margin-top:4px;">' + escapeHtml(props.region) + '</div>'
                        : '';
                    var photos = typeof props.photosCount === 'number' ? props.photosCount : 0;
                    html =
                        '<div style="font-family:Inter,system-ui,sans-serif;padding:2px 2px 4px;">' +
                        '<div style="font-size:14px;font-weight:600;color:#2a2e28;">' +
                        '<span style="color:#4a7c59;font-size:11px;font-weight:700;margin-right:6px;">TOWN</span>' +
                        escapeHtml(props.name || '') +
                        newBadge +
                        '</div>' +
                        photoBlock +
                        '<div style="font-size:12px;color:#4a5248;margin-top:4px;">' +
                        escapeHtml(props.state || '') +
                        ' · ' +
                        statusLabelTown(props.status) +
                        ' · ' +
                        photos +
                        ' photo' +
                        (photos === 1 ? '' : 's') +
                        '</div>' +
                        region +
                        '<div style="margin-top:10px;">' +
                        '<a href="' +
                        editUrl +
                        '" style="display:inline-block;font-size:12px;font-weight:600;color:#2d5016;text-decoration:none;">Edit town →</a>' +
                        '</div>' +
                        '</div>';
                }
                popup.setHTML(html);
                popup.setLngLat(lngLat).addTo(map);
            }

            map.on('click', 'dash-pois-dots', function (e) {
                var f = e.features && e.features[0];
                if (!f || !f.properties) {
                    return;
                }
                showPopup(f.properties, e.lngLat, 'poi');
            });
            map.on('click', 'dash-towns-dots', function (e) {
                var f = e.features && e.features[0];
                if (!f || !f.properties) {
                    return;
                }
                showPopup(f.properties, e.lngLat, 'town');
            });
            map.on('mouseenter', 'dash-pois-dots', function () {
                map.getCanvas().style.cursor = 'pointer';
            });
            map.on('mouseleave', 'dash-pois-dots', function () {
                map.getCanvas().style.cursor = '';
            });
            map.on('mouseenter', 'dash-towns-dots', function () {
                map.getCanvas().style.cursor = 'pointer';
            });
            map.on('mouseleave', 'dash-towns-dots', function () {
                map.getCanvas().style.cursor = '';
            });

            var root = document.getElementById('admin-dashboard-overview');
            if (root) {
                var inputs = root.querySelectorAll('select, input[type="checkbox"]');
                for (var j = 0; j < inputs.length; j++) {
                    inputs[j].addEventListener('change', function () {
                        applyFilters();
                    });
                }
            }
        });

        window.addEventListener(
            'resize',
            function () {
                if (map) {
                    map.resize();
                }
            },
            { passive: true }
        );
    }

    function boot() {
        var c = cfg();
        if (!c.enabled) {
            return;
        }
        waitForMaplibre(function () {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(initMap);
            });
        });
    }

    if (document.readyState === 'complete') {
        boot();
    } else {
        window.addEventListener('load', boot);
    }
})();
