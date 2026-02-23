@aware(['darkIcon'=>'moon','lightIcon'=>'sun','systemIcon'=>'computer-desktop','iconVariant' => "mini"])
<neura::button-group class="border rounded-box border-edge">
    <neura::button
        :icon="$lightIcon"
        :iconVariant="$iconVariant"
        variant="soft"
        class="hover:bg-hover transition-colors"
        x-on:click="$theme.setLight()"
        x-bind:class="{
            'bg-active!' : $theme.isLight
        }"
        role="button"
        x-bind:aria-pressed="$theme.isLight"
        x-bind:aria-label="window.t('activateLightTheme')"
    />
    <neura::button
        :icon="$darkIcon"
        :iconVariant="$iconVariant"
        variant="soft"
        class="hover:bg-hover transition-colors"
        x-on:click="$theme.setDark()"
        x-bind:class="{
            'bg-active!' : $theme.isDark
        }"
        role="button"
        x-bind:aria-pressed="$theme.isDark"
        x-bind:aria-label="window.t('activateDarkTheme')"
    />
    <neura::button
        :icon="$systemIcon"
        :iconVariant="$iconVariant"
        variant="soft"
        class="hover:bg-hover transition-colors"
        x-on:click="$theme.setSystem()"
        x-bind:class="{
            'bg-active!' : $theme.isSystem
        }"
        role="button"
        x-bind:aria-pressed="$theme.isSystem"
        x-bind:aria-label="window.t('activateSystemTheme')"
    />
</neura::button-group>