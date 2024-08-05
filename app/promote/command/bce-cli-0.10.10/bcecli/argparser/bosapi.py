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
"""
This module provides the sub-argparser for BOS.

Authors: fuqiang03
Date:    2015/06/08
"""


import bcecli
import bcecli.cmd.bosapi as bosapi

def __build_put_lifecycle_parser(parser):
    parser.set_defaults(func=bosapi.put_lifecycle)
    parser.add_argument(
        '--lifecycle-config-file',
        action='store',
        help='path to lifecycle file in json format, use --template to get an \
                template of the file.'
    )
    parser.add_argument(
        '--bucket-name',
        action='store',
        help='bucket you want to put lifecycle for.'
    )
    parser.add_argument(
        '--template',
        action='store_true',
        help='generates a lifeycle template for the given bucket.'
    )

def __build_get_lifecycle_parser(parser):
    parser.set_defaults(func=bosapi.get_lifecycle)
    parser.add_argument(
        '--bucket-name',
        action='store',
        required=True,
        help='bucket you want to get lifecycle config for.'
    )

def __build_delete_lifecycle_parser(parser):
    parser.set_defaults(func=bosapi.delete_lifecycle)
    parser.add_argument(
        '--bucket-name',
        action='store',
        required=True,
        help='bucket you want to get lifecycle config for.'
    )

def __build_put_logging_parser(parser):
    parser.set_defaults(func=bosapi.put_logging)
    parser.add_argument(
        '--target-bucket',
        action='store',
        required=True,
        help='which bucket will the log put to'
    )
    parser.add_argument(
        '--target-prefix',
        action='store',
        required=True,
        help='which prefix will the log put to'
    )
    parser.add_argument(
        '--bucket-name',
        action='store',
        required=True,
        help='bucket you want to put logging for.'
    )

def __build_get_logging_parser(parser):
    parser.set_defaults(func=bosapi.get_logging)
    parser.add_argument(
        '--bucket-name',
        action='store',
        required=True,
        help='bucket you want to list logging for.'
    )

def __build_delete_logging_parser(parser):
    parser.set_defaults(func=bosapi.delete_logging)
    parser.add_argument(
        '--bucket-name',
        action='store',
        required=True,
        help='bucket you want to list logging for.'
    )

def __build_put_bucket_storage_class_parser(parser):
    parser.set_defaults(func=bosapi.put_bucket_storage_class)
    parser.add_argument(
        '--bucket-name',
        action='store',
        required=True,
        help='bucket you want to put bucket storege for.'
    )
    parser.add_argument(
        '--storage-class',
        action='store',
        required=True,
        help='bucket storage class, should be STANDARD or STANDARD_IA or COLD'
    )

def __build_get_bucket_storage_class_parser(parser):
    parser.set_defaults(func=bosapi.get_bucket_storage_class)
    parser.add_argument(
        '--bucket-name',
        action='store',
        required=True,
        help='bucket you want to put bucket storege for.'
    )

def __build_get_object_meta_parser(parser):
    parser.set_defaults(func=bosapi.get_object_meta)
    parser.add_argument(
        '--bucket-name',
        action='store',
        required=True,
        help='bucket which contains the object you want to get meta.'
    )
    parser.add_argument(
        '--object-name',
        action='store',
        required=True,
        help='object which you want to get meta info. If the specified object exists, bce will '
             'print the meta info of the specified object and exit with 0; if the object does not '
             'exist, bce exit with 2; if any errors occur, bce will raise these errors and '
             'exit with 1.'
    )
    parser_group = parser.add_mutually_exclusive_group()
    parser_group.add_argument(
        '--schema',
        action='store',
        help='specified which attribute of object to display. NOTE: separate the attribute names '
             'by comma, and the attribute name can be "object_name", "content_length", '
             '"last_modified", "bce_storage_class". Example: 1. show object name and object size, '
             '--schema "object_name,content_length"; 2. show storage class, '
             '--schema "bce_storage_class"'
    )
    parser_group.add_argument(
        '--quiet',
        action='store_true',
        help='do not display the operations performed from the specified command.'
    )


def build_bosapi_parser(parser):
    """
    BOS API argparser builder
    """

    bos_parser_dict = dict()
    bos_parser_dict["root"] = parser

    sub_parsers = parser.add_subparsers(
        dest='cmd')

    # loggging
    put_lifecycle_parser = sub_parsers.add_parser(
        'put-lifecycle',
        help='put lifecycle'
    )
    bos_parser_dict["set-lifecycle"] = put_lifecycle_parser

    get_lifecycle_parser = sub_parsers.add_parser(
        'get-lifecycle',
        help='get lifecycle'
    )
    bos_parser_dict["get-lifecycle"] = get_lifecycle_parser

    delete_lifecycle_parser = sub_parsers.add_parser(
        'delete-lifecycle',
        help='delete lifecycle'
    )
    bos_parser_dict["delete-lifecycle"] = delete_lifecycle_parser

    # logging
    put_logging_parser = sub_parsers.add_parser(
        'put-logging',
        help='put logging'
    )
    bos_parser_dict["put-logging"] = put_logging_parser

    get_logging_parser = sub_parsers.add_parser(
        'get-logging',
        help='get logging'
    )
    bos_parser_dict["get-logging"] = get_logging_parser

    delete_logging_parser = sub_parsers.add_parser(
        'delete-logging',
        help='delete logging'
    )
    bos_parser_dict["delete-logging"] = delete_logging_parser

    # bucket storage class
    put_bucket_storage_class_parser = sub_parsers.add_parser(
        'put-bucket-storage-class',
        help='put bucket storage class'
    )
    bos_parser_dict["put-bucket-storage-class"] = put_bucket_storage_class_parser

    get_bucket_storage_class_parser = sub_parsers.add_parser(
        'get-bucket-storage-class',
        help='get bucket storage class'
    )
    bos_parser_dict["get-bucket-storage-class"] = get_bucket_storage_class_parser

    get_object_meta_parser = sub_parsers.add_parser(
        'get-object-meta',
        help='get object meta info. If the specified object exists, bce will print '
             'the meta info of the specified object and exit with 0; if the object does not '
             'exist, bce exit with 2; if any errors occur, bce will raise these errors and '
             'exit with 1.'
    )
    bos_parser_dict["get-object-meta"] = get_object_meta_parser

    __build_put_lifecycle_parser(put_lifecycle_parser)
    __build_get_lifecycle_parser(get_lifecycle_parser)
    __build_delete_lifecycle_parser(delete_lifecycle_parser)
    __build_put_logging_parser(put_logging_parser)
    __build_get_logging_parser(get_logging_parser)
    __build_delete_logging_parser(delete_logging_parser)
    __build_put_bucket_storage_class_parser(put_bucket_storage_class_parser)
    __build_get_bucket_storage_class_parser(get_bucket_storage_class_parser)
    __build_get_object_meta_parser(get_object_meta_parser)

    return bos_parser_dict
