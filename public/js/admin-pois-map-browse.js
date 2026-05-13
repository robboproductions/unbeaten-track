/**
 * Admin POIs map browse (MapLibre + MapTiler via Laravel proxy).
 * Expects window.__UT_ADMIN_POIS_MAP_BROWSE from admin/pois/map.
 */
(function () {
    'use strict';

    function cfg() {
        return window.__UT_ADMIN_POIS_MAP_BROWSE || {};
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
        var el = document.getElementById('pois-map-load-error');
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
                showLoadError('Map library did not load. Check your network or try disabling extensions that block scripts from cdn.jsdelivr.net.');
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
            return {
                center: [minLng, minLat],
                zoom: 11,
            };
        }
        return {
            sw: [minLng, minLat],
            ne: [maxLng, maxLat],
        };
    }

    function statusLabel(st) {
        if (st === 'published') {
            return 'Published';
        }
        if (st === 'pending') {
            return 'Pending';
        }
        return 'Draft';
    }

    function initMap() {
        var c = cfg();
        var el = document.getElementById('pois-admin-map-browse');
        if (!el) {
            return;
        }

        if (!c.enabled || !c.styleUrl || !c.proxyUrl) {
            return;
        }

        var nswCenter = Array.isArray(c.nswCenter) && c.nswCenter.length === 2 ? c.nswCenter : [150.75, -32.15];
        var nswZoom = typeof c.nswZoom === 'number' ? c.nswZoom : 6;
        var geojson = c.geojson && c.geojson.type === 'FeatureCollection' ? c.geojson : { type: 'FeatureCollection', features: [] };

        var map;
        try {
            map = new maplibregl.Map({
                container: el,
                style: c.styleUrl,
                center: nswCenter,
                zoom: nswZoom,
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

        map.on('load', function () {
            map.resize();

            try {
                map.addSource('pois-browse', {
                    type: 'geojson',
                    data: geojson,
                });

                map.addLayer({
                    id: 'pois-browse-dots',
                    type: 'circle',
                    source: 'pois-browse',
                    paint: {
                        'circle-radius': 7,
                        'circle-color': [
                            'case',
                            ['==', ['get', 'status'], 'published'],
                            '#2563eb',
                            ['==', ['get', 'status'], 'pending'],
                            '#ca8a04',
                            '#6b7280',
                        ],
                        'circle-opacity': 0.92,
                        'circle-stroke-width': 2,
                        'circle-stroke-color': '#ffffff',
                    },
                });
            } catch (e) {
                showLoadError('Could not add POI layer: ' + (e && e.message ? e.message : String(e)));
                return;
            }

            var bounds = boundsFromGeoJSON(geojson);
            if (bounds && bounds.sw) {
                map.fitBounds([bounds.sw, bounds.ne], { padding: 56, maxZoom: 12, duration: 500 });
            } else if (bounds && bounds.center) {
                map.flyTo({ center: bounds.center, zoom: bounds.zoom, duration: 400 });
            }

            map.resize();

            var popup = new maplibregl.Popup({ closeButton: true, closeOnClick: true, maxWidth: '300px' });

            function showPoiPopup(props, lngLat) {
                if (!props) {
                    return;
                }
                var photoUrl =
                    typeof props.primaryPhotoUrl === 'string' && props.primaryPhotoUrl.trim()
                        ? props.primaryPhotoUrl.trim()
                        : '';
                var photoBlock = photoUrl
                    ? '<div style="margin-top:8px;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb;background:#f3f4f6;">' +
                      '<img src="' +
                      escapeHtml(photoUrl) +
                      '" alt="' +
                      escapeHtml(props.name || 'POI') +
                      '" style="display:block;width:100%;max-height:168px;object-fit:cover;" loading="lazy" decoding="async" />' +
                      '</div>'
                    : '';
                var townLine =
                    typeof props.townName === 'string' && props.townName.trim()
                        ? '<div style="font-size:11px;color:#6b7280;margin-top:4px;">' + escapeHtml(props.townName.trim()) + '</div>'
                        : '';
                var cats =
                    typeof props.categories === 'string' && props.categories.trim()
                        ? '<div style="font-size:11px;color:#6b7280;margin-top:2px;">' + escapeHtml(props.categories.trim()) + '</div>'
                        : '';
                var editUrl = String(props.editUrl || '#').replace(/"/g, '');
                var html =
                    '<div style="font-family:Inter,system-ui,sans-serif;padding:2px 2px 4px;">' +
                    '<div style="font-size:14px;font-weight:600;color:#1a1f1a;">' +
                    escapeHtml(props.name || '') +
                    '</div>' +
                    photoBlock +
                    '<div style="font-size:12px;color:#4b5563;margin-top:4px;">' +
                    escapeHtml(props.state || '') +
                    ' · ' +
                    statusLabel(props.status) +
                    '</div>' +
                    townLine +
                    cats +
                    '<div style="margin-top:10px;">' +
                    '<a href="' +
                    editUrl +
                    '" style="display:inline-block;font-size:12px;font-weight:600;color:#2d5016;text-decoration:none;">Edit POI →</a>' +
                    '</div>' +
                    '</div>';
                popup.setHTML(html);
                popup.setLngLat(lngLat).addTo(map);
            }

            map.on('click', 'pois-browse-dots', function (e) {
                var f = e.features && e.features[0];
                if (!f || !f.properties) {
                    return;
                }
                showPoiPopup(f.properties, e.lngLat);
            });
            map.on('mouseenter', 'pois-browse-dots', function () {
                map.getCanvas().style.cursor = 'pointer';
            });
            map.on('mouseleave', 'pois-browse-dots', function () {
                map.getCanvas().style.cursor = '';
            });
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
