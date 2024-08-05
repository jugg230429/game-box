#!/usr/bin/env python
# coding=utf-8

import psutil
import time

def memissue():
    print('内存信息: ')
    mem = psutil.virtual_memory()
    # 单位换算为MB
    memtotal = float(mem.total/1024/1024)
    memused = float(mem.used/1024/1024)
    membaifen = memused/memtotal*100

    print("系统的内存容量为："+'%.2fMB' % memtotal)
    print("系统的内存已使用容量为："+'%.2fMB' % memused)
    print('系统的内存使用率为： %.2f' % membaifen + '%')

def cpuinfo():
    cpu_slv = round((psutil.cpu_percent(1)), 2)  # cpu使用率
    print('CPU信息: ')
    print("CPU使用率为：" + str(cpu_slv) + "%")


def disklist():
    print('硬盘信息: ')
    #diskinfo = psutil.disk_usage('/')
    #total_disk = round((float(diskinfo.total)/1024/1024/1000),2) #总大小
    #used_disk = round((float(diskinfo.used) / 1024 / 1024/1000), 2) #已用大小
    #free_disk = round((float(diskinfo.free) / 1024 / 1024/1000), 2) #剩余大小
    #syl_disk = diskinfo.percent
    #print("磁盘总大小" +'%.2fGB' % total_disk)
    #print("磁盘已使用大小" +'%.2fGB' % used_disk)
    #print("磁盘已使用率" +'%.2f' % syl_disk +"%")

    disk_partitions = psutil.disk_partitions(all=False)
    for partition in disk_partitions:
        usage = psutil.disk_usage(partition.mountpoint)
        total_disk = round((float(usage.total)/1024/1024/1000),2) #总大小
        used_disk = round((float(usage.used) / 1024 / 1024/1000), 2) #已用大小
        free_disk = round((float(usage.free) / 1024 / 1024/1000), 2) #剩余大小
        syl_disk = usage.percent
        print('硬盘名称：' + str(partition.device))
        print("磁盘总大小：" +'%.2fGB' % total_disk)
        print("磁盘已使用大小：" +'%.2fGB' % used_disk)
        print("磁盘已使用率：" +'%.2f' % syl_disk +"%")


memissue();

cpuinfo();

disklist();
