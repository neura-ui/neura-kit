<script>
    (function() {
        if (!document.head) return;
        
        const locale = @js(app()->getLocale());
        const fallbackLocale = @js(config('app.fallback_locale', 'en'));
        
        function ensureMetaTag(name, content) {
            let meta = document.querySelector(`meta[name="${name}"]`);
            if (!meta) {
                meta = document.createElement('meta');
                meta.setAttribute('name', name);
                document.head.appendChild(meta);
            }
            meta.setAttribute('content', content);
        }
        
        ensureMetaTag('app-locale', locale);
        ensureMetaTag('app-fallback-locale', fallbackLocale);
    })();
</script>

@if(class_exists(\Livewire\Livewire::class))
    @livewire('neura-kit.modal-manager')
@endif

<neura::dialog-manager />
<neura::toast />