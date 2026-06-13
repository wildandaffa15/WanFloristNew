# DESIGN.md — WanFlorist

## Project Overview
WanFlorist is a web-based order and service information system for a small florist UMKM in Banyuwangi, Indonesia. The system serves two audiences: public buyers (no login required) and admin users (owner and staff). The visual style is elegant, warm, and approachable — reflecting a handcrafted flower boutique, not a cold enterprise tool.

---

## Color Palette

### Primary Colors
| Token | Hex | Usage |
|---|---|---|
| `primary` | `#6B21A8` | Main brand color, headings, active nav underline |
| `primary-hover` | `#5B1A90` | Button hover, link hover |
| `primary-light` | `#7C3AED` | Accents, icons, price text |
| `primary-subtle` | `#F5F0FF` | Card backgrounds, section backgrounds, badge fills |
| `primary-border` | `#E9D5FF` | Card borders, dividers |

### Neutral Colors
| Token | Hex | Usage |
|---|---|---|
| `surface` | `#FFFFFF` | Page background, card surface |
| `surface-alt` | `#FAFAFA` | Alternate section backgrounds |
| `text-primary` | `#1A1A2E` | Main body text, headings |
| `text-secondary` | `#6B7280` | Subtitles, meta text, placeholders |
| `text-muted` | `#9CA3AF` | Timestamps, disabled states |

### Semantic Colors
| Token | Hex | Usage |
|---|---|---|
| `success` | `#16A34A` | Selesai status, lunas badge |
| `warning` | `#D97706` | Diproses status, pending badge |
| `danger` | `#DC2626` | Dibatalkan status, error states |
| `info` | `#2563EB` | Menunggu konfirmasi status |
| `star` | `#F59E0B` | Star rating icons |

### Footer / Dark Section
| Token | Hex | Usage |
|---|---|---|
| `dark-bg` | `#1E1040` | Footer background, dark CTA section |
| `dark-text` | `#E9D5FF` | Text on dark background |
| `dark-muted` | `#A78BFA` | Secondary text on dark background |

---

## Typography

### Font Families
- **Heading font**: `Playfair Display` — used for hero titles, section headers, page titles (elegant serif, matches florist brand feel)
- **Body font**: `Inter` — used for all body text, labels, buttons, form fields (clean and readable sans-serif)
- **Fallback**: `Georgia, serif` for heading; `system-ui, sans-serif` for body

### Type Scale
| Role | Font | Size | Weight | Line Height |
|---|---|---|---|---|
| Hero Title | Playfair Display | 48px | 700 | 1.15 |
| Page Title | Playfair Display | 36px | 700 | 1.2 |
| Section Heading | Playfair Display | 28px | 600 | 1.25 |
| Card Title | Inter | 18px | 600 | 1.4 |
| Body | Inter | 16px | 400 | 1.6 |
| Label / Caption | Inter | 14px | 500 | 1.4 |
| Small / Meta | Inter | 12px | 400 | 1.4 |

---

## Spacing Scale
Base unit: 4px

| Token | Value | Usage |
|---|---|---|
| `space-1` | 4px | Tight gaps between inline elements |
| `space-2` | 8px | Icon-to-text gap, badge padding |
| `space-3` | 12px | Small padding inside compact components |
| `space-4` | 16px | Default inner padding for cards, inputs |
| `space-5` | 20px | Gap between form fields |
| `space-6` | 24px | Section inner padding |
| `space-8` | 32px | Gap between cards in a grid |
| `space-10` | 40px | Section top/bottom padding (mobile) |
| `space-16` | 64px | Section top/bottom padding (desktop) |
| `space-24` | 96px | Hero section padding |

---

## Border Radius
| Token | Value | Usage |
|---|---|---|
| `radius-sm` | 8px | Input fields, small badges |
| `radius-md` | 12px | Cards, dropdowns |
| `radius-lg` | 16px | Large cards, modals |
| `radius-xl` | 24px | Hero image containers, featured cards |
| `radius-full` | 9999px | Buttons (pill shape), category tags, avatar |

---

## Shadows
| Token | Value | Usage |
|---|---|---|
| `shadow-sm` | `0 1px 3px rgba(0,0,0,0.08)` | Subtle card lift |
| `shadow-md` | `0 4px 12px rgba(107,33,168,0.10)` | Cards on hover, modals |
| `shadow-lg` | `0 8px 24px rgba(107,33,168,0.15)` | Dropdown menus, floating elements |

---

## Component Patterns

### Buttons
- **Primary Button**: background `primary`, text white, `radius-full`, padding `12px 28px`, font Inter 15px weight 600
- **Outline Button**: border 2px `primary`, text `primary`, background transparent, same radius and padding
- **Danger Button**: background `danger`, text white, same shape
- **Small Button**: padding `8px 16px`, font 13px — used inside tables and cards

### Input Fields
- Border: 1.5px solid `primary-border`, radius `radius-sm`
- Focus: border color `primary`, box-shadow `0 0 0 3px rgba(107,33,168,0.12)`
- Padding: `12px 16px`
- Font: Inter 15px, color `text-primary`
- Placeholder: color `text-muted`
- Label: Inter 14px weight 500, color `text-primary`, margin-bottom 6px

### Cards
- Background: `surface` (white)
- Border: 1px solid `primary-border`
- Border radius: `radius-md`
- Shadow: `shadow-sm`, on hover: `shadow-md`
- Padding: `space-6` (24px)
- Product card background: `primary-subtle`

### Status Badges
Pill-shaped, font Inter 12px weight 600, padding `4px 10px`, radius-full
- Menunggu Konfirmasi: background `#EFF6FF`, text `info`
- Diproses: background `#FFFBEB`, text `warning`
- Selesai: background `#F0FDF4`, text `success`
- Dibatalkan: background `#FEF2F2`, text `danger`

### Navigation (Public)
- Sticky top navbar, background white, border-bottom 1px `primary-border`
- Logo: WanFlorist with flower icon in `primary`
- Nav links: Inter 15px weight 500, color `text-secondary`
- Active link: color `primary`, underline 2px `primary`
- Search bar: rounded-full, border `primary-border`, icon `primary`

### Sidebar (Admin)
- Width: 240px, background `dark-bg` (`#1E1040`)
- Logo area: WanFlorist branding in white
- Menu items: Inter 14px weight 500, color `dark-muted`, padding `10px 20px`
- Active item: background `rgba(107,33,168,0.4)`, text white, left border 3px `primary-light`
- Icons: 18px, aligned left of label
- Sidebar is always visible on desktop, collapsible on mobile

### Tables (Admin)
- Header: background `primary-subtle`, text `text-primary`, Inter 13px weight 600
- Row: alternating white and `#FDFBFF`, border-bottom 1px `primary-border`
- Row hover: background `primary-subtle`
- Cell padding: `12px 16px`

---

## Grid and Layout

### Public Pages
- Max content width: 1200px, centered
- Navbar height: 72px
- Hero section: two-column (text left, image right), full-width with padding 96px top/bottom
- Product grid: 4 columns desktop, 2 columns tablet, 1 column mobile
- Category row: horizontal pill tags, centered

### Admin Pages
- Layout: fixed sidebar (240px) + main content area
- Main content padding: 32px
- Dashboard stat cards: 4 columns desktop, 2 tablet
- Tables: full width of content area

---

## Iconography
- Icon library: **Lucide Icons** (consistent, clean, MIT licensed)
- Size: 18px for inline/nav, 20px for buttons, 24px for dashboard stat cards
- Color: inherit from parent or use `primary` for emphasis icons

---

## Imagery Style
- Product photos: white or very light background, no harsh shadows, centered composition
- Avoid stock-photo corporate feel — keep warm, personal, handmade aesthetic
- Photo containers: rounded corners (`radius-xl`), no hard rectangular crops
- Florist imagery should feel soft, pastel-adjacent, and inviting

---

## Language and Copy
- Primary language: **Bahasa Indonesia**
- Tone: warm, friendly, approachable — not formal corporate
- Currency format: `Rp 150.000` (dot as thousand separator)
- Date format: `Senin, 12 Januari 2026`
- Status labels in Indonesian: Menunggu Konfirmasi, Diproses, Selesai, Dibatalkan

---

## Constraints (Hard Rules)
1. NO CSS frameworks — no Tailwind, no Bootstrap. Pure CSS only.
2. NO JavaScript frameworks — no React, no Vue. Vanilla JS only.
3. Backend: PHP Native with MySQL database.
4. Every color used MUST come from this design system. Do not invent new colors.
5. Every font used must be Playfair Display or Inter. No other fonts.
6. Buttons must always use `radius-full` (pill shape). No square buttons.
7. Admin sidebar uses `dark-bg` (#1E1040). Do not use purple sidebar.
8. Public navbar is separate from admin sidebar — they are two completely different layout components.
9. Buyers do NOT have accounts or login. Admin login is for owner and staff only.
10. All form labels must be in Bahasa Indonesia.
