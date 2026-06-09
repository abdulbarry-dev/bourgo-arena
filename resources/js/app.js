import {
    createLineChart,
    createDoughnutChart,
    createBarChart,
    createPieChart,
    destroyAllCharts,
} from './analytics-charts';

window.createLineChart = createLineChart;
window.createDoughnutChart = createDoughnutChart;
window.createBarChart = createBarChart;
window.createPieChart = createPieChart;
window.destroyAllCharts = destroyAllCharts;
