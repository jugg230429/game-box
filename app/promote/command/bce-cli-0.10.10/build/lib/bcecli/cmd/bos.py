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
This module provides the major operations on BOS.

Authors: BCE BOS
"""

# built-in
from collections import defaultdict
from os import path
from stat import S_ISDIR
from stat import S_ISREG
import datetime
import logging
import math
import os
import sys
import time
import argparse
import json
import multiprocessing
import base64

# bce cli
import bcecli.cmd.bos_impl.sync_strategy as sync_strategy
from bcecli.baidubce.services.bos import storage_class
from bcecli.baidubce.exception import BceHttpClientError
from bcecli.baidubce.exception import BceServerError
from bcecli.cmd import task
from bcecli.cmd import util
from bcecli.cmd import config as bceconfig
from bcecli.cmd.bos_impl.comparator import Comparator
from bcecli.cmd.bos_impl.file_sync_info import FileSyncInfo
from bcecli.cmd.breakpoint import RecordBreakPoint
from bcecli.cmd.context import MultiUploadFileContext
from bcecli.cmd.context import MultiUploadStreamContext
from bcecli.cmd.context import UploadCancelledError
from bcecli.cmd.ioHandle import StreamHandle
from bcecli.cmd import config
from bcecli.cmd.bos_init import bos
from bcecli.cmd.bos_sync_filter import BosSyncFilter

# third-party
from progress.bar import Bar

_logger = logging.getLogger(__name__)


BOS_PATH_PREFIX = 'bos:/'

MULTI_UPLOAD_MAX_FILE_SIZE = 5 << 40 # 5T
MAX_STREAM_UPLOAD_SIZE = 5 << 30 #5G
MULTI_UPLOAD_THRESHOLD = 32 << 20 # 32M
MULTI_COPY_THRESHOLD = 100 << 20 # 100M
PART_SIZE_BASE = 10 << 20 # 10M
MAX_PARTS= 10000
STREAM_DOWNLOAD_BUF_SIZE = 2 << 20

# ugly, print nothing
QUIET = False
# ugly, sync concurrency to check whether print multi-upload process when sync
IS_CONCURRENT_OPERATION = False

# whether safe print in multi-processing 
MULTI_PROCESS_PRINT_LOCK = None

def __print_if_not_quiet(content):
    if  MULTI_PROCESS_PRINT_LOCK is not None:
        with MULTI_PROCESS_PRINT_LOCK:
            if not QUIET:
                util.safe_print(content)
    else:
        if not QUIET:
            util.safe_print(content)


def __try_print_if_not_quiet(content):
    try:
        __print_if_not_quiet(content)
    except UnicodeEncodeError as e:
        __print_if_not_quiet("Error occurs when print message, error is: %s"%(e))

def gen_signed_url(args):
    """
    :param args: Parsed args, must have BOS_PATH attribute.
    """
    if not args.BOS_PATH.startswith(BOS_PATH_PREFIX):
        args.BOS_PATH = BOS_PATH_PREFIX + args.BOS_PATH
    bucket_name, object_key = __split_bos_bucket_key(args.BOS_PATH)
    if not bucket_name:
        raise Exception('BUCKET_NAME should not be empty "%s".' % bucket_name)
    elif not object_key:
        raise Exception('OBJECT_NAME should not be empty "%s".' % object_key)

    if args.expires and (args.expires<-1):
        raise Exception('Expire time must equal or greater than -1')
    expiration_in_seconds = args.expires if args.expires else 1800

    util._safe_invoke(lambda: __make_url(bucket_name, object_key, expiration_in_seconds))


def ls(args):
    """
    :param args: Parsed args, must have BOS_PATH attribute.
    """
    if args.BOS_PATH and not args.BOS_PATH.startswith(BOS_PATH_PREFIX):
        if args.BOS_PATH.startswith('/'):
            raise Exception(u"Invalid BOS path: %s" % args.BOS_PATH)
        args.BOS_PATH = BOS_PATH_PREFIX + args.BOS_PATH
    bucket_name, object_key = __split_bos_bucket_key(args.BOS_PATH)
    if not bucket_name or bucket_name == "":
        util._safe_invoke(lambda: __ls_buckets(args.summerize))
    else:
        params = [bucket_name, object_key, args.all, args.recursive, args.summerize]
        util._safe_invoke(lambda: __ls_objects(*params))


def mb(args):
    """
    :param args: Parsed args, must have BUCKET_NAME
    """
    global QUIET
    QUIET = args.quiet
    if args.BUCKET_NAME.startswith('/'):
        raise Exception(u"Invalid bucket name: %s" % args.BUCKET_NAME)
    if not args.BUCKET_NAME.startswith(BOS_PATH_PREFIX):
        args.BUCKET_NAME = BOS_PATH_PREFIX + args.BUCKET_NAME
    bucket_name, object_key = __split_bos_bucket_key(args.BUCKET_NAME)
    if not bucket_name:
        raise Exception('BUCKET_NAME should not be empty "%s".' % args.BUCKET_NAME)
    elif object_key:
        raise Exception('BUCKET_NAME should not contain object_key, "%s".' % args.BUCKET_NAME)
    else:
        # TODO: the function create_bucket in BosClient don't have parameter
        # 'region', but the create_bucekt in BosClientWrapper have.
        # Therefore, we need to check whether the option 'auto switch domain' is opened.
        def __make_bucket():
            region = args.region
            if bceconfig.server_config_provider.use_auto_switch_domain == 'yes':
                bos.create_bucket(bucket_name, region=region)
            else:
                default_region = bceconfig.server_config_provider.region
                if region and region != default_region:
                    ignore_region = __prompt_confirm("Option 'auto switch domain' is turned "
                        "off, default region %s will be used instead of %s, do you want "
                        "to continue?" % (default_region, region))
                    if ignore_region:
                        bos.create_bucket(bucket_name)
                    else:
                        print "Operation canceled by user!"
                        return
                else:
                    bos.create_bucket(bucket_name)
            if not args.quiet:
                print('Make bucket: %s' % bucket_name)
        util._safe_invoke(__make_bucket)


def rb(args):
    """
    :param args: Parsed args, must have BUCKET_NAME
    """
    global QUIET
    QUIET = args.quiet
    if args.BUCKET_NAME.startswith('/'):
        raise Exception(u"Invalid bucket name: %s" % args.BUCKET_NAME)
    if not args.BUCKET_NAME.startswith(BOS_PATH_PREFIX):
        args.BUCKET_NAME = BOS_PATH_PREFIX + args.BUCKET_NAME
    bucket_name, object_key = __split_bos_bucket_key(args.BUCKET_NAME)
    if not bucket_name:
        raise Exception('BUCKET_NAME should not be empty "%s".' % args.BUCKET_NAME)
    if object_key:
        raise Exception('BUCKET_NAME should not contain object_key, "%s".' % args.BUCKET_NAME)
    else:
        util._safe_invoke(
                lambda: __remove_bucket(bucket_name, args.yes, args.force))


def apk_pack(args):
    """
    :param args: Parsed args, must have BOS_PATH attribute.
    """
    bucket_name, object_key, params = __process_command_check(args)

    body = {
    "action": {
    "async": [{
    "url": "$(apk-pack)",
    "parameters": base64.b64encode(params)
    }]}}

    if args.url is not None:
        body["action"]["async"].append({"url":args.url})

    body_json = json.dumps(body)
    _logger.debug("apk_pack json: %s", body_json)
    util._safe_invoke(lambda: __process_command(bucket_name, object_key, body_json))
 
 
def img_censor(args):
    """
    :param args: Parsed args, must have BOS_PATH attribute.
    """
    bucket_name, object_key, params = __process_command_check(args)

    body = {
    "action": {
    "sync": [{
    "url": "$(img-censor)",
    "parameters": base64.b64encode(params)
    }]}}

    body_json = json.dumps(body)
    _logger.debug("img_censor json: %s", body_json)
    util._safe_invoke(lambda: __process_command(bucket_name, object_key, body_json))


def __make_url(bucket_name, object_key, expiration_in_seconds):
    url = bos.generate_pre_signed_url(bucket_name, object_key,
            expiration_in_seconds= expiration_in_seconds)
    print(url)


def __get_storage_class_from_str(str):
    ret = None
    if str is None or str == '':
        return ret
    if str.upper() == storage_class.STANDARD:
        ret = storage_class.STANDARD
    elif str.upper() == storage_class.STANDARD_IA:
        ret = storage_class.STANDARD_IA
    elif str.upper() == storage_class.COLD:
        ret = storage_class.COLD
    else:
        raise Exception("storage class '%s' is not supported" % str)
    return ret


def __is_same_object_name(src_object_key, dst_object_key):
    src = filter(lambda x: len(x) > 0, src_object_key.split('/'))
    dst = filter(lambda x: len(x) > 0, dst_object_key.split('/'))
    if src == dst:
        return True
    if len(src) == len(dst) + 1:
        dst.append(src[-1])
        return src == dst
    return False


def __calc_dst_object_key(src_object_key, dst_object_key):
    tmp_object = src_object_key.split('/')[-1]
    if tmp_object == '':
        raise Exception('Source object name error %s' % src_object_key)
    if dst_object_key == '':
        return tmp_object
    if dst_object_key.endswith('/'):
        return dst_object_key + tmp_object
    return dst_object_key


def __get_info_of_remote_file(bucket_name, object_key):
    file_meta = bos.get_object_meta_data(bucket_name, object_key).metadata
    file_size = int(file_meta.content_length)
    file_mtime = int(time.mktime((datetime.datetime.strptime(
            file_meta.last_modified,
            '%a, %d %b %Y %H:%M:%S GMT')
            - datetime.timedelta(hours=time.timezone / 3600))
            .timetuple()))
    storage_class = file_meta.bce_storage_class
    return (file_size, file_mtime, storage_class)


def __cp_remote_single(src_bucket_name, src_object_key, dst_bucket_name,
        dst_object_key, storage_class, restart):
    if not src_object_key:
        raise Exception('Please check source object name')
    if src_object_key.endswith('/'):
        raise Exception('Please use -r to copy path between bos')

    if not dst_object_key:
        dst_object_key = ''

    old_storage_class = None
    #TODO does need to get info of all files?
    src_file_size, src_file_mtime, old_storage_class = __get_info_of_remote_file(
            src_bucket_name, src_object_key)
    if src_bucket_name == dst_bucket_name:
        if __is_same_object_name(src_object_key, dst_object_key):
            #TODO most time, the storage_class is None, and old_storage_class is
            #     'STANDARD'
            if old_storage_class is None:
                old_storage_class= 'STANDARD'
            if old_storage_class == storage_class:
                raise Exception('Can not cover object with same object: %s' %
                        src_object_key)

    final_dst_object_key = __calc_dst_object_key(src_object_key, dst_object_key)

    def __copy_between_remote():
        __copy_object(src_bucket_name, src_object_key,
            dst_bucket_name, final_dst_object_key,
            storage_class=storage_class, src_file_size=src_file_size, 
            src_file_mtime=src_file_mtime, restart=restart)

    util._safe_invoke(__copy_between_remote)
    if not IS_CONCURRENT_OPERATION:
        __print_if_not_quiet("[1] object remote copied.")


def __cp_remote_batch_operation(src_bucket_name, src_object_key,
        dst_bucket_name, dst_object_key, storage_class, restart):
    if not dst_object_key:
        dst_object_key = ''
    dst_object_prefix = dst_object_key
    if not dst_object_key.endswith('/') and src_object_key.endswith('/'):
        raise Exception('Can not copy "%s:/%s" to an object "%s" (Notice: destination'
                ' path must end with "/")' 
                % (src_bucket_name, src_object_key, dst_object_key))
    #copy single object on remote
    if not src_object_key.endswith('/'):
        return __cp_remote_single(src_bucket_name, src_object_key,
                dst_bucket_name, dst_object_key, storage_class, restart)

    #copy directory objects
    object_list_iter = __get_object_list_iterator(bucket_name = src_bucket_name,
            object_key = src_object_key)

    prefix_cut_index = 0
    if src_object_key:
        prefix_cut_index = len(src_object_key)
        if not src_object_key.endswith('/'):
            prefix_cut_index += 1
    copied = 0
    for item in object_list_iter:
        try:
            if item.endswith('/'):
                continue
            tmp_dst_object_name = item[prefix_cut_index:]
            if tmp_dst_object_name == '':
                continue
            src_file_size, src_file_mtime, old_storage_class = __get_info_of_remote_file(
                src_bucket_name, str(item))
            __copy_object(src_bucket_name, str(item),
                    dst_bucket_name, str(dst_object_prefix + tmp_dst_object_name),
                    storage_class=storage_class, src_file_size=src_file_size,
                    src_file_mtime=src_file_mtime, restart=restart)
            copied += 1
        except Exception as e:
            if isinstance(e, BceHttpClientError) \
                    and e.last_error and hasattr(e.last_error, 'code') \
                    and e.last_error.code == 'AccessDenied':
                print('AccessDenied. The account may not have permission or enough balance.')
            else:
                print 'Error occurs when copy object: %s' % str(e)
    __print_if_not_quiet("[%s] object%s remote copied." % \
            (copied, 's' if copied != 1 else ''))


def __cp_between_remote(args):
    src_bucket_name, src_object_key = __split_bos_bucket_key(args.SRC)
    dst_bucket_name, dst_object_key = __split_bos_bucket_key(args.DST)
    storage_class = __get_storage_class_from_str(args.storage_class)
    restart = args.restart
    if not src_bucket_name or not dst_bucket_name:
        raise Exception('Please check source and destination bucket name')
    if not bos.does_bucket_exist(dst_bucket_name):
        raise Exception('Destination bucket %s does not exist!' % dst_bucket_name)
    if not bos.does_bucket_exist(src_bucket_name):
        raise Exception('Source bucket %s does not exist!' % src_bucket_name)

    if args.recursive:
        if args.DST.endswith('/') and not dst_object_key.endswith('/'):
            dst_object_key += '/'
        __cp_remote_batch_operation(src_bucket_name, src_object_key,
                                    dst_bucket_name, dst_object_key,
                                    storage_class, restart)
    else:
        __cp_remote_single(src_bucket_name, src_object_key,
                           dst_bucket_name, dst_object_key,
                           storage_class, restart)


def __cp_download_single(args, src_bucket_name, src_object_key):
    if not src_object_key:
        raise Exception('You must specify an object to copy')
    if src_object_key.endswith('/'):
        raise Exception('Please use -r to download bos path')
    if util._safe_invoke(lambda: __does_object_exist(src_bucket_name, src_object_key)) == False:
        raise BceServerError('Object not exist, can not download "%s".' % args.SRC)

    def __copy_single_object_from_remote():
        return __download_single_object(src_bucket_name, src_object_key,
                args.DST,
                args.over_write_dst if 'over_write_dst' in args.__dict__ else False)

    def __copy_single_object_to_stream_from_remote():
        __download_single_object_to_stream(src_bucket_name, src_object_key)

    dw_status = True
    if args.DST == "-":
        util._safe_invoke(__copy_single_object_to_stream_from_remote)
    else:
        dw_status = util._safe_invoke(__copy_single_object_from_remote)
        if not IS_CONCURRENT_OPERATION:
            __print_if_not_quiet("[%s] object downloaded." %
                (1 if dw_status else 0))


def __cp_download_batch_operation(args, src_bucket_name, src_object_key):
    # whatever the object is, we download every file after ls
    if path.exists(args.DST) and path.isfile(args.DST) and src_object_key.endswith('/'):
        raise Exception('Can not download files to a file %s' % args.DST)
    if path.exists(args.DST) and not __is_writable(args.DST):
        raise Exception("Download abort for existing destination %s not writeable." % args.DST)
    #download single file
    if not src_object_key.endswith('/'):
        return __cp_download_single(args, src_bucket_name, src_object_key)

    object_list_iter = __get_object_list_iterator(
            bucket_name = src_bucket_name, object_key = src_object_key)

    prefix_cut_index = 0
    if src_object_key:
        prefix_cut_index = len(unicode(src_object_key))
        if not src_object_key.endswith('/'):
            prefix_cut_index += 1
    downloaded = 0
    for item in object_list_iter:
        try:
            if item.endswith('/'):
                continue
            item = unicode(item)
            destination_file_path = item[prefix_cut_index:]
            if destination_file_path == '':
                continue
            dst_path = args.DST + os.sep + destination_file_path
            dw_status = util._safe_invoke(
                    lambda: __download_single_object(src_bucket_name, item, dst_path,
                            args.over_write_dst if 'over_write_dst' in args.__dict__ else False))
            downloaded = downloaded + 1 if dw_status else downloaded
        except Exception as e:
            print 'Receive Exception: %s' % str(e)
    __print_if_not_quiet("[%s] object%s downloaded." % \
            (downloaded, 's' if downloaded != 1 else ''))


def __cp_download(args):
    # Download
    _logger.debug("Start to download object.")
    src_bucket_name, src_object_key = __split_bos_bucket_key(args.SRC)
    if not src_bucket_name:
        raise Exception('Bucket name should not be empty "%s".' % args.SRC)
    if not bos.does_bucket_exist(src_bucket_name):
        raise Exception('Bucket %s does not exist!' % src_bucket_name)

    # cover existing file without prompt
    if args.yes:
        args.__dict__["over_write_dst"] = args.yes
    if args.recursive:
        __cp_download_batch_operation(
            args = args,
            src_bucket_name = src_bucket_name,
            src_object_key = src_object_key
        )
    else:
        __cp_download_single(
            args = args,
            src_bucket_name = src_bucket_name,
            src_object_key = src_object_key
        )


def __util_upload_file(file_name, dst_bucket_name, dst_object_key, restart,
        storage_class):
    source_file_size = path.getsize(file_name)
    if source_file_size >= MULTI_UPLOAD_THRESHOLD:
        def __copy_large_file_to_remote():
            __upload_single_object_multi_part(
                file_name,
                dst_bucket_name,
                dst_object_key,
                restart,
                storage_class
            )
        util._safe_invoke(__copy_large_file_to_remote)
    else:
        def __copy_small_file_to_remote():
            __upload_single_object(
                file_name,
                dst_bucket_name,
                dst_object_key,
                storage_class
            )
        util._safe_invoke(__copy_small_file_to_remote)


def __cp_upload_single(args, dst_bucket_name, dst_object_key, storage_class):
    if args.SRC == "-":
        if not dst_object_key:
            raise Exception('Object key can not be empty!')
        if dst_object_key.endswith('/'):
            raise Exception('Can not upload stream to path')

        __upload_stream_to_remote(dst_bucket_name=dst_bucket_name,
                dst_object_key=dst_object_key,
                storage_class=storage_class)

    else:
        if not path.exists(args.SRC):
            raise Exception('Source path %s does not exist!' % args.SRC)

        if path.isdir(args.SRC):
            raise Exception('Cannot upload directory %s.' % args.SRC)
        else:
            if not dst_object_key:
                dst_object_key = path.basename(args.SRC)
            elif dst_object_key.endswith('/'):
                dst_object_key = dst_object_key + path.basename(args.SRC)

            if path.islink(args.SRC):
                #no need to consider link dir
                args.SRC = path.realpath(args.SRC)

            __util_upload_file(
                file_name = args.SRC,
                dst_bucket_name = dst_bucket_name,
                dst_object_key = dst_object_key,
                restart = args.restart,
                storage_class = storage_class
            )
    if not IS_CONCURRENT_OPERATION:
        __print_if_not_quiet("[1] object uploaded.")


def __joint_object_key(origin_src, pathname, object_key):
    #get relative path without /
    relative_path = pathname[len(origin_src)+1:]
    relative_path = relative_path.replace(os.sep, '/')
    return object_key + relative_path


def __upload_file_func(file_name, bucket_name, object_key, restart,
        storage_class):
    try:
        __util_upload_file(
            file_name = file_name,
            dst_bucket_name = bucket_name,
            dst_object_key = object_key,
            restart = restart,
            storage_class = storage_class
        )
        return True
    except Exception as e:
        print 'Receive Exception: %s' % str(e)
        return False


def __cp_upload_recursive(src, bucket_name, object_key, origin_src, restart,
        upload_func, uploaded, storage_class):
    for f in os.listdir(src):
        pathname = os.path.join(src, f)
        mode = os.stat(pathname).st_mode
        if S_ISDIR(mode):
            #check is dir a link
            if path.islink(pathname):
                pathname = path.realpath(pathname)
            __cp_upload_recursive(pathname, bucket_name, object_key, origin_src,
                    restart, upload_func, uploaded, storage_class)
        elif S_ISREG(mode):
            final_object_key = __joint_object_key(origin_src, pathname, object_key)
            #check is file a link
            real_path_name = pathname
            if path.islink(pathname):
                real_path_name = path.realpath(pathname)
            up_status = upload_func(real_path_name, bucket_name, final_object_key,
                    restart, storage_class)
            if up_status:
                uploaded['success'] += 1
            else:
                uploaded['fail'] += 1
        else:
            print 'Error status file %s' % (pathname)


def __cp_upload_batch_operation(args, dst_bucket_name, dst_object_key, storage_class):
    #no need to consider stream
    if not path.exists(args.SRC):
        raise Exception('Source path %s does not exist!' % args.SRC)

    if path.isfile(args.SRC):
        return __cp_upload_single(
                args = args,
                dst_bucket_name = dst_bucket_name,
                dst_object_key = dst_object_key,
                storage_class = storage_class
                )
    #recursive find file and upload, not upload empty path
    if not dst_object_key:
        dst_object_key = ''
    elif not dst_object_key.endswith('/'):
        dst_object_key = dst_object_key + '/'
    abs_src = path.abspath(args.SRC)
    uploaded = {'success': 0, 'fail': 0}
    __cp_upload_recursive(
        src = abs_src,
        bucket_name = dst_bucket_name,
        object_key = dst_object_key,
        origin_src = abs_src,
        restart = args.restart,
        upload_func = __upload_file_func,
        uploaded = uploaded,
        storage_class = storage_class
    )
    uploaded = uploaded['success']
    __print_if_not_quiet("[%s] object%s uploaded." % \
            (uploaded, 's' if uploaded != 1 else ''))


def __cp_upload(args):
    # Upload
    dst_bucket_name, dst_object_key = __split_bos_bucket_key(args.DST)
    storage_class = __get_storage_class_from_str(args.storage_class)
    if not dst_bucket_name:
        raise Exception('Bucket name should not be empty "%s".' % args.DST)
    if not bos.does_bucket_exist(dst_bucket_name):
        raise Exception('Bucket %s does not exist!' % dst_bucket_name)

    if args.recursive:
        #recursive
        __cp_upload_batch_operation(
            args = args,
            dst_bucket_name = dst_bucket_name,
            dst_object_key = dst_object_key,
            storage_class = storage_class
        )
    else:
        __cp_upload_single(
            args = args,
            dst_bucket_name = dst_bucket_name,
            dst_object_key = dst_object_key,
            storage_class = storage_class
        )



def cp(args):
    """
    :param args: Parsed args, must have SRC, DST, force, no_override
    :exception: Both SRC and DST are local path or stream
    """
    global QUIET
    QUIET = args.quiet
    is_source_remote_path = args.SRC.startswith(BOS_PATH_PREFIX)
    is_destination_remote_path = args.DST.startswith(BOS_PATH_PREFIX)

    if args.SRC == "-" and args.DST == "-":
        raise ValueError("You can not cp/copy from stream to stream")
    if args.SRC == "-" or args.DST == "-":
        if args.recursive:
            raise ValueError("Can not copy from/to stream recursively")

    if is_source_remote_path and is_destination_remote_path:
        __cp_between_remote(args)
    elif is_source_remote_path:
        __cp_download(args)
    elif is_destination_remote_path:
        __cp_upload(args)
    else:
        raise Exception('You can use cp/copy to copy files between local file system.')


def __get_object_list_iterator(bucket_name, object_key):
    delimiter = None
    marker = None
    while True:
        response = bos.list_objects(
            bucket_name, marker=marker, prefix=object_key, delimiter=delimiter)
        if response.common_prefixes is not None:
            for item in response.common_prefixes:
                yield item.prefix
        for item in response.contents:
            yield item.key
        if response.is_truncated:
            marker = response.next_marker
        else:
            break


def rm(args):
    """
    :param args: Parsed args, must have BOS_PATH
    """
    global QUIET
    QUIET = args.quiet
    if not args.BOS_PATH.startswith(BOS_PATH_PREFIX):
        if args.BOS_PATH.startswith('/'):
            raise Exception(u"Invalid BOS path: %s" % args.BOS_PATH)
        args.BOS_PATH = BOS_PATH_PREFIX + args.BOS_PATH
    bucket_name, object_key = __split_bos_bucket_key(args.BOS_PATH)

    if not bucket_name:
        raise Exception('Bucket name should not be empty "%s".' % args.BOS_PATH)
    if not bos.does_bucket_exist(bucket_name):
        raise Exception('The specified bucket does not exist: %s' % bucket_name)

    removed = 0
    if args.recursive:
        object_list_iter = __get_object_list_iterator(
                bucket_name = bucket_name, object_key = object_key)
        for item in object_list_iter:
            try:
                rm_status = util._safe_invoke(
                        lambda: __remove_object(bucket_name, item, args.yes))
                removed = removed + 1 if rm_status else removed
            except Exception as e:
                print 'Error occurs when remove object: %s' % str(e)
    else:
        if not object_key:
            raise Exception('Object name should not be empty "%s".' % args.BOS_PATH)
        if args.BOS_PATH.endswith('/'):
            raise Exception('Please use --recursive to remove folder "%s".' % args.BOS_PATH)
        if util._safe_invoke(lambda: __does_object_exist(bucket_name, object_key)) == False:
            raise Exception('Object not exist, can not remove "%s".' % args.BOS_PATH)
        else:
            rm_status = util._safe_invoke(
                    lambda: __remove_object(bucket_name, object_key, args.yes))
            removed = removed + 1 if rm_status else removed

    if removed == 1:
        if not IS_CONCURRENT_OPERATION:
            __print_if_not_quiet("[%s] object removed on remote." % removed)
    else:
        __print_if_not_quiet("[%s] objects removed on remote." % removed)


def __list_local(path, filter=None, follow_symlinks=True):
    """
    This function yields the appropriate local file or local files
    under a directory depending on if the operation is on a directory.
    For directories a depth first search is implemented in order to
    follow the same sorted pattern as a bos list objects operation
    outputs. It yields the FileSyncInfo object with source path, size, and last
    update
    path path to list
    filter takes one arugment for the full relative path like aa/bb/cc,
        if you want to filter the path, makes the path and its sub directories not be listed,
        then return True
    follow_symlinks where to follow symlinks, if the file 
    """
    join, isdir, isfile, listdir = os.path.join, os.path.isdir, os.path.isfile, os.listdir
    def _gen_file_sync_info(full_path, last_modified):
        # follow symlinks
        if follow_symlinks:
            return FileSyncInfo(name=full_path, full_path=full_path,
                    size=os.path.getsize(os.path.realpath(full_path)),
                    last_modified=last_modified)
        else:
            return FileSyncInfo(name=full_path, full_path=full_path,
                    size=os.path.getsize(os.lstat(full_path)),
                    last_modified=last_modified)
    def _list_local(local_path):
        if filter and filter.pattern_filter(local_path):
            return
        if isdir(local_path):
            listdir_names = listdir(local_path)
            names = []
            for name in listdir_names:
                try:
                    file_path = join(local_path, name)
                    if isdir(file_path):
                        name = name + os.path.sep
                    names.append(name)
                except Exception as e:
                    print "Error occurs when process file:", local_path, name, "Error detail:", e
            # sort as bos path, compliant with windows' backslash
            names.sort(key=lambda item: item.replace(os.path.sep, '/'))
            for name in names:
                file_path = join(local_path, name)
                if filter and filter.pattern_filter(file_path):
                    continue
                # dfs search
                if isdir(file_path):
                    for i in _list_local(file_path):
                        yield i
                else:
                    if os.path.islink(file_path) and not follow_symlinks:
                        continue
                    if follow_symlinks:
                        last_modified = int(os.path.getmtime(os.path.realpath(file_path)))
                    else:
                        last_modified = int(os.lstat(file_path).st_mtime)
                    if filter and filter.time_filter(last_modified):
                        continue 
                    yield _gen_file_sync_info(file_path, last_modified)
        else:
            if os.path.islink(local_path) and not follow_symlinks:
                return
            if follow_symlinks:
                last_modified = int(os.path.getmtime(os.path.realpath(local_path)))
            else:
                last_modified = int(os.lstat(local_path).st_mtime)
            if filter and filter.time_filter(last_modified):
                return
            yield _gen_file_sync_info(local_path, last_modified)
    if isdir(path):
        local_path_prefix = path
    else:
        local_path_prefix = os.path.dirname(path)
    local_path_prefix_len = len(local_path_prefix)
    # dfs will generate full path as the file name
    for i in _list_local(path):
        name = i.name[local_path_prefix_len:].replace(os.path.sep, '/')
        i.name = name[1:] if name.startswith('/') else name
        i.compare_key = i.name
        yield i


def __list_bos(bos_path, filter=None):
    """
    Lists and yields all the object under a certain bos path.
    """
    bucket_name, object_key = __split_bos_bucket_key(bos_path)
    marker = None
    while True:
        response = bos.list_objects(
            bucket_name, max_keys=1000, marker=marker, prefix=object_key)
        for item in response.contents:
            if item.key.endswith('/'): # skip "folder" objects
                continue
            last_modified = int(time.mktime(
                (datetime.datetime.strptime(item.last_modified, '%Y-%m-%dT%H:%M:%SZ')
                - datetime.timedelta(hours=time.timezone / 3600))
                .timetuple()))
            full_path=BOS_PATH_PREFIX + bucket_name + "/" + item.key
            if filter and (filter.time_filter(last_modified) or \
                    filter.pattern_filter(full_path)):
                continue
            obj = FileSyncInfo(name=item.key,
                full_path=full_path,
                size=item.size,
                last_modified=last_modified)
            obj.compare_key = obj.full_path.replace(bos_path, '')
            obj.compare_key = obj.compare_key[1:] \
                    if obj.compare_key.startswith('/') else obj.compare_key
            obj.name = obj.compare_key
            yield obj

        if response.is_truncated:
            marker = response.next_marker
        else:
            break


def __check_and_reform_sync_path(src_path, dst_path):
    """
    Checks the input path for sync, raises if not legal.
    Returns the proper forms of the paths
    """
    #TODO support sync single file
    # src is local
    if not src_path.startswith(BOS_PATH_PREFIX) \
            and not os.path.isdir(util.trim_trailing_slash(src_path)):
        raise Exception("SRC must be a local folder.")
    # dst is local
    if not dst_path.startswith(BOS_PATH_PREFIX):
        if os.path.exists(util.trim_trailing_slash(dst_path)) \
                and not os.path.isdir(dst_path):
            raise Exception("DST must be a local folder.")
        if not os.path.isdir(dst_path):
            os.makedirs(dst_path)
    src_path = util.trim_trailing_slash(src_path) + '/'
    dst_path = util.trim_trailing_slash(dst_path) + '/'
    return src_path, dst_path


def __sync_op_func(flag, op_args, prompt):
    if flag == "delete":
        op = lambda op_args=op_args: __delete_local_file(op_args)
    elif flag == "cp":
        op = lambda op_args=op_args: cp(op_args)
    elif flag == "rm":
        op = lambda op_args=op_args: rm(op_args)
    else:
        def op():
            __try_print_if_not_quiet(prompt)
            emsg = u"Sync destination is a folder instead of a file: %s" \
                    % op_args.DST
            __try_print_if_not_quiet(emsg.encode())
            raise Exception(emsg.encode())
    try:
        op(op_args)
        with lock:
            cnt_suc.value += 1
    except Exception as e:
        # TODO: debug info shoul be print here
        with lock:
            cnt_fail.value += 1
        __print_if_not_quiet(u"Failed: " + prompt)


def sync(args):
    """
    syn local folder to bos
    # 1. list all src
    # 2. list all dst
    # 3. compare and gen file list of src to be put to dst, and src to delete, if delete is defined
    # 4. if dryrun is defined, show list to be processed
    :param args: parsed args, must have SRC and DST explicitly defined
    """
    global QUIET
    QUIET = args.quiet
    global IS_CONCURRENT_OPERATION
    IS_CONCURRENT_OPERATION = True
    if args.concurrency:
        sync_concurrency = int(args.concurrency)
    else:
        sync_concurrency = int(config.server_config_provider.multi_upload_thread_num)

    src_path, dst_path = __check_and_reform_sync_path(args.SRC, args.DST)
    sync_info = {}
    if src_path.startswith(BOS_PATH_PREFIX):
        sync_info["src_type"] = 'bos'
    else:
        sync_info["src_type"] = 'local'
    if dst_path.startswith(BOS_PATH_PREFIX):
        sync_info["dst_type"] = 'bos'
    else:
        sync_info["dst_type"] = 'local'
    if sync_info["src_type"] == 'local' and sync_info["dst_type"] == 'local':
        raise Exception('use your system command to sync local -> local')

    sync_info["sync_type"] = sync_info["src_type"] + sync_info["dst_type"]
    sync_info["src_path"] = src_path
    sync_info["dst_path"] = dst_path

    if args.delete and not args.yes:
        confirmed = __prompt_confirm(
            'Do you really want to REMOVE objects do not exist on %s but exist on %s?'
                % (src_path, dst_path))
        if not confirmed:
            print("Operation canceled by user")
            return

    filter = None 
    if args.exclude:
        filter = BosSyncFilter()
        filter.set_path_filter_is_include(False)
        for p in args.exclude:
            filter.path_pattern_insert(p)

    if args.include_time or args.exclude_time:
        if filter is None:
            filter = BosSyncFilter()
        filter.set_time_filter_is_include(args.include_time is not None)
        time_ranges = args.exclude_time if args.exclude_time is not None else \
            args.include_time
        for time_range in time_ranges:
            filter.time_range_insert(time_range)

    if sync_info["src_type"] == 'local':
        src_files = __list_local(src_path, filter)
    elif sync_info["src_type"] == 'bos':
        src_files = __list_bos(src_path, filter)
    if sync_info["dst_type"] == 'local':
        dst_files = __list_local(dst_path, None)
    elif sync_info["dst_type"] == 'bos':
        dst_files = __list_bos(dst_path)

    comparator = Comparator(sync_info,
            file_at_both_side_sync_strategy=sync_strategy.SizeAndLastModifiedSync(),
            file_not_at_dst_sync_strategy=sync_strategy.AlwaysSync(),
            file_not_at_src_sync_strategy=sync_strategy.DeleteDstSync() if args.delete else None)

    # TODO: replace with executor component and interated atomic counter
    lock = multiprocessing.Lock()
    multi_process_print_lock = multiprocessing.Lock()
    cnt_suc = multiprocessing.Value('i', 0)
    cnt_fail = multiprocessing.Value('i', 0)
    def __pool_init(l, multi_process_print_lock, num1, num2):
        # global varibles in pool
        global lock, cnt_suc, cnt_fail, MULTI_PROCESS_PRINT_LOCK
        lock = l
        MULTI_PROCESS_PRINT_LOCK =  multi_process_print_lock 
        cnt_suc = num1
        cnt_fail = num2
    p = multiprocessing.Pool(processes=sync_concurrency,
            initializer=__pool_init, initargs=(lock, multi_process_print_lock, cnt_suc, cnt_fail))

    for i in comparator.compare(src_files, dst_files):
        op_args = argparse.Namespace()
        op_args.__dict__["quiet"] = args.quiet
        flag = None
        prompt = None
        # file_to_upload, file_to_copy, file_to_download
        if i.sync_func == "cp":
            op_args.__dict__["SRC"] = i.src_path
            op_args.__dict__["DST"] = i.dst_path
            op_args.__dict__["restart"] = False
            op_args.__dict__["storage_class"] = args.storage_class
            op_args.__dict__["recursive"] = False
            op_args.__dict__["quiet"] = args.quiet
            op_args.__dict__["over_write_dst"] = True
            op_args.__dict__["yes"] = True
            predicate = u"Copy: "
            if sync_info["sync_type"] == "localbos":
                predicate = u"Upload: "
            elif sync_info["sync_type"] == "bosbos":
                predicate = u"Copy: "
            elif sync_info["sync_type"] == "boslocal":
                predicate = u"Download: "
            else:
                raise Exception("Unknown sync operation!")
            prompt = predicate + i.src_path + u" to " + i.dst_path
            if sync_info["dst_type"] == "local" and path.exists(i.dst_path) \
                    and path.isdir(i.dst_path):
                flag = "erro"
            else:
                flag = "cp"
        # file_to_delete
        elif i.sync_func == "delete":
            op_args.__dict__["BOS_PATH"] = i.dst_path
            op_args.__dict__["yes"] = True
            op_args.__dict__["recursive"] = False
            prompt = u"Delete: " + i.dst_path
            if sync_info["dst_type"] == 'bos':
                flag = "rm"
            elif sync_info["dst_type"] == 'local':
                op_args.__dict__["path"] = i.dst_path
                flag = "delete"
            else:
                pass
        elif i.sync_func == "never_sync":
            pass

        if args.dryrun and not args.quiet and prompt is not None:
            print(prompt.encode())
        elif flag is not None and not args.dryrun:
            p.apply_async(__sync_op_func, args=(flag, op_args, prompt,))
    p.close()
    p.join()
    __print_if_not_quiet("Sync done: %s to %s, [%d] success, [%d] failure"
                % (sync_info["src_path"], sync_info["dst_path"],
                    cnt_suc.value, cnt_fail.value))


def __delete_local_file(args):
    """
    Deletes a local file
    args argparse.Namespace()
    """
    global QUIET
    os.unlink(args.path)
    if not QUIET:
        print("Delete: %s" % args.path)

def __split_bos_bucket_key(bos_path):
    if bos_path is None:
        return None, None
    if bos_path.startswith(BOS_PATH_PREFIX):
        bos_path = bos_path[BOS_PATH_PREFIX.__len__():]
    return __find_bucket_key(bos_path)


def __find_bucket_key(bos_path):
    bos_path = bos_path.strip()
    bos_components = bos_path.split('/')
    bucket = bos_components[0]
    bos_key = ""
    if len(bos_components) > 1:
        bos_key_components = filter(lambda x: len(x) > 0, bos_components[1:-1])
        bos_key_components.append(bos_components[-1])

        if len(bos_key_components) > 0:
            bos_key = '/'.join(bos_key_components)
    return bucket, bos_key


def __ls_buckets(sum=False):
    buckets = bos.list_buckets().buckets
    for item in buckets:
        creation_date = item.creation_date
        region = item.location
        local_time = (datetime.datetime.strptime(creation_date, '%Y-%m-%dT%H:%M:%SZ')
                        - datetime.timedelta(hours=(time.timezone // 3600)))
        print('  %s  %7s  %s' % (local_time, region, item.name))
    if sum:
        print ' Total Buckets: %d' % len(buckets)


def __ls_objects(bucket_name, object_key, all=False, recur=False, sum=False):
    #preparing params for list objects
    delimiter = None if recur else '/'
    trim_pos = object_key.decode('utf-8').rfind('/') + 1

    #for summerize
    summerization = defaultdict(int)
    summerization['PRE'] += 0
    summerization['Object'] += 0
    summerization['Size Of Object'] += 0

    #list until truncated
    marker = None
    while True:
        response = bos.list_objects(
            bucket_name, marker=marker, prefix=object_key, delimiter=delimiter)
        if response.common_prefixes is not None:
            for item in response.common_prefixes:
                print '  %s %11s  %15s  %s' % \
                        (' ' * 19, ' ' * 11, 'PRE', item.prefix[trim_pos:])
                summerization['PRE'] += 1
        for item in response.contents:
            if item.key.endswith('/') and not recur: # skip folder objects
                continue
            if recur and item.key[trim_pos:].strip() == '':
                continue
            last_modified = item.last_modified
            storage_class = '' if item.storage_class is None else item.storage_class
            etag = '' if item.etag is None else item.etag
            # utc to local time
            local_time = (datetime.datetime.strptime(last_modified, '%Y-%m-%dT%H:%M:%SZ')
                        - datetime.timedelta(hours=(time.timezone // 3600)))
            print '  %s %15s  %11s  %s' % \
                    (local_time, item.size, storage_class, item.key[trim_pos:])

            summerization['Object'] += 1
            summerization['Size Of Object'] += int(item.size)
        if not all:
            if response.is_truncated:
                print 'more......'
            break
        if response.is_truncated:
            marker = response.next_marker
        else:
            break

    #output summerization
    if sum:
        for k, v in summerization.items():
            print ' Total %s(s): %s' % (k, str(v))



def __remove_bucket(bucket_name, yes=False, force=False):
    if force:
        confirmed = yes or __prompt_confirm(
            'Do you really want to REMOVE bucket %s%s and all objects in it?'
            % (BOS_PATH_PREFIX, bucket_name))
        if confirmed:
            object_generater = bos.list_all_objects(bucket_name)
            for obj in object_generater:
                __print_if_not_quiet('Delete object: %s%s/%s' % \
                        (BOS_PATH_PREFIX, bucket_name, obj.key))
                bos.delete_object(bucket_name, obj.key)
            bos.delete_bucket(bucket_name)
            __print_if_not_quiet('Remove bucket: %s' % bucket_name)
    else:
        confirmed = yes or __prompt_confirm(
            'Do you really want to REMOVE bucket %s%s?'
            % (BOS_PATH_PREFIX, bucket_name))
        if confirmed:
            bos.delete_bucket(bucket_name)
            __print_if_not_quiet('Remove bucket: %s' % bucket_name)

def __prompt_confirm(prompt):
    if sys.stdin.encoding is not None:
        prompt = prompt.encode(sys.stdin.encoding)
    confirm = raw_input('%s (Y/N) ' % prompt).strip()

    while True:
        if ['y', 'yes', 'Y', 'YES'].__contains__(confirm):
            return True
        elif ['n', 'no', 'N', 'NO'].__contains__(confirm):
            return False
        else:
            confirm = raw_input('Only accept Y or N, please confirm: ')
            confirm = confirm.strip()


def __copy_object(src_bucket_name, src_object_key,
                  dst_bucket_name, dst_object_key, storage_class, 
                  src_file_size, src_file_mtime, restart=False):
    if src_file_size >= MULTI_COPY_THRESHOLD:
        __copy_single_object_multi_part(
               src_bucket_name,
               src_object_key,
               dst_bucket_name,
               dst_object_key,
               restart,
               storage_class,
               src_file_size,
               src_file_mtime
        )
    else:
        bos.copy_object(src_bucket_name, src_object_key,
               dst_bucket_name, dst_object_key, storage_class=storage_class)
        __try_print_if_not_quiet('Copy: bos:/%s/%s to bos:/%s/%s' % \
                (src_bucket_name, src_object_key, dst_bucket_name, dst_object_key))


def __copy_single_object_multi_part(src_bucket_name, src_object_key,
                                    dst_bucket_name, dst_object_key,
                                    restart, storage_class, src_file_size,
                                    src_file_mtime):
    src_path = BOS_PATH_PREFIX + "/" + src_bucket_name + "/" + src_object_key

    parts_info = task.PartsInfo(src_path)
    parts_info.init(file_size = src_file_size)
    progress_bar = Bar('Copying', max=parts_info.get_all_parts_num())
    #extracting some parts of src file to caculate md5
    if src_file_size < 2048:
        file_ranges = ((0, src_file_size))
    else:
        file_ranges = ((0, 1024), (src_file_size - 1024, src_file_size))

    no_progress_bar = QUIET
    if IS_CONCURRENT_OPERATION:
        no_progress_bar = True
    context = MultiUploadFileContext(progress_bar, no_progress_bar)
    multiupload_task = task.MultiCopyTask(client=bos,
                                          src_bucket_name = src_bucket_name,
                                          src_object_key = src_object_key,
                                          dst_bucket_name = dst_bucket_name,
                                          dst_object_key = dst_object_key,
                                          storage_class = storage_class,
                                          upload_context = context,
                                          file_ranges=file_ranges)

    def __multiupload():
        multiupload_task.start()
        multiupload_task.create_multiupload_task(
                src_path,
                restart,
                src_file_size,
                src_file_mtime)
        multiupload_task.submit_file_parts(parts_info)
        multiupload_task.complete_multiuploads()
        multiupload_task.shutdown()

    __safe_invoke_multiupload(__multiupload,
                              multiupload_task,
                              dst_bucket_name,
                              dst_object_key,
                              context._upload_id)

    if not no_progress_bar:
        progress_bar.finish()
    if context._state == context._COMPLETED:
        if context._state_breakpoint:
            RecordBreakPoint.remove_file(context)
            __try_print_if_not_quiet('Breakpoint copy: bos:/%s/%s to bos:/%s/%s'
                    % (src_bucket_name, src_object_key, dst_bucket_name, dst_object_key))
        else:
            __try_print_if_not_quiet('Copy: bos:/%s/%s to bos:/%s/%s'
                        % (src_bucket_name, src_object_key, dst_bucket_name, dst_object_key))
    elif context.check_state_cancelled() is True:
        if context.check_user_interrupt_state() is False:
            RecordBreakPoint.create_file(context)
        raise Exception("Copy has been cancelled.")


def __split_path_and_file(input):
    input = input.strip()
    input_components = input.split(os.sep)
    filename = input_components[-1]
    filepath = ''
    if len(input_components) > 1:
        path_components = filter(lambda x: len(x) > 0, input_components[0:-1])
        if len(path_components) > 0:
            filepath = (os.sep).join(path_components)
    if input.startswith(os.sep):
        filepath = os.sep + filepath
    return filepath, filename


def __try_mkdir(path_to_make):
    if not path.exists(path_to_make):
        os.makedirs(path_to_make)
    elif path.isfile(path_to_make):
        raise Exception('Can not mkdir %s for it has exist a file with same name' % path_to_make)


def __is_writable(fp):
    if os.access(fp, os.F_OK) and os.access(fp, os.W_OK):
        return True
    return False


def __is_readable(fp):
    if os.access(fp, os.F_OK) and os.access(fp, os.R_OK):
        return True
    return False


def __download_single_object(src_bucket_name, src_object_key,
                             destination_file_path, over_write_dst=False):
    object_name = src_object_key.split('/')[-1]
    if object_name == '':
        raise Exception('Object name error %s' % src_object_key)

    destination_file_path = destination_file_path.replace('/', os.sep).replace('\\', os.sep)
    dst_path_endswith_sep = False
    if destination_file_path.endswith(os.sep):
        dst_path_endswith_sep = True
    final_file_name = ''
    if path.exists(destination_file_path):
        abs_file_name = path.abspath(destination_file_path)
        if path.isdir(abs_file_name):
            final_file_name = path.join(abs_file_name, object_name)
        else:
            final_file_name = abs_file_name
    else:
        current_dir = ""
        if destination_file_path.startswith('.'):
            current_dir = path.abspath('.')
        else:
            destination_file_path = path.abspath(destination_file_path)

        if destination_file_path.find(os.sep) == -1:
            final_file_name = path.join(current_dir, destination_file_path)
        else:
            if dst_path_endswith_sep:
                #catch the exception when permission denied
                path_to_make = path.join(current_dir, destination_file_path)
                __try_mkdir(path_to_make)
                final_file_name = path.join(path_to_make, object_name)
            else:
                split_path, split_file = __split_path_and_file(destination_file_path)
                path_to_make = path.join(current_dir, split_path)
                __try_mkdir(path_to_make)
                final_file_name = path.join(path_to_make, split_file)

    # for Windows invalid dir or file name
    if sys.platform.find('win32') != -1:
        split_path, split_file = __split_path_and_file(final_file_name)
        fs = set(split_file)
        invalid = set(r'/\"<*>?:|')
        if len(fs.intersection(invalid)) != 0 or split_file.startswith(' ') or \
                split_file.count('.') > 1:
            print('The input local file path contains illegal letter.')
            return False

    if path.exists(final_file_name):
        if not over_write_dst:
            cover = __prompt_confirm("Will you cover the existing file %s" % final_file_name)
        else:
            cover = True
        if not cover:
            print("Download abort for existing file.")
            return False
        elif cover and not __is_writable(final_file_name):
            print("Download abort for covering on a existing file not writeable.")
            return False
    bos.get_object_to_file(src_bucket_name, src_object_key,
                            final_file_name)
    __print_if_not_quiet('Download: bos:/%s/%s to %s' % \
                    (src_bucket_name, src_object_key, final_file_name))
    return True


def __download_single_object_to_stream(src_bucket_name, src_object_key):
    _logger.debug("Start to download to stream")
    buf_size = STREAM_DOWNLOAD_BUF_SIZE

    try:
        response = bos.get_object(src_bucket_name, src_object_key)
    except Exception as e:
        _logger.error("Request server when download object failed, error: %s" % str(e.message))
        raise e

    _logger.error("Request server when download object success. Start to read body.")
    try:
        while True:
            buf = response.data.read(buf_size)
            if not len(buf):
                break
            sys.stdout.write(buf)
    except Exception as e:
        _logger.error("Read body when download object failed. Error: %s" % str(e.message))
        raise e
    finally:
        response.data.close()


def __upload_single_object(source_file_path,
                           dst_bucket_name, dst_object_key, storage_class):
    bos.put_object_from_file(dst_bucket_name, dst_object_key,
                            source_file_path, storage_class=storage_class)
    __print_if_not_quiet('Upload: %s to bos:/%s/%s' % \
                (source_file_path, dst_bucket_name, dst_object_key))


def __does_object_exist(bucket_name, object_key):
    try:
        bos.get_object_meta_data(bucket_name, object_key)
        return True
    except BceHttpClientError as e:
        if (hasattr(e, 'last_error')
            and e.last_error
            and hasattr(e.last_error, 'status_code')
            and e.last_error.status_code
            and e.last_error.status_code == 404):
            return False
        else:
            raise


def __remove_object(bucket_name, object_key, force):
    confirmed = force or __prompt_confirm(
        'Do you really want to REMOVE object %s%s/%s?'
        % (BOS_PATH_PREFIX, bucket_name, object_key))
    if confirmed:
        bos.delete_object(bucket_name, object_key)
        __print_if_not_quiet('Delete object: %s%s/%s' % \
                (BOS_PATH_PREFIX, bucket_name, object_key))
        return True
    return False


def __safe_invoke_multiupload(multiupload,
                              multiupload_task,
                              dst_bucket_name,
                              dst_object_key,
                              upload_id):
    try:
        multiupload()
    except KeyboardInterrupt:
        _logger.error("Receive KeyboardInterrupt while multiupload to %s/%s" %
                      (dst_bucket_name, dst_object_key))
        multiupload_task.upload_context.announce_cancel_upload_task()
        multiupload_task.upload_context.change_user_interrupt_state()
        multiupload_task.upload_context.create_file()
        multiupload_task.shutdown()
        if upload_id is not None:
            _logger.debug("Abort multiupload, upload id: %s", upload_id)
            bos.abort_multipart_upload(dst_bucket_name, dst_object_key, upload_id)
    except Exception as e:
        _logger.error("Receive Exception: %s" % str(e))
        multiupload_task.upload_context.announce_cancel_upload_task()
        multiupload_task.shutdown()
        if upload_id is not None:
            _logger.debug("Abort multiupload, upload id: %s", upload_id)
            bos.abort_multipart_upload(dst_bucket_name, dst_object_key, upload_id)
        raise


def __upload_single_object_multi_part(source_file_path,
                                      dst_bucket_name, dst_object_key, restart,
                                      storage_class):
    parts_info = task.PartsInfo(source_file_path)
    parts_info.init()
    progress_bar = Bar('Uploading', max=parts_info.get_all_parts_num())

    no_progress_bar = QUIET
    if IS_CONCURRENT_OPERATION:
        no_progress_bar = True
    context = MultiUploadFileContext(progress_bar, no_progress_bar)
    multiupload_task = task.MultiUploadFileTask(client=bos,
                                                bucket_name=dst_bucket_name,
                                                key=dst_object_key,
                                                storage_class=storage_class,
                                                upload_context=context,
                                                source_file_path=source_file_path)

    def __multiupload():
        multiupload_task.start()
        multiupload_task.create_multiupload_task(source_file_path, restart)
        multiupload_task.submit_file_parts(source_file_path, parts_info)
        multiupload_task.complete_multiuploads()
        multiupload_task.shutdown()

    __safe_invoke_multiupload(__multiupload,
                              multiupload_task,
                              dst_bucket_name,
                              dst_object_key,
                              context._upload_id)

    if not no_progress_bar:
        progress_bar.finish()
    if context._state == context._COMPLETED:
        if context._state_breakpoint:
            RecordBreakPoint.remove_file(context)
            print('Breakpoint upload: %s to bos:/%s/%s'
                    % (source_file_path, dst_bucket_name, dst_object_key))
        else:
            __print_if_not_quiet('Upload: %s to bos:/%s/%s'
                        % (source_file_path, dst_bucket_name, dst_object_key))
    elif context.check_state_cancelled() is True:
        if context.check_user_interrupt_state() is False:
            RecordBreakPoint.create_file(context)
        raise Exception("Upload has been cancelled.")


def __upload_stream_to_remote(dst_bucket_name, dst_object_key, storage_class):
    _logger.debug("Start upload stream to object: %s/%s" % (dst_bucket_name, dst_object_key))
    context = MultiUploadStreamContext()
    io_handle = StreamHandle(context)
    fp, is_remaining, part_number = io_handle.read()
    #We read the first part to determine whether to multiupload stream.

    if not is_remaining:
        #If the stream is ended after read the first part, just put object from string.

        if fp.len == 0:
            raise Exception("Upload: stream can NOT be empty")

        def __upload_stream_to_single_object():
            _logger.debug("Stream size is small, upload stream to object from string. "
                          "Stream size is %s" % fp.len)
            bos.put_object_from_string(dst_bucket_name,
                                       dst_object_key,
                                       fp.read(),
                                       storage_class=storage_class)
        util._safe_invoke(__upload_stream_to_single_object)
        __print_if_not_quiet('Upload: stream to bos:/%s/%s' % \
                (dst_bucket_name, dst_object_key))
    else:
        _logger.debug("Multiupload stream to object.",)
        multiupload_task = task.MultiUploadStreamTask(client=bos,
                                                      bucket_name=dst_bucket_name,
                                                      key=dst_object_key,
                                                      storage_class=storage_class,
                                                      upload_context=context)

        def __multiupload():
            multiupload_task.start()
            multiupload_task.create_multiupload_task()
            multiupload_task.submit_parts(first_part_fp=fp,
                                          first_part_number=1,
                                          io_handle=io_handle)
            multiupload_task.complete_multiuploads()
            multiupload_task.shutdown()

        __safe_invoke_multiupload(__multiupload,
                                  multiupload_task,
                                  dst_bucket_name,
                                  dst_object_key,
                                  context._upload_id)

        if context._state == context._COMPLETED:
            print('Upload: stream to bos:/%s/%s' % (dst_bucket_name, dst_object_key))
        elif context.check_state_cancelled() is True:
            raise UploadCancelledError("Upload has been cancelled.")


def __process_command_check(args):
    """
    :param args: Parsed args, must have apk_pack BOS_PATH
    """
    if not args.BOS_PATH.startswith(BOS_PATH_PREFIX):
        if args.BOS_PATH.startswith('/'):
            raise Exception(u"Invalid BOS path: %s" % args.BOS_PATH)
        args.BOS_PATH = BOS_PATH_PREFIX + args.BOS_PATH
    bucket_name, object_key = __split_bos_bucket_key(args.BOS_PATH)

    if not bucket_name:
        raise Exception('Bucket name should not be empty "%s".' % args.BOS_PATH)
    if not bos.does_bucket_exist(bucket_name):
        raise Exception('The specified bucket does not exist: %s' % bucket_name)


    if not object_key:
        raise Exception('Object name should not be empty "%s".' % args.BOS_PATH)
    if util._safe_invoke(lambda: __does_object_exist(bucket_name, object_key)) == False:
        raise Exception('Object not exist, can not process "%s".' % args.BOS_PATH)
    
    if False == os.path.exists(args.parameter):
        raise Exception('Json file not exists :"%s".' % args.parameter)

    params=""
    with open(args.parameter, 'r') as f:
        params=f.read()
    if len(params) == 0:
        raise Exception('Parameter should not be empty "%s".' % args.parameter)

    return  (bucket_name, object_key, params)


def __process_command(bucket_name, object_key, body):
    response = bos.process_command(bucket_name, object_key, body)
    print response.body


