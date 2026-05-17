/*
Template Name: Velzon - Admin & Dashboard Template
Author: Themesbrand
Website: https://Themesbrand.com/
Contact: Themesbrand@gmail.com
File: Analytics sales init js
*/

// get colors array from the string
function getChartColorsArray(chartId) {
    if (document.getElementById(chartId) !== null) {
        const colorAttr = "data-colors" + ("-" + document.documentElement.getAttribute("data-theme") ?? "");
        var colors = document.getElementById(chartId).getAttribute(colorAttr) ?? document.getElementById(chartId).getAttribute("data-colors");
        if (colors) {
            colors = JSON.parse(colors);
            return colors.map(function (value) {
                var newValue = value.replace(" ", "");
                if (newValue.indexOf(",") === -1) {
                    var color = getComputedStyle(document.documentElement).getPropertyValue(newValue);
                    if (color) return color;
                    else return newValue;;
                } else {
                    var val = value.split(',');
                    if (val.length == 2) {
                        var rgbaColor = getComputedStyle(document.documentElement).getPropertyValue(val[0]);
                        rgbaColor = "rgba(" + rgbaColor + "," + val[1] + ")";
                        return rgbaColor;
                    } else {
                        return newValue;
                    }
                }
            });
        } else {
            console.warn('data-colors attributes not found on', chartId);
        }
    }
}

var worldemapmarkers = "";
var countriesChart = "";
var audiencesSessionsCountryChart = "";
var audiencesMetricsCharts = "";
var userDevicePieCharts = "";

function readJsonConfig(elementId, attributeName) {
    var el = document.getElementById(elementId);
    if (!el) {
        return null;
    }

    var raw = el.getAttribute(attributeName);
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch (error) {
        console.warn("Invalid JSON on", elementId, attributeName, error);
        return null;
    }
}

function loadCharts() {
    var liveUsersGeography = readJsonConfig("users-by-country", "data-live-users-geography");
    var otaGeography = readJsonConfig("countries_charts", "data-ota-geography");

    // Live users by country (IP geolocation)
    var vectorMapWorldLineColors = getChartColorsArray("users-by-country");
    if (vectorMapWorldLineColors && document.getElementById("users-by-country")) {
        document.getElementById("users-by-country").innerHTML = "";
        worldlinemap = "";

        var mapMarkers = (liveUsersGeography && liveUsersGeography.markers) ? liveUsersGeography.markers : [];

        worldlinemap = new jsVectorMap({
            map: "world_merc",
            selector: "#users-by-country",
            zoomOnScroll: false,
            zoomButtons: false,
            markers: mapMarkers,
            regionStyle: {
                initial: {
                    stroke: "#9599ad",
                    strokeWidth: 0.25,
                    fill: vectorMapWorldLineColors,
                    fillOpacity: 1,
                },
            },
        })
    }

    // OTA bookings by market (bar chart)
    let barchartCountriesColors = "";
    barchartCountriesColors = getChartColorsArray("countries_charts");
    if (barchartCountriesColors) {
        var barCategories = [];
        var barValues = [];

        if (otaGeography && Array.isArray(otaGeography.bars) && otaGeography.bars.length > 0) {
            barCategories = otaGeography.bars.map(function (bar) { return bar.label; });
            barValues = otaGeography.bars.map(function (bar) { return bar.value; });
        }

        if (barCategories.length === 0) {
            if (countriesChart != "") {
                countriesChart.destroy();
                countriesChart = "";
            }
        } else {
        const options = {
            series: [{
                data: barValues,
                name: 'Bookings',
            }],
            chart: {
                type: 'bar',
                height: 436,
                toolbar: {
                    show: false,
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    distributed: true,
                    dataLabels: {
                        position: 'top',
                    },
                }
            },
            colors: barchartCountriesColors,
            dataLabels: {
                enabled: true,
                offsetX: 32,
                style: {
                    fontSize: '12px',
                    fontWeight: 400,
                    colors: ['#adb5bd']
                }
            },

            legend: {
                show: false,
            },
            grid: {
                show: false,
            },
            xaxis: {
                categories: barCategories,
            },
        };
        if (countriesChart != "")
            countriesChart.destroy();
        countriesChart = new ApexCharts(document.querySelector("#countries_charts"), options);
        countriesChart.render();
        }
    }

    // Audiences metrics column charts
    var chartAudienceColumnChartsColors = "";
    chartAudienceColumnChartsColors = getChartColorsArray("audiences_metrics_charts");
    if (chartAudienceColumnChartsColors) {
        var columnoptions = {
            series: [{
                name: 'Last Year',
                data: [25.3, 12.5, 20.2, 18.5, 40.4, 25.4, 15.8, 22.3, 19.2, 25.3, 12.5, 20.2]
            }, {
                name: 'Current Year',
                data: [36.2, 22.4, 38.2, 30.5, 26.4, 30.4, 20.2, 29.6, 10.9, 36.2, 22.4, 38.2]
            }],
            chart: {
                type: 'bar',
                height: 309,
                stacked: true,
                toolbar: {
                    show: false,
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '20%',
                    borderRadius: 6,
                },
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                show: true,
                position: 'bottom',
                horizontalAlign: 'center',
                fontWeight: 400,
                fontSize: '8px',
                offsetX: 0,
                offsetY: 0,
                markers: {
                    width: 9,
                    height: 9,
                    radius: 4,
                },
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            grid: {
                show: false,
            },
            colors: chartAudienceColumnChartsColors,
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                axisTicks: {
                    show: false,
                },
                axisBorder: {
                    show: true,
                    strokeDashArray: 1,
                    height: 1,
                    width: '100%',
                    offsetX: 0,
                    offsetY: 0
                },
            },
            yaxis: {
                show: false
            },
            fill: {
                opacity: 1
            }
        };
        if (audiencesMetricsCharts != "")
            audiencesMetricsCharts.destroy();
        audiencesMetricsCharts = new ApexCharts(document.querySelector("#audiences_metrics_charts"), columnoptions);
        audiencesMetricsCharts.render();
    }

    // Heatmap Charts Generatedata
    function generateData(count, yrange) {
        var i = 0;
        var series = [];
        while (i < count) {
            var x = (i + 1).toString() + "h";
            var y = Math.floor(Math.random() * (yrange.max - yrange.min + 1)) + yrange.min;

            series.push({
                x: x,
                y: y
            });
            i++;
        }
        return series;
    }

    // Basic Heatmap Charts
    var chartHeatMapBasicColors = "";
    chartHeatMapBasicColors = getChartColorsArray("audiences-sessions-country-charts");
    if (chartHeatMapBasicColors) {
        var options = {
            series: [{
                name: 'Sat',
                data: generateData(18, {
                    min: 0,
                    max: 90
                })
            },
            {
                name: 'Fri',
                data: generateData(18, {
                    min: 0,
                    max: 90
                })
            },
            {
                name: 'Thu',
                data: generateData(18, {
                    min: 0,
                    max: 90
                })
            },
            {
                name: 'Wed',
                data: generateData(18, {
                    min: 0,
                    max: 90
                })
            },
            {
                name: 'Tue',
                data: generateData(18, {
                    min: 0,
                    max: 90
                })
            },
            {
                name: 'Mon',
                data: generateData(18, {
                    min: 0,
                    max: 90
                })
            },
            {
                name: 'Sun',
                data: generateData(18, {
                    min: 0,
                    max: 90
                })
            }
            ],
            chart: {
                height: 400,
                type: 'heatmap',
                offsetX: 0,
                offsetY: -8,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                heatmap: {
                    colorScale: {
                        ranges: [{
                            from: 0,
                            to: 50,
                            color: chartHeatMapBasicColors[0]
                        },
                        {
                            from: 51,
                            to: 100,
                            color: chartHeatMapBasicColors[1]
                        },
                        ],
                    },

                }
            },
            dataLabels: {
                enabled: false
            },
            legend: {
                show: true,
                horizontalAlign: 'center',
                offsetX: 0,
                offsetY: 20,
                markers: {
                    width: 20,
                    height: 6,
                    radius: 2,
                },
                itemMargin: {
                    horizontal: 12,
                    vertical: 0
                },
            },
            colors: chartHeatMapBasicColors,
            tooltip: {
                y: [{
                    formatter: function (y) {
                        if (typeof y !== "undefined") {
                            return y.toFixed(0) + "k";
                        }
                        return y;
                    }
                }]
            }
        };
        if (audiencesSessionsCountryChart != "")
            audiencesSessionsCountryChart.destroy();
        audiencesSessionsCountryChart = new ApexCharts(document.querySelector("#audiences-sessions-country-charts"), options);
        audiencesSessionsCountryChart.render();
    }

    // User by devices
    var dountchartUserDeviceColors = "";
    dountchartUserDeviceColors = getChartColorsArray("user_device_pie_charts");
    if (dountchartUserDeviceColors) {
        var options = {
            series: [78.56, 105.02, 42.89],
            labels: ["Desktop", "Mobile", "Tablet"],
            chart: {
                type: "donut",
                height: 219,
            },
            plotOptions: {
                pie: {
                    size: 100,
                    donut: {
                        size: "76%",
                    },
                },
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                show: false,
                position: 'bottom',
                horizontalAlign: 'center',
                offsetX: 0,
                offsetY: 0,
                markers: {
                    width: 20,
                    height: 6,
                    radius: 2,
                },
                itemMargin: {
                    horizontal: 12,
                    vertical: 0
                },
            },
            stroke: {
                width: 0
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return value + "k" + " Users";
                    }
                },
                tickAmount: 4,
                min: 0
            },
            colors: dountchartUserDeviceColors,
        };
        if (userDevicePieCharts != "")
            userDevicePieCharts.destroy();
        userDevicePieCharts = new ApexCharts(document.querySelector("#user_device_pie_charts"), options);
        userDevicePieCharts.render();
    }
}

window.onresize = function () {
    setTimeout(() => {
        loadCharts();
    }, 0);
};

loadCharts();