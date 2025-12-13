@aware(['darkIcon'=>'moon','lightIcon'=>'sun','systemIcon'=>'computer-desktop','iconVariant' => "mini"])
<div class=" flex items-center transition-all border rounded-box dark:border-white/10 border-black/10 duration-200 overflow-hidden">
    <neura::button
        :icon="$lightIcon"
        :iconVariant="$iconVariant"
        variant="none"
        class="dark:hover:bg-white/10 hover:bg-black/5"
        x-on:click="$theme.setLight()"
        x-bind:class="{
            'dark:!bg-white/5 !bg-black/10' : $theme.isLight
        }"
        role="button"
        aria-pressed="true"
        x-bind:aria-pressed="$theme.isLight"
        x-bind:aria-label="window.t('activateLightTheme')"
    />
    <neura::button
        :icon="$darkIcon"
        :iconVariant="$iconVariant"
        variant="none"
        class="dark:hover:bg-white/10 hover:bg-black/5"
        x-on:click="$theme.setDark()"
        x-bind:class="{
            'dark:!bg-white/5 !bg-black/10' : $theme.isDark
        }"
        role="button"
        aria-pressed="true"
        x-bind:aria-pressed="$theme.isDark"
        x-bind:aria-label="window.t('activateDarkTheme')"
    />
    <neura::button
        :icon="$systemIcon"
        :iconVariant="$iconVariant"
        variant="none"
        class="dark:hover:bg-white/10 hover:bg-black/5"
        x-on:click="$theme.setSystem()"
        x-bind:class="{
            'dark:!bg-white/5 !bg-black/10' : $theme.isSystem
        }"
        role="button"
        aria-pressed="true"
        x-bind:aria-pressed="$theme.isSystem"
        x-bind:aria-label="window.t('activateSystemTheme')"
    />
</div>