import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.plugin(focus);

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

window.api = async (url, { method = 'GET', body } = {}) => {
    const res = await fetch(url, {
        method,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        body: body ? JSON.stringify(body) : undefined,
        credentials: 'same-origin',
    });
    let data = null;
    try { data = await res.json(); } catch (e) { /* empty body */ }
    if (!res.ok) { const err = new Error(data?.message ?? 'Request failed'); err.status = res.status; err.errors = data?.errors ?? {}; throw err; }
    return data;
};

window.formatMoney = (cents) => {
    const v = (cents ?? 0) / 100;
    return '$' + v.toLocaleString('en-US', { minimumFractionDigits: Number.isInteger(v) ? 0 : 2, maximumFractionDigits: 2 });
};

/* ---------- Toasts ---------- */
Alpine.store('toast', {
    items: [],
    push(message, type = 'success') {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, type });
        setTimeout(() => (this.items = this.items.filter((t) => t.id !== id)), 4500);
    },
});
window.toast = (m, t) => Alpine.store('toast').push(m, t);

/* ---------- Theme (admin) ---------- */
Alpine.store('theme', {
    dark: document.documentElement.dataset.theme === 'dark',
    toggle() {
        this.dark = !this.dark;
        document.documentElement.dataset.theme = this.dark ? 'dark' : 'light';
        try { localStorage.setItem('salon-theme', this.dark ? 'dark' : 'light'); } catch (e) { /* private mode */ }
        window.dispatchEvent(new CustomEvent('themechange'));
    },
});

/* ---------- Developer disclaimer modal ("I'm a guy, I don't know women's prices") ---------- */
const JOKES = {
    dashboard: {
        emojis: ['👋', '💅', '💇‍♀️'],
        title: 'Welcome, and a quick confession',
        body: "I'm a guy. I have <b>no idea</b> what women's nails, balayage or lash lifts should cost. Every price in this demo is made up, so please don't judge the numbers. 😅",
        yes: 'Fair enough 😄',
        no: 'Ask a woman next time 🙋‍♀️',
    },
    services: {
        emojis: ['✋', '💅', '🎨'],
        title: 'Hold on, price alert!',
        body: "Quick note from the developer: I'm a guy, and the prices on these services are <b>pure guesses</b>. I asked myself what a gel manicure costs and my brain returned a 404. 🙈",
        yes: 'Understood, genius 😏',
        no: 'Hire a consultant 💁‍♀️',
    },
    nails: {
        emojis: ['💅', '🤔', '💸'],
        title: 'Nail prices? Oh no.',
        body: "I can't tell 'nude' from 'beige', so I definitely can't price <b>acrylics</b>. These are test numbers. Please do not show them to your manicurist. 🙏",
        yes: 'Your secret is safe',
        no: 'I know better prices 😤',
    },
    color: {
        emojis: ['🎨', '👩‍🦰', '😵‍💫'],
        title: 'Hair color pricing',
        body: "My entire hair routine is the word <b>'shampoo'</b>. Pricing a balayage was beyond me, so these amounts are fictional. Real stylists, please look away. 🫣",
        yes: 'Looks fine to me',
        no: 'That is too cheap! 😱',
    },
    makeup: {
        emojis: ['💄', '✨', '🪞'],
        title: 'Makeup prices, test mode',
        body: "I'm told contour is not just a shape on a map. 🗺️ I priced bridal makeup by <b>vibes only</b>, so treat every amount here as placeholder data.",
        yes: 'Vibes accepted',
        no: 'Send help 🆘',
    },
    lashes: {
        emojis: ['🦋', '👁️', '🤷‍♂️'],
        title: 'Lashes & brows',
        body: "I had to ask what brow <b>lamination</b> is (it is not for documents). The prices here are test data, nothing more. 📄",
        yes: 'Got it!',
        no: 'Explain lamination 🧐',
    },
    booking: {
        emojis: ['💇‍♀️', '💄', '💅'],
        title: 'Booking something fancy?',
        body: "Heads up: I'm a guy, so the <b>prices and durations</b> of these services are educated guesses at best. Everything you book here is demo data and resets daily. 🔄",
        yes: 'Book it anyway 😎',
        no: 'Ask my sister 👭',
    },
    reports: {
        emojis: ['📊', '💅', '🤓'],
        title: 'Numbers ahead',
        body: "The charts are real, the math is real, but the <b>prices behind them are made up</b> by a guy who thinks 'balayage' is a type of pastry. 🥐",
        yes: 'Carry on 📈',
        no: 'I will fix them myself',
    },
};

Alpine.store('joke', {
    open: false,
    current: JOKES.dashboard,
    key: null,
    show(key, force = false) {
        const seen = (() => { try { return sessionStorage.getItem('joke:' + key); } catch (e) { return null; } })();
        if (seen && !force) return;
        this.key = key;
        this.current = JOKES[key] ?? JOKES.dashboard;
        this.open = true;
    },
    close(mark = true) {
        this.open = false;
        if (mark) { try { sessionStorage.setItem('joke:' + this.key, '1'); } catch (e) { /* private mode */ } }
    },
    decline() {
        this.close();
        window.toast?.('Excellent plan. I will ask next time. 📝', 'success');
    },
});
window.addEventListener('joke', (e) => Alpine.store('joke').show(e.detail));

document.addEventListener('alpine:initialized', () => {
    const key = document.querySelector('[data-joke]')?.dataset.joke;
    if (key && JOKES[key]) setTimeout(() => Alpine.store('joke').show(key), 750);
});

/* ---------- Scroll reveal + count-up ---------- */
const reveal = () => {
    const io = new IntersectionObserver((entries) => entries.forEach((e) => {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
    }), { threshold: 0.12 });
    document.querySelectorAll('[data-reveal]').forEach((el) => io.observe(el));

    const counters = new IntersectionObserver((entries) => entries.forEach((e) => {
        if (!e.isIntersecting) return;
        counters.unobserve(e.target);
        const target = parseFloat(e.target.dataset.count);
        const decimals = (e.target.dataset.count.split('.')[1] ?? '').length;
        const suffix = e.target.dataset.suffix ?? '';
        const start = performance.now();
        const tick = (now) => {
            const p = Math.min(1, (now - start) / 1400);
            const v = target * (1 - Math.pow(1 - p, 3));
            e.target.textContent = v.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }), { threshold: 0.5 });
    document.querySelectorAll('[data-count]').forEach((el) => counters.observe(el));
};
document.addEventListener('DOMContentLoaded', reveal);

/* ---------- Clickable table rows ---------- */
document.addEventListener('click', (e) => {
    const row = e.target.closest('tr[data-href]');
    if (row && !e.target.closest('a, button, input, select, form, label')) window.location = row.dataset.href;
});

/* ---------- Confirm before destructive forms ---------- */
document.addEventListener('submit', (e) => {
    const msg = e.target.dataset?.confirm;
    if (msg && !window.confirm(msg)) e.preventDefault();
});

export default Alpine;
