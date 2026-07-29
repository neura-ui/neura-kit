{{--
    Horizontal ruler.

    Interactive: the two margin markers set the page's left and right margins,
    and the top marker sets the first-line indent of the paragraph at the caret.
    Tick marks are one per half inch, numbered on the whole inches.

    The whole bar carries the same `zoom` as the pages so its coordinate space
    lines up with the sheet below it, pixel for pixel.
--}}

<div
    class="nk-ruler"
    x-ref="ruler"
    x-bind:style="`--nk-zoom: ${zoom / 100}; width: ${pageWidth()}px`"
    role="group"
    aria-label="Page ruler"
>
    {{-- Shaded bands over the margin areas --}}
    <div class="nk-ruler-margin" x-bind:style="`left: 0; width: ${margins.left}px`"></div>
    <div class="nk-ruler-margin" x-bind:style="`right: 0; width: ${margins.right}px`"></div>

    {{-- Tick marks --}}
    <template x-for="tick in rulerTicks()" :key="tick.x">
        <div class="nk-ruler-tick" :class="tick.major ? 'is-major' : ''" x-bind:style="`left: ${tick.x}px`">
            <span x-show="tick.label !== null" x-text="tick.label"></span>
        </div>
    </template>

    {{-- First-line indent (top marker) --}}
    <button
        type="button"
        class="nk-ruler-handle nk-ruler-handle-indent"
        x-bind:style="`left: ${margins.left + firstLineIndent()}px`"
        x-on:pointerdown="startRulerDrag('firstLine', $event)"
        title="First line indent"
        aria-label="First line indent"
    ></button>

    {{-- Left margin (bottom marker) --}}
    <button
        type="button"
        class="nk-ruler-handle nk-ruler-handle-left"
        x-bind:style="`left: ${margins.left}px`"
        x-on:pointerdown="startRulerDrag('left', $event)"
        title="Left margin"
        aria-label="Left margin"
    ></button>

    {{-- Right margin --}}
    <button
        type="button"
        class="nk-ruler-handle nk-ruler-handle-right"
        x-bind:style="`left: ${pageWidth() - margins.right}px`"
        x-on:pointerdown="startRulerDrag('right', $event)"
        title="Right margin"
        aria-label="Right margin"
    ></button>
</div>
