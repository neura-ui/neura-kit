{{-- The name as a field, while it is being changed or a new folder named. --}}
<span class="flex min-w-0 flex-1 flex-col gap-0.5" x-show="isEditing(entry.id)" x-cloak x-on:click.stop x-on:dblclick.stop x-on:mousedown.stop>
    <input
        type="text"
        data-fm-name-input
        class="h-7 w-full min-w-0 rounded-md border-2 border-[var(--nk-fm-accent)] bg-surface px-1.5 text-sm text-fg outline-none"
        :value="isEditing(entry.id) ? editing.draft : ''"
        :aria-invalid="(editing && editing.error) ? 'true' : 'false'"
        aria-label="{{ neura_trans('rename') }}"
        title="{{ neura_trans('renameHint') }}"
        x-on:input="editing && (editing.draft = $event.target.value, editing.error = null)"
        x-on:keydown.stop="$event.key === 'Enter' ? ($event.preventDefault(), commitName()) : ($event.key === 'Escape' && ($event.preventDefault(), cancelName()))"
        x-on:blur="isEditing(entry.id) && cancelName()"
    />
    <span class="truncate text-[0.6875rem] text-danger-600 dark:text-danger-400" x-show="editing && editing.error" x-text="nameErrorText()" :title="nameErrorText()"></span>
</span>
