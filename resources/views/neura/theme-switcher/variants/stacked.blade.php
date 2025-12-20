@aware(['darkIcon'=>'moon','lightIcon'=>'sun','systemIcon'=>'computer-desktop','iconVariant' => "mini"])
<neura::button-group class="border rounded-box dark:border-white/10 border-black/10">
    <neura::button
        :icon="$lightIcon"
        :iconVariant="$iconVariant"
        variant="soft"
        class="dark:hover:bg-white/10 hover:bg-black/5 transition-colors"
        x-on:click="$theme.setLight()"
        x-bind:class="{
            'dark:bg-white/5! bg-black/10!' : $theme.isLight
        }"
        role="button"
        x-bind:aria-pressed="$theme.isLight"
        x-bind:aria-label="window.t('activateLightTheme')"
    />
    <neura::button
        :icon="$darkIcon"
        :iconVariant="$iconVariant"
        variant="soft"
        class="dark:hover:bg-white/10 hover:bg-black/5 transition-colors"
        x-on:click="$theme.setDark()"
        x-bind:class="{
            'dark:bg-white/5! bg-black/10!' : $theme.isDark
        }"
        role="button"
        x-bind:aria-pressed="$theme.isDark"
        x-bind:aria-label="window.t('activateDarkTheme')"
    />
    <neura::button
        :icon="$systemIcon"
        :iconVariant="$iconVariant"
        variant="soft"
        class="dark:hover:bg-white/10 hover:bg-black/5 transition-colors"
        x-on:click="$theme.setSystem()"
        x-bind:class="{
            'dark:bg-white/5! bg-black/10!' : $theme.isSystem
        }"
        role="button"
        x-bind:aria-pressed="$theme.isSystem"
        x-bind:aria-label="window.t('activateSystemTheme')"
    />
</neura::button-group>