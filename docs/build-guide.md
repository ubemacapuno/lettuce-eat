# Lettuce Eat — Data Model Build Guide

A step-by-step guide to building the `Restaurant` and `Dish` models yourself.

---

## Part 1: Answering your design questions

### "Restaurant to dishes is a what relationship?"

`hasMany`. Every `belongsTo` has an inverse, and for `belongsTo` the inverse is `hasMany`
(or `hasOne`, if there could only ever be one).

Think of it as: **the foreign key column lives on the `belongsTo` side.**

```
users                    restaurants                 dishes
-----                    -----------                 ------
id  <------------------  user_id                     id
name                     id  <-------------------->  restaurant_id
                         name                        name
```

`dishes.restaurant_id` points at a restaurant, so `Dish belongsTo Restaurant`,
and the mirror image is `Restaurant hasMany Dish`.

So your full picture is:

| Model | Relationship | Why |
|---|---|---|
| `User` | `hasMany(Restaurant::class)` | one user, many restaurants |
| `Restaurant` | `belongsTo(User::class)` | `restaurants.user_id` |
| `Restaurant` | `hasMany(Dish::class)` | one restaurant, many dishes |
| `Dish` | `belongsTo(Restaurant::class)` | `dishes.restaurant_id` |

### "Does rating need its own table?"

**No — it's a column on each table.**

Here's the rule of thumb that decides this every time:

> A value gets its own table when **many rows** of it attach to **one row** of the parent.

Yelp needs a `ratings` table because one restaurant has *ten thousand* ratings from
*ten thousand different users*. You described the opposite: everyone has their own
private instance, and you rate a restaurant once. That's **one rating per restaurant** —
a single value that belongs to the row. That's a column.

The one thing that would change this: if you later want rating *history*
("I rated Portillo's 4.4 in January, 4.8 in June"). Then each restaurant would have
many ratings over time and you'd want a table. You said keep it simple, so: column.

### "What about location?"

**Also just columns**, for the same reason — one optional address per restaurant.
A separate `locations` table would mean a JOIN on every single query to fetch three
strings. Not worth it.

Put `street_address`, `city`, `state` on `restaurants` as nullable columns.
(Nullable = optional, which is what you wanted.)

### "Are there other tables I should be thinking about?"

For your v1, **no** — `users`, `restaurants`, `dishes` covers everything you described.

Things you might *eventually* want, so you can recognize them later:

| Idea | Table you'd need | Why skip for now |
|---|---|---|
| Tags / cuisine types ("Mexican", "cheap eats") | `tags` + `restaurant_tag` pivot (many-to-many) | Real feature, but not in your description |
| Visit history ("went 3 times") | `visits` | You're rating the place, not each trip |
| Photos of dishes | `photos` | File uploads are a whole separate project |

Resist adding these until you actually want the feature. Every table you add is
another thing to keep in sync.

---

## Part 2: Build it

### Step 1 — Generate the files

The `-mf` flags mean "also make a **m**igration and a **f**actory".

```bash
php artisan make:model Restaurant -mf --no-interaction
php artisan make:model Dish -mf --no-interaction
```

That gives you six files:

```
app/Models/Restaurant.php
app/Models/Dish.php
database/migrations/xxxx_xx_xx_xxxxxx_create_restaurants_table.php
database/migrations/xxxx_xx_xx_xxxxxx_create_dishes_table.php
database/factories/RestaurantFactory.php
database/factories/DishFactory.php
```

> Laravel pluralizes for you: `Dish` → `dishes` table. It gets the tricky ones right.

---

### Step 2 — The migrations

**`database/migrations/..._create_restaurants_table.php`** — fill in the `up()` and `down()`:

```php
public function up(): void
{
    Schema::create('restaurants', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->decimal('rating', 2, 1)->nullable();
        $table->text('notes')->nullable();
        $table->string('street_address')->nullable();
        $table->string('city')->nullable();
        $table->string('state')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('restaurants');
}
```

**`database/migrations/..._create_dishes_table.php`:**

```php
public function up(): void
{
    Schema::create('dishes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->decimal('rating', 2, 1)->nullable();
        $table->text('notes')->nullable();
        $table->boolean('order_again')->default(false);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('dishes');
}
```

#### Line-by-line, the parts worth understanding

**`decimal('rating', 2, 1)`** — this is the important one.

`2` = total digits, `1` = digits after the decimal point. So `4.2`, `3.8`, `5.0`. 

**Do not use `float` for ratings.** Floats are stored as binary approximations, so `4.2`
is actually something like `4.19999999999999982`. Then `$dish->rating == 4.2` returns
`false` and you lose an afternoon to it. `decimal` stores the exact number. Money and
ratings both want `decimal`.

**`->nullable()` on rating** — lets you add a restaurant before you've decided on a
rating. Drop this if you want to force a rating at creation time.

**`->constrained()`** — creates a real foreign key constraint in the database.
It figures out from the column name `user_id` that it should point at `users.id`.

**`->cascadeOnDelete()`** — if a user is deleted, their restaurants are deleted too;
if a restaurant is deleted, its dishes go with it. Without this you get orphaned
rows: dishes pointing at a restaurant that no longer exists.

**No `->index()` anywhere — that's deliberate.** See
"[Why there are no indexes here](#why-there-are-no-indexes-here)" below if you're
wondering, or if you've seen `->index()` in other examples.

**`text` vs `string` for notes** — `string` is `VARCHAR(255)`, which your
"ask for the gravy on the side, add hot peppers" note would fit in, but you'll
eventually write a longer one. `text` has no practical limit. Use `text` for
free-form writing, `string` for names and labels.

> ⚠️ **SQLite gotcha:** SQLite ignores foreign key constraints unless they're
> explicitly enabled. Laravel enables them by default in modern versions, but if
> `cascadeOnDelete` doesn't seem to fire, that's the first thing to check.

#### Why there are no indexes here

You'll see `->index()` in a lot of migration examples, and it's fair to wonder why
this guide skips it. Short answer: **your tables are too small for it to matter.**

An index is the index at the back of a book. Without one, "find all restaurants where
`user_id = 3`" makes the database read *every row* and check each one — a full table
scan. With one, it jumps straight to the matches.

That's a real speedup on a big table. Yours won't be big. A personal food journal
might reach 200 restaurants and 1,000 dishes in its whole lifetime, and scanning 200
rows takes microseconds. Meanwhile indexes aren't free — they cost disk space, and
every `INSERT`/`UPDATE`/`DELETE` has to update the index too. At your scale you'd be
paying a real write cost for an unmeasurable read benefit.

Indexes start earning their keep somewhere in the tens of thousands of rows.

**Why you rarely see this discussed:** most Laravel apps run MySQL, where InnoDB
*automatically* creates an index on every foreign key column. So on MySQL,
`->index()` on an FK is redundant — you'd just get a duplicate. SQLite and PostgreSQL
don't do that automatically, but "not automatic" isn't the same as "necessary."

**The habit worth having: add an index in response to a slow query, not in
anticipation of one.** If this ever grows into something with real data, `user_id` is
the first index you'd add — in a new migration, after you measured the problem.

> ⚠️ **If you do ever add one, placement matters — and getting it wrong fails
> silently.** `->index()` must come *before* `->constrained()`:
>
> ```php
> $table->foreignId('user_id')->index()->constrained();   // ✅ creates the index
> $table->foreignId('user_id')->constrained()->index();   // ❌ silently does nothing
> ```
>
> `constrained()` returns a *different object* — a `ForeignKeyDefinition` (the
> constraint) rather than a `ColumnDefinition` (the column). That class extends
> `Fluent`, whose `__call()` magic method swallows any unknown method, sets a
> meaningless attribute, and returns `$this`. No error, no index. PhpStorm will
> underline it; the SQL just quietly comes out without the `create index` statement.
>
> **The rule:** `constrained()` is the dividing line. Everything *before* it modifies
> the **column** (`index()`, `nullable()`, `default()`). Everything *after* configures
> the **foreign key** (`cascadeOnDelete()`, `nullOnDelete()`, `restrictOnDelete()`).
> The same trap applies to `nullable()`, where it's nastier — you don't find out until
> an insert blows up.

---

### Step 3 — The models

These follow the style used in the official
[Laravel bootcamp](https://github.com/laravel/bootcamp) repo: `protected $fillable`, a
`casts()` method, and relationship methods with return type hints.

> **Note on `$fillable`:** Laravel 13's app skeleton ships `User.php` using a newer
> attribute syntax — `#[Fillable(['name', 'email', 'password'])]` above the class — so
> your `User.php` won't match these. Both work identically (the attribute just merges
> into `$fillable` internally) and neither is deprecated. This guide uses the property
> form to match the tutorial. If you want the codebase fully consistent, convert
> `User.php` to `protected $fillable` / `protected $hidden` — that's exactly what the
> bootcamp's `User.php` looks like.

**`app/Models/Restaurant.php`:**

```php
<?php

namespace App\Models;

use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'rating',
        'notes',
        'street_address',
        'city',
        'state',
    ];

    /**
     * The user who added this restaurant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The dishes this user has tried at this restaurant.
     */
    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
        ];
    }
}
```

**`app/Models/Dish.php`:**

```php
<?php

namespace App\Models;

use Database\Factories\DishFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


{
    /** @use HasFactory<DishFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'rating',
        'notes',
        'order_again',
    ];

    /**
     * The model's default values for attributes, mirroring the database defaults.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'order_again' => false,
    ];

    /**
     * The restaurant this dish was ordered at.
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
            'order_again' => 'boolean',
        ];
    }
}
```

**`app/Models/User.php`** — add two relationship methods and two `use` statements
to what's already there:

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
```

```php
/**
 * The restaurants this user has added.
 */
public function restaurants(): HasMany
{
    return $this->hasMany(Restaurant::class);
}

/**
 * Every dish this user has logged, across all of their restaurants.
 */
public function dishes(): HasManyThrough
{
    return $this->hasManyThrough(Dish::class, Restaurant::class);
}
```

#### The parts worth understanding

**🔒 Notice what's NOT in `$fillable`: `user_id` and `restaurant_id`.**

This is a real security practice, not a style choice. `fillable` is the allowlist of
columns that can be set from a single array — usually `$request->all()` from a form.
If `user_id` were fillable, someone could POST an extra `user_id` field and create a
restaurant inside *your* account.

So you never mass-assign the owner. You set it through the relationship instead,
which fills in the foreign key for you and ignores anything the user tried to sneak in:

```php
// ✅ Good — the relationship sets user_id, guaranteed
$restaurant = $request->user()->restaurants()->create($validated);

// ✅ Same idea for dishes
$dish = $restaurant->dishes()->create($validated);

// ❌ Bad — trusts the request to say who owns this
$restaurant = Restaurant::create($request->all());
```

(`$validated` comes from `$request->validate([...])` — see
"[Validation](#validation-for-when-you-build-the-form)" below.)

**`casts()`** converts values coming out of the database into proper PHP types.
Without `'order_again' => 'boolean'`, SQLite hands you back the integer `0`, and
`if ($dish->order_again)` gets confusing. With the cast, you get real `true`/`false`.

> ⚠️ **`decimal:1` returns a *string*, not a float.** `$dish->rating` gives you
> `"4.5"`, not `4.5`. That is intentional — it's how PHP avoids float precision
> problems. It prints fine in Blade and compares fine with `==`, but if you need to
> do math on it, cast it: `(float) $dish->rating`. This will surprise you in a test
> when `toBe(4.5)` fails and `toBe('4.5')` passes.

**`protected $attributes`** mirrors the database default into the model, so a
brand-new unsaved `Dish` already has `order_again = false` instead of `null`.

**`hasManyThrough`** is a bonus: it gets you all of a user's dishes across every
restaurant, without a `user_id` column on `dishes`. It "reaches through" the
restaurants table. Useful for a "everything I'd order again" page:

```php
$user->dishes()->where('order_again', true)->get();
```

This is also why `dishes` doesn't need its own `user_id` — the ownership chain
already exists through `restaurants`.

**Return type hints** (`: BelongsTo`, `: HasMany`) — required by this project's
PHP conventions, and they make your editor autocomplete correctly.

---

### Step 4 — The factories

Factories generate fake records for tests and seeding, so you never hand-write
test data.

> **Heads up:** the Laravel bootcamp doesn't cover factories or tests — it builds the
> UI instead. This step and Steps 5–6 go beyond the tutorial. They're standard Laravel
> practice (`make:model -f` generates a factory precisely because it's expected), but
> if you're following the tutorial lesson-by-lesson, know that this is extra.

**`database/factories/RestaurantFactory.php`:**

```php
<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'rating' => fake()->randomFloat(1, 1, 5),
            'notes' => fake()->sentence(),
            'street_address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
        ];
    }

    /**
     * Indicate that the restaurant has no location recorded.
     */
    public function withoutLocation(): static
    {
        return $this->state(fn (array $attributes) => [
            'street_address' => null,
            'city' => null,
            'state' => null,
        ]);
    }

    /**
     * Indicate that the restaurant has not been rated yet.
     */
    public function unrated(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => null,
        ]);
    }
}
```

**`database/factories/DishFactory.php`:**

```php
<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dish>
 */
class DishFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => fake()->randomElement([
                'Italian Beef',
                'Cake Shake',
                'Carne Asada Tacos',
                'Green Chile Burrito',
                'Margherita Pizza',
                'Pad Thai',
                'Fried Chicken Sandwich',
                'Pork Belly Ramen',
            ]),
            'rating' => fake()->randomFloat(1, 1, 5),
            'notes' => fake()->sentence(),
            'order_again' => fake()->boolean(),
        ];
    }

    /**
     * Indicate that the dish is worth ordering again.
     */
    public function orderAgain(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_again' => true,
        ]);
    }

    /**
     * Indicate that the dish is not worth ordering again.
     */
    public function skipNextTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_again' => false,
        ]);
    }
}
```

#### The parts worth understanding

**`'user_id' => User::factory()`** — a factory inside a factory. If you make a
restaurant without specifying a user, it creates one automatically. You never get a
restaurant with a dangling `user_id`.

**`randomFloat(1, 1, 5)`** — a random number between 1 and 5 with 1 decimal place.
Exactly your rating format.

**State methods** (`withoutLocation()`, `orderAgain()`) are named variations you can
chain. They make tests read like sentences:

```php
Restaurant::factory()->unrated()->withoutLocation()->create();
Dish::factory()->orderAgain()->count(3)->create();
```

**Building whole trees at once** — this is where factories really pay off:

```php
// A user with 3 restaurants, each with 5 dishes = 1 user, 3 restaurants, 15 dishes
User::factory()
    ->has(Restaurant::factory()->count(3)->hasDishes(5))
    ->create();

// Attach to an existing parent with ->for()
Dish::factory()->for($portillos)->create(['name' => 'Italian Beef']);
```

`hasDishes()` is a magic method — Laravel builds it from your `dishes()` relationship
name. `has()` goes down the tree (parent → children), `for()` goes up (child → parent).

---

### Step 5 — The seeder (optional but handy)

This gives you real data to look at while building the UI, using your own Portillo's
example. In `database/seeders/DatabaseSeeder.php`, inside `run()`:

```php
$user = User::factory()->create([
    'name' => 'Test User',
    'email' => 'test@example.com',
]);

$portillos = Restaurant::factory()->for($user)->create([
    'name' => "Portillo's",
    'rating' => 4.4,
    'notes' => 'Gets busy at lunch, go early.',
    'street_address' => '3105 S Market St',
    'city' => 'Gilbert',
    'state' => 'AZ',
]);

$portillos->dishes()->createMany([
    [
        'name' => 'Italian Beef',
        'rating' => 4.5,
        'order_again' => true,
        'notes' => 'Ask for the gravy on the side, add hot peppers.',
    ],
    [
        'name' => 'Cake Shake',
        'rating' => 3.8,
        'order_again' => false,
        'notes' => 'Order sparingly, very sweet!',
    ],
]);

// A few more so lists aren't empty
Restaurant::factory()->for($user)->count(4)->hasDishes(3)->create();
```

Don't forget the imports at the top:

```php
use App\Models\Restaurant;
use App\Models\User;
```

---

### Step 6 — Tests

**First, one setup change.** Open `tests/Pest.php` and uncomment the
`RefreshDatabase` line:

```php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)   // <- was commented out
    ->in('Feature');
```

`RefreshDatabase` wipes and re-migrates the database between tests, so tests can't
pollute each other. You need it now that you have real database models.

Create the files:

```bash
php artisan make:test --pest RestaurantTest --no-interaction
php artisan make:test --pest DishTest --no-interaction
```

**`tests/Feature/RestaurantTest.php`:**

```php
<?php

use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;

it('belongs to the user who added it', function () {
    $user = User::factory()->create();
    $restaurant = Restaurant::factory()->for($user)->create();

    expect($restaurant->user->id)->toBe($user->id);
});

it('has many dishes', function () {
    $restaurant = Restaurant::factory()->hasDishes(3)->create();

    expect($restaurant->dishes)->toHaveCount(3)
        ->and($restaurant->dishes->first())->toBeInstanceOf(Dish::class);
});

it('keeps each users restaurants separate', function () {
    $corey = User::factory()->has(Restaurant::factory()->count(2))->create();
    User::factory()->has(Restaurant::factory()->count(5))->create();

    expect($corey->restaurants)->toHaveCount(2)
        ->and(Restaurant::count())->toBe(7);
});

it('stores a rating to the tenths place', function () {
    $restaurant = Restaurant::factory()->create(['rating' => 4.2]);

    expect($restaurant->fresh()->rating)->toBe('4.2');
});

it('allows a restaurant with no location', function () {
    $restaurant = Restaurant::factory()->withoutLocation()->create();

    expect($restaurant->city)->toBeNull()
        ->and($restaurant->state)->toBeNull()
        ->and($restaurant->street_address)->toBeNull();
});

it('deletes its dishes when the restaurant is deleted', function () {
    $restaurant = Restaurant::factory()->hasDishes(2)->create();

    $restaurant->delete();

    expect(Dish::count())->toBe(0);
});

it('reaches every dish the user has logged', function () {
    $user = User::factory()->create();
    Restaurant::factory()->for($user)->hasDishes(2)->create();
    Restaurant::factory()->for($user)->hasDishes(3)->create();

    expect($user->dishes)->toHaveCount(5);
});

it('does not allow the owner to be mass assigned', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $restaurant = $owner->restaurants()->create([
        'name' => "Portillo's",
        'user_id' => $attacker->id,
    ]);

    expect($restaurant->user_id)->toBe($owner->id);
});
```

**`tests/Feature/DishTest.php`:**

```php
<?php

use App\Models\Dish;
use App\Models\Restaurant;

it('belongs to a restaurant', function () {
    $restaurant = Restaurant::factory()->create();
    $dish = Dish::factory()->for($restaurant)->create();

    expect($dish->restaurant->id)->toBe($restaurant->id);
});

it('casts order again to a boolean', function () {
    expect(Dish::factory()->orderAgain()->create()->order_again)->toBeTrue()
        ->and(Dish::factory()->skipNextTime()->create()->order_again)->toBeFalse();
});

it('defaults order again to false', function () {
    $dish = Restaurant::factory()->create()->dishes()->create(['name' => 'Cake Shake']);

    expect($dish->order_again)->toBeFalse();
});

it('records the full order-again story for a dish', function () {
    $restaurant = Restaurant::factory()->create(['name' => "Portillo's"]);

    $dish = $restaurant->dishes()->create([
        'name' => 'Italian Beef',
        'rating' => 4.5,
        'order_again' => true,
        'notes' => 'Ask for the gravy on the side, add hot peppers.',
    ]);

    expect($dish->restaurant->name)->toBe("Portillo's")
        ->and($dish->rating)->toBe('4.5')
        ->and($dish->order_again)->toBeTrue()
        ->and($dish->notes)->toContain('hot peppers');
});
```

Two of these are worth calling out. **"keeps each users restaurants separate"** is
the test for the core idea of your whole app — that everyone gets their own instance.
**"does not allow the owner to be mass assigned"** proves the `fillable` allowlist
actually protects you. Those two are the ones that would really hurt to get wrong.

Note `->toBe('4.2')` with quotes — that's the `decimal:1`-returns-a-string thing
from Step 3.

---

### Step 7 — Run it

```bash
php artisan migrate                  # create the tables
php artisan test --compact           # run the tests
vendor/bin/pint --dirty --format agent   # format your PHP (required by this project)
```

To wipe and reseed with fresh sample data at any point:

```bash
php artisan migrate:fresh --seed
```

⚠️ `migrate:fresh` **drops every table** and rebuilds from scratch. Great in
development, never on real data.

---

## Quick reference: the finished schema

**restaurants**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | primary key |
| `user_id` | bigint | FK → users, cascade on delete |
| `name` | varchar(255) | |
| `rating` | decimal(2,1) | nullable, e.g. `4.2` |
| `notes` | text | nullable |
| `street_address` | varchar(255) | nullable |
| `city` | varchar(255) | nullable |
| `state` | varchar(255) | nullable |
| `created_at` / `updated_at` | timestamp | |

**dishes**

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | primary key |
| `restaurant_id` | bigint | FK → restaurants, cascade on delete |
| `name` | varchar(255) | |
| `rating` | decimal(2,1) | nullable, e.g. `3.8` |
| `notes` | text | nullable |
| `order_again` | boolean | defaults to `false` |
| `created_at` / `updated_at` | timestamp | |

---

## Validation (for when you build the form)

The bootcamp validates **inline in the controller** with `$request->validate()`, so
that's what this uses. (Laravel also has Form Request classes — a separate file per
form. Better once rules get long or get reused, but inline is the right starting
point and it's what the tutorial teaches.)

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'rating' => ['nullable', 'numeric', 'between:1,5', 'decimal:0,1'],
        'notes' => ['nullable', 'string'],
        'street_address' => ['nullable', 'string', 'max:255'],
        'city' => ['nullable', 'string', 'max:255'],
        'state' => ['nullable', 'string', 'max:255'],
    ], [
        'name.required' => 'What is the place called?',
        'rating.between' => 'Rating must be between 1 and 5 stars.',
        'rating.decimal' => 'Use at most one decimal place, like 4.2.',
    ]);

    $request->user()->restaurants()->create($validated);

    return redirect('/')->with('success', 'Restaurant added!');
}
```

**`$validated` only contains fields you wrote rules for.** That's what makes it safe
to hand to `create()` — anything extra the browser posted is already gone. It's the
second layer of the same protection `$fillable` gives you.

**The second argument is custom error messages**, keyed `field.rule`. Straight from
the tutorial, and it's the cheapest UX win in Laravel.

On failure, `validate()` automatically redirects back with the errors and the old
input — you just display them:

```blade
<input name="name" value="{{ old('name') }}">
@error('name') <p class="text-red-500">{{ $message }}</p> @enderror
```

### Where the 1–5 rule actually lives

This is worth being precise about, because three different things touch `rating` and
only one of them enforces the range:

| Layer | What it does | Enforces 1–5? |
|---|---|---|
| `decimal('rating', 2, 1)` in the migration | how it's **stored** | ❌ (and SQLite ignores the `2,1` entirely) |
| `'rating' => 'decimal:1'` in the model | how it's **formatted** coming out | ❌ |
| `'between:1,5', 'decimal:0,1'` in validation | what's **allowed in** | ✅ |

`decimal:0,1` means "0 to 1 decimal places" — that's the rule that rejects `4.257`.
`between:1,5` is what rejects `12.5`.

### Dishes, and the checkbox gotcha

```php
$validated = $request->validate([
    'name' => ['required', 'string', 'max:255'],
    'rating' => ['nullable', 'numeric', 'between:1,5', 'decimal:0,1'],
    'notes' => ['nullable', 'string'],
    'order_again' => ['nullable', 'boolean'],
]);

$restaurant->dishes()->create($validated);
```

⚠️ **An unchecked checkbox sends nothing at all** — the key is simply absent from the
request, not `false`. So `$validated['order_again']` won't exist and the column falls
back to its database default (`false`), which happens to be what you want here.

If you ever need it explicit — an edit form that unchecks a previously-checked box —
reach for `$request->boolean('order_again')`, which turns `"1"`, `"true"`, `"on"`, and
absent into a real bool:

```php
$dish->update([...$validated, 'order_again' => $request->boolean('order_again')]);
```

---

## What to watch out for when you build the UI

Two things that will bite you later, so they're worth knowing now.

**1. Always scope queries to the logged-in user.** Since every user has their own
instance, a controller must never query `Restaurant::all()` — that returns *everyone's*
restaurants. Go through the relationship:

```php
// ✅ Only this user's restaurants
$restaurants = $request->user()->restaurants()->latest()->get();

// ❌ Everyone's restaurants
$restaurants = Restaurant::all();
```

For showing a single restaurant, the same applies — otherwise someone can change
the ID in the URL and read another account's notes. You'll want either a
[Policy](https://laravel.com/docs/13.x/authorization#creating-policies) or a scoped
lookup like `$request->user()->restaurants()->findOrFail($id)`.

**2. Eager-load dishes to avoid the N+1 problem.** If you loop over 20 restaurants
and touch `$restaurant->dishes` inside the loop, that's 21 separate database queries.
Ask for them up front instead:

```php
// ✅ 2 queries total
$restaurants = $request->user()->restaurants()->with('dishes')->get();

// ❌ 1 query + 1 per restaurant
$restaurants = $request->user()->restaurants()->get();
```

Related handy ones once you have data:

```php
$restaurant->dishes()->count();                       // no rows loaded, just a count
$restaurant->dishes()->avg('rating');                 // average dish rating
$user->dishes()->where('order_again', true)->get();   // the "would order again" list
```

---

# Part 3: Controllers, routes, and Blade forms

You have models, migrations, factories, a seeder, and 18 passing tests. What you do
**not** have is a single URL that shows any of it. This part gets you from
"the database works" to "I can add a restaurant in a browser."

## ⚠️ First, the blocker: there is no auth in this app

Run this:

```bash
php artisan route:list --except-vendor
```

You get exactly one route: `GET /`. There's no `/login`, no `/register`, no
`/dashboard`. `app/Http/Controllers/` contains only the empty base `Controller.php`.

That matters because **every line of the plan below assumes `$request->user()` returns
someone.** Right now it returns `null`, and `null->restaurants()` is a fatal error.
So auth is genuinely Step 8 — it isn't optional polish you bolt on at the end.

### Two ways forward

**Option A — install Breeze (recommended).** One command gives you working
register/login/logout/password-reset pages, plus a Blade layout to hang your own pages
off of.

**Option B — fake it for now.** Put `$user = User::first();` at the top of every
controller method instead of `$request->user()`, build all the CRUD, add real auth
later. Faster to first working page, but you'll touch every controller method a second
time, and none of your "does user A see user B's data?" tests mean anything until
auth is real.

Take Option A. It's about ten minutes and it's the same code the official tutorials
assume you already have.

---

### Step 8 — Install auth

⚠️ **Commit your work first.** Breeze overwrites `routes/web.php` and
`resources/views/welcome.blade.php`. If it's committed, you can diff and recover
anything you wanted to keep.

```bash
git add -A
git commit -m "models, migrations, factories, tests"
```

Then:

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build
php artisan migrate
```

`breeze:install blade` is the plain-Blade stack — no React, no Vue, no Livewire, no
build-tool rabbit hole. It does use Tailwind for styling, which comes preconfigured.

I checked that this works on your setup: Breeze `v2.4.2` declares
`illuminate/support: ^11.0|^12.0|^13.0`, and you're on `laravel/framework 13.25.0`.

**What you get:**

| Thing | Where |
|---|---|
| Login / register / forgot-password pages | `resources/views/auth/` |
| Auth controllers | `app/Http/Controllers/Auth/` |
| Auth routes | `routes/auth.php` (required from `web.php`) |
| A page layout you'll reuse | `resources/views/layouts/app.blade.php` |
| A `/dashboard` page | `resources/views/dashboard.blade.php` |
| Its own auth tests | `tests/Feature/Auth/` |

**Sanity check:** visit `http://lettuce-eat.test/register`, make yourself an account,
confirm you land on `/dashboard`. Also re-run `php artisan test --compact` — Breeze
adds ~20 of its own tests and they should all pass. If they don't, stop and fix that
before writing any of your own controllers.

> The `dashboard.blade.php` Breeze gives you is a placeholder that says "You're logged
> in!". It now has a button through to your restaurant list.

> ⚠️ Breeze's Blade stack also installs **Alpine.js** for its dropdown, hamburger menu,
> and delete-account modal. Part 4 rips that out and replaces it with Vue islands. If
> you're following along fresh, install Breeze first anyway — it's easier to replace
> working code than to write the nav from scratch.

---

### Step 9 — The routes

Add these inside `routes/web.php`. Everything goes in an `auth` middleware group —
that's the single line that makes the whole app private.

```php
use App\Http\Controllers\DishController;
use App\Http\Controllers\RestaurantController;

Route::middleware('auth')->group(function () {
    Route::resource('restaurants', RestaurantController::class);

    Route::resource('restaurants.dishes', DishController::class)
        ->shallow()
        ->except(['index', 'show']);
});
```

#### What `Route::resource` actually does

It's shorthand for seven routes that follow a naming convention Laravel uses
everywhere. `Route::resource('restaurants', RestaurantController::class)` expands to:

| Method | URI | Controller method | Route name | What it's for |
|---|---|---|---|---|
| GET | `/restaurants` | `index` | `restaurants.index` | the list |
| GET | `/restaurants/create` | `create` | `restaurants.create` | show the "new" **form** |
| POST | `/restaurants` | `store` | `restaurants.store` | **save** the new one |
| GET | `/restaurants/{restaurant}` | `show` | `restaurants.show` | one restaurant + its dishes |
| GET | `/restaurants/{restaurant}/edit` | `edit` | `restaurants.edit` | show the "edit" **form** |
| PUT/PATCH | `/restaurants/{restaurant}` | `update` | `restaurants.update` | **save** the edit |
| DELETE | `/restaurants/{restaurant}` | `destroy` | `restaurants.destroy` | delete it |

The pairing to internalize: **`create`/`edit` render forms, `store`/`update` receive
them.** Two methods per operation, one GET and one POST-ish.

#### Why the dish routes are nested

A dish has no meaning without its restaurant — you never browse "all dishes," you
browse "Portillo's dishes." Nesting puts that in the URL:

```
POST /restaurants/12/dishes        →  DishController@store
```

The `store` method receives the restaurant, so it always knows which one to attach to.

#### What `->shallow()` does

Without it, every dish route repeats the restaurant ID, which is redundant once you
have the dish's own ID — `/restaurants/12/dishes/34/edit` and `/dishes/34/edit`
identify the same row. `shallow()` keeps the restaurant in the URL **only where it's
actually needed** (creating a dish), and drops it everywhere else:

| Method | URI | Controller method | Route name |
|---|---|---|---|
| GET | `/restaurants/{restaurant}/dishes/create` | `create` | `restaurants.dishes.create` |
| POST | `/restaurants/{restaurant}/dishes` | `store` | `restaurants.dishes.store` |
| GET | `/dishes/{dish}/edit` | `edit` | `dishes.edit` |
| PUT/PATCH | `/dishes/{dish}` | `update` | `dishes.update` |
| DELETE | `/dishes/{dish}` | `destroy` | `dishes.destroy` |

Note the route names change too — shallow routes lose the `restaurants.` prefix. Get
this wrong in a Blade file and you'll see `Route [dishes.store] not defined`.

`->except(['index', 'show'])` drops the two you don't need: dishes are listed and read
on the restaurant's own page, so they never get pages of their own.

**Always verify with:**

```bash
php artisan route:list --except-vendor
```

Read the `Name` column carefully — those names are what you'll type into `route()` in
Blade, and a typo there is a runtime error, not a red squiggle.

---

### Step 10 — Generate the controllers

```bash
php artisan make:controller RestaurantController --model=Restaurant --resource --no-interaction
php artisan make:controller DishController --model=Dish --resource --no-interaction
```

- `--resource` stubs out all seven methods with the right names.
- `--model=Restaurant` type-hints the model in `show`/`edit`/`update`/`destroy`.

That second flag is what turns on **route model binding**. Because the route parameter
is `{restaurant}` and the method signature says `Restaurant $restaurant`, Laravel
looks up `Restaurant::find(12)` for you — and 404s automatically if there's no row.
You never write `findOrFail()`.

```php
// Route:  /restaurants/{restaurant}
public function show(Restaurant $restaurant)   // ← already the model, already loaded
```

> There's also a `--requests` flag that generates Form Request classes. Skip it. This
> guide validates inline in the controller, which is what the official tutorial
> teaches; Form Requests are the thing you graduate to when rules get long or shared.

---

### Step 11 — Ownership: build the Policy *before* the controllers

This is the single most important part of Part 3, so it comes first.

Remember the warning from "What to watch out for when you build the UI": scoping
queries to `$request->user()` protects `index` and `store`. It does **not** protect
`show`, `edit`, `update`, or `destroy` — because route model binding looks the model
up by ID straight from the URL, with no idea who's asking.

```php
public function show(Restaurant $restaurant)
{
    return view('restaurants.show', ['restaurant' => $restaurant]);
}
```

That code hands out any restaurant in the database to anyone who edits the number in
the address bar. It's the most common security bug in a first Laravel CRUD app.

#### Make the policies

```bash
php artisan make:policy RestaurantPolicy --model=Restaurant --no-interaction
php artisan make:policy DishPolicy --model=Dish --no-interaction
```

Laravel auto-discovers these by name — `Restaurant` → `RestaurantPolicy` — so there's
nothing to register.

`app/Policies/RestaurantPolicy.php`:

```php
public function view(User $user, Restaurant $restaurant): bool
{
    return $user->id === $restaurant->user_id;
}

public function update(User $user, Restaurant $restaurant): bool
{
    return $user->id === $restaurant->user_id;
}

public function delete(User $user, Restaurant $restaurant): bool
{
    return $user->id === $restaurant->user_id;
}
```

Yes, all three are identical. That's fine and normal — they only diverge later if you
add sharing, or a read-only mode, or an admin.

`app/Policies/DishPolicy.php` — a dish is owned via its restaurant:

```php
public function update(User $user, Dish $dish): bool
{
    return $user->id === $dish->restaurant->user_id;
}

public function delete(User $user, Dish $dish): bool
{
    return $user->id === $dish->restaurant->user_id;
}
```

#### Calling it

⚠️ **Older tutorials say `$this->authorize('update', $restaurant)`. That will not work
in this app.** Your `app/Http/Controllers/Controller.php` is an empty abstract class —
Laravel 11 removed the `AuthorizesRequests` trait from it. Use the `Gate` facade
instead:

```php
use Illuminate\Support\Facades\Gate;

public function edit(Restaurant $restaurant)
{
    Gate::authorize('update', $restaurant);

    return view('restaurants.edit', ['restaurant' => $restaurant]);
}
```

`Gate::authorize()` throws a 403 and stops the request if the policy returns `false`.
One line, at the top of the method, and the hole is closed.

---

### Step 12 — RestaurantController

```php
<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        return view('restaurants.index', [
            'restaurants' => $request->user()
                ->restaurants()
                ->withCount('dishes')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('restaurants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'between:1,5', 'decimal:0,1'],
            'notes' => ['nullable', 'string'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
        ]);

        $restaurant = $request->user()->restaurants()->create($validated);

        return redirect()
            ->route('restaurants.show', $restaurant)
            ->with('success', "{$restaurant->name} added!");
    }

    public function show(Restaurant $restaurant): View
    {
        Gate::authorize('view', $restaurant);

        $restaurant->load('dishes');

        return view('restaurants.show', ['restaurant' => $restaurant]);
    }

    public function edit(Restaurant $restaurant): View
    {
        Gate::authorize('update', $restaurant);

        return view('restaurants.edit', ['restaurant' => $restaurant]);
    }

    public function update(Request $request, Restaurant $restaurant): RedirectResponse
    {
        Gate::authorize('update', $restaurant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'between:1,5', 'decimal:0,1'],
            'notes' => ['nullable', 'string'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
        ]);

        $restaurant->update($validated);

        return redirect()
            ->route('restaurants.show', $restaurant)
            ->with('success', 'Updated!');
    }

    public function destroy(Restaurant $restaurant): RedirectResponse
    {
        Gate::authorize('delete', $restaurant);

        $restaurant->delete();

        return redirect()
            ->route('restaurants.index')
            ->with('success', 'Deleted.');
    }
}
```

**Four things worth pausing on:**

1. **`$request->user()->restaurants()->create($validated)`** — creating *through* the
   relationship sets `user_id` automatically. This is exactly why `user_id` is left out
   of `$fillable`: it can't be forged from form input, and it doesn't need to be,
   because the relationship fills it in.

2. **`withCount('dishes')`** adds a `dishes_count` property to each restaurant using
   one extra query, instead of loading every dish row just to call `count()` on them.
   Use it on the list page; use `load('dishes')` on the detail page where you actually
   render them.

3. **`->latest()`** orders by `created_at` descending. Newest first.

4. **`->with('success', ...)`** flashes a message into the session for exactly one
   request. You render it in Blade with `session('success')`.

**On the duplicated validation array:** `store` and `update` need identical rules, so
rather than paste the block twice, both controllers keep them in small protected
methods at the bottom:

```php
$validated = $request->validate($this->rules(), $this->messages());
```

That's the smallest possible fix and it means the two can never drift apart. The next
step up — once rules get long, or get reused outside the controller — is a Form
Request class (`php artisan make:request StoreRestaurantRequest`), which also gives you
somewhere to put the `authorize()` check. Don't jump there yet; you don't need it.

---

### Step 13 — DishController

Only five methods, and no `index`/`show`.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DishController extends Controller
{
    public function create(Restaurant $restaurant): View
    {
        Gate::authorize('update', $restaurant);

        return view('dishes.create', ['restaurant' => $restaurant]);
    }

    public function store(Request $request, Restaurant $restaurant): RedirectResponse
    {
        Gate::authorize('update', $restaurant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'between:1,5', 'decimal:0,1'],
            'notes' => ['nullable', 'string'],
            'order_again' => ['nullable', 'boolean'],
        ]);

        $restaurant->dishes()->create([
            ...$validated,
            'order_again' => $request->boolean('order_again'),
        ]);

        return redirect()
            ->route('restaurants.show', $restaurant)
            ->with('success', 'Dish added!');
    }

    public function edit(Dish $dish): View
    {
        Gate::authorize('update', $dish);

        return view('dishes.edit', ['dish' => $dish]);
    }

    public function update(Request $request, Dish $dish): RedirectResponse
    {
        Gate::authorize('update', $dish);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rating' => ['nullable', 'numeric', 'between:1,5', 'decimal:0,1'],
            'notes' => ['nullable', 'string'],
            'order_again' => ['nullable', 'boolean'],
        ]);

        $dish->update([
            ...$validated,
            'order_again' => $request->boolean('order_again'),
        ]);

        return redirect()
            ->route('restaurants.show', $dish->restaurant)
            ->with('success', 'Dish updated!');
    }

    public function destroy(Dish $dish): RedirectResponse
    {
        Gate::authorize('delete', $dish);

        $restaurant = $dish->restaurant;
        $dish->delete();

        return redirect()
            ->route('restaurants.show', $restaurant)
            ->with('success', 'Dish removed.');
    }
}
```

**Note the two parameters on `store`:** `Request $request, Restaurant $restaurant`.
Laravel resolves `$request` from the container and `$restaurant` from the `{restaurant}`
URL segment. Order doesn't matter — it matches on type, not position.

**Note `'order_again' => $request->boolean('order_again')`** — this is the checkbox
gotcha from the Validation section, and on the edit form it's not optional. An
unchecked box sends *nothing*, so without this line, unchecking a box and saving
would leave the old `true` in the database.

**Note `$restaurant = $dish->restaurant;` before `$dish->delete();`** in `destroy`.
Grab it while the row still exists, or you have nowhere to redirect to.

---

### Step 14 — The Blade views

Six files:

```
resources/views/partials/flash.blade.php            ← the green "success" banner
resources/views/restaurants/index.blade.php
resources/views/restaurants/create.blade.php
resources/views/restaurants/edit.blade.php
resources/views/restaurants/show.blade.php
resources/views/restaurants/partials/form.blade.php ← shared by create + edit
resources/views/dishes/create.blade.php
resources/views/dishes/edit.blade.php
resources/views/dishes/partials/form.blade.php      ← shared by create + edit
```

The `partials/form.blade.php` files exist because `create` and `edit` need the *same*
fields. Each starts with `@php($restaurant = $restaurant ?? null)` so it works on the
create page where there's no model yet, and every value reads
`old('name', $restaurant?->name)` — the `?->` handles the null case. Breeze does the
same thing in `profile/partials/`, so it matches the conventions already in the repo.

Breeze gives you `<x-app-layout>` — a component wrapping your page in the nav bar and
styling. Every page starts and ends with it.

#### `restaurants/index.blade.php`

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">My Restaurants</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto p-6">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        <a href="{{ route('restaurants.create') }}"
           class="inline-block mb-4 px-4 py-2 bg-gray-800 text-white rounded">
            + Add a restaurant
        </a>

        @forelse ($restaurants as $restaurant)
            <div class="p-4 mb-2 bg-white rounded shadow">
                <a href="{{ route('restaurants.show', $restaurant) }}"
                   class="font-bold text-lg">
                    {{ $restaurant->name }}
                </a>

                @if ($restaurant->rating)
                    <span>⭐ {{ $restaurant->rating }}</span>
                @endif

                <p class="text-sm text-gray-500">
                    {{ $restaurant->city }}{{ $restaurant->state ? ', '.$restaurant->state : '' }}
                    — {{ $restaurant->dishes_count }} dishes
                </p>
            </div>
        @empty
            <p class="text-gray-500">No restaurants yet. Add your first one!</p>
        @endforelse
    </div>
</x-app-layout>
```

`@forelse`/`@empty` is `@foreach` with a built-in "nothing here" branch. Use it —
empty states are the thing juniors forget most.

`route('restaurants.show', $restaurant)` takes the **model**, not `$restaurant->id`.
Laravel pulls the key out for you.

#### `restaurants/create.blade.php`

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Add a Restaurant</h2>
    </x-slot>

    <div class="max-w-2xl mx-auto p-6">
        <form method="POST" action="{{ route('restaurants.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block font-medium">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="w-full border rounded p-2" required>
                @error('name')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="rating" class="block font-medium">Rating (1–5)</label>
                <input type="number" name="rating" id="rating" value="{{ old('rating') }}"
                       min="1" max="5" step="0.1" class="w-full border rounded p-2">
                @error('rating')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="block font-medium">Notes</label>
                <textarea name="notes" id="notes" rows="3"
                          class="w-full border rounded p-2">{{ old('notes') }}</textarea>
            </div>

            <div>
                <label for="street_address" class="block font-medium">Street address</label>
                <input type="text" name="street_address" id="street_address"
                       value="{{ old('street_address') }}" class="w-full border rounded p-2">
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <label for="city" class="block font-medium">City</label>
                    <input type="text" name="city" id="city" value="{{ old('city') }}"
                           class="w-full border rounded p-2">
                </div>
                <div class="w-24">
                    <label for="state" class="block font-medium">State</label>
                    <input type="text" name="state" id="state" value="{{ old('state') }}"
                           class="w-full border rounded p-2">
                </div>
            </div>

            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded">
                Save
            </button>
        </form>
    </div>
</x-app-layout>
```

**The three non-negotiables in every Laravel form:**

| | Why |
|---|---|
| `@csrf` | Without it you get a **419 Page Expired** on submit. Laravel rejects any POST with no CSRF token. This is the #1 "why doesn't my form work" bug. |
| `old('field')` | On a validation failure Laravel redirects back — without `old()` the user retypes everything. |
| `@error('field')` | Shows the message. Silent validation failure looks like "the button does nothing." |

Also: `step="0.1"` on the rating input matches your `decimal:0,1` rule, so the browser
rejects `4.257` before it ever reaches the server. Belt *and* braces —
`type="number"` in HTML is a nicety, not security. The server rule is the real one.

> 📌 **What's actually in the repo:** this number input was later replaced by the
> `StarRating` Vue island (Part 4). It submits through a hidden input named `rating`,
> so the controller and its validation rules are completely unchanged.

#### `restaurants/edit.blade.php`

Same form, three differences:

```blade
<form method="POST" action="{{ route('restaurants.update', $restaurant) }}">
    @csrf
    @method('PUT')

    <input type="text" name="name" value="{{ old('name', $restaurant->name) }}">
    ...
```

1. **`@method('PUT')`** — browsers can only send GET and POST. This adds a hidden
   `_method` field that Laravel reads to route the request to `update()`. Leave it out
   and you'll hit `store()` instead, creating a duplicate.
2. **`old('name', $restaurant->name)`** — the second argument is the fallback. On first
   load you see the saved value; after a failed validation you see what was typed.
3. Action points at `restaurants.update` with the model.

#### `restaurants/show.blade.php` — restaurant + its dishes

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">{{ $restaurant->name }}</h2>
    </x-slot>

    <div class="max-w-4xl mx-auto p-6">
        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="p-4 mb-6 bg-white rounded shadow">
            @if ($restaurant->rating)
                <p class="text-lg">⭐ {{ $restaurant->rating }}</p>
            @endif

            @if ($restaurant->street_address)
                <p class="text-gray-600">
                    {{ $restaurant->street_address }},
                    {{ $restaurant->city }}, {{ $restaurant->state }}
                </p>
            @endif

            @if ($restaurant->notes)
                <p class="mt-2">{{ $restaurant->notes }}</p>
            @endif

            <div class="mt-4 flex gap-2">
                <a href="{{ route('restaurants.edit', $restaurant) }}"
                   class="px-3 py-1 border rounded">Edit</a>

                <form method="POST" action="{{ route('restaurants.destroy', $restaurant) }}"
                      onsubmit="return confirm('Delete {{ $restaurant->name }} and all its dishes?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1 border rounded text-red-600">
                        Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="flex justify-between items-center mb-2">
            <h3 class="font-bold text-lg">Dishes</h3>
            <a href="{{ route('restaurants.dishes.create', $restaurant) }}"
               class="px-3 py-1 bg-gray-800 text-white rounded">+ Add dish</a>
        </div>

        @forelse ($restaurant->dishes as $dish)
            <div class="p-4 mb-2 bg-white rounded shadow">
                <div class="flex justify-between">
                    <span class="font-semibold">{{ $dish->name }}</span>
                    <span>
                        @if ($dish->rating) ⭐ {{ $dish->rating }} @endif
                        {{ $dish->order_again ? '🔁 Order again' : '' }}
                    </span>
                </div>

                @if ($dish->notes)
                    <p class="text-sm text-gray-600 mt-1">{{ $dish->notes }}</p>
                @endif

                <div class="mt-2 flex gap-2 text-sm">
                    <a href="{{ route('dishes.edit', $dish) }}" class="underline">Edit</a>

                    <form method="POST" action="{{ route('dishes.destroy', $dish) }}"
                          onsubmit="return confirm('Remove {{ $dish->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="underline text-red-600">Remove</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-gray-500">No dishes logged here yet.</p>
        @endforelse
    </div>
</x-app-layout>
```

⚠️ **Watch the route names here.** Adding a dish is
`route('restaurants.dishes.create', $restaurant)` — nested, needs the restaurant.
Editing one is `route('dishes.edit', $dish)` — shallow, needs only the dish. That's
`shallow()` showing up in your Blade files.

⚠️ **A delete "button" has to be a `<form>`, not an `<a>`.** Links issue GET requests;
`destroy` needs DELETE. A GET-based delete also means any crawler or link-prefetcher
can wipe your data.

> 📌 **What's actually in the repo:** these two delete forms were later replaced by the
> `ConfirmButton` Vue island (see Part 4), which builds the same POST + `_method=DELETE`
> form itself and swaps `window.confirm()` for a real modal. The rule above still
> holds — the island renders a `<form>`, not a link.

#### `dishes/create.blade.php` — and the checkbox

```blade
<form method="POST" action="{{ route('restaurants.dishes.store', $restaurant) }}">
    @csrf

    <div>
        <label for="name" class="block font-medium">Dish name</label>
        <input type="text" name="name" id="name" value="{{ old('name') }}"
               class="w-full border rounded p-2" required>
        @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="rating" class="block font-medium">Rating (1–5)</label>
        <input type="number" name="rating" id="rating" value="{{ old('rating') }}"
               min="1" max="5" step="0.1" class="w-full border rounded p-2">
        @error('rating') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="notes" class="block font-medium">Notes</label>
        <textarea name="notes" id="notes" rows="3"
                  class="w-full border rounded p-2">{{ old('notes') }}</textarea>
    </div>

    <label class="flex items-center gap-2">
        <input type="hidden" name="order_again" value="0">
        <input type="checkbox" name="order_again" value="1"
               @checked(old('order_again'))>
        Order again?
    </label>

    <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded">Save</button>
</form>
```

**The hidden input before the checkbox is the trick.** Browsers send nothing for an
unchecked box. Pairing it with a hidden field of the same name means:

- unchecked → only the hidden field is sent → `order_again=0`
- checked → both are sent, and the later one wins → `order_again=1`

So the key is always present. Combined with `$request->boolean('order_again')` in the
controller, unchecking a box on the edit form actually saves as `false`.

`@checked(...)` is a Blade directive that prints `checked` when the expression is
truthy. On the **edit** form it becomes
`@checked(old('order_again', $dish->order_again))`.

---

### Step 15 — Tests for the controllers

These are more valuable than the model tests you already wrote, because they exercise
the thing that can actually leak data. Same `test()` style, same inline `->with()`
datasets.

```bash
php artisan make:test --pest RestaurantControllerTest
php artisan make:test --pest DishControllerTest
```

The five that matter most:

```php
<?php

use App\Models\Restaurant;
use App\Models\User;

test('guests are redirected to login', function (string $method, string $uri) {
    $this->$method($uri)->assertRedirect('/login');
})->with([
    ['get', '/restaurants'],
    ['get', '/restaurants/create'],
    ['post', '/restaurants'],
]);

test('a user only sees their own restaurants on the index', function () {
    $me = User::factory()->create();
    $mine = Restaurant::factory()->for($me)->create(['name' => 'Portillos']);
    $theirs = Restaurant::factory()->create(['name' => 'Somewhere Else']);

    $this->actingAs($me)
        ->get(route('restaurants.index'))
        ->assertSuccessful()
        ->assertSee($mine->name)
        ->assertDontSee($theirs->name);
});

test('a user cannot view another users restaurant', function () {
    $theirs = Restaurant::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('restaurants.show', $theirs))
        ->assertForbidden();
});

test('a restaurant is saved against the logged in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('restaurants.store'), [
            'name' => 'Portillos',
            'rating' => 4.5,
            'city' => 'Gilbert',
            'state' => 'AZ',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('restaurants', [
        'name' => 'Portillos',
        'user_id' => $user->id,
    ]);
});

test('invalid ratings are rejected', function (mixed $rating) {
    $this->actingAs(User::factory()->create())
        ->post(route('restaurants.store'), ['name' => 'Test', 'rating' => $rating])
        ->assertSessionHasErrors('rating');
})->with([
    'too high' => 12.5,
    'too low' => 0.5,
    'too precise' => 4.257,
    'not a number' => 'delicious',
]);
```

- **`actingAs($user)`** logs someone in for the request. Without it you're a guest.
- **`assertForbidden()`** is the 403 assertion — that's the one proving your policy works.
- **`assertSessionHasErrors('rating')`** is how you test validation failures.
- **`assertDatabaseHas`** checks the row landed with the right `user_id` — this is the
  test that catches a missing `$request->user()->` in `store`.

Run just these while iterating:

```bash
php artisan test --compact --filter=RestaurantController
```

---

### Build order

Don't do this breadth-first. Get one vertical slice working end to end, then repeat.

1. ☐ Commit, then install Breeze — register an account, confirm `/dashboard` loads
2. ☐ Add the routes; confirm with `php artisan route:list --except-vendor`
3. ☐ Generate both controllers and both policies
4. ☐ `RestaurantController@index` + `restaurants/index.blade.php` — should show the empty state
5. ☐ `create` + `store` + `restaurants/create.blade.php` — **now you can add a restaurant in a browser**
6. ☐ `show` + `restaurants/show.blade.php` (with the policy) — now you can read one
7. ☐ `edit` + `update` + `destroy`
8. ☐ Repeat 4–7 for dishes
9. ☐ Write the controller tests
10. ☐ `vendor/bin/pint --dirty` and `php artisan test --compact`

Step 5 is the milestone. Everything after it is the same four moves again.

---

### Things that will go wrong

| Symptom | Cause |
|---|---|
| **419 Page Expired** | missing `@csrf` |
| **404 on a form submit** | missing `@method('PUT')` / `@method('DELETE')` |
| **`Route [x] not defined`** | route-name typo — check `php artisan route:list` |
| **`Call to a member function restaurants() on null`** | route isn't inside the `auth` middleware group |
| **Form submits, nothing saves, no error** | field missing from `$fillable`, or missing from the validation rules (`$validated` only holds fields you wrote rules for) |
| **`Attempt to read property on null` in Blade** | you didn't pass the variable from the controller, or it's `null`-able and needs an `@if` |
| **Checkbox won't turn off** | no hidden input + no `$request->boolean()` |
| **Styles look broken after Breeze** | run `npm run dev` (or `npm run build`) |
| **`$this->authorize()` is undefined** | use `Gate::authorize()` — see Step 11 |

---

# Part 4: Vue as islands (replacing Alpine)

Breeze's Blade stack ships with **Alpine.js** for its three interactive bits: the user
dropdown, the mobile hamburger menu, and the delete-account modal. This app uses **Vue
instead**, mounted as *islands*.

## What "island" means here

The page is still server-rendered Blade. Vue does **not** own routing, layout, or data
fetching — there's no SPA, no Inertia, no client-side router. Blade renders HTML, and
Vue takes over a handful of small `<div>`s that need to be interactive.

```
┌─ Blade renders the whole page ────────────┐
│  <h2>Portillo's</h2>                      │
│  ┌─ Vue island ──────────┐                │
│  │  ★★★★☆  4.5  clear    │  ← interactive │
│  └───────────────────────┘                │
│  <p>ask for gravy on the side</p>         │
└───────────────────────────────────────────┘
```

**Why this and not Inertia?** Inertia replaces Blade entirely — every page becomes a
Vue file and the controllers return `Inertia::render()` instead of `view()`. That's a
fine architecture, but it throws away everything in Part 3. Islands let you keep
`view()`, `@csrf`, `old()`, `@error`, and Blade partials, and add Vue only where
plain HTML genuinely isn't enough.

---

### Step 16 — Swap the dependency

```bash
npm uninstall alpinejs
npm install --save-dev vue @vitejs/plugin-vue
```

`vite.config.js` — add the Vue plugin so `.vue` files get compiled:

```js
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
});
```

⚠️ **`tailwind.config.js` needs `.vue` in its `content` array**, or every Tailwind
class you write inside a Vue component gets stripped out of the production build and
your islands render unstyled:

```js
content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',   // ← this line
],
```

This is the single easiest thing to forget. Tailwind only generates classes it can
literally *see* in a file listed here.

---

### Step 17 — The mounter

This is the whole islands mechanism — about 15 lines in `resources/js/app.js`:

```js
import { createApp } from 'vue';

import AutoHide from './components/AutoHide.vue';
import ConfirmButton from './components/ConfirmButton.vue';
import NavBar from './components/NavBar.vue';
import StarRating from './components/StarRating.vue';

const islands = { AutoHide, ConfirmButton, NavBar, StarRating };

document.querySelectorAll('[data-vue]').forEach((el) => {
    const component = islands[el.dataset.vue];

    if (! component) {
        console.warn(`Unknown Vue island: "${el.dataset.vue}"`);

        return;
    }

    createApp(component, JSON.parse(el.dataset.props || '{}')).mount(el);
});
```

Read it as: *find every element with a `data-vue` attribute, look up the component by
that name, and start a Vue app on it using `data-props` as its props.*

`createApp(Component, props)` — the second argument is the **root props**. That's how
server data crosses into Vue.

From Blade, mounting an island is one line:

```blade
<div data-vue="StarRating" data-props="{{ json_encode([
    'name' => 'rating',
    'value' => old('rating', $restaurant?->rating),
]) }}"></div>
```

`{{ }}` escapes the JSON's quotes to `&quot;`, which is exactly right inside a
double-quoted HTML attribute — the browser un-escapes it and `JSON.parse` gets clean
JSON. **Don't reach for `{!! !!}` here.** That would inject raw quotes and break the
attribute, and it's an XSS hole the moment any of that data came from a user.

**To add a new island:** write the `.vue` file, add it to the `islands` object, mount
it from Blade. That's the whole workflow.

---

### Step 18 — The four components

All in `resources/js/components/`.

| Component | Replaces | Used by |
|---|---|---|
| `NavBar.vue` | Alpine dropdown + hamburger | `layouts/navigation.blade.php` |
| `ConfirmButton.vue` | Alpine `x-modal` **and** `window.confirm()` | restaurant/dish delete, profile delete-account |
| `AutoHide.vue` | Alpine `x-init="setTimeout(...)"` | the "Saved." flashes on the profile page |
| `StarRating.vue` | *(new)* the `<input type="number">` for ratings | both forms |

`resources/views/components/dropdown.blade.php` and `modal.blade.php` were **deleted** —
they were pure Alpine and nothing references them any more.

#### The pattern, using `StarRating.vue`

```vue
<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    name: { type: String, default: 'rating' },
    value: { type: [String, Number], default: null },
    max: { type: Number, default: 5 },
});

const rating = ref(props.value === null || props.value === '' ? null : Number(props.value));
const hovered = ref(null);

const displayed = computed(() => hovered.value ?? rating.value ?? 0);
</script>

<template>
    <div class="flex items-center gap-2">
        <input type="hidden" :name="name" :value="rating ?? ''">
        ...
    </div>
</template>
```

**The hidden input is the important line.** The island is inside a normal `<form>`, so
it has to participate in a normal form POST — Vue holds the value in `ref`, and the
hidden input is what actually gets submitted. Nothing about the controller changes:
it still reads `$request->validate([...])` and never knows Vue was involved.

That's the contract for every form island you write: **render a real input, keep the
name the server expects.**

⚠️ `props.value` arrives as a **string** (`"4.5"`), because the model casts `rating`
to `decimal:1`. Hence the `Number(...)`. This is the same cast you learned about in
Step 3 showing up again, this time in JavaScript.

#### `ConfirmButton.vue` and `<Teleport>`

The delete buttons live deep inside cards with `overflow-hidden` and stacking
contexts. A modal rendered there gets clipped. `<Teleport to="body">` renders the
modal markup at the end of `<body>` while keeping it logically inside the component:

```vue
<Teleport to="body">
    <Transition ...>
        <div v-if="open" class="fixed inset-0 z-50 ...">
```

It also builds its own form rather than wrapping one, because a `<form>` can't be
nested inside another `<form>`:

```vue
<form :action="action" method="POST">
    <input type="hidden" name="_token" :value="csrf">
    <input type="hidden" name="_method" :value="method">
```

Those two hidden inputs are what `@csrf` and `@method('DELETE')` compile to. Same
mechanism, written by hand because Blade isn't rendering this part. The token comes
from the layout's `<meta name="csrf-token">`:

```js
const csrf = document.querySelector('meta[name="csrf-token"]').content;
```

---

### Step 19 — Mounting from Blade

`layouts/navigation.blade.php` is now just a mount point:

```blade
@php
    $navLinks = [
        ['label' => __('Dashboard'), 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
        ['label' => __('Restaurants'), 'href' => route('restaurants.index'), 'active' => request()->routeIs('restaurants.*') || request()->routeIs('dishes.*')],
    ];
@endphp

<div data-vue="NavBar" data-props="{{ json_encode([
    'user' => ['name' => Auth::user()->name, 'email' => Auth::user()->email],
    'links' => $navLinks,
    'profileUrl' => route('profile.edit'),
    'logoutUrl' => route('logout'),
]) }}"></div>
```

Notice `route()` and `routeIs()` still run **on the server**. Vue receives finished
URLs and booleans. The Vue component never needs to know what a Laravel route is —
that's the division of labour that keeps islands simple.

And the delete button on `restaurants/show.blade.php`:

```blade
<div data-vue="ConfirmButton" data-props="{{ json_encode([
    'label' => 'Delete',
    'action' => route('restaurants.destroy', $restaurant),
    'variant' => 'button',
    'title' => "Delete {$restaurant->name}?",
    'message' => 'Every dish you logged here goes with it. This cannot be undone.',
    'confirmLabel' => 'Delete',
]) }}"></div>
```

---

### Step 20 — Testing islands

Your feature tests assert on **server-rendered HTML**, so they see the mount point,
not the mounted component — Vue never runs in a PHP test.

```php
test('the create form renders with a star rating island', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('restaurants.create'))
        ->assertSuccessful()
        ->assertSee('data-vue="StarRating"', false);
});
```

⚠️ **The `false` second argument matters.** By default `assertSee()` escapes what you
pass it before searching. `data-vue` is a literal attribute in the source, so you need
`false` to search the raw HTML.

Note the asymmetry: `data-vue="StarRating"` appears unescaped, but `data-props`
contains `&quot;` because Blade escaped the JSON. Assert on `data-vue`, not on the
props blob.

**This is the ceiling of what a PHP test can check about an island.** To test the
interactive behaviour — that clicking the third star sets `3`, that the modal opens —
you need a browser test (`visit()` in Pest 5). That's a reasonable next step, but the
server-side tests are what protect your *data*, and those are all still green.

---

### Things that will go wrong

| Symptom | Cause |
|---|---|
| Island renders as an empty gap | component not added to the `islands` object in `app.js` — check the browser console for the warning |
| Island appears but is unstyled | `./resources/js/**/*.vue` missing from `tailwind.config.js` `content` |
| `Unexpected token < in JSON` | you used `{!! !!}` instead of `{{ }}` for `data-props` |
| Nothing updates after editing a `.vue` | `npm run dev` isn't running, and the last `npm run build` is stale |
| Modal appears clipped or behind other content | missing `<Teleport to="body">` |
| 419 on a Vue-submitted form | forgot the `_token` hidden input inside the component |
| `Cannot read properties of null (reading 'content')` | the layout is missing `<meta name="csrf-token">` |

---

# Part 5: The shadcn-style design system

Breeze ships light-mode Tailwind with hardcoded colours — `bg-white`, `text-gray-700`,
`focus:ring-indigo-500`. That's fine until you want to change anything, at which point
you're find-and-replacing `gray-700` across thirty files.

This app uses the **shadcn/ui approach** instead: a fixed set of *semantic* colour
tokens defined once as CSS variables, with Tailwind classes that point at them.

---

### Step 21 — Tokens, not colours

`resources/css/app.css` defines the palette in one place:

```css
@layer base {
    :root {
        --background: 240 10% 3.9%;
        --foreground: 0 0% 98%;

        --card: 240 6% 7%;
        --card-foreground: 0 0% 98%;

        --primary: 0 0% 98%;
        --primary-foreground: 240 5.9% 10%;

        --muted: 240 3.7% 15.9%;
        --muted-foreground: 240 5% 64.9%;

        --destructive: 0 72% 51%;
        --border: 240 3.7% 15.9%;
        --ring: 240 4.9% 83.9%;
        --radius: 0.5rem;
    }

    * { @apply border-border; }
    body { @apply bg-background text-foreground antialiased; }
}
```

Then `tailwind.config.js` maps class names onto them:

```js
colors: {
    background: 'hsl(var(--background) / <alpha-value>)',
    foreground: 'hsl(var(--foreground) / <alpha-value>)',
    card: {
        DEFAULT: 'hsl(var(--card) / <alpha-value>)',
        foreground: 'hsl(var(--card-foreground) / <alpha-value>)',
    },
    // ...
}
```

So you write `bg-card text-card-foreground border-border` instead of
`bg-white text-gray-900 border-gray-200`.

#### Why the values are bare numbers like `240 10% 3.9%`

That's an HSL triplet **without** the `hsl()` wrapper. It looks odd, but it's what
makes opacity modifiers work. Because the variable holds only the channels, the config
can compose them:

```
hsl(var(--background) / <alpha-value>)
```

`<alpha-value>` is a Tailwind placeholder. When you write `bg-primary` it becomes `1`;
when you write `bg-destructive/10` it becomes `0.1`. Store the full `hsl(...)` string
in the variable instead and `bg-destructive/10` silently does nothing — you can't
inject an alpha into an already-complete colour function.

That's why the modals can use `bg-background/80` for the backdrop and the "Order again"
badge can use `bg-success/15` for a tinted chip, all from the same three tokens.

#### The `-foreground` naming convention

Tokens come in pairs: `--card` is a surface, `--card-foreground` is the text that goes
on it. Whenever you set a background, set its matching foreground:

```blade
<div class="bg-card text-card-foreground">      ✅ pair
<div class="bg-primary text-primary-foreground"> ✅ pair
<div class="bg-primary text-foreground">         ❌ white on white
```

`--primary` is nearly white and `--primary-foreground` is nearly black — on a dark
theme the "primary" button is a *light* button. If you forget the pairing you get an
invisible label.

---

### Step 22 — What changed

**Every** stock Tailwind colour is gone from `resources/views/` and `resources/js/`.
The mapping used:

| Before | After |
|---|---|
| `bg-gray-100` (page) | `bg-background` |
| `bg-white` (card) | `bg-card` + `border border-border` |
| `text-gray-900` / `800` / `700` | `text-foreground` |
| `text-gray-600` / `500` | `text-muted-foreground` |
| `bg-gray-800` (button) | `bg-primary` + `text-primary-foreground` |
| `text-red-600` | `text-destructive` |
| `focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2` | `focus:ring-1 focus:ring-ring` |
| `shadow` on cards | `border border-border` + `shadow-sm` |

That last row is the biggest *visual* difference between Breeze and shadcn. Breeze
separates cards from the page with a **drop shadow**; shadcn uses a **1px border** and
an almost-invisible shadow. Shadows read as "raised" and mostly disappear on a dark
background — borders stay crisp.

The Blade primitives in `resources/views/components/` were rewritten with shadcn's
actual class strings, so every form in the app inherits the look for free:

```blade
{{-- text-input.blade.php --}}
class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1
       text-sm text-foreground shadow-sm transition-colors
       placeholder:text-muted-foreground focus:border-ring focus:outline-none
       focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
```

The stock Laravel `welcome.blade.php` was replaced too — it carried a 37KB inlined
Tailwind v4 stylesheet as a no-build fallback, which fights the app's real CSS.

---

### Two things that could have bitten and didn't

**1. `@tailwindcss/forms` didn't need removing.** shadcn normally doesn't use it, and
the worry is that its input styling (`color: #2563eb`, `background-color: #fff`)
overrides your classes. It doesn't, because the current version wraps everything in
`:where()`:

```css
input:where([type=checkbox]),input:where([type=radio]){appearance:none;color:#2563eb;...}
```

`:where()` has **zero specificity**, so any single class beats it. The plugin is worth
keeping because `appearance: none` is what makes a checkbox stylable at all — without
it, `bg-transparent border-input` on a checkbox does nothing on most browsers.

**2. Tailwind still needs to see your `.vue` files.** Same trap as Part 4, and it bites
twice as hard now — a missing `content` glob means island classes vanish from the
build while the Blade ones survive, so you get a half-styled page rather than an
obviously broken one.

---

### Changing the whole look

Everything routes through those variables, so a redesign is one file. Lighter theme:

```css
:root {
    --background: 0 0% 100%;
    --foreground: 240 10% 3.9%;
    --card: 0 0% 100%;
    --card-foreground: 240 10% 3.9%;
    --primary: 240 5.9% 10%;
    --primary-foreground: 0 0% 98%;
    --muted-foreground: 240 3.8% 46.1%;
    --border: 240 5.9% 90%;
}
```

Rounder corners: change `--radius`. A brand colour on buttons: change `--primary` and
`--primary-foreground`. No view files get touched.

**If you ever want a light/dark toggle**, that's the payoff for this structure: add
`darkMode: 'class'` to the config, move the current values under `.dark { }`, put the
light values on `:root`, and flip a class on `<html>`. Every component follows
automatically because none of them name a colour.

---
---

# 🔖 TODO (later): Tags

**Do not build this until Parts 1 and 2 are done and working.** This section exists so
the decision is already made when you get there — and so you don't accidentally build
something now that makes it harder later.

## The question: restaurants, dishes, or both?

**Recommendation: tag restaurants first. Build the `tags` table so dishes can join
later without a rewrite.**

Here's the reasoning.

### Restaurant tags and dish tags are different vocabularies

Write out the tags you'd actually use and the split is obvious:

| Feels like a **restaurant** tag | Feels like a **dish** tag |
|---|---|
| Mexican, Thai, BBQ | spicy, vegetarian, gluten free |
| cheap eats, splurge | shareable, huge portion |
| date night, quick lunch | too sweet, greasy |
| patio, drive-thru, takeout | comes with fries |

There's a little overlap ("vegetarian" fits both), but mostly these are two different
lists. That's a clue that they're two different features, not one.

### Why restaurants win the first round

1. **It matches the question you actually ask.** The thing you'll open this app to
   answer is *"where should we eat tonight?"* — that's a restaurant-level question.
   "Show me my Mexican places rated 4+" is the killer feature. "Show me all spicy
   dishes" is a nice-to-have.
2. **Dishes already have `notes`.** "Very spicy, ask for mild" fits fine in the notes
   field you already built. Tags only earn their complexity when you need to *filter
   by them*, and you'll want to filter restaurants long before dishes.
3. **Cuisine is genuinely a property of the place.** Portillo's is "Chicago style" as
   a restaurant. Tagging the Italian Beef "Chicago style" and the Cake Shake
   "Chicago style" separately is just repeating yourself.

So: restaurants first. Add dish tags later only if you find yourself wanting them.

## ⚠️ The decision that actually matters: pivot vs. polymorphic

This is the part where you can paint yourself into a corner, so read this bit even
though you're not building it yet.

When you eventually want tags on *both* models, there are two ways to structure it:

**Option A — a shared `tags` table plus one pivot table per model.** ✅ Do this one.

```
tags              restaurant_tag           dish_tag
----              --------------           --------
id                restaurant_id            dish_id
user_id           tag_id                   tag_id
name
```

**Option B — polymorphic ("taggables"), one structure that handles any model.**
❌ Skip this one.

```
tags              taggables
----              ---------
id                tag_id
name              taggable_id      <- could be a restaurant OR a dish
                  taggable_type    <- "App\Models\Restaurant" or "App\Models\Dish"
```

Option B *looks* smarter and it's what a lot of tutorials reach for. Avoid it here:

- **You lose foreign keys.** `taggable_id` points at two different tables, so the
  database can't enforce a constraint on it. No `cascadeOnDelete` — you have to
  remember to clean up orphaned rows by hand, forever.
- **Queries get uglier**, and so do the error messages when you get one wrong.
- **The flexibility buys you nothing.** Polymorphic pays off at five or six taggable
  models. You have two, and you're only sure about one.

Option A means adding dish tags later is a single ~15-line migration for `dish_tag`
plus a `tags()` method on `Dish`. That's a small enough cost that "flexibility" isn't
worth paying for up front.

**So the only thing you need to get right now: put tags in their own `tags` table
from the start, not as a comma-separated string column on `restaurants`.** A
`tags` VARCHAR column is the actual trap — it can't be indexed usefully, `LIKE
'%thai%'` also matches "Thai-adjacent", and renaming a tag means rewriting every row.

## 🔒 The gotcha specific to your app: tags need a `user_id` too

Everything in this app is per-user. Tags are no exception.

If `tags` has no `user_id`, then the moment another person creates a "Mexican" tag,
it shows up in *your* autocomplete — and your tag list slowly fills with strangers'
vocabulary. Worse, if two users both type "Date Night" you now have a shared row that
neither of them owns.

So `tags` gets `user_id` just like `restaurants` does, and a unique constraint on the
**pair** so one user can't create "Mexican" twice:

```php
$table->unique(['user_id', 'name']);
```

This does mean the string "Mexican" is stored once per user who uses it. That's
correct here — it's the same tradeoff as everything else in your per-user design.

## The schema, for when you're ready

```php
// create_tags_table
Schema::create('tags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->timestamps();

    $table->unique(['user_id', 'name']);
});

// create_restaurant_tag_table
Schema::create('restaurant_tag', function (Blueprint $table) {
    $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

    $table->primary(['restaurant_id', 'tag_id']);
});
```

**Pivot table naming is a convention you have to follow**, or Laravel won't find it:
the two model names, **singular**, **alphabetical**, snake_cased. `restaurant_tag`
(r before t), and later `dish_tag` (d before t). Not `tags_restaurants`, not
`restaurant_tags`.

The pivot has no `id` and no timestamps — it exists only to connect two rows. The
composite primary key doubles as "you can't attach the same tag twice."

Then on the models:

```php
// app/Models/Restaurant.php
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class);
}

// app/Models/Tag.php
public function restaurants(): BelongsToMany
{
    return $this->belongsToMany(Restaurant::class);
}
```

`belongsToMany` on **both** sides — that's what makes it many-to-many. Compare with
`hasMany`/`belongsTo`, where the two sides are different.

Attaching and querying:

```php
// Attach / detach / replace-the-whole-set
$restaurant->tags()->attach($tag->id);
$restaurant->tags()->detach($tag->id);
$restaurant->tags()->sync([$mexican->id, $cheapEats->id]);  // most useful for a form

// "Show me my Mexican places, 4 stars or better"
$request->user()->restaurants()
    ->whereHas('tags', fn ($query) => $query->where('name', 'Mexican'))
    ->where('rating', '>=', 4)
    ->with('tags')
    ->get();
```

`sync()` is the one you'll want behind a tag-picker UI — hand it the full array of
tag IDs and it works out what to add and remove.

And remember the N+1 rule applies here too: `->with('tags')` when you're listing
restaurants, or you'll fire one query per restaurant to fetch its tags.

## If you later want dish tags

Add exactly this, and nothing else changes:

```php
Schema::create('dish_tag', function (Blueprint $table) {
    $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

    $table->primary(['dish_id', 'tag_id']);
});
```

```php
// app/Models/Dish.php
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class);
}
```

Same `tags` table, so "vegetarian" is one row that both a restaurant and a dish can
point at, and your autocomplete stays a single list.

---
---

# 🔖 Recipes

**Status: shipped.** The migration, the `Recipe` model, `RecipeFactory`, the routes, the
`resources/views/recipes/` views, `RecipeController` (full resource) and `RecipePolicy`
are all in, covered by `tests/Feature/RecipeControllerTest.php` and
`RecipePolicyTest.php`. The decisions below are kept as the record of *why* it looks the
way it does — not as a to-do list.

Recipes are the third top-level thing in the app: restaurants you go to, and recipes you
cook at home. The important structural point is that **recipes do not hang off
restaurants**. A `Dish` belongs to a `Restaurant` because a dish only exists in the
context of a place. A recipe has no such parent — it belongs directly to you. So
`recipes` gets a `user_id` and its own top-level route, exactly like `restaurants` does,
and *unlike* `dishes`.

## The question: one markdown field or two?

You'd flagged that if you're adding a markdown editor anyway, ingredients and
how-to-cook could collapse into a single body field, since markdown already gives you
headings and lists.

**Recommendation: keep them as two `text` columns, both markdown.**

Three reasons, in order of how much they'll actually bite you.

### 1. Mobile cooking is the deciding factor

This is the one that matters. At the stove you bounce between *"what do I need"* and
*"what's step 4."* With one blob, every trip back to step 4 scrolls past the entire
ingredient list. With two columns, the show page can render two independent
sections — so ingredients can be collapsed while you cook and pinned open while you
shop. You can't offer that on a single field without parsing headings back out of the
markdown, which is exactly the kind of string-sniffing you don't want in a Blade view.

### 2. Splitting later is expensive; merging later is free

If you go one-field and later want a shopping list, ingredient search, or "what can I
make with what's in the fridge," you're writing a markdown-heading parser and
backfilling every existing row through it. Going the other direction — two columns into
one — is string concatenation. **When changing your mind in one direction costs a
migration and a backfill, and the other direction costs a `.` operator, start on the
side that's cheap to leave.**

### 3. You lose nothing in the editor

Headings, bold, and nested lists all still work *inside* each field. It's the same
editor component rendered twice. The entire cost of this decision is one extra
`<textarea>` on the create form.

## The schema

✅ Shipped as written, in `create_recipes_table`.

```php
Schema::create('recipes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->decimal('rating', 2, 1)->nullable();
    $table->text('ingredients')->nullable();   // markdown
    $table->text('instructions')->nullable();  // markdown
    $table->string('source_url')->nullable();
    $table->unsignedSmallInteger('total_minutes')->nullable();
    $table->unsignedTinyInteger('servings')->nullable();
    $table->boolean('make_again')->default(false);
    $table->timestamps();
});
```

The non-obvious columns:

| Column | Why it's there |
|---|---|
| `user_id` | Recipes are top-level and user-owned, like `restaurants`. Needed for `$request->user()->recipes()` and for the policy. |
| `rating` `decimal(2,1)` | An exact mirror of restaurants and dishes, so the existing `StarRating` island and the `multiple_of:0.5` rule drop straight in with no new code. |
| `source_url` | The highest-value cheap column here. Almost every recipe comes from a site or a video, and you'll want to re-check the original. Nullable string, zero data-entry friction. |
| `total_minutes` | Deliberately **one** field, not `prep_minutes` + `cook_minutes`. "How long is this" is the question you'll actually filter on, and two number inputs on a phone is friction you won't pay. |
| `servings` | `unsignedTinyInteger` is plenty (max 255) and signals intent better than `integer`. |
| `make_again` | Mirrors `dishes.order_again` on purpose — same vocabulary for the same idea keeps the app coherent. |

**Deliberately skipped: `notes`.** Restaurants and dishes both have one, so leaving it
off is a conscious break in symmetry. It overlaps almost completely with the
`instructions` body, and three text fields on one mobile form is where the entry form
starts feeling like a chore. "Last time I doubled the garlic" goes at the bottom of the
instructions.

**Deferred: `last_cooked_at`.** Genuinely nice — it unlocks a "haven't made this in a
while" view. But it needs a UI affordance to set it, which makes it its own small
feature rather than a free column. Add it when you build that view, not before.

## ✅ The model is `Recipe`, not `Recipes`

Done — model, factory, controller type hints, policy import, and the seeder all moved
together, with `git mv` so history follows the files.

The scaffold generated `Recipes` (plural). Laravel's convention is **singular model,
plural table** — `Recipe` → `recipes`, the same as `Restaurant` → `restaurants`. The old
name resolved the right table by luck (`Str::pluralStudly('Recipes')` is already
`'Recipes'`), so nothing broke loudly, which is exactly what made it worth fixing before
more code leaned on it.

Two things the rename dragged along, both non-optional:

**The factory had to move too.** Laravel resolves factories by naming convention:
`Recipe` → `Database\Factories\RecipeFactory`. Leaving it as `RecipesFactory` would have
broken `Recipe::factory()`, and therefore `DatabaseSeeder`, the moment the model was
renamed.

**It silently fixed policy discovery.** Laravel guesses `App\Policies\{Model}Policy`, so
under the old name it was hunting for `RecipesPolicy`. `RecipePolicy` would have been
ignored completely and every `Gate::authorize()` call would have failed closed with no
error explaining why. Worth remembering the next time a policy "isn't firing" — check
the model name before you debug the policy.

The trait is typed the way the other models do it:

```php
/** @use HasFactory<RecipeFactory> */
use HasFactory;
```

## 🔒 Recipes need a policy

`RestaurantController` gates every non-index action with `Gate::authorize(...)`. Recipes
need the same, or any logged-in user can read anyone else's. This is the same per-user
rule that drives `user_id` on `tags` — nothing in this app is global.

Scoping `index` through `$request->user()->recipes()` handles the listing; the policy
covers `show`, `edit`, `update`, and `destroy`.

## Validation

✅ Shipped in `RecipeController::rules()`, following the shape of
`RestaurantController::rules()`:

```php
[
    'name' => ['required', 'string', 'max:255'],
    'rating' => ['nullable', 'numeric', 'between:1,5', 'multiple_of:0.5'],
    'ingredients' => ['nullable', 'string'],
    'instructions' => ['nullable', 'string'],
    'source_url' => ['nullable', 'url:http,https', 'max:255'],
    'total_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
    'servings' => ['nullable', 'integer', 'min:1', 'max:255'],
    'make_again' => ['boolean'],
]
```

**⚠️ `source_url` carries two rules that both matter, for different reasons.**

`max:255` is there because the migration shipped `$table->string('source_url')`, which is
255 characters. Validation *looser* than the column is the one direction that actually
breaks — a long URL with tracking params would pass the rule and then blow up at the
database. If you ever want longer URLs, widen the column *first*, then raise the rule.

`url:http,https` is the security half, and it is not optional. The show page renders the
value straight into `href="{{ $recipe->source_url }}"`. Blade escapes entities but it
does **not** escape the *scheme*, so with a plain `'string'` rule a saved
`javascript:alert(document.cookie)` executes in your own origin the moment you click the
link — and it sails through the form's `type="url"` check on the way in, because the
browser considers it a perfectly valid absolute URL. Laravel 13's bare `url` rule happens
to reject `javascript:` and `data:` already, but naming the two schemes you actually want
is what makes the intent survive a framework upgrade. `tests/Feature/RecipeControllerTest.php`
pins both halves.

Keep the `rating` line character-for-character identical to the other two controllers.
The half-star rule is enforced in three places now, and the day they drift is the day a
seeded recipe fails to save.

## Rendering the markdown

**Good news: it's already installed.** `league/commonmark` is a hard requirement of
`laravel/framework`, so `Str::markdown()` works today with no change to
`composer.json`.

Two rules:

**Don't store rendered HTML.** Render on read. A `instructions_html` column is just a
cache you now have to remember to invalidate on every edit.

**⚠️ Strip raw HTML on the way out.** CommonMark passes raw HTML through by default, so
pasting a block from a recipe site can inject markup into your page. Single-user or not,
pass the options:

```php
Str::markdown($this->instructions, [
    'html_input' => 'strip',
    'allow_unsafe_links' => false,
]);
```

An accessor on the model is a reasonable home for that, so the Blade view stays a plain
`{!! $recipe->instructions_html !!}` and there's exactly one place the sanitising
options live.

## What to watch out for when you build the editor

**Skip the WYSIWYG.** A full toolbar is miserable on a phone and it fights markdown
rather than helping. The pattern that actually works on mobile, in rough order of
value-per-line-of-code:

1. **Auto-continue lists on Enter.** Type `- flour`, hit Enter, and `- ` is already
   there. This single behaviour is most of what makes typing an ingredient list on a
   phone bearable, and it's about ten lines.
2. **A Write / Preview toggle.** Fits the existing Vue-island pattern from Part 4 — one
   `data-vue="MarkdownField"` mount, used twice on the form.
3. **A tiny sticky button row** that inserts at the cursor: bold, heading, list item.
   Three buttons, not twelve.

**The styling snag, and how it was settled.** `@tailwindcss/typography` is still not
installed — `tailwind.config.js` only loads `forms` — so rendered markdown would arrive
unstyled, with headings that don't look like headings. Two ways out were on the table:

- Add the plugin. That's a dependency change, so it needs a decision rather than a
  drive-by `npm install`.
- Hand-write the rules for `h1–h3 / p / ul / ol / li / strong / em / blockquote / code / a`.

✅ **The hand-rolled option shipped.** `resources/views/recipes/show.blade.php` builds a
`$prose` string of Tailwind arbitrary variants (`[&_h2]:…`, `[&_ul]:…`) in a `@php` block
at the top of the file and applies it to both rendered bodies. `package.json` stays
untouched, which keeps this consistent with how the rest of the design system in Part 5
was built.

The one thing to know if you edit it: those classes only survive a production build
because `tailwind.config.js` scans `resources/views/**/*.blade.php`, and the string is
written as `.`-concatenated literals rather than interpolation. Build a class name
dynamically there and Tailwind won't see it — it'll work in dev and vanish in
`npm run build`.

## Build order

Get recipes usable first, make them pretty second:

1. ✅ Migration, `Recipe` model + factory, `RecipePolicy`.
2. ✅ `RecipeController` (full resource) and `Route::resource('recipes', ...)` inside the
   `auth` group.
3. ✅ Index / show / create / edit views, reusing `restaurants/partials/form.blade.php` as
   the template — plain `<textarea>`s for now.
4. ✅ Server-side markdown rendering on the show page.
5. ⬜ *Then* the editor island.

Steps 1–4 shipped, which is a working recipe book you can put real data into. Step 5 is a
comfort upgrade you'll design better once you've typed a few recipes in the raw.

## Explicitly out of scope: photos

No image columns anywhere in the above. Photos are their own epic covering recipes,
dishes, *and* restaurants together — storage driver, upload validation, resizing,
and a rethink of the Docker volume layout on the Pi (`docs/deployment.md`). Doing it
once across all three models is much less work than doing it three times.

# Part 6: Shipping it to the Pi

The step-by-step is in [`docs/deployment.md`](deployment.md) — prerequisites, deploy
keys, `tailscale serve`, backups, and troubleshooting. This section is the *why*
behind the shape it lands in, which is the part that gets forgotten first.

## The shape

```
your laptop / phone  ──tailnet──►  tailscale serve (TLS)  ──►  127.0.0.1:8080
                                                                     │
                                                          one FrankenPHP container
                                                                     │
                                                            SQLite on a bind mount
```

One container, one file of state. No nginx, no php-fpm, no database server, no
queue worker, no Redis.

## Why each piece is absent

**No database server.** Sessions, cache, and queue all point at SQLite, so the
database is a file the app already knows how to open. For one user that deletes a
whole container. `DB_JOURNAL_MODE=WAL` is set so reads don't block behind writes —
without it a long-running server serializes every request against every write.

**No nginx + php-fpm.** FrankenPHP is Caddy with PHP embedded. The usual two-process
dance with a socket between them collapses into one process.

**No queue worker.** There is no `app/Jobs` directory and nothing dispatches or
schedules anything. The moment that stops being true, `compose.yaml` needs a second
service running `queue:work` — `QUEUE_CONNECTION=database` means jobs will pile up
silently in a table with nobody reading it, which fails quietly rather than loudly.

## Two traps that cost real time

**Never bind-mount over `/app/database`.** That directory is *code* — migrations,
factories, seeders. Mounting a volume there hides them, and the container boots
reporting "No migrations found" while cheerfully creating an empty database with
only a `migrations` table in it. This is why `DB_DATABASE` points at
`/var/lib/lettuce-eat/database.sqlite`, outside the app tree entirely. `/data` is
taken too — Caddy uses it for its own storage inside the FrankenPHP image.

**`bootstrap/cache/*.php` must stay in `.dockerignore`.** Those manifests are
generated on a machine where require-dev packages exist. The image installs
`--no-dev`, so shipping them makes the container boot referencing
`Laravel\Boost\BoostServiceProvider`, which isn't in there.

Both of these are recorded in `.ai/rules/general.md` so the next session doesn't
rediscover them.

## The proxy dependency

`bootstrap/app.php` calls `trustProxies(at: '*')`. Tailscale terminates TLS and
forwards plain HTTP to the container, so without this Laravel generates `http://`
URLs on an `https://` page — which shows up as a login redirect loop and missing
CSS, not as an obvious error. `tests/Feature/TrustedProxyTest.php` fails if that
call is removed, which is the only reason it won't quietly regress.

The `*` is safe *here specifically* because the only thing that can reach the
container is `tailscale serve` on loopback. It would not be safe on a public box.

## `serve`, never `funnel`

`tailscale serve` publishes to your tailnet. `tailscale funnel` publishes to the
public internet. One character of muscle memory apart, and the whole reason this
app is comfortable being single-user with open registration during setup.
