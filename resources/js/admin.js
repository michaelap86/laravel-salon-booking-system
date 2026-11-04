import Alpine from './common';

Alpine.data('adminShell', () => ({
    sidebar: false,
    userMenu: false,
    notif: false,
    palette: false,
}));

/* Live "new appointment" price preview, with the developer's disclaimer for girly services */
Alpine.data('apptForm', (cfg) => ({
    cfg,
    serviceId: cfg.service ?? '',
    staffId: cfg.preStaff ?? '',
    date: cfg.date ?? '',
    time: cfg.time ?? '',
    slots: [],
    loading: false,
    get service() { return this.cfg.services.find((s) => String(s.id) === String(this.serviceId)); },
    get staffOptions() { return this.cfg.staff.filter((s) => this.service && s.services.includes(this.service.id)); },
    pick() {
        if (this.service && this.cfg.ladies.includes(this.service.cat)) window.dispatchEvent(new CustomEvent('joke', { detail: this.service.cat === 'nails' ? 'nails' : this.service.cat === 'hair-color' ? 'color' : this.service.cat === 'makeup' ? 'makeup' : 'lashes' }));
        if (!this.staffOptions.find((s) => String(s.id) === String(this.staffId))) this.staffId = '';
        this.load();
    },
    async load() {
        if (!this.service || !this.date) { this.slots = []; return; }
        this.loading = true;
        try { this.slots = (await api(`${this.cfg.urls.slots}?service=${this.service.id}&staff=${this.staffId || 'any'}&date=${this.date}`)).slots; }
        finally { this.loading = false; }
    },
    init() { if (this.service) this.load(); },
}));

Alpine.start();
