/**
 * Natural-language dates for the calendar and the date picker, in French and English:
 * "demain", "vendredi prochain", "du 3 au 7 mars", "lundi et mercredi", "tous les lundis
 * de décembre", "tomorrow at 3pm", "march 3-7", "next 7 days"…
 *
 * A parse is a kind (one date, a range, several dates), local YYYY-MM-DD dates and an
 * optional HH:mm time. `selectionFor` fits it to the calendar's mode; `describeNaturalDate`
 * says it back in the locale. No imports: the tests load this file with Node directly.
 */

export type NaturalKind = 'single' | 'range' | 'multiple';
export type NaturalMode = 'single' | 'range' | 'multiple';
export type NaturalResult = { kind: NaturalKind; dates: string[]; time: string | null };
export type NaturalOptions = { locale?: string; today?: Date; weekStart?: number };

const WEEKDAYS: Record<string, number> = {
    dimanche: 0, lundi: 1, mardi: 2, mercredi: 3, jeudi: 4, vendredi: 5, samedi: 6,
    sunday: 0, monday: 1, tuesday: 2, wednesday: 3, thursday: 4, friday: 5, saturday: 6,
};

const MONTHS: Record<string, number> = {
    janvier: 0, fevrier: 1, mars: 2, avril: 3, mai: 4, juin: 5, juillet: 6, aout: 7, septembre: 8, octobre: 9, novembre: 10, decembre: 11,
    janv: 0, fev: 1, avr: 3, juil: 6, sept: 8, oct: 9, nov: 10, dec: 11,
    january: 0, february: 1, march: 2, april: 3, may: 4, june: 5, july: 6, august: 7, september: 8, october: 9, november: 10, december: 11,
    jan: 0, feb: 1, mar: 2, apr: 3, jun: 5, jul: 6, aug: 7, sep: 8,
};

const NUMBERS: Record<string, number> = {
    un: 1, une: 1, a: 1, an: 1, one: 1, deux: 2, two: 2, trois: 3, three: 3, quatre: 4, four: 4, cinq: 5, five: 5,
    six: 6, sept: 7, seven: 7, huit: 8, eight: 8, dix: 10, ten: 10, quinze: 15, fifteen: 15,
};

const DAY_MS = 86400000;

function pad(n: number): string {
    return (n < 10 ? '0' : '') + n;
}

function iso(d: Date): string {
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
}

function fromIso(s: string): Date {
    const [y, m, d] = s.split('-').map(Number);
    return new Date(y, m - 1, d);
}

function add(d: Date, days: number): Date {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate() + days);
}

/** A real day only: 31 February is no date. */
function valid(y: number, m: number, d: number): Date | null {
    const x = new Date(y, m, d);
    return x.getMonth() === ((m % 12) + 12) % 12 && x.getDate() === d ? x : null;
}

function count(word: string): number | null {
    return /^\d+$/.test(word) ? Number(word) : (NUMBERS[word] ?? null);
}

function strip(text: string): string {
    return String(text).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[’']/g, "'").replace(/\s+/g, ' ').trim();
}

function extractTime(s: string): { time: string | null; rest: string } {
    let m: RegExpMatchArray | null;
    const take = (time: string, match: string) => ({ time, rest: s.replace(match, ' ').replace(/\s+/g, ' ').trim() });

    if ((m = s.match(/(?:^|\s)(?:a |vers |at )?(?:midi|noon)(?=\s|$)/))) return take('12:00', m[0]);
    if ((m = s.match(/(?:^|\s)(?:a |vers |at )?(?:minuit|midnight)(?=\s|$)/))) return take('00:00', m[0]);
    if ((m = s.match(/(?:^|\s)(?:at )?(\d{1,2})(?::(\d{2}))? ?(am|pm)(?=\s|$)/)) && Number(m[1]) >= 1 && Number(m[1]) <= 12) {
        const hour = (Number(m[1]) % 12) + (m[3] === 'pm' ? 12 : 0);
        return take(pad(hour) + ':' + (m[2] ?? '00'), m[0]);
    }
    if ((m = s.match(/(?:^|\s)(?:a |vers |at )?(\d{1,2})(?:h(\d{2})?|:(\d{2}))(?=\s|$)/)) && Number(m[1]) < 24) {
        return take(pad(Number(m[1])) + ':' + (m[2] ?? m[3] ?? '00'), m[0]);
    }
    return { time: null, rest: s };
}

export function parseNaturalDate(text: string, options: NaturalOptions = {}): NaturalResult | null {
    const now = options.today ?? new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const weekStart = options.weekStart ?? 1;
    // 03/12 is 3 December, except in US English where it is 12 March.
    const monthFirst = /^en(?:[-_]us)?$/i.test(options.locale ?? 'en');

    const upcoming = (m: number, d: number): Date | null => {
        const x = valid(today.getFullYear(), m, d);
        return x && x < today ? valid(today.getFullYear() + 1, m, d) : x;
    };

    const weekday = (wd: number, mode: 'near' | 'next' | 'last'): Date => {
        if (mode === 'last') return add(today, -(((today.getDay() - wd + 7) % 7) || 7));
        let diff = (wd - today.getDay() + 7) % 7;
        if (mode === 'next' && diff === 0) diff = 7;
        return add(today, diff);
    };

    const startOfWeek = (d: Date): Date => add(d, -((d.getDay() - weekStart + 7) % 7));

    // "le 2 du mois prochain", "mardi de la semaine prochaine": a month or a week as context.
    let monthHint: { year: number; month: number } | undefined;
    let weekFrom: Date | undefined;

    const one = (raw: string, hint: { year: number; month: number } | undefined = monthHint): Date | null => {
        const s = raw.replace(/^(?:(?:le|la|l'|on|the)\s+)+/, '').trim();
        let m: RegExpMatchArray | null;
        if (!s) return null;
        if (/^(?:aujourd'?hui|today)$/.test(s)) return today;
        if (/^(?:demain|tomorrow)$/.test(s)) return add(today, 1);
        if (/^(?:apres[- ]demain|day after tomorrow)$/.test(s)) return add(today, 2);
        if (/^(?:hier|yesterday)$/.test(s)) return add(today, -1);
        if ((m = s.match(/^(?:dans|in) (\d+|[a-z]+) (jours?|days?|semaines?|weeks?|mois|months?)$/))) {
            const n = count(m[1]);
            if (!n) return null;
            if (/^(?:jour|day)/.test(m[2])) return add(today, n);
            if (/^(?:semaine|week)/.test(m[2])) return add(today, 7 * n);
            return valid(today.getFullYear(), today.getMonth() + n, today.getDate());
        }
        if ((m = s.match(/^il y a (\d+|[a-z]+) (jours?|semaines?)$/)) || (m = s.match(/^(\d+|[a-z]+) (days?|weeks?) ago$/))) {
            const n = count(m[1]);
            return n ? add(today, -n * (/^(?:jour|day)/.test(m[2]) ? 1 : 7)) : null;
        }
        if ((m = s.match(/^(?:(next|last|this) )?([a-z]+)(?: (prochain|suivant|dernier))?$/)) && WEEKDAYS[m[2]] !== undefined) {
            const mode = m[1] === 'next' || m[3] === 'prochain' || m[3] === 'suivant' ? 'next' : m[1] === 'last' || m[3] === 'dernier' ? 'last' : 'near';
            if (weekFrom && mode === 'near') return add(weekFrom, (WEEKDAYS[m[2]] - weekStart + 7) % 7);
            return weekday(WEEKDAYS[m[2]], mode);
        }
        if ((m = s.match(/^(\d{1,2})(?:er|st|nd|rd|th)? ([a-z]+)\.?(?: (\d{4}))?$/)) && MONTHS[m[2]] !== undefined) {
            return m[3] ? valid(Number(m[3]), MONTHS[m[2]], Number(m[1])) : upcoming(MONTHS[m[2]], Number(m[1]));
        }
        if ((m = s.match(/^([a-z]+)\.? (\d{1,2})(?:st|nd|rd|th)?(?:,? (\d{4}))?$/)) && MONTHS[m[1]] !== undefined) {
            return m[3] ? valid(Number(m[3]), MONTHS[m[1]], Number(m[2])) : upcoming(MONTHS[m[1]], Number(m[2]));
        }
        if ((m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/))) return valid(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
        if ((m = s.match(/^(\d{1,2})[/.](\d{1,2})(?:[/.](\d{2,4}))?$/))) {
            const [day, month] = monthFirst ? [Number(m[2]), Number(m[1])] : [Number(m[1]), Number(m[2])];
            if (!m[3]) return upcoming(month - 1, day);
            return valid(m[3].length === 2 ? 2000 + Number(m[3]) : Number(m[3]), month - 1, day);
        }
        if ((m = s.match(/^(\d{1,2})(?:er|st|nd|rd|th)?$/))) {
            if (hint) return valid(hint.year, hint.month, Number(m[1]));
            const x = valid(today.getFullYear(), today.getMonth(), Number(m[1]));
            return x && x < today ? valid(today.getFullYear(), today.getMonth() + 1, Number(m[1])) : x;
        }
        return null;
    };

    const { time, rest } = extractTime(strip(text));
    const y = today.getFullYear();
    const mo = today.getMonth();

    // A month or a week next to something else sets the context; alone, it is the whole period.
    let phrase = rest;
    const context = (pattern: RegExp, apply: (match: RegExpMatchArray) => void) => {
        const match = phrase.match(pattern);
        if (!match) return;
        const remainder = phrase.replace(match[0], ' ').replace(/\s+/g, ' ').trim();
        if (!remainder) return;
        apply(match);
        phrase = remainder;
    };
    context(/(?:^|\s)(?:(?:du|de|le|au|of|in|on|for)\s+)*(?:(mois prochain|next month)|ce mois(?:-ci)?|this month)(?=\s|$)/, (match) => {
        monthHint = match[1] ? { year: mo === 11 ? y + 1 : y, month: (mo + 1) % 12 } : { year: y, month: mo };
    });
    context(/(?:^|\s)(?:(?:de la|la|de|of|in|on)\s+)*(?:(semaine prochaine|semaine suivante|next week)|cette semaine|this week)(?=\s|$)/, (match) => {
        weekFrom = match[1] ? add(startOfWeek(today), 7) : startOfWeek(today);
    });
    const s = phrase.replace(/^les? (?=\d)/, '').trim();
    let m: RegExpMatchArray | null;

    const range = (a: Date | null, b: Date | null): NaturalResult | null => {
        if (!a || !b) return null;
        const [start, end] = a <= b ? [a, b] : [b, a];
        return { kind: 'range', dates: [iso(start), iso(end)], time };
    };

    if (!s) return time ? { kind: 'single', dates: [iso(today)], time } : null;

    if (/^(?:cette semaine|this week)$/.test(s)) return range(startOfWeek(today), add(startOfWeek(today), 6));
    if (/^(?:la )?semaine (?:prochaine|suivante)$|^next week$/.test(s)) {
        const a = add(startOfWeek(today), 7);
        return range(a, add(a, 6));
    }
    if (/^(?:ce |le )?week-?end(?: prochain)?$|^(?:this |next |the )?weekend$/.test(s)) {
        const a = today.getDay() === 0 ? add(today, -1) : weekday(6, 'near');
        return range(a, add(a, 1));
    }
    if (/^(?:ce mois(?:-ci)?|this month)$/.test(s)) return range(new Date(y, mo, 1), new Date(y, mo + 1, 0));
    if (/^(?:le )?mois prochain$|^next month$/.test(s)) return range(new Date(y, mo + 1, 1), new Date(y, mo + 2, 0));
    if ((m = s.match(/^(?:les )?(\d+|[a-z]+) prochains jours$/)) || (m = s.match(/^(?:the )?next (\d+|[a-z]+) days$/))) {
        const n = count(m[1]);
        return n ? range(today, add(today, n - 1)) : null;
    }

    // Repeats: every Monday (of a month)
    if ((m = s.match(/^(?:tous les|chaque|every) ([a-z]+?)s?(?: (?:de|d'|en|du mois de|in|of) ([a-z]+))?$/)) && WEEKDAYS[m[1]] !== undefined) {
        const month = m[2] !== undefined ? MONTHS[m[2]] : mo;
        if (month === undefined) return null;
        const year = month < mo ? y + 1 : y;
        const all: string[] = [];
        for (let d = 1; d <= 31; d++) {
            const x = valid(year, month, d);
            if (!x) break;
            if (x.getDay() === WEEKDAYS[m[1]] && x >= today) all.push(iso(x));
        }
        return all.length ? { kind: 'multiple', dates: all, time } : null;
    }

    // 3-7 mars, march 3-7
    if ((m = s.match(/^(\d{1,2}) ?- ?(\d{1,2}) ([a-z]+)\.?(?: (\d{4}))?$/)) && MONTHS[m[3]] !== undefined) {
        const b = one(m[2] + ' ' + m[3] + (m[4] ? ' ' + m[4] : ''));
        return b ? range(valid(b.getFullYear(), b.getMonth(), Number(m[1])), b) : null;
    }
    if ((m = s.match(/^([a-z]+)\.? (\d{1,2}) ?- ?(\d{1,2})(?:,? (\d{4}))?$/)) && MONTHS[m[1]] !== undefined) {
        const b = one(m[1] + ' ' + m[3] + (m[4] ? ' ' + m[4] : ''));
        return b ? range(valid(b.getFullYear(), b.getMonth(), Number(m[2])), b) : null;
    }

    // Ranges: du … au …, from … to …, entre … et …
    if ((m = s.match(/^(?:du|de|from|entre|between) (.+?) (?:au|a|jusqu'au|jusqu'a|to|until|through|et|and) (.+)$/)) || (m = s.match(/^(.+?) (?:au|jusqu'au|jusqu'a|to|until|through) (.+)$/))) {
        let b = one(m[2]);
        if (!b) return null;
        const a = one(m[1], { year: b.getFullYear(), month: b.getMonth() });
        // "du lundi au vendredi": the Friday after that Monday
        if (a && b < a && WEEKDAYS[strip(m[2]).replace(/ .*/, '')] !== undefined) b = add(b, 7);
        return range(a, b);
    }

    // Lists: lundi et mercredi, 3, 5 et 9 décembre
    const parts = s.split(/\s*,\s*|\s+(?:et|and)\s+/).filter(Boolean);
    if (parts.length > 1) {
        const last = one(parts[parts.length - 1]);
        if (!last) return null;
        const hint = { year: last.getFullYear(), month: last.getMonth() };
        const found: string[] = [];
        for (let i = 0; i < parts.length; i++) {
            const d = i === parts.length - 1 ? last : one(parts[i], /^\d/.test(parts[i]) ? hint : undefined);
            if (!d) return null;
            if (!found.includes(iso(d))) found.push(iso(d));
        }
        return { kind: 'multiple', dates: found.sort(), time };
    }

    const single = one(s);
    return single ? { kind: 'single', dates: [iso(single)], time } : null;
}

/** What a parse selects in each calendar mode: a date, `{ start, end }` or a list. */
export function selectionFor(result: NaturalResult, mode: NaturalMode): string | { start: string; end: string | null } | string[] {
    const d = result.dates;
    if (mode === 'single') return d[0];
    if (mode === 'range') return { start: d[0], end: result.kind === 'single' ? null : d[d.length - 1] };
    if (result.kind === 'range') {
        const all: string[] = [];
        for (let x = fromIso(d[0]); x <= fromIso(d[1]) && all.length < 62; x = add(x, 1)) all.push(iso(x));
        return all;
    }
    return [...d];
}

/** The parse said back: "Vendredi 13 novembre à 15h", "Mar 3 – Mar 7, 2027 · 5 days"… */
export function describeNaturalDate(result: NaturalResult, mode: NaturalMode, locale = 'en', today: Date = new Date()): string {
    const french = /^fr/i.test(locale);
    const long = new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long' });
    const short = new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short' });
    const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);
    const year = (d: Date) => (d.getFullYear() !== today.getFullYear() ? (french ? ' ' : ', ') + d.getFullYear() : '');
    const dates = result.dates.map(fromIso);

    let at = '';
    if (result.time) {
        const [h, mm] = result.time.split(':').map(Number);
        at = french
            ? ' à ' + h + 'h' + (mm ? pad(mm) : '')
            : ' at ' + new Intl.DateTimeFormat(locale, { hour: 'numeric', minute: '2-digit' }).format(new Date(2000, 0, 1, h, mm));
    }

    if (mode !== 'single' && (result.kind === 'range' || (mode === 'range' && result.kind === 'multiple'))) {
        const first = dates[0];
        const last = dates[dates.length - 1];
        const days = Math.round((last.getTime() - first.getTime()) / DAY_MS) + 1;
        return french
            ? 'Du ' + short.format(first) + ' au ' + short.format(last) + year(last) + ' · ' + days + (days > 1 ? ' jours' : ' jour') + at
            : short.format(first) + ' – ' + short.format(last) + year(last) + ' · ' + days + (days > 1 ? ' days' : ' day') + at;
    }
    if (mode === 'multiple' && result.kind === 'multiple') {
        const shown = dates.slice(0, 4).map((d) => short.format(d)).join(', ');
        return dates.length + ' dates · ' + shown + (dates.length > 4 ? '…' : '') + at;
    }
    return cap(long.format(dates[0])) + year(dates[0]) + at;
}
