// 基于准备好的dom，初始化echarts实例
var myChart = echarts.init(document.getElementById('newwusers'));
var saveAsImage = window.saveAsImage || '';
option = {
    title: {
        text: 'SUM设备 | 账户 '+sum_fire_device+'/'+sum_new_register_user+'     AVG设备 | 账户 '+avg_fire_device+'/'+avg_new_register_user,
        textStyle: {
            fontSize: 14,
            fontWeight: 'normal',
            color: '#6d6d6d' // 主标题文字颜色
        },

    },
    tooltip: {
        trigger: 'axis'
    },
    legend: {
        data: ['新增设备', '新增用户'],
        textStyle: {
            fontSize: 14,
            fontWeight: 'normal',
            color: '#6d6d6d'
        },
    },
    toolbox: {
        show: true,
        feature: {
            saveAsImage: {
                show: true,
                name:'游戏概况_运营指标_新增激活和账号',
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
        name: '新增设备',
        type: 'line',
        itemStyle : {
            normal : {
                color:'#41a6ed',
                lineStyle:{
                    color:'#41a6ed'
                }
            }
        },
        data: JSON.parse( fire_device )
    },

        {
            name: '新增用户',
            type: 'line',
            itemStyle : {
                normal : {
                    lineStyle:{
                        color:'#ffb170'
                    }
                }
            },
            data: JSON.parse( new_register_user )

        }
    ]
};
myChart.setOption(option);
