import ApexCharts from 'apexcharts';

const css = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();
const money = (v) => '$' + Math.round(v).toLocaleString('en-US');

function build(el) {
    const c = JSON.parse(el.dataset.config);
    const dark = document.documentElement.dataset.theme === 'dark';
    const brand = css('--brand');
    const muted = css('--muted');
    const line = css('--line');
    const palette = c.colors ?? [brand, '#d4a24c', '#38bdf8', '#34d399', '#f472b6', '#a78bfa', '#fb923c'];
    const fmt = c.money ? money : (v) => (Number.isInteger(v) ? v : v.toFixed(1));

    const base = {
        chart: { type: c.type, height: c.height ?? 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', foreColor: muted, background: 'transparent', animations: { speed: 600 }, stacked: !!c.stacked },
        theme: { mode: dark ? 'dark' : 'light' },
        colors: palette,
        series: c.series,
        grid: { borderColor: line, strokeDashArray: 4 },
        dataLabels: { enabled: false },
        legend: { show: c.legend ?? c.series.length > 1, position: 'top', horizontalAlign: 'right', markers: { size: 6 } },
        tooltip: { theme: dark ? 'dark' : 'light', y: { formatter: fmt } },
        stroke: { width: c.type === 'bar' ? 0 : 3, curve: 'smooth' },
        xaxis: { categories: c.categories, labels: { rotate: 0, hideOverlappingLabels: true }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { formatter: fmt }, min: 0 },
    };

    if (c.type === 'area') base.fill = { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.38, opacityTo: 0.02, stops: [0, 95] } };
    if (c.type === 'bar') { base.plotOptions = { bar: { borderRadius: 6, columnWidth: '55%', horizontal: !!c.horizontal, distributed: !!c.distributed } }; if (c.distributed) base.legend.show = false; }
    if (c.type === 'donut') {
        base.labels = c.categories;
        base.legend = { show: true, position: 'bottom', markers: { size: 6 } };
        base.stroke = { width: 3, colors: [css('--surface')] };
        base.plotOptions = { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: c.totalLabel ?? 'Total', formatter: (w) => fmt(w.globals.seriesTotals.reduce((a, b) => a + b, 0)) } } } } };
        delete base.xaxis; delete base.yaxis; delete base.grid;
    }
    if (c.type === 'heatmap') {
        base.chart.height = c.height ?? 320;
        base.plotOptions = { heatmap: { radius: 5, shadeIntensity: 0.6, colorScale: { ranges: [{ from: 0, to: 0, color: line, name: 'none' }] } } };
        base.colors = [brand]; base.stroke = { width: 2, colors: [css('--surface')] }; base.legend.show = false;
        base.xaxis.labels.rotate = 0; base.yaxis = { labels: { show: true } };
    }
    if (c.type === 'radialBar') {
        base.plotOptions = { radialBar: { hollow: { size: '62%' }, track: { background: line }, dataLabels: { value: { fontSize: '26px', fontWeight: 700, color: css('--ink') }, name: { color: muted } } } };
        base.labels = c.categories; delete base.xaxis; delete base.yaxis; delete base.grid; base.legend.show = false;
    }

    const chart = new ApexCharts(el, base);
    chart.render();

    return chart;
}

const charts = new Map();
const mount = () => {
    charts.forEach((ch) => ch.destroy());
    charts.clear();
    document.querySelectorAll('[data-chart]').forEach((el) => { el.innerHTML = ''; el.dataset.config = el.dataset.chart; charts.set(el, build(el)); });
};

document.addEventListener('DOMContentLoaded', mount);
window.addEventListener('themechange', () => setTimeout(mount, 50));
