<form method="POST" action="{{ route('locale.switch', ['locale' => app()->getLocale() === 'dv' ? 'en' : 'dv']) }}">
    @csrf

    <button type="submit" class="fi-btn fi-btn-size-sm fi-btn-color-gray fi-color-gray locale-switch">
        {{ app()->getLocale() === 'dv' ? 'EN' : 'DV' }}
    </button>
</form>

<script>
    document.documentElement.lang = @js(app()->getLocale());
    document.documentElement.dir = @js(app()->getLocale() === 'dv' ? 'rtl' : 'ltr');
</script>
