window.addEventListener('turbolinks:load', function() {
    $.each(window.LaravelDataTables, function (tableID, dataTable) {
        dataTable.on("draw", function () {
            let tableData = dataTable.ajax.json().charts;
            $.each(tableData, function (key, table) {
                // Declare axis for the column graph
                let chartId = tableID+"_chart";
                if (!$('#'+chartId).length) {
                    $('#'+tableID+'_wrapper').parent().prepend(`<div id="${chartId}"></div>`);
                }
                if (table.type == 'column') {
                    var axis = {
                        min: 0,
                        title: {
                            text: table.title
                        }
                    };
                    // Declare inital series with the values from the getSalaries function
                    var series = {
                        name: table.column,
                        data: Object.values(table.data)
                    };
                    Highcharts.chart(chartId, {
                        chart: {
                            type: "column"
                        },
                        title: {
                            text: table.title
                        },
                        xAxis: {
                            categories: Object.keys(table.data)
                        },
                        yAxis: axis,
                        series: [series]
                    });
                }
                else if (table.type == 'pie') {
                    let dataMap = $.map(table.data, function (val, key) {
                        return {
                            name: key,
                            y: val,
                        };
                    });
                    Highcharts.chart(chartId, {
                        chart: {
                            type: 'pie',
                        },
                        title: {
                            text: table.title,
                        },
                        series: [
                            {
                                data: dataMap,
                            },
                        ],
                    });
                }
            })
        })
    });
});
