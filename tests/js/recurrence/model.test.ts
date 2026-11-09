import { test } from 'node:test';
import assert from 'node:assert/strict';
import { defaultRule, iso, occurrences, parseRRule, toRRule } from '../../../resources/js/features/forms/recurrence/model.ts';

/**
 * The recurrence editor holds a rule it reads from and writes to an RRULE (RFC 5545) string,
 * and lists the dates it gives from a start. Friday 6 November 2026 is the start throughout.
 */

const start = new Date(2026, 10, 6);
const dates = (rule: string, limit = 6, from?: Date) => occurrences(parseRRule(rule, start), start, limit, from).map(iso);

test('the default rule repeats every week on the start weekday', () => {
    const rule = defaultRule(start);
    assert.equal(rule.freq, 'weekly');
    assert.deepEqual(rule.days, [5]);
    assert.equal(rule.monthDay, 6);
    assert.equal(rule.nth, 1);
    assert.equal(rule.nthDay, 5);
    assert.equal(rule.month, 11);
    assert.equal(toRRule(rule), 'FREQ=WEEKLY;BYDAY=FR');
});

test('an RRULE reads back into the rule and writes out the same', () => {
    const samples = [
        'FREQ=DAILY',
        'FREQ=DAILY;INTERVAL=3;COUNT=10',
        'FREQ=WEEKLY;BYDAY=MO,TH;BYHOUR=9;BYMINUTE=0',
        'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU;UNTIL=20261231T235959',
        'FREQ=MONTHLY;BYMONTHDAY=15',
        'FREQ=MONTHLY;BYMONTHDAY=-1',
        'FREQ=MONTHLY;BYDAY=1MO;BYHOUR=10;BYMINUTE=30',
        'FREQ=MONTHLY;INTERVAL=3;BYDAY=-1FR',
        'FREQ=YEARLY;BYMONTH=11;BYMONTHDAY=6',
    ];
    for (const sample of samples) assert.equal(toRRule(parseRRule(sample, start)), sample);
});

test('reading is forgiving about the shape of the string', () => {
    assert.equal(toRRule(parseRRule('RRULE:FREQ=WEEKLY;BYDAY=SU,MO', start)), 'FREQ=WEEKLY;BYDAY=MO,SU');
    assert.equal(toRRule(parseRRule('DTSTART:20261106T090000\nRRULE:freq=weekly;wkst=MO;interval=1;byday=we', start)), 'FREQ=WEEKLY;BYDAY=WE');
    assert.equal(toRRule(parseRRule('FREQ=MONTHLY;BYDAY=TH;BYSETPOS=2', start)), 'FREQ=MONTHLY;BYDAY=2TH');
    assert.equal(toRRule(parseRRule('FREQ=WEEKLY;UNTIL=20270115', start)), 'FREQ=WEEKLY;BYDAY=FR;UNTIL=20270115T235959');
    assert.equal(toRRule(parseRRule('', start)), 'FREQ=WEEKLY;BYDAY=FR');
    assert.equal(toRRule(parseRRule('nonsense', start)), 'FREQ=WEEKLY;BYDAY=FR');
});

test('the time is written only when asked for', () => {
    const rule = parseRRule('FREQ=DAILY;BYHOUR=7;BYMINUTE=5', start);
    assert.equal(rule.time, '07:05');
    assert.equal(toRRule(rule, { time: false }), 'FREQ=DAILY');
});

test('weekly dates follow the days and skip the off weeks', () => {
    assert.deepEqual(dates('FREQ=WEEKLY;BYDAY=MO,TH'), ['2026-11-09', '2026-11-12', '2026-11-16', '2026-11-19', '2026-11-23', '2026-11-26']);
    assert.deepEqual(dates('FREQ=WEEKLY;INTERVAL=2;BYDAY=TU', 3), ['2026-11-17', '2026-12-01', '2026-12-15']);
});

test('daily, monthly and yearly dates', () => {
    assert.deepEqual(dates('FREQ=DAILY;INTERVAL=3', 3), ['2026-11-06', '2026-11-09', '2026-11-12']);
    assert.deepEqual(dates('FREQ=MONTHLY;BYDAY=1MO', 3), ['2026-12-07', '2027-01-04', '2027-02-01']);
    assert.deepEqual(dates('FREQ=MONTHLY;BYDAY=-1FR', 2), ['2026-11-27', '2026-12-25']);
    assert.deepEqual(dates('FREQ=MONTHLY;BYMONTHDAY=-1', 3), ['2026-11-30', '2026-12-31', '2027-01-31']);
    // Months without a 31st are skipped, as RFC 5545 says.
    assert.deepEqual(dates('FREQ=MONTHLY;BYMONTHDAY=31', 3), ['2026-12-31', '2027-01-31', '2027-03-31']);
    assert.deepEqual(dates('FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=29', 2), ['2028-02-29', '2032-02-29']);
});

test('an end by date or by count stops the dates', () => {
    assert.deepEqual(dates('FREQ=WEEKLY;BYDAY=FR;UNTIL=20261120T235959'), ['2026-11-06', '2026-11-13', '2026-11-20']);
    assert.deepEqual(dates('FREQ=DAILY;COUNT=2'), ['2026-11-06', '2026-11-07']);
    // COUNT counts from the start, even the dates before `from`.
    assert.deepEqual(dates('FREQ=DAILY;COUNT=3', 6, new Date(2026, 10, 7)), ['2026-11-07', '2026-11-08']);
});
