@props([
    'images' => [],
    'columns' => 5,
    'gap' => 'gap-5',
    'aspectRatio' => 'aspect-[5/6] lg:aspect-[2/3] xl:aspect-[3/4]',
    'keyboardNavigation' => true,
])

@php
    use Illuminate\Support\Arr;

    $galleryImages = collect($images ?? []);
    $columnClasses = match($columns) {
        2 => 'grid-cols-2',
        3 => 'grid-cols-3',
        4 => 'grid-cols-4',
        5 => 'grid-cols-2 lg:grid-cols-5',
        6 => 'grid-cols-3 lg:grid-cols-6',
        default => 'grid-cols-2 lg:grid-cols-5',
    };
@endphp

<div 
    x-data="{
        imageGalleryOpened: false,
        imageGalleryActiveUrl: null,
        imageGalleryImageIndex: null,
        imageGallery: @js($galleryImages->toArray()),
        
        imageGalleryOpen(event) {
            this.imageGalleryImageIndex = event.target.dataset.index;
            this.imageGalleryActiveUrl = event.target.src;
            this.imageGalleryOpened = true;
        },
        
        imageGalleryClose() {
            this.imageGalleryOpened = false;
            setTimeout(() => this.imageGalleryActiveUrl = null, 300);
        },
        
        imageGalleryNext() {
            if (this.imageGallery.length === 0) return;
            this.imageGalleryImageIndex = (this.imageGalleryImageIndex == this.imageGallery.length) ? 1 : (parseInt(this.imageGalleryImageIndex) + 1);
            const nextImage = this.$refs.gallery.querySelector('[data-index=\'' + this.imageGalleryImageIndex + '\']');
            if (nextImage) {
                this.imageGalleryActiveUrl = nextImage.src;
            }
        },
        
        imageGalleryPrev() {
            if (this.imageGallery.length === 0) return;
            this.imageGalleryImageIndex = (this.imageGalleryImageIndex == 1) ? this.imageGallery.length : (parseInt(this.imageGalleryImageIndex) - 1);
            const prevImage = this.$refs.gallery.querySelector('[data-index=\'' + this.imageGalleryImageIndex + '\']');
            if (prevImage) {
                this.imageGalleryActiveUrl = prevImage.src;
            }
        }
    }"
    @if($keyboardNavigation)
        @image-gallery-next.window="imageGalleryNext()"
        @image-gallery-prev.window="imageGalleryPrev()"
        @keyup.right.window="imageGalleryNext()"
        @keyup.left.window="imageGalleryPrev()"
    @endif
    {{ $attributes->class(['w-full select-none']) }}
>
    @if($galleryImages->isEmpty())
        <div class="rounded-lg border border-dashed border-neutral-200 dark:border-neutral-800 px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
            No images available
        </div>
    @else
        <div class="mx-auto max-w-6xl">
            <ul x-ref="gallery" class="grid {{ $columnClasses }} {{ $gap }}">
                <template x-for="(image, index) in imageGallery" :key="index">
                    <li>
                        <img 
                            x-on:click="imageGalleryOpen($event)" 
                            :src="image.photo || image.url || image.src" 
                            :alt="image.alt || image.caption || ''" 
                            :data-index="index + 1" 
                            class="object-cover select-none w-full h-auto bg-neutral-200 dark:bg-neutral-800 rounded-lg cursor-zoom-in transition-transform hover:scale-105 {{ $aspectRatio }}"
                        />
                    </li>
                </template>
            </ul>
        </div>
    @endif

    <template x-teleport="body">
        <div 
            x-show="imageGalleryOpened" 
            x-transition:enter="transition ease-in-out duration-300" 
            x-transition:enter-start="opacity-0" 
            x-transition:leave="transition ease-in-out duration-300" 
            x-transition:leave-end="opacity-0" 
            @click="imageGalleryClose" 
            @keydown.window.escape="imageGalleryClose" 
            x-trap.inert.noscroll="imageGalleryOpened"
            class="fixed inset-0 z-99 flex items-center justify-center bg-black/50 dark:bg-black/70 backdrop-blur-sm select-none cursor-zoom-out"
            x-cloak
        >
            <div class="flex relative justify-center items-center w-11/12 xl:w-4/5 h-11/12"> 
                <button
                    type="button"
                    @click.stop="$dispatch('image-gallery-prev')"
                    class="flex absolute left-0 justify-center items-center w-14 h-14 text-white rounded-full translate-x-10 cursor-pointer xl:-translate-x-24 2xl:-translate-x-32 bg-white/10 hover:bg-white/20 transition-colors"
                    x-bind:aria-label="window.t('previousImage')"
                >
                    <neura::icon name="chevron-left" class="w-6 h-6" />
                </button>

                <img 
                    x-show="imageGalleryOpened" 
                    x-transition:enter="transition ease-in-out duration-300" 
                    x-transition:enter-start="opacity-0 transform scale-50" 
                    x-transition:leave="transition ease-in-out duration-300" 
                    x-transition:leave-end="opacity-0 transform scale-50" 
                    class="object-contain object-center w-full h-full select-none cursor-zoom-out" 
                    :src="imageGalleryActiveUrl" 
                    alt=""
                    style="display: none;"
                />

                <button
                    type="button"
                    @click.stop="$dispatch('image-gallery-next')"
                    class="flex absolute right-0 justify-center items-center w-14 h-14 text-white rounded-full -translate-x-10 cursor-pointer xl:translate-x-24 2xl:translate-x-32 bg-white/10 hover:bg-white/20 transition-colors"
                    x-bind:aria-label="window.t('nextImage')"
                >
                    <neura::icon name="chevron-right" class="w-6 h-6" />
                </button>
            </div>
        </div>
    </template>
</div>

