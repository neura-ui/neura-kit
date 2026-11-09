import { test } from 'node:test';
import assert from 'node:assert/strict';
import { defaultRule, toRRule } from '../../../resources/js/features/forms/recurrence/model.ts';
import { describeRule, parseNaturalRule } from '../../../resources/js/features/forms/recurrence/language.ts';

/**
 * A repetition typed in plain words (French or English) becomes a rule, and a rule reads
 * back as a sentence. Friday 6 November 2026 is today throughout.
 */

const today = new Date(2026, 10, 6);
const base = defaultRule(today);
const typed = (text: string) => {
    const rule = parseNaturalRule(text, base, today);
    return rule ? toRRule(rule) : null;
};

test('weekdays, with or without a time', () => {
    assert.equal(typed('tous les lundis et jeudis à 9h'), 'FREQ=WEEKLY;BYDAY=MO,TH;BYHOUR=9;BYMINUTE=0');
    assert.equal(typed('every Monday and Thursday at 9am'), 'FREQ=WEEKLY;BYDAY=MO,TH;BYHOUR=9;BYMINUTE=0');
    assert.equal(typed('le mardi à 18h30'), 'FREQ=WEEKLY;BYDAY=TU;BYHOUR=18;BYMINUTE=30');
    assert.equal(typed('every friday at 2:15 pm'), 'FREQ=WEEKLY;BYDAY=FR;BYHOUR=14;BYMINUTE=15');
    assert.equal(typed('en semaine à 8h30'), 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;BYHOUR=8;BYMINUTE=30');
    assert.equal(typed('weekdays'), 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR');
    assert.equal(typed('le week-end à midi'), 'FREQ=WEEKLY;BYDAY=SA,SU;BYHOUR=12;BYMINUTE=0');
});

test('intervals in digits or words', () => {
    assert.equal(typed('toutes les 2 semaines le mardi'), 'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU');
    assert.equal(typed('une semaine sur deux le lundi'), 'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO');
    assert.equal(typed('every other week on wednesday'), 'FREQ=WEEKLY;INTERVAL=2;BYDAY=WE');
    assert.equal(typed('tous les trois jours'), 'FREQ=DAILY;INTERVAL=3');
    assert.equal(typed('every 3 months'), 'FREQ=MONTHLY;INTERVAL=3;BYMONTHDAY=6');
    assert.equal(typed('tous les jours'), 'FREQ=DAILY');
    assert.equal(typed('daily at 7'), 'FREQ=DAILY;BYHOUR=7;BYMINUTE=0');
});

test('a day of the month, or the nth weekday', () => {
    assert.equal(typed('le premier lundi du mois à 10h'), 'FREQ=MONTHLY;BYDAY=1MO;BYHOUR=10;BYMINUTE=0');
    assert.equal(typed('the last friday of every month'), 'FREQ=MONTHLY;BYDAY=-1FR');
    assert.equal(typed('le 15 de chaque mois'), 'FREQ=MONTHLY;BYMONTHDAY=15');
    assert.equal(typed('tous les mois le 1er'), 'FREQ=MONTHLY;BYMONTHDAY=1');
    assert.equal(typed('on the 15th of each month'), 'FREQ=MONTHLY;BYMONTHDAY=15');
    assert.equal(typed('le dernier jour du mois'), 'FREQ=MONTHLY;BYMONTHDAY=-1');
});

test('a date every year', () => {
    assert.equal(typed('tous les ans le 14 juillet'), 'FREQ=YEARLY;BYMONTH=7;BYMONTHDAY=14');
    assert.equal(typed('every year on december 25'), 'FREQ=YEARLY;BYMONTH=12;BYMONTHDAY=25');
});

test('an end by date or by count', () => {
    assert.equal(typed("en semaine à 8h30 jusqu'au 31 décembre"), 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;BYHOUR=8;BYMINUTE=30;UNTIL=20261231T235959');
    assert.equal(typed('every day until march 3'), 'FREQ=DAILY;UNTIL=20270303T235959');
    assert.equal(typed('tous les jours, 10 fois'), 'FREQ=DAILY;COUNT=10');
    assert.equal(typed('weekly on monday 5 times'), 'FREQ=WEEKLY;BYDAY=MO;COUNT=5');
});

test('words it does not know give nothing', () => {
    assert.equal(typed('bonjour'), null);
    assert.equal(typed(''), null);
});

test('a rule reads back as a French sentence', () => {
    const fr = (text: string, time = true) => describeRule(parseNaturalRule(text, base, today)!, 'fr', { time });
    assert.equal(fr('tous les lundis et jeudis à 9h'), 'Tous les lundis et jeudis à 9h');
    assert.equal(fr('toutes les 2 semaines le mardi'), 'Toutes les 2 semaines, le mardi');
    assert.equal(fr('en semaine à 8h30'), 'Du lundi au vendredi à 8h30');
    assert.equal(fr('tous les jours'), 'Tous les jours');
    assert.equal(fr('le premier lundi du mois à 10h'), 'Tous les mois, le premier lundi à 10h');
    assert.equal(fr('tous les mois le 1er'), 'Tous les mois, le 1er');
    assert.equal(fr('le dernier jour du mois'), 'Tous les mois, le dernier jour');
    assert.equal(fr('tous les ans le 14 juillet'), 'Tous les ans, le 14 juillet');
    assert.equal(fr("tous les jours jusqu'au 1er janvier"), 'Tous les jours, jusqu’au 1er janvier 2027');
    assert.equal(fr('tous les jours, 10 fois'), 'Tous les jours, 10 fois');
    assert.equal(fr('tous les lundis à 9h', false), 'Tous les lundis');
});

test('a rule reads back as an English sentence', () => {
    const en = (text: string) => describeRule(parseNaturalRule(text, base, today)!, 'en', { time: true });
    assert.equal(en('every monday and thursday at 9am'), 'Every Monday and Thursday at 9:00 AM');
    assert.equal(en('every 2 weeks on tuesday'), 'Every 2 weeks on Tuesday');
    assert.equal(en('weekdays'), 'Every weekday');
    assert.equal(en('the last friday of every month'), 'Every month on the last Friday');
    assert.equal(en('on the 2nd of each month'), 'Every month on the 2nd');
    assert.equal(en('every year on december 25'), 'Every year on December 25');
    assert.equal(en('daily until march 3'), 'Every day, until March 3, 2027');
    assert.equal(en('daily 1 times'), 'Every day, once');
});
