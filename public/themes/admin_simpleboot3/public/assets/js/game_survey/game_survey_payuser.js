// 基于准备好的dom，初始化echarts实例
var myChart = echarts.init(document.getElementById('newwusers'));
var saveAsImage = window.saveAsImage || '';
option = {
    title: {
        text: 'AVG：'+avg_pay_user,
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
        data: ['新增付费用户', '老付费用户','总计付费用户']
    },
    toolbox: {
        show: true,
        feature: {
            saveAsImage: {
                show: true,
                name:'游戏概况_运营指标_付费用户',
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
        name: '新增付费用户',
        type: 'line',
        itemStyle : {
            normal : {
                color:'#41a6ed',
                lineStyle:{
                    color:'#41a6ed'
                }
            }
        },
        data: JSON.parse( new_pay_user )
    },
        {
            name: '老付费用户',
            type: 'line',
            itemStyle : {
                normal : {
                    lineStyle:{
                        color:'#ffb170'
                    }
                }
            },
            data: JSON.parse( old_pay_user )

        },
        {
            name: '总计付费用户',
            type: 'line',
            itemStyle : {
                normal : {
                    lineStyle:{
                        color:'#ffb170'
                    }
                }
            },
            data: JSON.parse( all_pay_user )

        }
    ]
};
myChart.setOption(option);
