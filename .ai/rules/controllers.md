---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Any user-supplied value rendered into an href needs url:http,https
Blade's `{{ }}` escapes entities but NOT the URL scheme. A value validated as plain `'string'` and rendered as `href="{{ $model->some_url }}"` lets a saved `javascript:alert(document.cookie)` execute in our own origin on click — and it passes the form's `type="url"` check on the way in, because the browser treats it as a valid absolute URL.

Validate every such column as `['nullable', 'url:http,https', 'max:255']`. Name the two schemes explicitly rather than relying on bare `url`; Laravel 13's bare rule rejects `javascript:`/`data:` today, but the explicit form is what survives a framework upgrade.

Keep `max:` matched to the column width. `source_url` is `$table->string(...)` = 255; validation looser than the column passes the rule and then blows up at the database. Widen the column first if you ever need longer URLs.

Live example: `RecipeController::rules()`, pinned by `tests/Feature/RecipeControllerTest.php`.
