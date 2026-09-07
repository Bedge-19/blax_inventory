---
name: High-Performance Marketplace
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#434655'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#737686'
  outline-variant: '#c3c6d7'
  surface-tint: '#0053db'
  primary: '#004ac6'
  on-primary: '#ffffff'
  primary-container: '#2563eb'
  on-primary-container: '#eeefff'
  inverse-primary: '#b4c5ff'
  secondary: '#545f73'
  on-secondary: '#ffffff'
  secondary-container: '#d5e0f8'
  on-secondary-container: '#586377'
  tertiary: '#005a82'
  on-tertiary: '#ffffff'
  tertiary-container: '#0074a6'
  on-tertiary-container: '#e4f2ff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#d8e3fb'
  secondary-fixed-dim: '#bcc7de'
  on-secondary-fixed: '#111c2d'
  on-secondary-fixed-variant: '#3c475a'
  tertiary-fixed: '#c9e6ff'
  tertiary-fixed-dim: '#89ceff'
  on-tertiary-fixed: '#001e2f'
  on-tertiary-fixed-variant: '#004c6e'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  title-lg:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  button:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.01em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  container-max: 1440px
  columns: '12'
  gutter: 24px
  margin: 40px
  xs: 4px
  sm: 8px
  md: 16px
  lg: 24px
  xl: 48px
  xxl: 80px
---

## Brand & Style
The design system is engineered for a high-trust, multi-tenant ecosystem. It balances the utilitarian precision of developer tools with the polished elegance of premium retail platforms. The aesthetic is rooted in **Modern Minimalism**, emphasizing clarity, intentional white space, and a sophisticated hierarchy that allows tenant content to shine without visual interference.

The interface evokes a sense of reliability and speed. It utilizes a "Surface-First" philosophy where the background remains neutral to let functional containers and interactive elements drive the user's focus. The emotional response is one of professional competence and effortless scale.

## Colors
This design system employs a functional palette optimized for readability and clear action signaling. 

- **Primary (#2563EB):** Reserved for high-priority actions and active states.
- **Secondary (#1E293B):** Used for navigation backgrounds, heavy text, and structural anchors to provide grounded contrast.
- **Accent (#0EA5E9):** Applied to data visualizations and secondary interactive cues to maintain a vibrant, modern energy.
- **Surface & Background:** The distinction between `Background (#F8FAFC)` and `Surface (#FFFFFF)` is critical for creating depth. All primary content containers reside on Surface white to ensure maximum legibility.

## Typography
The system relies exclusively on **Inter** to achieve a systematic, utilitarian look. 

- **Titles & Headings:** Use Bold (700) for primary titles and Semi-Bold (600) for section headings to create a clear scan-path. Negative letter-spacing is applied to larger sizes to maintain high density and a premium "editorial" feel.
- **Body Text:** Set to Regular (400) for optimal long-form legibility.
- **Interactive Elements:** Buttons and labels use Medium (500) to distinguish them from static body text without the visual heaviness of bold weights.

## Layout & Spacing
The design system follows a strict **8-point grid** for all spatial relationships. 

- **Grid System:** A 12-column fluid-width grid with a max-container width of 1440px. 
- **Gutters:** Fixed at 24px to provide ample breathing room between complex data modules.
- **Vertical Rhythm:** All margins and paddings must be multiples of 8px. Use 48px or 80px (xl/xxl) for major section transitions to reinforce the minimalist, airy aesthetic.

## Elevation & Depth
Depth is communicated through **Soft Shadows** and **Tonal Layering** rather than heavy borders.

- **Low Elevation:** Used for cards and secondary buttons. A subtle `0 1px 3px rgba(0,0,0,0.1)` shadow.
- **Mid Elevation:** Used for dropdowns and hover states. A more diffused `0 10px 15px -3px rgba(0,0,0,0.1)` shadow.
- **Glassmorphism:** Auth cards and specific modal overlays utilize a backdrop blur (12px to 20px) with a semi-transparent White (80% opacity) surface. This creates a high-end, layered effect inspired by modern OS design.
- **Interactive Transitions:** Elements should elevate slightly on hover (Y-axis translation of -2px) with a 200ms ease-out transition.

## Shapes
The shape language is consistently rounded to soften the corporate nature of the marketplace. 

- **Standard Radius:** 8px for small components like inputs and tags.
- **Container Radius:** 12px to 16px for cards and primary content modules.
- **Full Radius:** Reserved for status pills and iconic buttons (e.g., "Add" buttons).
- **Consistent Enclosure:** Inner elements (like images inside a card) should always have a radius 4px smaller than their parent container to maintain visual harmony.

## Components
Consistent component implementation is vital for the multi-tenant experience:

- **Buttons:**
  - *Primary:* Solid #2563EB with White text. Medium weight.
  - *Ghost:* Transparent background, #1E293B text, appears only on hover or within subtle toolbars.
- **High-Fidelity Cards:** Use a White surface, 16px radius, and a 1px border (#E5E7EB). On hover, transition to a Mid-Elevation shadow.
- **Data Tables:** Borderless rows with 1px bottom dividers. Header text should be `label-sm` in Secondary color with 60% opacity.
- **Charts:** Use a mix of Primary (#2563EB) and Accent (#0EA5E9) with smooth, non-aliased curves. Background grid lines should be #F1F5F9.
- **Inputs:** 8px radius, #F8FAFC background, #E5E7EB border. On focus, the border shifts to Primary #2563EB with a 2px soft glow.
- **Auth Cards:** Feature the Glassmorphism style—Backdrop blur (20px), 1px semi-transparent white border, and 24px radius.