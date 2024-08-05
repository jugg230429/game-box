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
This module provide the shell interface.

Authors: zhangshuai14
Date:    2015/01/26
"""

import os
import sys
import argparse
import logging
import bcecli.cmd.bos
import bcecli.cmd.cdn
import bcecli.cmd.debug
import bcecli.cmd.config
from bcecli.cmd.config import destruct_conf_folder
from bcecli.cmd.config import init_conf_folder 
from bcecli.argparser.common import BcecliParser
from bcecli.argparser.bos import build_bos_parser
from bcecli.argparser.bosapi import build_bosapi_parser
from bcecli.argparser.cdn import build_cdn_parser
from bcecli.argparser.vpc import build_vpc_parser

reload(sys)
sys.setdefaultencoding("utf-8")

VERSION_MSG = "bcecli v" + bcecli.CLI_VERSION + " from http://bce.baidu.com"

logger = logging.getLogger()
logger.setLevel(logging.CRITICAL)


def __build_argument_parser():
    parser = BcecliParser()

    parser.add_argument(
        '-v', 
        '--version', 
        action='version', 
        version=VERSION_MSG)

    #--conf-path should not use with -c.
    #because both of them can follow with a CONF_PATH
    parser_group = parser.add_mutually_exclusive_group()
    parser_group.add_argument(
        '-c', 
        '--configure', 
        nargs='?', 
        metavar='CONF_PATH',
        const=os.path.expanduser('~') + os.sep + '.bce',
        action=bcecli.cmd.config.ConfigAction, 
        help="configure AK SK Region and Domain for bcecli and will be \
              written to CONF_PATH(the user's home director by default)")

    parser_group.add_argument(
        '--conf-path', 
        nargs='?', 
        const=os.path.expanduser('~') + os.sep + '.bce',
        action=bcecli.cmd.config.ReloadConfAction,
        help='load configure from CONF_PATH for bcecli')

    parser.add_argument(
        '-d', 
        '--debug', 
        nargs=0, 
        action=bcecli.cmd.debug.DebugAction, 
        help='show debug message')

    sub_parsers = parser.add_subparsers(
        dest='srv',
        title='BCE services')
    bos_parser = sub_parsers.add_parser(
        'bos',
        help='BOS command.')

    bos_parser_dict = build_bos_parser(bos_parser)

    bosapi_parser = sub_parsers.add_parser(
        'bosapi',
        help='BOS API command.')

    bosapi_parser_dict = build_bosapi_parser(bosapi_parser)

    cdn_parser = sub_parsers.add_parser(
        'cdn',
        help='CDN API command.')
    cdn_parser_dict = build_cdn_parser(cdn_parser)

    vpc_parser = sub_parsers.add_parser(
        'vpc',
        help='VPC command.')
    vpc_parser_dict = build_vpc_parser(vpc_parser)

    parser_dict = {
            'root':parser, 
            'bos':bos_parser_dict, 
            'bosapi':bosapi_parser, 
            'cdn': cdn_parser_dict,
            'vpc': vpc_parser_dict,
            }

    return parser_dict


def main():
    """
    Main method
    :return: 0 for success, exception would raise.
    """
    # Unique every input string to unicode encoding string
    def _unique_encoding(x):
        input_encoding = sys.stdin.encoding
        if input_encoding is not None:
            return x.decode(input_encoding)
        return x
    sys.argv = map(_unique_encoding, sys.argv)

    init_conf_folder(os.path.expanduser('~') + os.sep + '.bce')
    parser_dict = __build_argument_parser()

    parser = parser_dict['root']
    args, unknown = parser.parse_known_args()
    if len(unknown) != 0:
        print "bce %s %s: error: unknown args:%s" % (args.srv, args.cmd, " ".join(unknown))
        parser_dict[args.srv][args.cmd].print_help()
    else:
        args.func(args)
        destruct_conf_folder()
    return 0


if __name__ == '__main__':
    sys.exit(main())
