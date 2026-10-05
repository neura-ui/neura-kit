@php
    use Neura\Kit\Support\PackResolver;

    $toastRoundedClass = PackResolver::rounded(neura_config('toast', 'rounded'));
    $toastShadowClass = PackResolver::shadow(neura_config('toast', 'shadow'));
@endphp

<article
    class="nk-toast-card {{ $toastRoundedClass }} {{ $toastShadowClass }}"
    data-toast
    tabindex="-1"
    x-bind:data-id="toast.id"
    x-bind:data-type="toast.type"
    x-init="mountCard($el)"
    x-on:pointerdown="down($event, toast.id)"
    x-on:pointermove="movePointer($event)"
    x-on:pointerup="up($event)"
    x-on:pointercancel="up($event)"
>
    <span class="sr-only" x-text="labelFor(toast.type) + ': '"></span>
    <span class="nk-toast-icon" aria-hidden="true">
        <svg x-show="toast.type === 'info'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
        <svg x-show="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <svg x-show="toast.type === 'error'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
        <svg x-show="toast.type === 'warning'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
    </span>
    <p class="nk-toast-copy" x-text="toast.content"></p>
    <button
        type="button"
        class="nk-toast-close"
        x-on:click.stop="dismiss(toast.id)"
        x-bind:aria-label="(window.t ? window.t('closeNotification', { type: labelFor(toast.type).toLowerCase() }) : 'Dismiss')"
    >
        <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>
    <div class="nk-toast-timer" x-show="toast.showProgress !== false && toast.duration > 0" aria-hidden="true">
        <span></span>
    </div>
</article>
