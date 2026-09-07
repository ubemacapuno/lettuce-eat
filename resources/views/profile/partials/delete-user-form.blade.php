<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-foreground">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-muted-foreground">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
        </p>
    </header>

    <div data-vue="ConfirmButton" data-props="{{ json_encode([
        'label' => __('Delete Account'),
        'action' => route('profile.destroy'),
        'method' => 'delete',
        'variant' => 'danger',
        'title' => __('Are you sure you want to delete your account?'),
        'message' => __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.'),
        'confirmLabel' => __('Delete Account'),
        'requiresPassword' => true,
        'passwordError' => $errors->userDeletion->first('password'),
        'startOpen' => $errors->userDeletion->isNotEmpty(),
    ]) }}"></div>
</section>
