@props([
    'name' => $attributes->whereStartsWith('wire:model')->first() ?? $attributes->whereStartsWith('x-model')->first(),
    'value' => null,
    'minDate' => null,
    'maxDate' => null,
    'disabled' => false,
    'disabledDates' => [],
    'locale' => 'en',
    'firstDayOfWeek' => 0,
    'multiple' => false,
    'range' => false,
    'showReset' => false,
])

@php
    $disabled = filled($disabled) && $disabled;
    $multiple = filled($multiple) && $multiple;
    $range = filled($range) && $range;
    
    if ($range) {
        $value = $value ?? ['start' => null, 'end' => null];
    } elseif ($multiple) {
        $value = $value ?? [];
    } else {
        $value = $value ?? '';
    }
@endphp

<div
    x-data="{
        selectedDate: {{ json_encode($value) }},
        isMultiple: {{ json_encode($multiple) }},
        isRange: {{ json_encode($range) }},
        currentMonth: null,
        currentYear: null,
        minDate: {{ $minDate ? json_encode($minDate) : 'null' }},
        maxDate: {{ $maxDate ? json_encode($maxDate) : 'null' }},
        disabledDates: {{ json_encode($disabledDates) }},
        isDisabled: {{ json_encode($disabled) }},
        firstDayOfWeek: {{ json_encode($firstDayOfWeek) }},
        daysInMonth: [],
        weekDays: [],
        
        init() {
            let today;
            if (this.isRange) {
                today = this.selectedDate.start ? new Date(this.selectedDate.start) : new Date();
            } else if (this.isMultiple) {
                today = this.selectedDate.length > 0 ? new Date(this.selectedDate[0]) : new Date();
            } else {
                today = this.selectedDate ? new Date(this.selectedDate) : new Date();
            }
            this.currentMonth = today.getMonth();
            this.currentYear = today.getFullYear();
            
            this.initWeekDays();
            this.generateCalendar();
            
            this.$watch('currentMonth', () => this.generateCalendar());
            this.$watch('currentYear', () => this.generateCalendar());
            
            this.$watch('selectedDate', (value) => {
                this.$root?._x_model?.set(value);
                
                let wireModel = this?.$root.getAttributeNames().find(n => n.startsWith('wire:model'));
                if(this.$wire && wireModel){
                    let prop = this.$root.getAttribute(wireModel);
                    this.$wire.set(prop, value, wireModel?.includes('.live'));
                }
            });
        },
        
        initWeekDays() {
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            this.weekDays = [];
            for (let i = 0; i < 7; i++) {
                this.weekDays.push(days[(this.firstDayOfWeek + i) % 7]);
            }
        },
        
        generateCalendar() {
            const firstDay = new Date(this.currentYear, this.currentMonth, 1);
            const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
            const prevLastDay = new Date(this.currentYear, this.currentMonth, 0);
            
            const firstDayIndex = (firstDay.getDay() - this.firstDayOfWeek + 7) % 7;
            const lastDayDate = lastDay.getDate();
            const prevLastDayDate = prevLastDay.getDate();
            
            this.daysInMonth = [];
            
            for (let i = firstDayIndex; i > 0; i--) {
                this.daysInMonth.push({
                    date: prevLastDayDate - i + 1,
                    month: this.currentMonth - 1,
                    year: this.currentYear,
                    isCurrentMonth: false,
                    isToday: false,
                    isSelected: false,
                    isInRange: false,
                    isDisabled: true,
                });
            }
            
            for (let date = 1; date <= lastDayDate; date++) {
                const dateStr = this.formatDate(this.currentYear, this.currentMonth, date);
                const isToday = this.isToday(this.currentYear, this.currentMonth, date);
                let isSelected = false;
                let isInRange = false;
                
                if (this.isRange) {
                    const start = this.selectedDate.start;
                    const end = this.selectedDate.end;
                    if (start && end) {
                        isSelected = dateStr === start || dateStr === end;
                        isInRange = dateStr > start && dateStr < end;
                    } else if (start) {
                        isSelected = dateStr === start;
                    }
                } else if (this.isMultiple) {
                    isSelected = this.selectedDate.includes(dateStr);
                } else {
                    isSelected = this.selectedDate === dateStr;
                }
                
                const isDisabled = this.isDateDisabled(dateStr);
                
                this.daysInMonth.push({
                    date,
                    month: this.currentMonth,
                    year: this.currentYear,
                    isCurrentMonth: true,
                    isToday,
                    isSelected,
                    isInRange,
                    isDisabled,
                });
            }
            
            const remainingDays = 42 - this.daysInMonth.length;
            for (let date = 1; date <= remainingDays; date++) {
                this.daysInMonth.push({
                    date,
                    month: this.currentMonth + 1,
                    year: this.currentYear,
                    isCurrentMonth: false,
                    isToday: false,
                    isSelected: false,
                    isInRange: false,
                    isDisabled: true,
                });
            }
        },
        
        selectDate(day) {
            if (this.isDisabled || day.isDisabled || !day.isCurrentMonth) return;
            
            const dateStr = this.formatDate(day.year, day.month, day.date);
            
            if (this.isRange) {
                if (!this.selectedDate.start || (this.selectedDate.start && this.selectedDate.end)) {
                    this.selectedDate = { start: dateStr, end: null };
                } else {
                    if (dateStr >= this.selectedDate.start) {
                        this.selectedDate.end = dateStr;
                    } else {
                        this.selectedDate = { start: dateStr, end: this.selectedDate.start };
                    }
                    this.selectedDate = { ...this.selectedDate };
                }
            } else if (this.isMultiple) {
                const index = this.selectedDate.indexOf(dateStr);
                if (index > -1) {
                    this.selectedDate.splice(index, 1);
                } else {
                    this.selectedDate.push(dateStr);
                }
                this.selectedDate = [...this.selectedDate];
            } else {
                this.selectedDate = dateStr;
            }
            
            this.generateCalendar();
            
            this.$dispatch('date-selected', { 
                date: this.isRange ? this.selectedDate : (this.isMultiple ? this.selectedDate : dateStr),
                dates: this.isRange ? [this.selectedDate.start, this.selectedDate.end].filter(Boolean) : (this.isMultiple ? this.selectedDate : [dateStr])
            });
        },
        
        isToday(year, month, date) {
            const today = new Date();
            return today.getFullYear() === year && 
                   today.getMonth() === month && 
                   today.getDate() === date;
        },
        
        isDateDisabled(dateStr) {
            if (this.disabledDates.includes(dateStr)) return true;
            
            if (this.minDate && dateStr < this.minDate) return true;
            if (this.maxDate && dateStr > this.maxDate) return true;
            
            return false;
        },
        
        formatDate(year, month, date) {
            const m = String(month + 1).padStart(2, '0');
            const d = String(date).padStart(2, '0');
            return year + '-' + m + '-' + d;
        },
        
        getMonthName() {
            const monthKeys = [
                'january',
                'february',
                'march',
                'april',
                'may',
                'june',
                'july',
                'august',
                'september',
                'october',
                'november',
                'december'
            ];
            const monthName = window.t(monthKeys[this.currentMonth]);
            return monthName ? monthName.charAt(0).toUpperCase() + monthName.slice(1) : monthName;
        },
        
        previousMonth() {
            if (this.isDisabled) return;
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
        },
        
        nextMonth() {
            if (this.isDisabled) return;
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
        },
        
        previousYear() {
            if (this.isDisabled) return;
            this.currentYear--;
        },
        
        nextYear() {
            if (this.isDisabled) return;
            this.currentYear++;
        },
        
        goToToday() {
            if (this.isDisabled) return;
            const today = new Date();
            this.currentMonth = today.getMonth();
            this.currentYear = today.getFullYear();
            const todayStr = this.formatDate(today.getFullYear(), today.getMonth(), today.getDate());
            
            if (this.isMultiple) {
                const index = this.selectedDate.indexOf(todayStr);
                if (index === -1) {
                    this.selectedDate.push(todayStr);
                    this.selectedDate = [...this.selectedDate];
                }
            } else {
                this.selectedDate = todayStr;
            }
            
            this.generateCalendar();
            this.$dispatch('date-selected', { 
                date: this.isMultiple ? this.selectedDate : todayStr,
                dates: this.isMultiple ? this.selectedDate : [todayStr]
            });
        },
        
        reset() {
            if (this.isDisabled) return;
            
            if (this.isRange) {
                this.selectedDate = { start: null, end: null };
            } else if (this.isMultiple) {
                this.selectedDate = [];
            } else {
                this.selectedDate = '';
            }
            
            this.generateCalendar();
            this.$dispatch('date-selected', { 
                date: this.selectedDate,
                dates: []
            });
        }
    }"
    {{ $attributes->class([
        'select-none',
        'opacity-50 cursor-not-allowed' => $disabled,
    ]) }}
>
    @if ($name)
        <input
            type="hidden"
            name="{{ $name }}"
            x-bind:value="selectedDate"
        />
    @endif

    <div class="bg-white dark:bg-neutral-900 rounded-box shadow-lg p-4 w-full max-w-sm">
        <div class="flex items-center justify-between mb-4">
            <neura::button
                variant="ghost"
                size="xs"
                icon="chevron-double-left"
                x-on:click="previousYear"
                x-bind:disabled="isDisabled"
            />
            
            <neura::button
                variant="ghost"
                size="xs"
                icon="chevron-left"
                x-on:click="previousMonth"
                x-bind:disabled="isDisabled"
            />
            
            <div class="flex flex-col items-center">
                <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-100" x-text="getMonthName()"></h3>
                <span class="text-xs text-neutral-500 dark:text-neutral-400" x-text="currentYear"></span>
            </div>
            
            <neura::button
                variant="ghost"
                size="xs"
                icon="chevron-right"
                x-on:click="nextMonth"
                x-bind:disabled="isDisabled"
            />
            
            <neura::button
                variant="ghost"
                size="xs"
                icon="chevron-double-right"
                x-on:click="nextYear"
                x-bind:disabled="isDisabled"
            />
        </div>

        <div class="grid grid-cols-7 gap-1 mb-2">
            <template x-for="day in weekDays" :key="day">
                <div class="text-center text-xs font-medium text-neutral-500 dark:text-neutral-400 py-2" x-text="day"></div>
            </template>
        </div>

        <div class="grid grid-cols-7 gap-1">
            <template x-for="(day, index) in daysInMonth" :key="index">
                <neura::button
                    variant="ghost"
                    size="xs"
                    x-on:click="selectDate(day)"
                    x-bind:disabled="day.isDisabled || !day.isCurrentMonth || isDisabled"
                    x-bind:class="{
                        '[&]:bg-primary-600! [&]:text-white! dark:[&]:bg-primary-500! dark:[&]:text-white!': day.isSelected && day.isCurrentMonth,
                        '[&]:bg-primary-100! [&]:text-primary-700! dark:[&]:bg-primary-900/50! dark:[&]:text-primary-300!': day.isInRange && day.isCurrentMonth,
                        '[&]:bg-primary-50! [&]:text-primary-600! [&]:ring-1! [&]:ring-primary-300! dark:[&]:bg-primary-950/50! dark:[&]:text-primary-400! dark:[&]:ring-primary-700!': day.isToday && !day.isSelected && !day.isInRange && day.isCurrentMonth,
                        '[&]:text-neutral-400!': !day.isCurrentMonth,
                    }"
                    class="w-full! h-fit! aspect-square!"
                    x-text="day.date"
                />
            </template>
        </div>

        <div class="flex justify-center gap-2 mt-4">
            <neura::button
                variant="outline"
                size="sm"
                x-on:click="goToToday"
                x-bind:disabled="isDisabled"
            >
                Today
            </neura::button>
            
            @if ($showReset)
                <neura::button
                    variant="outline"
                    size="sm"
                    x-on:click="reset"
                    x-bind:disabled="isDisabled"
                >
                    Reset
                </neura::button>
            @endif
        </div>
    </div>
</div>

