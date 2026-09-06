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
