// 基于准备好的dom，初始化echarts实例
var myChart = echarts.init(document.getElementById('newwusers'));
var saveAsImage = window.saveAsImage || '';
option = {
    title: {
        textStyle: {
            fontSize: 14,
            fontWeight: 'normal',
            color: '#393B6B' // 主标题文字颜色
        },

    },
    tooltip: {
        trigger: 'axis'
    },
    legend: {
        data: ['时长', '次数']
    },
    toolbox: {
        show: true,
        feature: {
            saveAsImage: {
                show: true,
                name:'游戏概况_平均游戏时长',
                icon: saveAsImage,
                title:'下载'
            }
        }
    },
    calculable: true,
    xAxis: [{
        type: 'category',
        boundaryGap: false,
        data: JSON.parse( keys ),

    }],
    yAxis: [{
        type: 'value',
        axisLabel: {
            formatter: '{value}'
        }
    }],
    series: [{
        name: '时长',
        type: 'line',
        itemStyle : {
            normal : {
                color:'#41a6ed',
                lineStyle:{
                    color:'#41a6ed'
                }
            }
        },
        data: JSON.parse( online_time )
    },
        {
            name: '次数',
            type: 'line',
            itemStyle : {
                normal : {
                    lineStyle:{
                        color:'#ffb170'
                    }
                }
            },
            data: JSON.parse( login_count )

        }
    ]
};
myChart.setOption(option);
