'use strict';

$(document).ready(function() {
    // Vérifier si les graphiques sont désactivés
    if (window.disableApexCharts) {
        console.log('ApexCharts est désactivé sur cette page');
        return;
    }

    // Fonction pour générer des données aléatoires
    function generateData(baseval, count, yrange) {
        var i = 0;
        var series = [];
        while (i < count) {
            var x = Math.floor(Math.random() * (750 - 1 + 1)) + 1;
            var y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;
            var z = Math.floor(Math.random() * (75 - 15 + 1)) + 15;
            series.push([x, y, z]);
            baseval += 86400000;
            i++;
        }
        return series;
    }

    // Graphique des ventes
    var columnCtx = document.getElementById("sales_chart");
    if (columnCtx) {
        var columnConfig = {
            colors: ['#7638ff', '#fda600'],
            series: [{
                name: "Received",
                type: "column",
                data: [70, 150, 80, 180, 150, 175, 201, 60, 200, 120, 190, 160, 50]
            }, {
                name: "Pending",
                type: "column",
                data: [23, 42, 35, 27, 43, 22, 17, 31, 22, 22, 12, 16, 80]
            }],
            chart: {
                type: 'bar',
                fontFamily: 'Poppins, sans-serif',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%',
                    endingShape: 'rounded'
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
            },
            yaxis: {
                title: {
                    text: '$ (thousands)'
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return "$ " + val + " thousands"
                    }
                }
            }
        };
        var columnChart = new ApexCharts(columnCtx, columnConfig);
        columnChart.render();
    } else {
        console.log('Élément #sales_chart non trouvé, graphique des ventes non initialisé');
    }

    // Graphique des factures
    var pieCtx = document.getElementById("invoice_chart");
    if (pieCtx) {
        var pieConfig = {
            colors: ['#7638ff', '#ff737b', '#fda600', '#1ec1b0'],
            series: [55, 40, 20, 10],
            chart: {
                fontFamily: 'Poppins, sans-serif',
                height: 350,
                type: 'donut',
            },
            labels: ['Paid', 'Unpaid', 'Overdue', 'Draft'],
            legend: {
                show: false
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };
        var pieChart = new ApexCharts(pieCtx, pieConfig);
        pieChart.render();
    } else {
        console.log('Élément #invoice_chart non trouvé, graphique des factures non initialisé');
    }
});
