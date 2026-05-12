<?php

return [
    'country_default' => 'AU',

    /** Full jurisdiction names (stored on towns.state / pois.state) */
    'states' => [
        'Australian Capital Territory',
        'New South Wales',
        'Northern Territory',
        'Queensland',
        'South Australia',
        'Tasmania',
        'Victoria',
        'Western Australia',
        'Christmas Island',
        'Cocos (Keeling) Islands',
        'Norfolk Island',
        'Jervis Bay Territory',
    ],

    /** Legacy 2–3 letter codes → full name (imports, filters, old rows) */
    'legacy_state_codes' => [
        'ACT' => 'Australian Capital Territory',
        'NSW' => 'New South Wales',
        'NT' => 'Northern Territory',
        'QLD' => 'Queensland',
        'SA' => 'South Australia',
        'TAS' => 'Tasmania',
        'VIC' => 'Victoria',
        'WA' => 'Western Australia',
    ],

    /**
     * Regions by state (tourism / administrative groupings).
     * Keys must match entries in "states" exactly.
     */
    'regions' => require __DIR__.'/data/australia_regions_by_state.php',
];
