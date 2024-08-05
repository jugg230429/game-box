// 基于准备好的dom，初始化echarts实例
var myChart = echarts.init(document.getElementById('newwusers'));
var saveAsImage = window.saveAsImage || '';
option = {
    title: {
        text: 'SUM：￥'+sum_pay_total+'     AVG：￥'+avg_pay_total,
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
        data: ['充值']
    },
    toolbox: {
        show: true,
        feature: {
            saveAsImage: {
                show: true,
                name:'游戏概况_运营指标_充值',
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
        name: '充值',
        type: 'line',
        itemStyle : {
            normal : {
                color:'#41a6ed',
                lineStyle:{
                    color:'#41a6ed'
                }
            }
        },
        data: JSON.parse( all_pay_total )
    }
    ]
};
myChart.setOption(option);
