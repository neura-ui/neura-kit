import { onAlpineInit } from '../../runtime/alpine';
import { prefersReducedMotion } from '../../shared/motion-tokens';

/**
 * Mention input.
 *
 * An autosizing textarea where @people and #channels are single tokens.
 * Suggestions open at the caret. A mirror behind the transparent field tints
 * the tokens; assistive tech reads the plain text.
 */

const EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';
const BOUNDARY = /[\s([{"']/;

type Kind = 'person' | 'channel';

interface Mention {
    kind: Kind;
    id: string;
    label: string;
    start: number;
    end: number;
}

interface Person {
    id: string;
    name: string;
    role?: string;
    avatar?: string;
}

interface Channel {
    id: string;
    name: string;
    description?: string;
    members?: number;
}

interface Trigger {
    kind: Kind;
    start: number;
    query: string;
}

interface MentionInputConfig {
    text?: string;
    mentions?: Mention[];
    people?: Person[];
    channels?: Channel[];
    minRows?: number;
    maxRows?: number;
    maxSuggestions?: number;
    placement?: 'auto' | 'top' | 'bottom';
    submitOnEnter?: boolean;
    disabled?: boolean;
    method?: string | null;
    wireProperty?: string | null;
    listId?: string;
}

function mentionText(kind: Kind, label: string): string {
    return `${kind === 'channel' ? '#' : '@'}${label}`;
}

function escapeHtml(value: string): string {
    return value.replace(/[&<>"']/g, (char) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char] ?? char
    ));
}

function reconcile(previous: string, next: string, mentions: Mention[]): Mention[] {
    let prefix = 0;
    const maxPrefix = Math.min(previous.length, next.length);
    while (prefix < maxPrefix && previous[prefix] === next[prefix]) prefix += 1;

    let suffix = 0;
    while (
        suffix < previous.length - prefix
        && suffix < next.length - prefix
        && previous[previous.length - 1 - suffix] === next[next.length - 1 - suffix]
    ) {
        suffix += 1;
    }

    const delta = next.length - previous.length;
    const changeEnd = previous.length - suffix;

    return mentions.flatMap((mention) => {
        if (mention.end <= prefix) return [mention];
        if (mention.start >= changeEnd) {
            return [{ ...mention, start: mention.start + delta, end: mention.end + delta }];
        }
        return [];
    }).filter((mention) => next.slice(mention.start, mention.end) === mentionText(mention.kind, mention.label));
}

function findTrigger(text: string, caret: number, mentions: Mention[]): Trigger | null {
    if (mentions.some((mention) => mention.start < caret && caret < mention.end)) return null;

    for (let index = caret - 1; index >= 0; index -= 1) {
        const char = text[index];
        if (char === '\n') return null;
        if (char !== '@' && char !== '#') continue;

        const before = index === 0 ? '' : text[index - 1];
        if (index > 0 && !BOUNDARY.test(before)) return null;
        if (mentions.some((mention) => mention.start <= index && index < mention.end)) return null;

        const query = text.slice(index + 1, caret);
        if (query.length > 48) return null;

        return {
            kind: char === '#' ? 'channel' : 'person',
            start: index,
            query,
        };
    }

    return null;
}

function rank<T extends { name: string; role?: string; description?: string }>(items: T[], query: string): T[] {
    const needle = query.trim().toLowerCase();
    const scored = items.map((item, index) => {
        if (!needle) return { item, score: 1, index };
        const name = item.name.toLowerCase();
        const extra = `${item.role ?? ''} ${item.description ?? ''}`.toLowerCase();
        let score = 9;
        if (name.startsWith(needle)) score = 0;
        else if (name.split(/\s+/).some((word) => word.startsWith(needle))) score = 1;
        else if (name.includes(needle)) score = 2;
        else if (extra.includes(needle)) score = 3;
        return { item, score, index };
    });

    return scored
        .filter((entry) => entry.score < 9)
        .sort((a, b) => a.score - b.score || a.index - b.index)
        .map((entry) => entry.item);
}

function insertText(input: HTMLTextAreaElement, text: string): void {
    const start = input.selectionStart ?? 0;
    const end = input.selectionEnd ?? start;
    input.focus();
    const wrote = text === ''
        ? document.execCommand('delete', false)
        : document.execCommand('insertText', false, text);
    if (wrote) return;

    const next = input.value.slice(0, start) + text + input.value.slice(end);
    input.value = next;
    const caret = start + text.length;
    input.setSelectionRange(caret, caret);
    input.dispatchEvent(new Event('input', { bubbles: true }));
}

if (typeof window !== 'undefined') {
    onAlpineInit(() => {
        window.Alpine.data('neuraMentionInput', (config: MentionInputConfig = {}) => ({
            text: config.text ?? '',
            mentions: (config.mentions ?? []) as Mention[],
            people: (config.people ?? []) as Person[],
            channels: (config.channels ?? []) as Channel[],
            minRows: config.minRows ?? 1,
            maxRows: config.maxRows ?? 8,
            maxSuggestions: config.maxSuggestions ?? 6,
            placement: config.placement ?? 'auto',
            submitOnEnter: config.submitOnEnter === true,
            disabled: config.disabled === true,
            method: config.method || null,
            wireProperty: config.wireProperty || null,
            listId: config.listId || 'nk-mention-list',
            open: false,
            active: 0,
            suggestions: [] as Array<Person | Channel>,
            trigger: null as Trigger | null,
            live: '',
            pending: null as Mention | null,
            dismissed: null as { key: string; query: string } | null,
            onSelect: null as (() => void) | null,

            init() {
                if (this.wireProperty && this.$wire) {
                    const current = this.$wire.get(this.wireProperty);
                    if (typeof current === 'string') this.text = current;
                }

                this.$watch('text', (value: string) => {
                    const input = this.$refs.input as HTMLTextAreaElement | undefined;
                    if (!input || input.value === value) return;
                    input.value = value;
                    this.paint();
                    this.resize(false);
                });

                this.onSelect = () => {
                    if (document.activeElement !== this.$refs.input) return;
                    this.refreshTrigger();
                };
                document.addEventListener('selectionchange', this.onSelect);

                this.$nextTick(() => {
                    const input = this.$refs.input as HTMLTextAreaElement | undefined;
                    if (!input) return;
                    input.value = this.text;
                    this.paint();
                    this.resize(false);
                });
            },

            destroy() {
                if (this.onSelect) document.removeEventListener('selectionchange', this.onSelect);
            },

            serialized(): string {
                const mentions = [...this.mentions].sort((a, b) => b.start - a.start);
                let text = this.text;
                mentions.forEach((mention) => {
                    const token = mention.kind === 'channel' ? `<#${mention.id}>` : `<@${mention.id}>`;
                    text = text.slice(0, mention.start) + token + text.slice(mention.end);
                });
                return text;
            },

            initials(name: string): string {
                return name
                    .split(/\s+/)
                    .slice(0, 2)
                    .map((part) => part.charAt(0).toUpperCase())
                    .join('');
            },

            meta(item: Person | Channel): string {
                if ('members' in item && item.members) return `${item.description ?? ''} · ${item.members} members`.replace(/^ · /, '');
                return ('role' in item ? item.role : item.description) || '';
            },

            optionId(index: number): string {
                return `${this.listId}-opt-${index}`;
            },

            activeDescendant(): string | null {
                if (!this.open || !this.suggestions.length) return null;
                return this.optionId(this.active);
            },

            paint() {
                const mirror = this.$refs.mirror as HTMLElement | undefined;
                const input = this.$refs.input as HTMLTextAreaElement | undefined;
                if (!mirror || !input) return;

                const text = input.value;
                const mentions = [...this.mentions].sort((a, b) => a.start - b.start);
                let html = '';
                let cursor = 0;
                mentions.forEach((mention) => {
                    if (mention.start < cursor || mention.end > text.length) return;
                    html += escapeHtml(text.slice(cursor, mention.start));
                    html += `<span class="nk-mention-token" data-kind="${mention.kind}">${escapeHtml(text.slice(mention.start, mention.end))}</span>`;
                    cursor = mention.end;
                });
                html += escapeHtml(text.slice(cursor));
                if (text.endsWith('\n')) html += '<br>';
                mirror.innerHTML = html;
                mirror.scrollTop = input.scrollTop;
            },

            syncScroll() {
                const mirror = this.$refs.mirror as HTMLElement | undefined;
                const input = this.$refs.input as HTMLTextAreaElement | undefined;
                if (mirror && input) mirror.scrollTop = input.scrollTop;
            },

            resize(animate = true) {
                const input = this.$refs.input as HTMLTextAreaElement | undefined;
                if (!input) return;
                const style = getComputedStyle(input);
                const line = parseFloat(style.lineHeight) || 21;
                const pad = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
                const min = Math.ceil(line * this.minRows + pad);
                const max = Math.ceil(line * this.maxRows + pad);
                const previous = input.offsetHeight;
                input.style.height = 'auto';
                const next = Math.min(max, Math.max(min, input.scrollHeight));
                input.style.overflowY = input.scrollHeight > max + 1 ? 'auto' : 'hidden';
                input.style.height = `${next}px`;
                if (!animate || prefersReducedMotion() || previous === next) return;
                input.animate(
                    [{ height: `${previous}px` }, { height: `${next}px` }],
                    { duration: 280, easing: EASE },
                );
            },

            onInput() {
                const input = this.$refs.input as HTMLTextAreaElement;
                const next = input.value;
                let mentions = reconcile(this.text, next, this.mentions);
                if (this.pending && next.slice(this.pending.start, this.pending.end) === mentionText(this.pending.kind, this.pending.label)) {
                    mentions = mentions.filter((mention) => mention.start !== this.pending?.start);
                    mentions.push(this.pending);
                    this.$dispatch('mention-add', { ...this.pending });
                }
                this.pending = null;
                this.text = next;
                this.mentions = mentions.sort((a, b) => a.start - b.start);
                this.paint();
                this.resize(true);
                this.refreshTrigger();
                this.pushWire();
            },

            refreshTrigger() {
                const input = this.$refs.input as HTMLTextAreaElement | undefined;
                if (!input || this.disabled) {
                    this.open = false;
                    return;
                }

                const trigger = findTrigger(input.value, input.selectionStart ?? 0, this.mentions);
                if (!trigger) {
                    this.open = false;
                    this.trigger = null;
                    this.dismissed = null;
                    return;
                }

                const source = trigger.kind === 'channel' ? this.channels : this.people;
                if (!source.length) {
                    this.open = false;
                    this.trigger = null;
                    return;
                }

                const key = `${trigger.start}:${trigger.kind}`;
                if (this.dismissed && this.dismissed.key === key && this.dismissed.query === trigger.query) {
                    this.open = false;
                    this.trigger = trigger;
                    return;
                }

                const queryChanged = !this.trigger || this.trigger.query !== trigger.query || this.trigger.kind !== trigger.kind;
                this.trigger = trigger;
                this.suggestions = rank(source, trigger.query).slice(0, this.maxSuggestions);
                if (queryChanged) this.active = 0;
                if (this.active >= this.suggestions.length) this.active = 0;
                this.live = this.suggestions.length
                    ? `${this.suggestions.length} ${trigger.kind === 'channel' ? 'channels' : 'people'}`
                    : 'No matches';
                this.open = true;
                this.$nextTick(() => {
                    this.place();
                    this.moveGlide(!queryChanged);
                });
            },

            place() {
                const input = this.$refs.input as HTMLTextAreaElement | undefined;
                const panel = this.$refs.panel as HTMLElement | undefined;
                const probe = this.$refs.probe as HTMLElement | undefined;
                const shell = this.$refs.shell as HTMLElement | undefined;
                if (!input || !panel || !probe || !shell || !this.open) return;

                const style = getComputedStyle(input);
                probe.style.width = `${input.clientWidth}px`;
                probe.style.padding = style.padding;
                probe.style.lineHeight = style.lineHeight;
                probe.textContent = input.value.slice(0, input.selectionStart ?? 0);
                const mark = document.createElement('span');
                mark.textContent = '\u200b';
                probe.append(mark);
                const markRect = mark.getBoundingClientRect();
                const shellRect = shell.getBoundingClientRect();
                const caretTop = markRect.top - input.scrollTop;
                const caretBottom = markRect.bottom - input.scrollTop;
                const width = Math.min(272, shellRect.width);
                let left = markRect.left - shellRect.left;
                left = Math.max(8, Math.min(left, shellRect.width - width - 8));
                const spaceBelow = window.innerHeight - caretBottom;
                const spaceAbove = caretTop;
                const above = this.placement === 'top' || (this.placement !== 'bottom' && spaceBelow < 300 && spaceAbove > spaceBelow);

                panel.style.width = `${width}px`;
                panel.style.left = `${left}px`;
                panel.dataset.side = above ? 'top' : 'bottom';
                if (above) {
                    panel.style.top = 'auto';
                    panel.style.bottom = `${shellRect.bottom - caretTop + 6}px`;
                } else {
                    panel.style.bottom = 'auto';
                    panel.style.top = `${caretBottom - shellRect.top + 6}px`;
                }
            },

            moveGlide(animate = true) {
                const list = this.$refs.list as HTMLElement | undefined;
                const glide = this.$refs.glide as HTMLElement | undefined;
                if (!list || !glide || !this.suggestions.length) return;
                const option = list.querySelector<HTMLElement>(`[data-index="${this.active}"]`);
                if (!option) return;
                const top = option.offsetTop;
                const height = option.offsetHeight;
                if (!animate || prefersReducedMotion()) {
                    glide.style.transform = `translateY(${top}px)`;
                    glide.style.height = `${height}px`;
                } else {
                    glide.animate(
                        [
                            { transform: glide.style.transform || `translateY(${top}px)`, height: glide.style.height || `${height}px` },
                            { transform: `translateY(${top}px)`, height: `${height}px` },
                        ],
                        { duration: 180, easing: EASE },
                    );
                    glide.style.transform = `translateY(${top}px)`;
                    glide.style.height = `${height}px`;
                }
                option.scrollIntoView({ block: 'nearest' });
            },

            choose(item: Person | Channel) {
                const trigger = this.trigger;
                const input = this.$refs.input as HTMLTextAreaElement | undefined;
                if (!trigger || !input || !item) return;
                const token = mentionText(trigger.kind, item.name);
                const start = trigger.start;
                this.pending = {
                    kind: trigger.kind,
                    id: item.id,
                    label: item.name,
                    start,
                    end: start + token.length,
                };
                input.focus();
                input.setSelectionRange(start, input.selectionStart ?? start);
                this.open = false;
                this.trigger = null;
                insertText(input, `${token} `);
            },

            onKey(event: KeyboardEvent) {
                if (this.disabled || event.isComposing || event.keyCode === 229) return;
                const input = this.$refs.input as HTMLTextAreaElement;

                if (this.open) {
                    if (event.key === 'ArrowDown' && this.suggestions.length) {
                        event.preventDefault();
                        this.active = (this.active + 1) % this.suggestions.length;
                        this.moveGlide(true);
                        return;
                    }
                    if (event.key === 'ArrowUp' && this.suggestions.length) {
                        event.preventDefault();
                        this.active = (this.active - 1 + this.suggestions.length) % this.suggestions.length;
                        this.moveGlide(true);
                        return;
                    }
                    if ((event.key === 'Enter' || event.key === 'Tab') && this.suggestions[this.active]) {
                        event.preventDefault();
                        this.choose(this.suggestions[this.active]);
                        return;
                    }
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        if (this.trigger) {
                            this.dismissed = { key: `${this.trigger.start}:${this.trigger.kind}`, query: this.trigger.query };
                        }
                        this.open = false;
                        return;
                    }
                }

                const start = input.selectionStart ?? 0;
                const end = input.selectionEnd ?? start;
                if (event.key === 'Backspace' || event.key === 'Delete') {
                    let from = start;
                    let to = end;
                    if (start === end) {
                        const hit = this.mentions.find((mention) => (
                            event.key === 'Backspace'
                                ? start > mention.start && start <= mention.end
                                : start >= mention.start && start < mention.end
                        ));
                        if (hit) {
                            event.preventDefault();
                            input.setSelectionRange(hit.start, hit.end);
                            insertText(input, '');
                            return;
                        }
                    }
                    this.mentions.forEach((mention) => {
                        if (mention.end > from && mention.start < to) {
                            from = Math.min(from, mention.start);
                            to = Math.max(to, mention.end);
                        }
                    });
                    if (from !== start || to !== end) {
                        event.preventDefault();
                        input.setSelectionRange(from, to);
                        insertText(input, '');
                    }
                    return;
                }

                if (start === end && event.key === 'ArrowLeft') {
                    const hit = this.mentions.find((mention) => start > mention.start && start <= mention.end);
                    if (hit) {
                        event.preventDefault();
                        input.setSelectionRange(hit.start, hit.start);
                    }
                    return;
                }
                if (start === end && event.key === 'ArrowRight') {
                    const hit = this.mentions.find((mention) => start >= mention.start && start < mention.end);
                    if (hit) {
                        event.preventDefault();
                        input.setSelectionRange(hit.end, hit.end);
                    }
                    return;
                }

                if (this.submitOnEnter && event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    this.submit();
                }
            },

            submit() {
                if (!this.text.trim()) return;
                const serialized = this.serialized();
                this.$dispatch('mention-submit', {
                    text: this.text,
                    serialized,
                    mentions: this.mentions,
                });
                if (this.method && this.$wire) {
                    this.$wire.call(this.method, serialized);
                }
                this.text = '';
                this.mentions = [];
                this.open = false;
                const input = this.$refs.input as HTMLTextAreaElement | undefined;
                if (input) input.value = '';
                this.paint();
                this.resize(true);
                this.pushWire();
            },

            pushWire() {
                if (!this.wireProperty || !this.$wire) return;
                if (this.$wire.get(this.wireProperty) === this.text) return;
                this.$wire.set(this.wireProperty, this.text);
            },
        }));
    });
}
