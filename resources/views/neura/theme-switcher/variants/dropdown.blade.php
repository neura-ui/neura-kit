@aware(['darkIcon'=>'moon','lightIcon'=>'sun','systemIcon'=>'computer-desktop','iconVariant' => "mini"])
<neura::dropdown>
    <x-slot:button
        class="cursor-pointer hover:opacity-80 transition"
        role="button"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="theme-menu"
    >
        <neura::icon :name="$darkIcon" :variant="$iconVariant" class="dark:hidden inline-flex"/>
        <neura::icon :name="$lightIcon" :variant="$iconVariant" class="hidden dark:inline-flex"/>
    </x-slot:button>

    <x-slot:menu>
        <neura::dropdown.item
            :icon="$lightIcon"
            :iconVariant="$iconVariant"
            x-on:click="$theme.setLight();close()"
            x-bind:class="{'dark:bg-white/5 bg-black/5' : $theme.isLight }"
        >
            light
        </neura::dropdown.item>

        <neura::dropdown.item
            :icon="$darkIcon"
            :iconVariant="$iconVariant"
            x-on:click="$theme.setDark();close()"
            x-bind:class="{'dark:bg-white/5 bg-black/5' : $theme.isDark }"
        >
            dark
        </neura::dropdown.item>

        <neura::dropdown.item
            :icon="$systemIcon"
            :iconVariant="$iconVariant"
            x-on:click="$theme.setSystem();close()"
            x-bind:class="{'dark:bg-white/5 bg-black/5' : $theme.isSystem }"
        >
            system
        </neura::dropdown.item>
    </x-slot:menu>
</neura::dropdown>