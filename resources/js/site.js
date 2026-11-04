import Alpine from './common';

/* ---------- Public site shell ---------- */
Alpine.data('siteNav', () => ({
    open: false,
    scrolled: false,
    init() {
        const update = () => (this.scrolled = window.scrollY > 24);
        update();
        window.addEventListener('scroll', update, { passive: true });
    },
}));

Alpine.data('servicesBrowser', () => ({
    cat: 'all',
    q: '',
    visible(el) {
        const okCat = this.cat === 'all' || el.dataset.cat === this.cat;
        const okQ = !this.q || el.dataset.name.toLowerCase().includes(this.q.toLowerCase());
        return okCat && okQ;
    },
}));

Alpine.data('carousel', () => ({
    i: 0,
    n: 1,
    timer: null,
    init() {
        this.n = this.$refs.track.children.length;
        this.start();
    },
    start() { this.timer = setInterval(() => this.go(this.i + 1), 6500); },
    pause() { clearInterval(this.timer); },
    go(to) { this.i = (to + this.n) % this.n; },
}));

/* ---------- Booking wizard ---------- */
Alpine.data('booking', (cfg) => ({
    cfg,
    step: cfg.preselect ? 2 : 1,
    query: '',
    cat: 'all',
    serviceId: cfg.preselect ?? null,
    staffId: 'any',
    month: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
    dates: {},
    loadingDates: false,
    date: null,
    slots: [],
    loadingSlots: false,
    time: null,
    timeStaff: [],
    form: { name: cfg.user?.name ?? '', email: cfg.user?.email ?? '', phone: cfg.user?.phone ?? '', notes: '', terms: false },
    promo: '',
    promoInfo: null,
    promoError: '',
    card: { number: '4242 4242 4242 4242', expiry: '12/30', cvc: '123' },
    errors: {},
    error: '',
    submitting: false,

    init() {
        if (this.serviceId) this.afterService();
        this.$watch('staffId', () => { this.date = null; this.time = null; this.loadDates(); });
    },

    get steps() { return ['Service', 'Stylist', 'Date & time', 'Your details', 'Confirm']; },
    get service() { return this.cfg.services.find((s) => s.id === this.serviceId) ?? null; },
    get eligibleStaff() { return this.cfg.staff.filter((s) => this.service && s.services.includes(this.service.id)); },
    get chosenStaff() {
        if (this.staffId === 'any') return null;
        return this.cfg.staff.find((s) => s.id === this.staffId) ?? null;
    },
    get filteredServices() {
        const q = this.query.toLowerCase();
        return this.cfg.services.filter((s) => (this.cat === 'all' || s.cat === this.cat) && (!q || s.name.toLowerCase().includes(q)));
    },
    get quote() {
        if (!this.service) return { price: 0, deposit: 0, discount: 0 };
        const pct = this.promoInfo?.percent ?? 0;
        const price = Math.round((this.service.price * (100 - pct)) / 100);
        const deposit = this.service.deposit ? Math.round((price * this.cfg.depositPercent) / 100) : 0;
        return { price, deposit, discount: this.service.price - price };
    },
    get monthLabel() { return this.month.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }); },
    get canPrev() { const now = new Date(); return this.month > new Date(now.getFullYear(), now.getMonth(), 1); },
    get days() {
        const y = this.month.getFullYear(), m = this.month.getMonth();
        const lead = new Date(y, m, 1).getDay();
        const total = new Date(y, m + 1, 0).getDate();
        const cells = Array.from({ length: lead }, () => null);
        const today = new Date(); today.setHours(0, 0, 0, 0);
        for (let d = 1; d <= total; d++) {
            const iso = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
            cells.push({ n: d, iso, past: new Date(y, m, d) < today, count: this.dates[iso] ?? 0 });
        }
        return cells;
    },
    get groupedSlots() {
        const groups = { Morning: [], Afternoon: [], Evening: [] };
        this.slots.forEach((s) => { const h = parseInt(s.time, 10); groups[h < 12 ? 'Morning' : h < 17 ? 'Afternoon' : 'Evening'].push(s); });
        return Object.entries(groups).filter(([, v]) => v.length);
    },
    get dateLabel() {
        if (!this.date) return '';
        return new Date(this.date + 'T12:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
    },
    get timeLabel() { return this.slots.find((s) => s.time === this.time)?.label ?? ''; },
    get assignedStaff() {
        if (this.chosenStaff) return this.chosenStaff;
        return this.cfg.staff.find((s) => this.timeStaff.includes(s.id)) ?? null;
    },
    money: (c) => window.formatMoney(c),

    // ----- steps
    pickService(s) { this.serviceId = s.id; this.staffId = 'any'; this.date = null; this.time = null; this.promoInfo = null; this.afterService(); this.step = 2; this.scrollTop(); },
    afterService() { this.dates = {}; this.date = null; this.time = null; },
    pickStaff(id) { this.staffId = id; this.step = 3; this.scrollTop(); if (!Object.keys(this.dates).length) this.loadDates(); },
    async loadDates() {
        if (!this.service) return;
        this.loadingDates = true;
        const month = `${this.month.getFullYear()}-${String(this.month.getMonth() + 1).padStart(2, '0')}`;
        try {
            const res = await api(`${this.cfg.urls.dates}?service=${this.service.id}&staff=${this.staffId}&month=${month}`);
            this.dates = res.dates;
            if (!this.date) {
                const first = Object.keys(this.dates).sort()[0];
                if (first) this.pickDate(first);
            }
        } finally { this.loadingDates = false; }
    },
    shiftMonth(delta) { this.month = new Date(this.month.getFullYear(), this.month.getMonth() + delta, 1); this.dates = {}; this.loadDates(); },
    async pickDate(iso) {
        this.date = iso; this.time = null; this.loadingSlots = true;
        const d = new Date(iso + 'T12:00:00');
        if (d.getMonth() !== this.month.getMonth()) { this.month = new Date(d.getFullYear(), d.getMonth(), 1); this.loadDates(); }
        try {
            const res = await api(`${this.cfg.urls.slots}?service=${this.service.id}&staff=${this.staffId}&date=${iso}`);
            this.slots = res.slots;
        } finally { this.loadingSlots = false; }
    },
    pickTime(s) { this.time = s.time; this.timeStaff = s.staff; },
    next() { if (this.validStep(this.step)) { this.step++; this.scrollTop(); } },
    back() { if (this.step > 1) { this.step--; this.scrollTop(); } },
    goTo(n) { if (n < this.step) { this.step = n; this.scrollTop(); } },
    scrollTop() { this.$nextTick(() => this.$refs.top?.scrollIntoView({ behavior: 'smooth', block: 'start' })); },
    validStep(n) {
        this.errors = {};
        if (n === 3 && (!this.date || !this.time)) { this.errors.time = 'Please choose a day and a time.'; return false; }
        if (n === 4) {
            if (!this.form.name.trim()) this.errors.name = 'Please tell us your name.';
            if (!/^\S+@\S+\.\S+$/.test(this.form.email)) this.errors.email = 'Please enter a valid email.';
            if (!this.form.phone.trim()) this.errors.phone = 'A phone number helps us reach you.';
            return Object.keys(this.errors).length === 0;
        }
        return true;
    },
    async applyPromo() {
        this.promoError = '';
        if (!this.promo.trim()) return;
        try {
            const res = await api(this.cfg.urls.promo, { method: 'POST', body: { code: this.promo } });
            this.promoInfo = res;
            window.toast(`${res.code} applied: ${res.percent}% off`);
        } catch (e) { this.promoInfo = null; this.promoError = e.message; }
    },
    formatCard() { this.card.number = this.card.number.replace(/\D/g, '').slice(0, 16).replace(/(.{4})/g, '$1 ').trim(); },
    formatExpiry() { let v = this.card.expiry.replace(/\D/g, '').slice(0, 4); if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2); this.card.expiry = v; },
    async submit() {
        this.error = ''; this.errors = {};
        if (!this.form.terms) { this.error = 'Please accept the cancellation policy to continue.'; return; }
        this.submitting = true;
        try {
            const res = await api(this.cfg.urls.book, { method: 'POST', body: {
                service_id: this.service.id, staff_id: this.staffId === 'any' ? null : this.staffId, date: this.date, time: this.time,
                name: this.form.name, email: this.form.email, phone: this.form.phone, notes: this.form.notes, promo: this.promoInfo?.code ?? null,
                card: this.quote.deposit > 0 ? this.card : null,
            } });
            window.location = res.url;
        } catch (e) {
            this.errors = Object.fromEntries(Object.entries(e.errors ?? {}).map(([k, v]) => [k, v[0]]));
            this.error = this.errors.card ?? this.errors.time ?? (Object.keys(this.errors).length ? 'Please check the highlighted fields.' : e.message);
            if (this.errors.time) { this.step = 3; this.pickDate(this.date); }
            else if (this.errors.name || this.errors.email || this.errors.phone) this.step = 4;
        } finally { this.submitting = false; }
    },
}));

/* ---------- Reschedule picker (client portal) ---------- */
Alpine.data('reschedule', (cfg) => ({
    cfg, date: '', slots: [], time: '', loading: false, error: '',
    async load() {
        if (!this.date) return;
        this.loading = true; this.time = '';
        try { this.slots = (await api(`${cfg.urls.slots}?service=${cfg.service}&staff=${cfg.staff}&date=${this.date}&ignore=${cfg.appointment}`)).slots; }
        finally { this.loading = false; }
    },
}));

Alpine.start();
