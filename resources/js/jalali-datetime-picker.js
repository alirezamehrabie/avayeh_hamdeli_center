const JALALI_MONTHS = [
    'فروردین', 'اردیبهشت', 'خرداد', 'تیر',
    'مرداد', 'شهریور', 'مهر', 'آبان',
    'آذر', 'دی', 'بهمن', 'اسفند',
];

const JALALI_DAYS = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

function g2d(gy, gm, gd) {
    const gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    return (
        365 * gy +
        Math.floor((gy + 3) / 4) -
        Math.floor((gy + 99) / 100) +
        Math.floor((gy + 399) / 400) +
        gd +
        gdm[gm - 1] -
        79
    );
}

function d2g(jdn) {
    const j = jdn + 7477716439;
    const cy = Math.floor((4 * j + 146367) / 146097);
    const jj = j - Math.floor((146097 * cy + 3) / 4);
    const i = Math.floor((4000 * (jj + 1)) / 1461001);
    const ji0 = jj - Math.floor((1461 * i) / 4) + 31;
    const jm = Math.floor((80 * ji0) / 2447);
    const jd = ji0 - Math.floor((2447 * jm) / 80);
    const ji = Math.floor(jm / 11);
    const gm = jm + 2 - 12 * ji;
    const gy = 100 * (cy - 4716) + i + ji;
    return [gy, gm, jd];
}

function j2d(jy, jm, jd) {
    const r = jalaliCal(jy);
    return (
        g2d(r.gy, 3, r.march) +
        (jm - 1) * 31 -
        Math.floor(jm / 7) * (jm - 7) +
        jd -
        1
    );
}

function d2j(jdn) {
    const gy = d2g(jdn)[0];
    let jy = gy - 621;
    const r = jalaliCal(jy);
    const jdn1f = g2d(r.gy, 3, r.march);
    let k = jdn - jdn1f;
    if (k >= 0) {
        if (k <= 185) {
            return { jy, jm: 1 + Math.floor(k / 31), jd: (k % 31) + 1 };
        }
        k -= 186;
    } else {
        jy--;
        const r2 = jalaliCal(jy);
        k = jdn - g2d(r2.gy, 3, r2.march);
        if (k <= 185) {
            return { jy, jm: 1 + Math.floor(k / 31), jd: (k % 31) + 1 };
        }
        k -= 186;
    }
    return { jy, jm: 7 + Math.floor(k / 30), jd: (k % 30) + 1 };
}

function jalaliCal(jy) {
    const breaks = [
        -61, 9, 38, 199, 426, 682, 748, 431, 334, 38,
        -3, 33, 8, 235, 451, 589, 276, 22, 69, 202,
        357, -3, 35, 36, 228, 461, 587, 272, 19, 19,
        -2, 35, 13, 211, 431, 559, 246, 19, 51, -3,
        35, 13, 202, 432, 559, 265, 22, 12, 96, 188,
    ];
    const leap = 25920 / 682;
    let leapY;
    let march;
    const leapLen = (jy + 1) % 33;
    if (leapLen < 8) {
        leapY = 1;
    } else if (leapLen < 16) {
        leapY = 2;
    } else if (leapLen < 24) {
        leapY = 3;
    } else {
        leapY = 4;
    }
    const jp = breaks[0];
    let jump = 0;
    for (let i = 1; i < breaks.length; i++) {
        const jm = breaks[i];
        jump = jm - jp;
        if (jy < jm) {
            break;
        }
        jp = jm;
    }
    let nd = jy - jp;
    if (jump - nd < 6) {
        nd = jump - nd + 2 * (jump + 4 - nd) / (jump + 5);
    }
    const cy = Math.floor((nd - (jump > 6 ? 4 : 0)) / 32);
    nd += cy;
    march = Math.floor(Math.round(leap * (nd + (-14 + (jump > 6 ? -1 : 0)) * 32)) / 32);
    return { gy: 1594 + jump + cy, leap: leapY, march };
}

function jalaliMonthLength(jy, jm) {
    if (jm <= 6) {
        return 31;
    }
    if (jm <= 11) {
        return 30;
    }
    return jalaliCal(jy).leap === 4 ? 30 : 29;
}

function toJalali(gy, gm, gd) {
    return d2j(g2d(gy, gm, gd));
}

function formatJalaliNumber(n) {
    return String(n).padStart(2, '0');
}

function parseJalaliDateTime(str) {
    if (!str || !str.trim()) {
        return null;
    }
    const parts = str.trim().split(' ');
    if (parts.length !== 2) {
        return null;
    }
    const dateParts = parts[0].split('/');
    const timeParts = parts[1].split(':');
    if (dateParts.length !== 3 || timeParts.length !== 2) {
        return null;
    }
    const jy = parseInt(dateParts[0], 10);
    const jm = parseInt(dateParts[1], 10);
    const jd = parseInt(dateParts[2], 10);
    const hour = parseInt(timeParts[0], 10);
    const minute = parseInt(timeParts[1], 10);
    if (isNaN(jy) || isNaN(jm) || isNaN(jd) || isNaN(hour) || isNaN(minute)) {
        return null;
    }
    if (jm < 1 || jm > 12 || jd < 1 || jd > jalaliMonthLength(jy, jm) || hour < 0 || hour > 23 || minute < 0 || minute > 59) {
        return null;
    }
    return { jy, jm, jd, hour, minute };
}

function formatJalaliDateTime(jy, jm, jd, hour, minute) {
    return `${jy}/${formatJalaliNumber(jm)}/${formatJalaliNumber(jd)} ${formatJalaliNumber(hour)}:${formatJalaliNumber(minute)}`;
}

function jalaliDayOfWeek(jy, jm, jd) {
    return (j2d(jy, jm, jd) + 1) % 7;
}

function jalaliMonthGrid(jy, jm) {
    const firstDow = jalaliDayOfWeek(jy, jm, 1);
    const daysInMonth = jalaliMonthLength(jy, jm);
    const days = [];
    for (let i = 0; i < firstDow; i++) {
        days.push(null);
    }
    for (let d = 1; d <= daysInMonth; d++) {
        days.push(d);
    }
    return days;
}

window.JalaliDateTimePicker = function () {
    return {
        open: false,
        value: '',
        year: 1404,
        month: 1,
        day: 1,
        hour: 12,
        minute: 0,
        viewYear: 1404,
        viewMonth: 1,
        grid: [],
        months: JALALI_MONTHS,
        days: JALALI_DAYS,
        init() {
            this.syncFromValue();
            this.$watch('value', () => this.syncFromValue());
        },
        syncFromValue() {
            const parsed = parseJalaliDateTime(this.value);
            if (parsed) {
                this.year = parsed.jy;
                this.month = parsed.jm;
                this.day = parsed.jd;
                this.hour = parsed.hour;
                this.minute = parsed.minute;
                this.viewYear = parsed.jy;
                this.viewMonth = parsed.jm;
            } else {
                const now = new Date();
                const j = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
                this.viewYear = j.jy;
                this.viewMonth = j.jm;
            }
            this.buildGrid();
        },
        buildGrid() {
            this.grid = jalaliMonthGrid(this.viewYear, this.viewMonth);
        },
        prevMonth() {
            if (this.viewMonth === 1) {
                this.viewMonth = 12;
                this.viewYear--;
            } else {
                this.viewMonth--;
            }
            this.buildGrid();
        },
        nextMonth() {
            if (this.viewMonth === 12) {
                this.viewMonth = 1;
                this.viewYear++;
            } else {
                this.viewMonth++;
            }
            this.buildGrid();
        },
        prevYear() {
            this.viewYear--;
            this.buildGrid();
        },
        nextYear() {
            this.viewYear++;
            this.buildGrid();
        },
        selectDay(d) {
            if (d === null) return;
            this.year = this.viewYear;
            this.month = this.viewMonth;
            this.day = d;
            this.emit();
        },
        isSelected(d) {
            return d !== null && this.year === this.viewYear && this.month === this.viewMonth && this.day === d;
        },
        isToday(d) {
            if (d === null) return false;
            const now = new Date();
            const j = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
            return j.jy === this.viewYear && j.jm === this.viewMonth && j.jd === d;
        },
        incrementHour() {
            this.hour = (this.hour + 1) % 24;
            this.emit();
        },
        decrementHour() {
            this.hour = (this.hour + 23) % 24;
            this.emit();
        },
        incrementMinute() {
            this.minute = (this.minute + 5) % 60;
            this.emit();
        },
        decrementMinute() {
            this.minute = (this.minute + 55) % 60;
            this.emit();
        },
        setHour(h) {
            this.hour = h;
            this.emit();
        },
        setMinute(m) {
            this.minute = m;
            this.emit();
        },
        emit() {
            this.value = formatJalaliDateTime(this.year, this.month, this.day, this.hour, this.minute);
        },
        displayValue() {
            const parsed = parseJalaliDateTime(this.value);
            if (!parsed) {
                return '';
            }
            return `${parsed.jy}/${formatJalaliNumber(parsed.jm)}/${formatJalaliNumber(parsed.jd)} ${formatJalaliNumber(parsed.hour)}:${formatJalaliNumber(parsed.minute)}`;
        },
        setToday() {
            const now = new Date();
            const j = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
            this.year = j.jy;
            this.month = j.jm;
            this.day = j.jd;
            this.hour = now.getHours();
            this.minute = Math.floor(now.getMinutes() / 5) * 5;
            this.viewYear = j.jy;
            this.viewMonth = j.jm;
            this.buildGrid();
            this.emit();
        },
        clear() {
            this.value = '';
        },
        goToNow() {
            const now = new Date();
            const j = toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate());
            this.viewYear = j.jy;
            this.viewMonth = j.jm;
            this.buildGrid();
        },
        hourOptions() {
            const h = [];
            for (let i = 0; i < 24; i++) h.push(i);
            return h;
        },
        minuteOptions() {
            const m = [];
            for (let i = 0; i < 60; i += 5) m.push(i);
            return m;
        },
        hourLabel(h) {
            return formatJalaliNumber(h);
        },
        minuteLabel(m) {
            return formatJalaliNumber(m);
        },
    };
};
