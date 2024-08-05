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
This module provides the major operations on BOS API.

Authors: BCE BOS
"""

# built-in
import json
import os

# bce cli
from bcecli.baidubce.auth.bce_credentials import BceCredentials
from bcecli.baidubce.bce_client_configuration import BceClientConfiguration
from bcecli.baidubce.exception import BceHttpClientError
from bcecli.baidubce.retry_policy import BackOffRetryPolicy
from bcecli.baidubce.services.bos.bos_client import BosClient
from bcecli.cmd import config
from bcecli.lazy_load_proxy import LazyLoadProxy
from bcecli.cmd import util

from bcecli.cmd.bos_init import bos

HEAD_OBJECT_ATTRIBUTES = ['object_name', 'content_length', 'last_modified', 'bce_storage_class']

def put_lifecycle(args):
    """
    :param args: Parsed args, must have bucket_name
    """
    if args.template:
        lifecycle = {'rule': [{'id':'sample-id', 'resource':
            ['${bucket_name}/${prefix}/*'], 'action': {'name':'Transition',
                'storageClass':'STANDARD_IA'}, 'status':'enabled',
            'condition':{'time':{'dateGreaterThan':'$(lastModified)+P30D'}}}]}
        print(json.dumps(lifecycle, indent=2, separators=(',', ': ')))
        return
    if args.lifecycle_config_file is None or args.bucket_name is None:
        raise Exception('you must specify lifecycle_config_file and bucket_name')
    lifecycle = None
    with open(args.lifecycle_config_file) as data_file:
        lifecycle = json.load(data_file)
    print(json.dumps(lifecycle, indent=2, separators=(',', ': ')))
    bos.set_bucket_lifecycle(args.bucket_name, lifecycle)


def get_lifecycle(args):
    """
    :param args: Parsed args, must have bucket_name
    """
    lifecycle = bos.get_bucket_lifecycle(args.bucket_name)
    print(lifecycle.body)


def delete_lifecycle(args):
    """
    :param args: Parsed args, must have bucket_name
    """
    bos.delete_bucket_lifecycle(args.bucket_name)


def put_logging(args):
    """
    :param args: Parsed args, must have bucket_name
    """
    logging = {'status': 'enabled', \
            'targetBucket': args.target_bucket, \
            'targetPrefix': args.target_prefix}
    print(json.dumps(logging, indent=4, separators=(',', ': ')))
    bos.set_bucket_logging(args.bucket_name, logging)


def get_logging(args):
    """
    :param args: Parsed args, must have bucket_name
    """
    logging = bos.get_bucket_logging(args.bucket_name)
    logging = json.loads(logging.body)
    print(json.dumps(logging, indent=4, separators=(',', ': ')))


def delete_logging(args):
    """
    :param args: Parsed args, must have bucket_name
    """
    bos.delete_bucket_logging(args.bucket_name)


def put_bucket_storage_class(args):
    """
    :param args: Parsed args, must have bucket-name and storage-class
    """
    bucket_name = args.bucket_name
    storage_class = args.storage_class
    bos.put_bucket_storage_class(bucket_name, storage_class)


def get_bucket_storage_class(args):
    """
    :param args: Parsed args, must have bucket-name and storage-class
    """
    bucket_name = args.bucket_name
    storage_class = bos.get_bucket_storage_class(bucket_name)
    print(storage_class.body)


def __display_meta_of_object(object_name, schema, metadata):
    schemas = []
    if schema:
        temp_schemas = schema.split(',')
        for attr in temp_schemas:
            attr = attr.strip()
            if not attr:
                continue
            if attr not in HEAD_OBJECT_ATTRIBUTES:
                raise Exception(u"Invalid attribute in schema: '%s'! The attribute name can be "
                    "%s" % (attr, HEAD_OBJECT_ATTRIBUTES))
            schemas.append(attr)
        if len(schemas) == 0:
            raise Exception(u"Invalid schema! Separate the attribute names with comma, and the "
                    "attribute name can be %s" % HEAD_OBJECT_ATTRIBUTES)
    else:
        schemas = HEAD_OBJECT_ATTRIBUTES

    object_info = "{" 
    for attr in schemas:
        val = "\"" + attr + "\": " 
        if attr == 'object_name':
            val += "\"" + object_name + "\""
        elif hasattr(metadata, attr):
            val += "\"" + str(getattr(metadata, attr)) + "\""
        else:
            val += "\"None\""

        if object_info != "{":
            object_info += ", "
        object_info += val

    object_info += "}"
    print 'Success:', object_info


def get_object_meta(args):
    """
    get meta info of object.
    """
    global QUIET
    QUIET = args.quiet
    bucket_name = args.bucket_name
    object_key = args.object_name

    if not bos.does_bucket_exist(bucket_name):
        raise Exception('The specified bucket does not exist: %s' % bucket_name)

    try:
        response = util._safe_invoke(
                    lambda:bos.get_object_meta_data(bucket_name, object_key), False)
        if not QUIET:
            __display_meta_of_object(object_key, args.schema, response.metadata)
    except BceHttpClientError as e:
        if (hasattr(e, 'last_error')
            and e.last_error
            and hasattr(e.last_error, 'status_code')
            and e.last_error.status_code
            and e.last_error.status_code == 404):

            if not QUIET:
                print 'Failed: The specified object does not exist'
            os._exit(2)
        else:
            raise


