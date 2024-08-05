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
This module provide the sub-argparser for CDN.

Authors: sunyixing
Date:    2017/07/12
"""

import bcecli

def __build_config_parser(parser):
    parser.set_defaults(func=bcecli.cmd.cdn.configuration)
    parser.add_argument(
        '--enable',
        nargs='?',
        help='a domain that you want to enable cdn service'
    )

    parser.add_argument(
        '--disable',
        nargs='?',
        help='a domain that you want to disable cdn service'
    )

    parser.add_argument(
        '--query',
        nargs='?',
        help='a domain that you want to get all its configs'
    )

    parser.add_argument(
        '--update',
        nargs='?',
        help='a domain that you want to update a config file to'
    )

    parser.add_argument(
        '--origin',
        nargs='?',
        help='a json file contains origin peer config'
    )

    parser.add_argument(
        '--cachettl',
        nargs='?',
        help='a json file contains cache ttl config'
    )

def __build_ls_parser(parser):
    parser.set_defaults(func=bcecli.cmd.cdn.ls)
    parser.add_argument(
        '-a',
        '--all',
        action='store_true',
        help='list all domains.'
    )

def __build_prefetch_parser(parser):
    parser.set_defaults(func=bcecli.cmd.cdn.prefetch)
    parser.add_argument(
        '--url',
        nargs='?',
        help='url you want to prefetch.'
    )

    parser.add_argument(
        '--bos',
        nargs='?',
        type=str,
        help='import file list from bos bucket[bos:/bucket_name[/prefix]]'
    )

    parser.add_argument(
        '--file',
        nargs='?',
        type=str,
        help='import url from file[/path/to/file]'
    )

    parser.add_argument(
        '--domain',
        nargs='?',
        type=str,
        help='prefetch bos bucket to specified domain.'
    )

    parser.add_argument(
        '--batch',
        nargs = '?',
        type = int,
        default = 10,
        help = 'submit task with specified batch size.'
    )

    parser.add_argument(
        '--query',
        nargs = '?',
        type = str,
        help = 'query the prefetch-id task.'
    )

def __build_purge_parser(parser):
    parser.set_defaults(func=bcecli.cmd.cdn.purge)
    parser.add_argument(
        '--url',
        nargs='?',
        type=str,
        help='url you want to purge.'
    )

    parser.add_argument(
        '--directory',
        nargs='?',
        type=str,
        help='directory you want to purge.'
    )

    parser.add_argument(
        '--query',
        nargs = '?',
        type = str,
        help = 'query the purge-id task.'
    )

def build_cdn_parser(parser):
    """
    CDN argparser builder
    """

    cdn_parser_dict = dict()
    cdn_parser_dict["root"] = parser

    sub_parsers = parser.add_subparsers(
        dest='cmd')

    config_parser = sub_parsers.add_parser(
        'config',
        help='set or get a certain configuration.'
    )
    cdn_parser_dict["config"]=config_parser

    ls_parser = sub_parsers.add_parser(
        'ls',
        help='list domains.'
    )
    cdn_parser_dict["ls"] = ls_parser

    prefetch_parser = sub_parsers.add_parser(
        'prefetch',
        help='prefetch specified url.'
    )
    cdn_parser_dict["prefetch"] = prefetch_parser

    purge_parser = sub_parsers.add_parser(
        'purge',
        help='purge specified url.'
    )
    cdn_parser_dict["purge"] = purge_parser

    __build_ls_parser(ls_parser)
    __build_prefetch_parser(prefetch_parser)
    __build_purge_parser(purge_parser)
    __build_config_parser(config_parser)
    return cdn_parser_dict
