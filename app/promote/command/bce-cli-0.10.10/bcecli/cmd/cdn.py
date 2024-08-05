# Copyright (c) 2014 Baidu.com, Inc. All Rights Reserved
#
# Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file
# except in compliance with the License. You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software distributed under the
# License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND,
# either express or implied. See the License for the specific language governing permissions
# and limitations under the License.
#coding=utf-8
"""
This module provides the major operations on CDN.

Authors: BCE CDN
"""

# built-in
from collections import defaultdict
from os import path
from stat import S_ISDIR
from stat import S_ISREG
import datetime
import logging
import sys
import json

# bce cli
from bcecli.baidubce.auth.bce_credentials import BceCredentials
from bcecli.baidubce.bce_client_configuration import BceClientConfiguration
from bcecli.baidubce.exception import BceHttpClientError
from bcecli.baidubce.retry_policy import BackOffRetryPolicy
from bcecli.baidubce.services.cdn.cdn_client import CdnClient
from bcecli.cmd import util
from bcecli.cmd import bos
from bcecli.cmd import config
from bcecli.lazy_load_proxy import LazyLoadProxy

_logger = logging.getLogger(__name__)

def __build_cdn_client():
    retry_policy = BackOffRetryPolicy(max_error_retry=3)
    bce_client_config = BceClientConfiguration(
        BceCredentials(
            config.credential_provider.access_key,
            config.credential_provider.secret_key),
        "cdn.baidubce.com",
        retry_policy=retry_policy)

    return CdnClient(bce_client_config)

cdn = LazyLoadProxy(__build_cdn_client)

def configuration(args):
    """
    set configurations
    :param args: require arguments
    """
    if args.enable:
        __enable(args.enable)
    if args.disable:
        __disable(args.disable)
    if args.query:
        __query(args.query)
    if args.update and args.origin:
        __update_origin(args.update, args.origin)
    if args.update and args.cachettl:
        __update_cachettl(args.update, args.cachettl)

def __enable(domain):
    response = cdn.enable_domain(domain)
    print "OK"

def __disable(domain):
    response = cdn.disable_domain(domain)
    print "OK"

def __query(domain):
    response = cdn.get_domain_config(domain)
    print "%16s: %s" % ("domain" , response.domain)
    print "%16s: %s" % ("cname", response.cname)
    print "%16s: %s" % ("status", response.status)
    print "%16s: %s" % ("create time", response.create_time)
    print "%16s: %s" % ("last modify time", response.last_modify_time)
    print "%16s: %s" % ("isBan", response.is_ban)
    print "%16s: %d" % ("limit rate", response.limit_rate)
    for items in enumerate(response.origin):
        print "%16s:" % ("origin")
        print "%22s: %s" % ('peer', items[-1].peer)
        print "%22s: %s" % ('host', items[-1].host)
    for idx, cacheTTL in enumerate(response.cache_ttl):
        print "%15s%d:" % ("cacheTTL", idx + 1)
        print "%22s: %s" % ('type', cacheTTL.type)
        print "%22s: %s" % ('value', cacheTTL.value)
        print "%22s: %d" % ('TTL', cacheTTL.ttl)
        print "%22s: %d" % ('weight', cacheTTL.weight)

def __update_origin(domain, json_file):
    try:
        with open(json_file) as fd:
            jsonfile = json.load(fd)
            response = cdn.set_domain_origin(domain, jsonfile)
            print "OK"
    except ValueError as ve:
        print "Invalid json format. " + str(ve)

def __update_cachettl(domain, json_file):
    try:
        with open(json_file) as fd:
            jsonfile = json.load(fd)
            response = cdn.set_domain_cache_ttl(domain, jsonfile)
            print "OK"
    except ValueError as ve:
        print "Invalid json format. " + str(ve)

def ls(args):
    """
    list domains
    :param args: nothing
    """
    util._safe_invoke(lambda: __ls_domains())

def __ls_domains():
    domains = cdn.list_domains().domains
    for item in domains:
        print item.name

def prefetch(args):
    """
    prefetch specified url from origin
    :param args: Parsed args, must have url attribute.
    """

    if args.query:
        __prefetch_query(args.query)
    else:
        __prefetch_submit(args)

def __prefetch_submit(args):
    urls = []

    if args.url:
        urls.append(args.url)
    elif args.file:
        for line in open(args.file):
            urls.append(line.strip())
    elif args.bos:
        if not args.domain:
            raise Exception(u"missing argument: \"domain\"")

        if not args.domain.startswith("http://"):
            args.domain = "http://" + args.domain
        for key in __list_bucket(args.bos):
            urls.append(args.domain + "/" + key)
    else:
        raise Exception(u"Invalid args")

    if not 0 < args.batch <= 100:
        raise Exception(u"\"batch\" should be an integer between 1 and 100")

    tasks = []
    for u in urls:
        tasks.append({'url': u})
        if len(tasks) == args.batch:
            response = cdn.prefetch(tasks)
            print "prefetch", map(lambda x: x['url'], tasks), "ok, id:", response.id
            tasks = []

    if len(tasks) > 0:
        response = cdn.prefetch(tasks)
        print "prefetch", map(lambda x: x['url'], tasks), "ok, id:", response.id

def __prefetch_query(task_id):
    response = cdn.list_prefetch_tasks(task_id)

    details = response.details
    if details is not None:
        for idx, task in enumerate(details):
            print 'task', idx + 1

            url = task.task.url
            speed = task.task.speed
            start_time = task.task.start_time
            print '\turl:', url,
            if speed is not None:
                print ', speed:', speed,
            if start_time is not None:
                print ', startTime:', start_time,
            print ''

            print '\tstatus:     ', task.status
            print '\tcreatedAt:  ', task.created_at
            print '\tstartedAt:  ', task.started_at
            print '\tfinishedAt: ', task.finished_at
            print '\tprogress:   ', task.progress

def __list_bucket(bos_path):
    rc = []
    if not bos_path.startswith(bos.BOS_PATH_PREFIX):
        if bos_path.startswith('/'):
            raise Exception(u"Invalid BOS path: %s" % bos_path)
        bos_path = bos.BOS_PATH_PREFIX + bos_path
    bos_path = bos_path[bos.BOS_PATH_PREFIX.__len__():].strip()
    bos_components = bos_path.split('/')
    bucket = bos_components[0]
    bos_key = ""
    if len(bos_components) > 1:
        bos_key_components = filter(lambda x: len(x) > 0, bos_components[1:-1])
        bos_key_components.append(bos_components[-1])

        if len(bos_key_components) > 0:
            bos_key = '/'.join(bos_key_components)

    trim_pos = bos_key.decode('utf-8').rfind('/') + 1
    #list until truncated
    recur = True
    marker = None
    delimiter = None if recur else '/'
    while True:
        response = bos.bos.list_objects(
            bucket, marker=marker, prefix=bos_key, delimiter=delimiter)
        for item in response.contents:
            if item.key.endswith('/') and not recur: # skip folder objects
                continue
            if recur and item.key[trim_pos:].strip() == '':
                continue
            rc.append(item.key)

        if response.is_truncated:
            marker = response.next_marker
        else:
            break
    return rc

def purge(args):
    """
    purge cache with specified url
    :param args: Parsed args, must have url attribute.
    """

    if args.query:
        __purge_query(args.query)
    elif args.directory:
        __purge_submit(args.directory, 'directory')
    elif args.url:
        __purge_submit(args.url, 'file')
    else:
        raise Exception(u"Invalid args")

def __purge_submit(url, type):
    task = {}
    task['url'] = url
    task['type'] = type
    response = cdn.purge([task])
    print "purge ok, id:", response.id

def __purge_query(task_id):
    response = cdn.list_purge_tasks(task_id)

    details = response.details
    if details is not None:
        for idx, task in enumerate(details):
            print 'task', idx + 1

            url = task.task.url
            type = task.task.type
            print '\turl:', url,
            if type is not None:
               print ', type:', type,
            print ''

            print '\tstatus:     ', task.status
            print '\tcreatedAt:  ', task.created_at
            print '\tstartedAt:  ', task.started_at
            print '\tfinishedAt: ', task.finished_at
            print '\tprogress:   ', task.progress

