import { test } from 'node:test';
import assert from 'node:assert/strict';
import { describeNaturalDate, parseNaturalDate, selectionFor } from '../../../resources/js/features/forms/natural-date/parse.ts';

/**
 * Natural-language dates for the calendar and the date picker, French and English.
 * "Today" is fixed: Friday 6 November 2026.
 */
const today = new Date(2026, 10, 6);
const fr = (text: string, weekStart = 1) => parseNaturalDate(text, { locale: 'fr', today, weekStart });
const en = (text: string, weekStart = 0) => parseNaturalDate(text, { locale: 'en', today, weekStart });
const dates = (r: ReturnType<typeof fr>) => r && r.dates;

test('relative days', () => {
    assert.deepEqual(fr("aujourd'hui"), { kind: 'single', dates: ['2026-11-06'], time: null });
    assert.deepEqual(dates(fr('demain')), ['2026-11-07']);
    assert.deepEqual(dates(fr('après-demain')), ['2026-11-08']);
    assert.deepEqual(dates(fr('hier')), ['2026-11-05']);
    assert.deepEqual(dates(en('tomorrow')), ['2026-11-07']);
    assert.deepEqual(dates(en('yesterday')), ['2026-11-05']);
    assert.deepEqual(dates(fr('dans 2 semaines')), ['2026-11-20']);
    assert.deepEqual(dates(en('in 3 days')), ['2026-11-09']);
    assert.deepEqual(dates(fr('il y a 2 jours')), ['2026-11-04']);
    assert.deepEqual(dates(en('2 days ago')), ['2026-11-04']);
});

test('weekdays: the coming one, the next one, the last one', () => {
    assert.deepEqual(dates(fr('vendredi')), ['2026-11-06']);
    assert.deepEqual(dates(fr('vendredi prochain')), ['2026-11-13']);
    assert.deepEqual(dates(fr('lundi')), ['2026-11-09']);
    assert.deepEqual(dates(en('next monday')), ['2026-11-09']);
    assert.deepEqual(dates(fr('lundi dernier')), ['2026-11-02']);
    assert.deepEqual(dates(en('last friday')), ['2026-10-30']);
});

test('dates, rolling over to next year once past', () => {
    assert.deepEqual(dates(fr('le 24 décembre')), ['2026-12-24']);
    assert.deepEqual(dates(fr('3 mars')), ['2027-03-03']);
    assert.deepEqual(dates(en('March 3')), ['2027-03-03']);
    assert.deepEqual(dates(fr('14 juillet 2028')), ['2028-07-14']);
    assert.deepEqual(dates(en('2027-01-15')), ['2027-01-15']);
    assert.deepEqual(dates(fr('14/02')), ['2027-02-14']);
    assert.deepEqual(dates(en('02/14')), ['2027-02-14']);
    assert.deepEqual(dates(fr('le 20')), ['2026-11-20']);
    assert.deepEqual(dates(fr('le 2')), ['2026-12-02']);
    assert.equal(fr('31 février'), null);
});

test('ranges', () => {
    assert.deepEqual(fr('du 3 au 7 décembre'), { kind: 'range', dates: ['2026-12-03', '2026-12-07'], time: null });
    assert.deepEqual(dates(fr('3-7 mars')), ['2027-03-03', '2027-03-07']);
    assert.deepEqual(dates(en('march 3-7')), ['2027-03-03', '2027-03-07']);
    assert.deepEqual(dates(en('from december 3 to december 7')), ['2026-12-03', '2026-12-07']);
    assert.deepEqual(dates(fr('du lundi au vendredi')), ['2026-11-09', '2026-11-13']);
    assert.deepEqual(dates(fr('entre le 10 et le 12 décembre')), ['2026-12-10', '2026-12-12']);
    assert.deepEqual(dates(fr('cette semaine')), ['2026-11-02', '2026-11-08']);
    assert.deepEqual(dates(en('this week')), ['2026-11-01', '2026-11-07']);
    assert.deepEqual(dates(fr('semaine prochaine')), ['2026-11-09', '2026-11-15']);
    assert.deepEqual(dates(fr('ce week-end')), ['2026-11-07', '2026-11-08']);
    assert.deepEqual(dates(fr('ce mois-ci')), ['2026-11-01', '2026-11-30']);
    assert.deepEqual(dates(en('next month')), ['2026-12-01', '2026-12-31']);
    assert.deepEqual(dates(fr('les 10 prochains jours')), ['2026-11-06', '2026-11-15']);
    assert.deepEqual(dates(en('next 7 days')), ['2026-11-06', '2026-11-12']);
});

test('several dates and repeats', () => {
    assert.deepEqual(fr('lundi et mercredi'), { kind: 'multiple', dates: ['2026-11-09', '2026-11-11'], time: null });
    assert.deepEqual(dates(fr('3, 5 et 9 décembre')), ['2026-12-03', '2026-12-05', '2026-12-09']);
    assert.deepEqual(dates(en('monday and wednesday')), ['2026-11-09', '2026-11-11']);
    assert.deepEqual(dates(fr('tous les lundis de décembre')), ['2026-12-07', '2026-12-14', '2026-12-21', '2026-12-28']);
    assert.deepEqual(dates(en('every friday in november')), ['2026-11-06', '2026-11-13', '2026-11-20', '2026-11-27']);
});

test('a time goes with the date', () => {
    assert.deepEqual(fr('demain à 15h'), { kind: 'single', dates: ['2026-11-07'], time: '15:00' });
    assert.deepEqual(fr('vendredi à 9h30'), { kind: 'single', dates: ['2026-11-06'], time: '09:30' });
    assert.deepEqual(fr('lundi prochain à midi'), { kind: 'single', dates: ['2026-11-09'], time: '12:00' });
    assert.deepEqual(en('tomorrow at 3pm'), { kind: 'single', dates: ['2026-11-07'], time: '15:00' });
    assert.deepEqual(en('at 9:15am'), { kind: 'single', dates: ['2026-11-06'], time: '09:15' });
    assert.deepEqual(fr('15:30'), { kind: 'single', dates: ['2026-11-06'], time: '15:30' });
});

test('nonsense is not a date', () => {
    assert.equal(fr('blabla'), null);
    assert.equal(fr('   '), null);
    assert.equal(en('the meeting'), null);
});

test('selectionFor fits a parse to the calendar mode', () => {
    const range = fr('du 3 au 5 décembre')!;
    const single = fr('demain')!;
    const several = fr('lundi et mercredi')!;

    assert.equal(selectionFor(range, 'single'), '2026-12-03');
    assert.deepEqual(selectionFor(single, 'range'), { start: '2026-11-07', end: null });
    assert.deepEqual(selectionFor(several, 'range'), { start: '2026-11-09', end: '2026-11-11' });
    assert.deepEqual(selectionFor(range, 'multiple'), ['2026-12-03', '2026-12-04', '2026-12-05']);
    assert.deepEqual(selectionFor(single, 'multiple'), ['2026-11-07']);
    assert.deepEqual(selectionFor(several, 'multiple'), ['2026-11-09', '2026-11-11']);
});

test('describeNaturalDate says what was understood, in the locale', () => {
    assert.match(describeNaturalDate(fr('vendredi prochain')!, 'single', 'fr', today), /^Vendredi 13 novembre$/);
    assert.match(describeNaturalDate(fr('demain à 15h')!, 'single', 'fr', today), /^Samedi 7 novembre à 15h$/);
    assert.match(describeNaturalDate(fr('du 3 au 7 décembre')!, 'range', 'fr', today), /^Du .*3 déc.* au .*7 déc.* · 5 jours$/);
    assert.match(describeNaturalDate(fr('lundi et mercredi')!, 'multiple', 'fr', today), /^2 dates · /);
    assert.match(describeNaturalDate(en('next friday')!, 'single', 'en', today), /^Friday, November 13$/);
    assert.match(describeNaturalDate(en('march 3-7')!, 'range', 'en', today), /^Mar.* 3.*Mar.* 7, 2027 · 5 days$/);
    assert.match(describeNaturalDate(en('tomorrow at 3pm')!, 'single', 'en', today), /^Saturday, November 7 at 3:00\sPM$/);
});

test('a month or a week as context for a day', () => {
    assert.deepEqual(dates(fr('le mois prochain le 2 décembre')), ['2026-12-02']);
    assert.deepEqual(dates(fr('le mois prochain le 2')), ['2026-12-02']);
    assert.deepEqual(dates(fr('le 2 du mois prochain')), ['2026-12-02']);
    assert.deepEqual(dates(fr('le 15 de ce mois')), ['2026-11-15']);
    assert.deepEqual(dates(en('the 2nd of next month')), ['2026-12-02']);
    assert.deepEqual(dates(en('next month on the 2nd')), ['2026-12-02']);
    assert.deepEqual(dates(fr('le 3 et le 5 du mois prochain')), ['2026-12-03', '2026-12-05']);
    assert.deepEqual(dates(fr('du 3 au 7 du mois prochain')), ['2026-12-03', '2026-12-07']);
    assert.deepEqual(dates(fr('mardi de la semaine prochaine')), ['2026-11-10']);
    assert.deepEqual(dates(fr('mardi semaine prochaine')), ['2026-11-10']);
    assert.deepEqual(dates(en('tuesday next week')), ['2026-11-10']);
    assert.deepEqual(dates(fr('lundi et jeudi de la semaine prochaine')), ['2026-11-09', '2026-11-12']);
    // Alone, they still mean the whole period
    assert.deepEqual(dates(fr('le mois prochain')), ['2026-12-01', '2026-12-31']);
    assert.deepEqual(dates(fr('semaine prochaine')), ['2026-11-09', '2026-11-15']);
});
