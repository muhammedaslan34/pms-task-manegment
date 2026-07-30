# Soft Blue Studio — Visual Design Spec

**Date:** 2026-07-30  
**Product:** TaskFlow (Laravel + Livewire)  
**Scope:** Visual polish of the public submit form and admin dashboard (including shared layout, login, and task detail for consistency)

## Goal

Make TaskFlow feel like a clean, professional SaaS support tool without changing layouts, features, or workflows. Replace the generic indigo/slate look with a cohesive deep-blue visual system.

## Decisions

| Topic | Choice |
|-------|--------|
| Personality | Clean & professional |
| Accent | Deep blue |
| Redesign depth | Visual polish only (same layouts) |
| Approach | Soft Blue Studio |

## Out of scope

- New features or Livewire behavior changes
- Layout restructure (keep current grid/table/modal structure)
- Dark mode
- Backend, routes, or validation changes

## Visual system

### Color

| Token | Value | Use |
|-------|-------|-----|
| Primary | `#1e40af` (blue-800) | Buttons, links, focus rings, selected states |
| Brand / strong | `#1e3a5f` | Logo mark, primary heading emphasis |
| Primary soft | `#eff6ff` / `#dbeafe` | Selected tiles, priority chips, light fills |
| Page background | Soft blue-gray wash (`#f0f4f8` → `#e8eef5`) | Body background (subtle gradient or solid tint) |
| Surface | `#ffffff` | Cards, form panels, table, modals |
| Border | `slate-200` at ~80% opacity feel | Card and input borders |
| Text | slate-900 / slate-700 / slate-500 | Titles, body, muted |
| Success | Soft emerald (keep semantic) | Success toast, resolution blocks |
| Danger | Soft red (keep semantic) | Errors, remove actions |
| Status badges | Existing semantic colors, cooler-toned if needed | High/Med/Low and task statuses |

Replace all `indigo-*` utility classes in scoped views with the deep blue equivalents (`blue-800` / `blue-700` / `blue-50` / `blue-100` as appropriate).

### Typography

- **Headings:** Plus Jakarta Sans (weights 600–700)
- **Body / UI:** Instrument Sans (existing)
- Load Plus Jakarta Sans via Google Fonts alongside Instrument Sans
- Page titles: clearer weight hierarchy; slightly tighter letter-spacing on headings
- Labels stay medium weight, muted helper text stays slate-500

### Surfaces & elevation

- Cards: white, rounded-2xl (keep), subtle border, soft shadow (`shadow-sm`)
- Header: white/translucent with thin blue bottom accent or soft shadow
- Inputs: rounded-lg, slate borders, deep-blue focus ring
- No new card patterns; polish existing containers only

### Motion

- Keep existing Alpine success toast transition
- Subtle hover transitions on buttons, nav, table rows, and priority chips
- No decorative animation systems

## Page-level polish

### Shared layout (`resources/views/components/layouts/app.blade.php`)

- Apply page background wash and font stack
- TaskFlow mark and wordmark use deep blue
- Nav: same links; clearer hover/active (blue-tinted hover, not indigo)
- Session flash banner: keep emerald success styling, align border radius with cards

### Submit form (`resources/views/livewire/tasks/create.blade.php`)

- Keep 2-column layout (form + tips aside)
- Refine field spacing and label clarity
- Priority radio chips: selected state uses deep blue ring/fill
- Submit CTA: deep blue primary button
- Screenshot dropzone: calmer dashed border; blue hover/drag affordance
- Tips and priority guide cards: match shared card style
- Success toast: soft emerald (unchanged semantics)

### Admin task list (`resources/views/livewire/admin/task-list.blade.php`)

- Keep status count tiles, search/filters, table, pagination, manage modal
- Selected status tile: deep blue ring + light blue fill
- Table header: slightly stronger contrast
- Row hover: soft slate/blue-gray
- Manage button and modal primary actions: deep blue
- Modal structure and fields unchanged

### Task detail (`resources/views/livewire/admin/task-show.blade.php`)

- Same layout; swap indigo accents for deep blue
- Action buttons keep semantic colors (blue start / emerald complete)
- Timeline and manage aside match shared card style

### Login (`resources/views/livewire/auth/login.blade.php`)

- Same centered form layout
- Primary button and focus rings use deep blue
- Demo credentials note stays muted

## Implementation notes

- Prefer Tailwind utilities in Blade; add CSS variables in `resources/css/app.css` `@theme` only if helpful for fonts/colors
- Files to touch: layout, create, task-list, task-show, login, `app.css`
- Do not change Livewire PHP components unless a class rename forces a trivial view-only fix
- Verify both desktop and mobile widths for form and dashboard

## Success criteria

1. Public form and admin pages no longer use indigo as the primary accent
2. Layouts and features behave identically to today
3. Header, form, dashboard, detail, and login feel like one product
4. Looks professional and calm on light backgrounds; readable status badges preserved
