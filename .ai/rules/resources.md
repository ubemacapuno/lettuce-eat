---
paths:
  - 'resources/**'
---

# Resources

## Checked checkbox glyph is overridden in app.css
@tailwindcss/forms paints checked inputs as `background-color: currentColor` with a hardcoded *white* SVG glyph. Our dark theme's `--primary` is `0 0% 98%`, so `text-primary` on a checkbox gives a white check on a white box — invisible.

`resources/css/app.css` redraws the checkbox/radio glyph in `--primary-foreground` (`#18181b`) under `@layer base`. If that token ever changes, update the hardcoded hex in those data URIs too.

## Checked checkbox glyph is overridden in app.css
SUPERSEDED — the app.css data-URI override was removed. Use `<x-checkbox>` (resources/views/components/checkbox.blade.php) instead.

Two traps it exists to avoid:
1. @tailwindcss/forms paints a checked input as `background-color: currentColor` plus a hardcoded *white* SVG glyph. Our `--primary` is `0 0% 98%`, so `text-primary` gave a white check on a white box. The component uses `appearance-none checked:bg-none` to drop the plugin glyph and renders a real `<svg>` sibling shown via `peer-checked`, inheriting `text-primary-foreground`.
2. Never put a Blade directive inside a component tag — `<x-checkbox @checked($v) />` silently fails to compile and ships `<x-checkbox>` to the browser as literal markup. Use a bound attribute: `:checked="(bool) $v"`.
