import type { Freq, Rule } from './model';

/**
 * Recurrence in words.
 *
 * `parseNaturalRule` reads a repetition typed in French or English ("tous les lundis et
 * jeudis à 9h", "the last friday of every month", "toutes les 2 semaines jusqu'au 31
 * décembre") into a rule; `describeRule` says a rule back as one sentence. Other locales
 * get the English sentence with their own day and month names left to Intl. No runtime
 * imports, so it runs in Node.
 */

const NUMBERS: Record<string, number> = {
    un: 1, une: 1, deux: 2, trois: 3, quatre: 4, cinq: 5, six: 6, sept: 7, huit: 8, neuf: 9, dix: 10, douze: 12,
    one: 1, two: 2, three: 3, four: 4, five: 5, seven: 7, eight: 8, nine: 9, ten: 10, twelve: 12, other: 2,
};

const WEEKDAYS: Record<string, number> = {
    dimanche: 0, lundi: 1, mardi: 2, mercredi: 3, jeudi: 4, vendredi: 5, samedi: 6,
    sunday: 0, monday: 1, tuesday: 2, wednesday: 3, thursday: 4, friday: 5, saturday: 6,
};

const MONTHS: Record<string, number> = {
    janvier: 1, janv: 1, fevrier: 2, fevr: 2, fev: 2, mars: 3, avril: 4, avr: 4, mai: 5, juin: 6,
    juillet: 7, juil: 7, aout: 8, septembre: 9, sept: 9, octobre: 10, novembre: 11, decembre: 12,
    january: 1, jan: 1, february: 2, feb: 2, march: 3, mar: 3, april: 4, apr: 4, may: 5, june: 6, jun: 6,
    july: 7, jul: 7, august: 8, aug: 8, september: 9, sep: 9, october: 10, oct: 10, november: 11, nov: 11,
    december: 12, dec: 12,
};

const ORDINALS: Record<string, number> = {
    premier: 1, premiere: 1, '1er': 1, '1re': 1, deuxieme: 2, '2e': 2, '2eme': 2, second: 2, seconde: 2,
    troisieme: 3, '3e': 3, '3eme': 3, quatrieme: 4, '4e': 4, '4eme': 4, dernier: -1, derniere: -1,
    first: 1, '1st': 1, '2nd': 2, third: 3, '3rd': 3, fourth: 4, '4th': 4, last: -1,
};

const UNITS: Array<[RegExp, Freq]> = [
    [/^(?:jours?|days?)$/, 'daily'],
    [/^(?:semaines?|weeks?)$/, 'weekly'],
    [/^(?:mois|months?)$/, 'monthly'],
    [/^(?:ans?|annees?|years?)$/, 'yearly'],
];

const WEEKDAY = Object.keys(WEEKDAYS).join('|');
const ORDINAL = Object.keys(ORDINALS).join('|');
const NUMBER = `\\d{1,3}|${Object.keys(NUMBERS).join('|')}`;
const UNIT = 'jours?|semaines?|mois|ans|an|annees?|days?|weeks?|months?|years?';

function pad(n: number): string {
    return String(n).padStart(2, '0');
}

function isoDate(date: Date): string {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function clean(text: string): string {
    return text
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[’‘`]/g, "'")
        .replace(/[,;]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function numberOf(word: string): number | null {
    if (/^\d+$/.test(word)) return Number(word);
    return NUMBERS[word] ?? null;
}

function unitOf(word: string): Freq | null {
    return UNITS.find(([pattern]) => pattern.test(word))?.[1] ?? null;
}

/** A day of the year in words: "31 decembre", "1er janvier 2027", "march 3", "2026-12-31". */
function parseDay(phrase: string, today: Date): { month: number; day: number; date: Date } | null {
    const text = phrase.trim().replace(/^(?:le|the|on)\s+/, '');
    let day: number | null = null;
    let month: number | null = null;
    let year: number | null = null;
    let m: RegExpMatchArray | null;

    if ((m = text.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/))) {
        [year, month, day] = [Number(m[1]), Number(m[2]), Number(m[3])];
    } else if ((m = text.match(/^(\d{1,2})(?:er|re|e|eme|st|nd|rd|th)?\s+([a-z]+)\.?(?:\s+(\d{4}))?$/)) && MONTHS[m[2]]) {
        [day, month, year] = [Number(m[1]), MONTHS[m[2]], m[3] ? Number(m[3]) : null];
    } else if ((m = text.match(/^([a-z]+)\.?\s+(\d{1,2})(?:st|nd|rd|th)?(?:\s+(\d{4}))?$/)) && MONTHS[m[1]]) {
        [month, day, year] = [MONTHS[m[1]], Number(m[2]), m[3] ? Number(m[3]) : null];
    }

    if (!day || !month || day > 31 || month > 12) return null;
    let date = new Date(year ?? today.getFullYear(), month - 1, day);
    if (date.getDate() !== day) return null;
    // A date without a year that has passed is next year's.
    if (year === null && date < new Date(today.getFullYear(), today.getMonth(), today.getDate())) {
        date = new Date(today.getFullYear() + 1, month - 1, day);
    }
    return { month, day, date };
}

/** A time of day: "9h", "9h30", "14 h", "9:30", "9am", "2:15 pm", "at 7", "midi", "noon". */
function takeTime(text: string): { time: string; rest: string } | null {
    const patterns: Array<[RegExp, (m: RegExpMatchArray) => [number, number] | null]> = [
        [/\b(?:(?:a|at|vers)\s+)?(\d{1,2}):(\d{2})\s*(am|pm)?\b/, (m) => [twelve(Number(m[1]), m[3]), Number(m[2])]],
        [/\b(?:(?:a|at|vers)\s+)?(\d{1,2})\s*(am|pm)\b/, (m) => [twelve(Number(m[1]), m[2]), 0]],
        [/\b(?:(?:a|vers)\s+)?(\d{1,2})\s*h\s*(\d{2})?(?![a-z])/, (m) => [Number(m[1]), m[2] ? Number(m[2]) : 0]],
        [/\b(?:a|at)\s+(\d{1,2})\b(?!\s*(?:fois|times|jours?|semaines?|mois|ans?|days?|weeks?|months?|years?|[a-z]))/, (m) => [Number(m[1]), 0]],
        [/\b(?:(?:a|at)\s+)?(?:midi|noon)\b/, () => [12, 0]],
        [/\b(?:(?:a|at)\s+)?(?:minuit|midnight)\b/, () => [0, 0]],
    ];

    for (const [pattern, read] of patterns) {
        const m = text.match(pattern);
        if (!m) continue;
        const parts = read(m);
        if (!parts || parts[0] > 23 || parts[1] > 59) continue;
        return { time: `${pad(parts[0])}:${pad(parts[1])}`, rest: text.replace(m[0], ' ') };
    }
    return null;
}

function twelve(hour: number, half?: string): number {
    if (!half) return hour;
    if (half === 'am') return hour === 12 ? 0 : hour;
    return hour === 12 ? 12 : hour + 12;
}

/**
 * A repetition typed in plain words, as a rule over `base`: what the text says replaces
 * the frequency, days and end; the time is kept when the text gives none. Null when
 * nothing in it reads as a repetition.
 */
export function parseNaturalRule(text: string, base: Rule, today: Date = new Date()): Rule | null {
    let s = clean(text);
    if (!s) return null;

    const rule: Rule = { ...base, days: [...base.days], interval: 1, end: 'never' };
    let understood = false;
    let freq: Freq | null = null;
    let m: RegExpMatchArray | null;

    const time = takeTime(s);
    if (time) {
        rule.time = time.time;
        s = time.rest;
        understood = true;
    }

    if ((m = s.match(/\b(?:jusqu'?\s*(?:au|a|en)|until|till|through)\s+(.+?)(?=\s+\d+\s*(?:fois|times)\b|\s*$)/))) {
        const day = parseDay(m[1], today);
        if (day) {
            rule.end = 'until';
            rule.until = isoDate(day.date);
            s = s.replace(m[0], ' ');
            understood = true;
        }
    }

    if ((m = s.match(/\b(\d{1,3})\s*(?:fois|times)\b/)) || (m = s.match(/\b(once)\b/))) {
        rule.end = 'count';
        rule.count = m[1] === 'once' ? 1 : Math.max(1, Number(m[1]));
        s = s.replace(m[0], ' ');
        understood = true;
    }

    // "une semaine sur deux", "every other week", "toutes les 2 semaines", "every 3 months".
    if ((m = s.match(new RegExp(`\\b(?:un|une)\\s+(${UNIT})\\s+sur\\s+(${NUMBER})\\b`)))) {
        freq = unitOf(m[1]);
        rule.interval = numberOf(m[2]) ?? 1;
    } else if ((m = s.match(new RegExp(`\\b(?:toutes les|tous les|every|chaque)\\s+(${NUMBER})\\s+(${UNIT})\\b`)))) {
        freq = unitOf(m[2]);
        rule.interval = numberOf(m[1]) ?? 1;
    }
    if (freq) {
        s = s.replace(m![0], ' ');
        understood = true;
    }
    rule.interval = Math.max(1, Math.min(999, rule.interval));

    // The nth weekday of the month.
    if ((m = s.match(new RegExp(`\\b(${ORDINAL})\\s+(${WEEKDAY})s?\\b`)))) {
        return { ...rule, freq: 'monthly', monthMode: 'nth', nth: ORDINALS[m[1]], nthDay: WEEKDAYS[m[2]], interval: freq === 'monthly' ? rule.interval : 1 };
    }

    if (/\b(?:dernier jour du mois|fin (?:du|de chaque) mois|last day of (?:the|every|each) month|end of (?:the|every|each) month)\b/.test(s)) {
        return { ...rule, freq: 'monthly', monthMode: 'day', monthDay: -1, interval: freq === 'monthly' ? rule.interval : 1 };
    }

    // A day of the month: "le 15 de chaque mois", "tous les mois le 1er", "the 15th of each month".
    if ((m = s.match(/\b(\d{1,2})(?:er|re|e|eme|st|nd|rd|th)?\s+(?:de chaque mois|du mois|de tous les mois|of (?:every|each|the) month)\b/))
        || (m = s.match(/\b(?:chaque mois|tous les mois|every month|each month|monthly)\s+(?:le\s+|on\s+(?:the\s+)?|the\s+)?(\d{1,2})(?:er|re|e|eme|st|nd|rd|th)?\b/))) {
        const day = Number(m[1]);
        if (day >= 1 && day <= 31) {
            return { ...rule, freq: 'monthly', monthMode: 'day', monthDay: day, interval: freq === 'monthly' ? rule.interval : 1 };
        }
    }

    // A date every year: "tous les ans le 14 juillet", "every year on december 25".
    if ((m = s.match(/\b(?:tous les ans|chaque annee|every year|each year|yearly|annually)\s+(?:le\s+|on\s+(?:the\s+)?)?(.+)$/))
        || (m = s.match(/^(?:le\s+|on\s+(?:the\s+)?)?(.+?)\s+(?:de chaque annee|tous les ans|every year|each year)$/))) {
        const day = parseDay(m[1].trim(), today);
        if (day) {
            return { ...rule, freq: 'yearly', month: day.month, yearDay: day.day, interval: freq === 'yearly' ? rule.interval : 1 };
        }
    }

    // Weekdays, the working week or the weekend.
    let days: number[] = [];
    if (/\b(?:en semaine|jours? ouvres|jours? de semaine|du lundi au vendredi|weekdays?|monday (?:to|through) friday)\b/.test(s)) {
        days = [1, 2, 3, 4, 5];
    } else if (/\b(?:week-?ends?|fins? de semaine)\b/.test(s)) {
        days = [6, 0];
    } else {
        for (const word of s.match(new RegExp(`\\b(?:${WEEKDAY})s?\\b`, 'g')) ?? []) {
            const day = WEEKDAYS[word.replace(/s$/, '')];
            if (day !== undefined && !days.includes(day)) days.push(day);
        }
    }
    if (days.length) {
        return { ...rule, freq: 'weekly', days, interval: freq === 'weekly' ? rule.interval : 1 };
    }

    if (!freq) {
        if (/\b(?:tous les jours|chaque jour|quotidien(?:ne)?(?:ment)?|every day|each day|daily)\b/.test(s)) freq = 'daily';
        else if (/\b(?:toutes les semaines|chaque semaine|hebdomadaire(?:ment)?|every week|each week|weekly)\b/.test(s)) freq = 'weekly';
        else if (/\b(?:tous les mois|chaque mois|mensuel(?:le)?(?:ment)?|every month|each month|monthly)\b/.test(s)) freq = 'monthly';
        else if (/\b(?:tous les ans|chaque annee|annuel(?:le)?(?:ment)?|every year|each year|yearly|annually)\b/.test(s)) freq = 'yearly';
        if (freq) understood = true;
    }

    if (freq) {
        rule.freq = freq;
        if (freq === 'weekly' && !rule.days.length) rule.days = [today.getDay()];
        return rule;
    }

    // Only a time or an end: the current repetition with them.
    return understood ? { ...rule, freq: base.freq, interval: base.interval } : null;
}

function language(locale: string): 'fr' | 'en' {
    return String(locale || 'en').toLowerCase().startsWith('fr') ? 'fr' : 'en';
}

function safeFormat(locale: string, options: Intl.DateTimeFormatOptions, date: Date): string {
    try {
        return new Intl.DateTimeFormat(locale || undefined, options).format(date).replace(/ /g, ' ');
    } catch {
        return new Intl.DateTimeFormat('en', options).format(date).replace(/ /g, ' ');
    }
}

/** Weekday names, Sunday first. 1 November 2026 is a Sunday. */
export function weekdayNames(locale: string, style: 'long' | 'short' | 'narrow' = 'long'): string[] {
    return Array.from({ length: 7 }, (_, i) => safeFormat(locale, { weekday: style }, new Date(2026, 10, 1 + i)));
}

export function monthNames(locale: string, style: 'long' | 'short' = 'long'): string[] {
    return Array.from({ length: 12 }, (_, i) => safeFormat(locale, { month: style }, new Date(2026, i, 1)));
}

function list(items: string[], locale: string): string {
    try {
        return new Intl.ListFormat(locale || undefined, { style: 'long', type: 'conjunction' }).format(items);
    } catch {
        return items.length < 2 ? items.join('') : `${items.slice(0, -1).join(', ')} & ${items[items.length - 1]}`;
    }
}

function englishOrdinal(n: number): string {
    const tens = n % 100;
    if (tens >= 11 && tens <= 13) return `${n}th`;
    return `${n}${['th', 'st', 'nd', 'rd'][n % 10] ?? 'th'}`;
}

function frenchDay(n: number): string {
    return n === 1 ? '1er' : String(n);
}

function capitalize(text: string): string {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

/** The weekdays of the rule, from the first day of the week. */
function orderedDays(days: number[], weekStart: number): number[] {
    return [...new Set(days)].sort((a, b) => ((a - weekStart + 7) % 7) - ((b - weekStart + 7) % 7));
}

function setOf(days: number[]): string {
    return [...new Set(days)].sort().join(',');
}

/**
 * The rule as one sentence: "Tous les lundis et jeudis à 9h", "Every 2 weeks on Tuesday,
 * until December 31, 2026". `time: false` leaves the hour out.
 */
export function describeRule(rule: Rule, locale: string, options: { time?: boolean; weekStart?: number } = {}): string {
    const lang = language(locale);
    const names = weekdayNames(locale);
    const n = rule.interval;
    const days = orderedDays(rule.days.length ? rule.days : [0], options.weekStart ?? 1);
    const workweek = setOf(days) === '1,2,3,4,5';
    const weekend = setOf(days) === '0,6';
    const everyDay = days.length === 7;
    let text = '';

    if (lang === 'fr') {
        const ordinal: Record<number, string> = { 1: 'premier', 2: 'deuxième', 3: 'troisième', 4: 'quatrième', [-1]: 'dernier' };
        const months = monthNames(locale);
        if (rule.freq === 'daily') {
            text = n === 1 ? 'Tous les jours' : `Tous les ${n} jours`;
        } else if (rule.freq === 'weekly') {
            if (n === 1) {
                text = workweek ? 'Du lundi au vendredi'
                    : weekend ? 'Tous les week-ends'
                    : everyDay ? 'Tous les jours'
                    : `Tous les ${list(days.map((d) => `${names[d]}s`), locale)}`;
            } else {
                text = `Toutes les ${n} semaines, `
                    + (workweek ? 'du lundi au vendredi' : weekend ? 'le week-end' : everyDay ? 'tous les jours' : list(days.map((d) => `le ${names[d]}`), locale));
            }
        } else if (rule.freq === 'monthly') {
            text = (n === 1 ? 'Tous les mois' : `Tous les ${n} mois`) + ', le '
                + (rule.monthMode === 'nth'
                    ? `${ordinal[rule.nth] ?? ordinal[1]} ${names[rule.nthDay]}`
                    : rule.monthDay === -1 ? 'dernier jour' : frenchDay(rule.monthDay));
        } else {
            text = (n === 1 ? 'Tous les ans' : `Tous les ${n} ans`) + `, le ${frenchDay(rule.yearDay)} ${months[rule.month - 1]}`;
        }

        if (options.time !== false && rule.time) {
            const [hour, minute] = rule.time.split(':');
            text += ` à ${Number(hour)}h${minute === '00' ? '' : minute}`;
        }
        if (rule.end === 'until' && rule.until) {
            const [y, mo, d] = rule.until.split('-').map(Number);
            text += `, jusqu’au ${frenchDay(d)} ${months[mo - 1]} ${y}`;
        } else if (rule.end === 'count') {
            text += `, ${rule.count} fois`;
        }
        return capitalize(text);
    }

    const ordinal: Record<number, string> = { 1: 'first', 2: 'second', 3: 'third', 4: 'fourth', [-1]: 'last' };
    if (rule.freq === 'daily') {
        text = n === 1 ? 'Every day' : `Every ${n} days`;
    } else if (rule.freq === 'weekly') {
        if (n === 1) {
            text = workweek ? 'Every weekday'
                : weekend ? 'Every weekend'
                : everyDay ? 'Every day'
                : `Every ${list(days.map((d) => names[d]), locale)}`;
        } else {
            text = `Every ${n} weeks `
                + (workweek ? 'on weekdays' : weekend ? 'on weekends' : everyDay ? 'on every day' : `on ${list(days.map((d) => names[d]), locale)}`);
        }
    } else if (rule.freq === 'monthly') {
        text = (n === 1 ? 'Every month' : `Every ${n} months`) + ' on the '
            + (rule.monthMode === 'nth'
                ? `${ordinal[rule.nth] ?? ordinal[1]} ${names[rule.nthDay]}`
                : rule.monthDay === -1 ? 'last day' : englishOrdinal(rule.monthDay));
    } else {
        text = (n === 1 ? 'Every year' : `Every ${n} years`)
            + ` on ${safeFormat(locale, { month: 'long', day: 'numeric' }, new Date(2024, rule.month - 1, rule.yearDay))}`;
    }

    if (options.time !== false && rule.time) {
        const [hour, minute] = rule.time.split(':').map(Number);
        text += ` at ${safeFormat(locale, { hour: 'numeric', minute: '2-digit' }, new Date(2026, 0, 1, hour, minute))}`;
    }
    if (rule.end === 'until' && rule.until) {
        const [y, mo, d] = rule.until.split('-').map(Number);
        text += `, until ${safeFormat(locale, { year: 'numeric', month: 'long', day: 'numeric' }, new Date(y, mo - 1, d))}`;
    } else if (rule.end === 'count') {
        text += rule.count === 1 ? ', once' : `, ${rule.count} times`;
    }
    return capitalize(text);
}
