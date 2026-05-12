@php
    /** @var list<string> $stateOptions */
    /** @var array<string, list<string>> $regionsByState */
    /** @var string $selectedState */
    /** @var string $selectedRegion */
@endphp

<div class="town-form-field-row town-form-field-row--state-region">
    <div class="town-form-field">
        <label class="town-form-label" for="town_state">State / territory</label>
        <select id="town_state" name="state" required class="town-form-control town-form-control--select">
            @foreach ($stateOptions as $st)
                <option value="{{ $st }}" @selected($selectedState === $st)>{{ $st }}</option>
            @endforeach
        </select>
        @error('state')<p class="town-form-error">{{ $message }}</p>@enderror
    </div>

    <div class="town-form-field">
        <label class="town-form-label" for="town_region">Region</label>
        <select id="town_region" name="region" class="town-form-control town-form-control--select">
            <option value="">— Select region —</option>
        </select>
        @error('region')<p class="town-form-error">{{ $message }}</p>@enderror
    </div>
</div>
<p class="town-form-hint">Region list updates when you change state.</p>

<script>
(function () {
    const BY_STATE = @json($regionsByState);
    const stateSel = document.getElementById('town_state');
    const regSel = document.getElementById('town_region');
    let preservedRegion = @json($selectedRegion);

    function fillRegions() {
        const st = stateSel.value;
        const list = BY_STATE[st] || [];
        regSel.innerHTML = '<option value="">— Select region —</option>';
        list.forEach(function (r) {
            const o = document.createElement('option');
            o.value = r;
            o.textContent = r;
            if (preservedRegion && r === preservedRegion) {
                o.selected = true;
            }
            regSel.appendChild(o);
        });
        if (preservedRegion && list.indexOf(preservedRegion) === -1) {
            const o = document.createElement('option');
            o.value = preservedRegion;
            o.textContent = preservedRegion + ' (saved)';
            o.selected = true;
            regSel.appendChild(o);
        }
    }

    stateSel.addEventListener('change', function () {
        preservedRegion = '';
        fillRegions();
    });

    fillRegions();
})();
</script>
