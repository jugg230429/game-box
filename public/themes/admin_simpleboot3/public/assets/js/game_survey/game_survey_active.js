// 基于准备好的dom，初始化echarts实例
var myChart = echarts.init(document.getElementById('newwusers'));
var saveAsImage = window.saveAsImage || '';
option = {
    title: {
        text: 'AVG：'+avg_user,
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
        data: ['新用户', '老用户','总计']
    },
    toolbox: {
        show: true,
        feature: {
            saveAsImage: {
                show: true,
                name:'游戏概况_运营指标_活跃用户',
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
        name: '新用户',
        type: 'line',
        itemStyle : {
            normal : {
                color:'#41a6ed',
                lineStyle:{
                    color:'#41a6ed'
                }
            }
        },
        data: JSON.parse( new_user )
    },
        {
            name: '老用户',
            type: 'line',
            itemStyle : {
                normal : {
                    lineStyle:{
                        color:'#ffb170'
                    }
                }
            },
            data: JSON.parse( old_user )

        },
        {
            name: '总计',
            type: 'line',
            itemStyle : {
                normal : {
                    lineStyle:{
                        color:'#ffb170'
                    }
                }
            },
            data: JSON.parse( all_user )

        }
    ]
};
myChart.setOption(option);
