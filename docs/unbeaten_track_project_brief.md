# Unbeaten Track — Project Brief
**Last updated:** May 2026 | **Version:** 1.0

This document is the single source of truth for the Unbeaten Track project. Upload it to the Claude Project so all chats share the same context.

---

## What we are building

**Unbeaten Track** is a road trip planning web app for Australian back roads. It helps travellers discover lesser-known towns, POIs and stories along their route — the kind of places GPS and mainstream travel apps ignore.

**Core value proposition:** "The road less travelled, planned properly."

---

## Primary persona

**"Kath"** — the passenger-seat navigator.
- 50s+ demographic (design accordingly — larger fonts, high contrast)
- Travelling with her husband Andrew (also co-founder)
- Plans the trip before departure, then uses the app during the drive
- Not particularly tech-savvy — clarity and simplicity are essential
- Caravanning or car travel, pet friendly routes important

**Andrew Robertson** — co-founder, more technical, handles admin and content entry.

---

## Tech stack

| Layer | Choice |
|-------|--------|
| Framework | Laravel 13 / PHP 8.3 |
| Build tool | Vite |
| Frontend interactivity | Alpine.js |
| Map | MapLibre GL JS |
| Database | MySQL |
| Template reference | Limitless admin template |
| Deployment | TBD |

**Primary target:** Web (laptop-first, 1280px+). Mobile = Travel Mode (separate simplified layout). Responsive strategy planned for later.

---

## Design system — LOCKED

### Font
**Inter only** — no serif, no display font.
- Loaded via Google Fonts: `https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap`
- Base size: **15px** (bumped from 13px for older demographic)
- Weights: 400 body · 500 medium · 600 semibold
- Line height: 1.6

| Use | Size | Weight |
|-----|------|--------|
| Hero heading | 30px | 600 |
| H2 section | 20px | 600 |
| H3 card | 16px | 600 |
| Body / stop detail | 15px | 400 |
| Small / meta | 13px | 400 |
| Labels / eyebrows | 11px | 600 uppercase |
| Muted / placeholders | 12px | 400 |

### Colour palette

```css
:root {
  /* Brand greens */
  --color-deep-forest:    #1e3d28;   /* Nav bar bg, dark surfaces */
  --color-bush-green:     #4a7c59;   /* Primary CTA, logo mark, accent */
  --color-mid-green:      #2d6a4f;   /* Narration border, active underlines */
  --color-green-tint:     #ddeee4;   /* Button fill (light), active pills */
  --color-green-wash:     #f2f9f5;   /* Narration bg, active card bg */
  --color-green-border:   #b8ddc8;   /* Active card borders */

  /* Neutrals */
  --color-river-stone:    #f4f5f2;   /* Page bg, nav bg */
  --color-white:          #ffffff;   /* Cards, sidebar, inputs */
  --color-border:         #dde0da;   /* Card borders, dividers */
  --color-near-black:     #2a2e28;   /* Headings, primary text */
  --color-charcoal:       #4a5248;   /* Body text, details */
  --color-mid-grey:       #7a8578;   /* Labels, placeholders, muted */

  /* POI category colours — mark / tag bg / tag text */
  --cat-deep-roots-mark:         #c4552f;
  --cat-deep-roots-bg:           #f5ddd6;
  --cat-deep-roots-text:         #5c1f0f;

  --cat-local-legends-mark:      #c49020;
  --cat-local-legends-bg:        #f5e8c0;
  --cat-local-legends-text:      #4a3000;

  --cat-wild-places-mark:        #4a7c59;
  --cat-wild-places-bg:          #ddeee4;
  --cat-wild-places-text:        #1e3d28;

  --cat-good-finds-mark:         #2e5f8a;
  --cat-good-finds-bg:           #d8e8f5;
  --cat-good-finds-text:         #1c3150;

  --cat-creative-mark:           #3a2560;
  --cat-creative-bg:             #e8e0f5;
  --cat-creative-text:           #3a2560;

  --cat-working-mark:            #2a3a28;
  --cat-working-bg:              #e8ede5;
  --cat-working-text:            #2a3a28;

  /* Journey status badges */
  --badge-in-progress-bg:        #ddeee4;
  --badge-in-progress-text:      #1e3d28;
  --badge-draft-bg:              #f5e8c0;
  --badge-draft-text:            #4a3000;
  --badge-saved-bg:              #d8e8f5;
  --badge-saved-text:            #1c3150;

  /* Border radius */
  --radius-tag:    4px;
  --radius-sm:     6px;
  --radius-md:     7px;
  --radius-lg:     8px;
  --radius-xl:     10px;

  /* Layout */
  --nav-height:           56px;
  --sidebar-width:        300px;
  --right-rail-width:     300px;
  --itinerary-width:      380px;
}
```

### UI decisions — LOCKED

| Decision | Choice |
|----------|--------|
| Nav background | River Stone `#f4f5f2` with `1px solid #dde0da` border-bottom |
| Nav active link | White bg + border |
| Nav CTA button | Bush Green `#4a7c59` solid, white text |
| Journey bar | Deep Forest `#1e3d28` background |
| Hero section | Photo background + dark gradient overlay (`rgba(0,0,0,0.78)` at bottom) |
| Hero primary button | Bush Green solid, white text |
| Hero secondary buttons | Ghost — transparent, `1.5px solid rgba(255,255,255,0.5)` |
| Cards | White bg, `1px solid #dde0da`, `border-radius: 8px`, no shadows |
| Page background | River Stone `#f4f5f2` |
| Sidebar (admin) | Deep Forest `#1e3d28` |
| Buttons | `display: inline-flex` — NOT `<button>` elements (avoids Limitless style overrides) |
| Border radius | 7–8px inputs/buttons, 8px cards, 10px popups/flyouts |

---

## POI categories

| Category | Colour name | Hex | Description |
|----------|-------------|-----|-------------|
| Deep Roots | Red Earth | `#c4552f` | Historic sites, graves, colonial history |
| Local Legends | Wattle Gold | `#c49020` | Famous people, birthplaces, local heroes |
| Wild Places | Bush Green | `#4a7c59` | National parks, swimming holes, nature |
| Good Finds | Slate Blue | `#2e5f8a` | Cafes, local producers, hidden gems |
| Creative Towns | Dusty Purple | `#3a2560` | Art, galleries, heritage-listed towns |
| Working Australia | Dark Sage | `#2a3a28` | Farms, industry, rural heritage |

---

## Logo

- Current logo: winding road/snake mark in a circle
- **Decided colour:** Bush Green `#4a7c59` mark on River Stone `#f4f5f2` (light version)
- Dark version: white mark on Deep Forest `#1e3d28` (for nav bar use)
- The original coral/orange has been retired — do not reintroduce it
- Wordmark font: matches Inter at current weight
- Files available: horizontal lockup (mark + wordmark)

---

## Photo system

**One master image per POI, stored at 3:2 ratio.** CSS handles all display crops.

| Display context | Size | Method |
|----------------|------|--------|
| Stop card thumbnail | 96px wide × full height | `background-size: cover` |
| Discover / rail card | Full width × 150–160px | `background-size: cover`, max-height clamp |
| Hover card | 300px wide × 3:2 | `padding-top: 66.67%` ratio box |
| Map popup | 240px wide × 3:2 | ratio box |
| Search result thumb | 60×40px | `background-size: cover` |
| Hero | Full bleed × 250px | `background-size: cover` + gradient overlay |

Multiple images optional only for high-profile/popular places.

---

## Screens designed so far

### Public-facing app (`unbeaten_track_mockup_v6.html` — latest)
Three screens with switchable preview nav + photo toggle:

1. **Planning home**
   - Nav with "My Journeys" dropdown (not a sidebar — saves space)
   - Full-width hero with photo overlay, stats row, narration block, stops list
   - Right rail (300px, scrollable) — Discover nearby cards with 3:2 photo top, category dot + tag, description, action buttons

2. **Route builder**
   - Journey bar (Deep Forest) with stats + Map/Table toggle
   - Left panel (380px): Itinerary tabs, day selector (pills for ≤4 days, dropdown for 5+), search box with results flyout over map, itinerary list with drag handles + up/down/remove
   - Right: Map fills everything — MapLibre GL placeholder, POI popup, legend (bottom-left), terrain toggle (bottom-right), zoom controls (top-right)

3. **Table view** (sub-page of journey)
   - Journey bar with Map/Table toggle
   - Date at **day level** (not row level) — click to edit inline
   - Full itinerary table: thumbnail, stop, type, category tag, drive time, duration, notes, actions
   - ▲ ▼ buttons to reorder rows
   - Edit single row OR edit all rows simultaneously
   - Export to Excel button
   - + Add stop to day / + Add new day

### Admin (`unbeaten_track_admin.html`)
Eight screens, Deep Forest sidebar:

1. **Dashboard** — stats, content health alerts, activity feed, category breakdown chart
2. **Towns** — table with completeness progress bars, publish status, search/filter
3. **POIs** — table with category tags, photo status, pending review workflow
4. **Edit POI** — full form: name, category, description, map pin, practicals, photo upload, narration preview, publishing stats
5. **Narration** — missing scripts alert, published scripts with duration/play
6. **Media library** — photo grid, 3:2 thumbnails, unassigned photo alerts
7. **Users** — admin table + user table, roles, suspend/view
8. **Statistics** — most popular POIs/towns, journey analytics, category popularity, user reports

### Style guide (`unbeaten_track_style_guide.html`)
Full design reference with live examples of all components.

---

## Responsive breakpoints (planned, not yet built)

| Breakpoint | Width | Behaviour |
|------------|-------|-----------|
| Desktop | 1280px+ | Full two-column layouts |
| Laptop | 1024px | Sidebar collapses |
| Tablet | 768px | Off-canvas sidebar, single column |
| Mobile | <768px | Travel Mode — separate simplified layout |

---

## Key developer notes

- **Buttons:** Use `display: inline-flex`, never `<button>` — Limitless template overrides button backgrounds
- **Day selector:** Pills for ≤4 days, `<select>` dropdown for 5+. Alpine.js: `x-show="journey.days.length <= 4"`
- **Map:** MapLibre GL JS replaces all SVG placeholders in real build
- **Narration block CSS:** `border-left: 3px solid var(--color-mid-green); border-radius: 0;` — no radius on single-sided borders
- **Photo ratio box:** `position: relative; width: 100%; padding-top: 66.67%; overflow: hidden;` with child `position: absolute; inset: 0;`

---

## Files in this project

| File | Description |
|------|-------------|
| `unbeaten_track_mockup_v6.html` | Latest public-facing mockup (3 screens) |
| `unbeaten_track_admin.html` | Admin area mockup (8 screens) |
| `unbeaten_track_style_guide.html` | Full design system reference |
| `unbeaten_track_project_brief.md` | This file |
| `Green_Transparent_Logo_Horizontal.png` | Current logo file |
| `Original_Logo.png` | Original logo (coral/navy — retired) |

---

*This brief should be kept up to date as decisions are made. When a major decision is locked, update this document and re-upload to the Project.*
