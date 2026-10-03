import ApexCharts from 'apexcharts';

const data = JSON.parse(document.getElementById('report-data').textContent);

const volumeMax = Math.max(1, ...data.volume.created, ...data.volume.resolved);

const baseOptions = {
    chart: { fontFamily: 'inherit', toolbar: { show: false }, parentHeightOffset: 0 },
    dataLabels: { enabled: false },
    grid: { strokeDashArray: 4 },
    legend: { position: 'bottom' },
};

new ApexCharts(document.getElementById('chart-volume'), {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'area', height: 280 },
    series: [
        { name: data.labels.created, data: data.volume.created },
        { name: data.labels.resolved, data: data.volume.resolved },
    ],
    xaxis: { categories: data.volume.labels, type: 'datetime', labels: { datetimeUTC: false } },
    yaxis: { min: 0, tickAmount: Math.min(volumeMax, 5), labels: { formatter: (value) => Math.round(value) } },
    stroke: { width: 2, curve: 'smooth' },
    fill: { opacity: 0.16, type: 'solid' },
    colors: ['#066fd1', '#2fb344'],
}).render();

new ApexCharts(document.getElementById('chart-priority'), {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'donut', height: 260 },
    series: Object.values(data.priority),
    labels: Object.keys(data.priority),
    colors: ['#9ba9be', '#4299e1', '#f76707', '#d63939'],
}).render();

new ApexCharts(document.getElementById('chart-category'), {
    ...baseOptions,
    chart: { ...baseOptions.chart, type: 'bar', height: 260 },
    series: [{ name: data.labels.tickets, data: data.category.map((row) => row.total) }],
    xaxis: { categories: data.category.map((row) => row.name) },
    plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '60%' } },
    colors: ['#066fd1'],
}).render();
