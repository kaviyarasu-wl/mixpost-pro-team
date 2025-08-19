import {isProxy, toRaw} from "vue";
import {utcToZonedTime} from "date-fns-tz";
import useDateLocalize from "./Composables/useDateLocalize";

export function getWindowDimensions() {
    let width = Math.max(
        document.body.scrollWidth,
        document.documentElement.scrollWidth,
        document.body.offsetWidth,
        document.documentElement.offsetWidth,
        document.documentElement.clientWidth
    );

    let height = Math.max(
        document.body.scrollHeight,
        document.documentElement.scrollHeight,
        document.body.offsetHeight,
        document.documentElement.offsetHeight,
        document.documentElement.clientHeight
    );

    return {width, height};
}

export function lightOrDark(color) {
    // Variables for red, green, blue values
    let r, g, b, hsp;

    // Check the format of the color, HEX or RGB?
    if (color.match(/^rgb/)) {
        // If RGB --> store the red, green, blue values in separate variables
        color = color.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+(?:\.\d+)?))?\)$/);

        r = color[1];
        g = color[2];
        b = color[3];
    } else {
        // If hex --> Convert it to RGB: http://gist.github.com/983661
        color = +("0x" + color.slice(1).replace(
            color.length < 5 && /./g, '$&$&'));

        r = color >> 16;
        g = color >> 8 & 255;
        b = color & 255;
    }

    // HSP (Highly Sensitive Poo) equation from http://alienryderflex.com/hsp.html
    hsp = Math.sqrt(
        0.299 * (r * r) +
        0.587 * (g * g) +
        0.114 * (b * b)
    );

    // Using the HSP value, determine whether the color is light or dark
    if (hsp > 127.5) {
        return 'light';
    }

    return 'dark';
}

export function decomposeString(string) {
    return string.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

export function isTimePast(date, timeZone = null) {
    const today = timeZone ? utcToZonedTime(new Date().toISOString(), timeZone) : new Date();

    return date.getTime() < today.getTime()
}

export function isDatePast(date, timeZone = null) {
    const today = timeZone ? utcToZonedTime(new Date().toISOString(), timeZone) : new Date();

    today.setHours(0, 0, 0, 0);

    return date < today;
}

export function isDateTimePast(datetime, timeZone = null) {
    const today = timeZone ? utcToZonedTime(new Date().toISOString(), timeZone) : new Date();
    today.setSeconds(0);

    return datetime < today;
}

export function convertTime12to24(time12h) {
    const [time, modifier] = time12h.split(' ');

    let [hours, minutes] = time.split(':');

    if (hours === '12') {
        hours = '00';
    }

    if (modifier === 'PM') {
        hours = parseInt(hours, 10) + 12;
    }

    return `${hours}:${minutes}`;
}

export function parseDateTime(datetime, timeZone, _timeFormat) {
    const zonedDateTime = utcToZonedTime(datetime, timeZone);
    const today = utcToZonedTime(new Date().toISOString(), timeZone);

    return {
        zonedDateTime,
        format: zonedDateTime.getFullYear() === today.getFullYear()
            ? `MMMM d, ${timeFormat(_timeFormat)}`
            : `MMMM d, yyyy, ${timeFormat(_timeFormat)}`,
    };
}

export function dateTimeFormat(datetime, timeZone, _timeFormat, customFormat = null) {
    const {translatedFormat} = useDateLocalize();

    const {zonedDateTime, format} = parseDateTime(datetime, timeZone, _timeFormat);

    return translatedFormat(zonedDateTime, customFormat ? customFormat : format);
}

export function timeFormat(value) {
    return value === 24 ? 'H:mm' : 'h:mmaaa';
}

export function convertTime24to12(time24h, customFormat = 'h:mmaaa') {
    const date = new Date();

    const [hours, minutes] = time24h.split(':');

    date.setHours(hours, minutes);

    const {translatedFormat} = useDateLocalize();

    return translatedFormat(date, customFormat);
}

export function toRawIfProxy(obj) {
    return isProxy(obj) ? toRaw(obj) : obj
}

export function convertLaravelErrorsToString(object) {
    return Object.keys(object).map((item) => {
        if (typeof object[item] === 'string') {
            return object[item];
        }

        return object[item].join("\n");
    }).join("\n");
}

export function extractFirstURL(text) {
    const urlRegex = /(\bhttps?:\/\/[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*))/i;
    const match = text.match(urlRegex);
    return match ? match[0] : null;
}

export function gwUrl(url) {
    const baseUrl = process.env.GW_APP_URL || 'https://app.gravitywrite.com';
    return `${baseUrl.replace(/\/?$/, '/')}${url.replace(/^\//, '')}`;
}
