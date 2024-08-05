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
This module provide the sub-argparser for BOS.

Authors: fuqiang03
Date:    2015/06/08
"""


import bcecli


def __build_ls_parser(parser):
    parser.set_defaults(func=bcecli.cmd.bos.ls)
    parser.add_argument(
        'BOS_PATH',
        nargs='?',
        default='bos:/',
        help=('BOS path start with "bos:/". '
              'only 1000 objects would be listed if there are more objects in a bucket.')
    )
    parser.add_argument(
        '-a',
        '--all',
        action='store_true',
        help='list all objects and subdirs.'
    )
    parser.add_argument(
        '-r',
        '--recursive',
        action='store_true',
        help='list objects under subdirs'
    )
    parser.add_argument(
        '-s',
        '--summerize',
        action='store_true',
        help='show summerization'
    )


def __build_gen_signed_url_parser(parser):
    parser.set_defaults(func=bcecli.cmd.bos.gen_signed_url)
    parser.add_argument(
        'BOS_PATH',
        help='the BOS path will be used by signed url.'
    )
    parser.add_argument(
        '-e',
        '--expires',
        action='store',
        type=int,
        help = 'you can specify the expiration time for the signed url, the \
        expiration time must be equal or greater than -1, in which -1 means \
        never expires.'
    )


def __build_mb_parser(parser):
    parser.set_defaults(func=bcecli.cmd.bos.mb)
    parser.add_argument(
        'BUCKET_NAME',
        help='bucket name you want to create.'
    )
    parser.add_argument(
        '-r',
        '--region',
        action='store',
        help = 'specify the region for the bucket'
    )

    parser.add_argument(
        '--quiet',
        action='store_true',
        help='do not display the operations performed from the specified command'
    )


def __build_rb_parser(parser):
    parser.set_defaults(func=bcecli.cmd.bos.rb)
    parser.add_argument(
        'BUCKET_NAME',
        help='bucket name you want to delete.'
    )
    parser.add_argument(
        '-f',
        '--force',
        action='store_true',
        help='delete bucket and ALL objects in it.'
    )
    parser.add_argument(
        '-y',
        '--yes',
        action='store_true',
        help='delete bucket without any prompt'
    )
    parser.add_argument(
        '--quiet',
        action='store_true',
        help='do not display the operations performed from the specified command'
    )


def __build_cp_parser(parser):
    parser.set_defaults(func=bcecli.cmd.bos.cp)
    parser.add_argument(
        'SRC',
        help=("source path, could be either local or BOS path. When local path is '-', "
              "read data from stdin.")
    )
    parser.add_argument(
        'DST',
        help=("destination path, could be either local or BOS path. When local path is '-', "
              "write data to stdout. source and destination could NOT be both local path or '-'.")
    )
    parser.add_argument(
        '-r',
        '--recursive',
        action='store_true',
        help='list objects under subdirs'
    )
    parser.add_argument(
        '--restart',
        action='store_true',
        help='restart upload object.'
    )
    parser.add_argument(
        '--storage-class',
        default='',
        nargs='?',
        type=str,
        help='storage class configuration, should be STANDARD or STANDARD_IA or COLD'
    )
    parser.add_argument(
        '--quiet',
        action='store_true',
        help='do not display the operations performed from the specified command'
    )
    parser.add_argument(
        '--yes',
        action='store_true',
        help='download file without any prompt when cover the existing file'
    )
 

def __build_rm_parser(parser):
    parser.set_defaults(func=bcecli.cmd.bos.rm)
    parser.add_argument(
        'BOS_PATH',
        help='BOS path start with "bos:/"'
    )
    parser.add_argument(
        '-y',
        '--yes',
        action='store_true',
        help='delete object without prompts.'
    )
    parser.add_argument(
        '-r',
        '--recursive',
        action='store_true',
        help='delete objects under subdirs.'
    )
    parser.add_argument(
        '--quiet',
        action='store_true',
        help='do not display the operations performed from the specified command'
    )


def __build_process_parser(parser):
    sub_parsers = parser.add_subparsers(
        dest='cmd')

    #1. apk_pack
    apk_pack_parser = sub_parsers.add_parser(
        'apk-pack',
        help='Apk pack handler.'
    )

    apk_pack_parser.set_defaults(func=bcecli.cmd.bos.apk_pack)
    apk_pack_parser.add_argument(
        'BOS_PATH',
        nargs='?',
        default='bos:/',
        help=('BOS path start with "bos:/". '
              'only 1000 objects would be listed if there are more objects in a bucket.')
    )

    apk_pack_parser.add_argument(
        '--parameter',
        required=True,
        help="Local json file path which contains parameter."
    )
    apk_pack_parser.add_argument(
        '--url',
        required=False,
        help="Notify url."
    )

    #2. img_censor
    img_censor_parser = sub_parsers.add_parser(
        'img-censor',
        help='Image censor handler.'
    )

    img_censor_parser.set_defaults(func=bcecli.cmd.bos.img_censor)
    img_censor_parser.add_argument(
        'BOS_PATH',
        nargs='?',
        default='bos:/',
        help=('BOS path start with "bos:/". '
              'only 1000 objects would be listed if there are more objects in a bucket.')
    )
    img_censor_parser.add_argument(
        '--parameter',
        required=True,
        help="Local json file path which contains parameter."
    )

def __build_sync_parser(parser):
    parser.set_defaults(func=bcecli.cmd.bos.sync)
    parser.add_argument(
        'SRC',
        help="source path, should be BOS path or local paht."
    )
    parser.add_argument(
        'DST',
        help="destination path, should be BOS path or local paht."
    )
    parser.add_argument(
        '--delete',
        action='store_true',
        help='delete objects of destination which do not exist on the source'
    )
    parser.add_argument(
        '--dryrun',
        action='store_true',
        help='list what will be synced, and what will be deleted(if --delete is specified)'
    )
    parser.add_argument(
        '--yes',
        action='store_true',
        help='continue doing things with positive confirmations'
    )
    parser.add_argument(
        '--quiet',
        action='store_true',
        help='do not display the operations performed from the specified command.'
    )
    parser.add_argument(
        '--storage-class',
        default='',
        nargs='?',
        type=str,
        help='storage class configuration, should be STANDARD or STANDARD_IA or COLD'
    )
    parser.add_argument(
        '--exclude',
        action='append',
        type=str,
        help="""multiple patterns to filter file when sync; \
                the value should be quoted if it contains wildcard(*). \
                e.g. --exclude './.svn/*' --exclude ./path/to/file \
                --exclude '*/aa/bb' --exclude 'bos:/bucket/path/*' """
    )
    parser_group_time = parser.add_mutually_exclusive_group()
    parser_group_time.add_argument(
        '--exclude-time',
        action='append',
        type=str,
        help="""exclude files those are modified/created in the time ranges in 
                format like '[start, end)', close-open
                range, in which start and end can be: 

                1. empty, which represent infinite;
                2. 'now', which represent current time;
                3. 'midnight', which represent 24:00:00 of today;
                4. a specific time like '2017-01-02 23:04:54' local time or
                '2017-01-02T23:04:54Z' UTC;
                5. duration in format "PnnYnnMnnWnnDTnnHnnMnnS", in which not
                all units are needed, like 'P1D', for more info, please refer to
                ISO8601;
                6. timestamp.

                Examples:
                1. exclude files modified in from 2017-01-02 23:04:54 to
                2017-01-03 23:04:54, the time range can be '[2017-01-02 23:04:54, P1D)' 
                or '[2017-01-02 23:04:54, 2017-01-03 23:04:54)' (notice: because the
                time range is close-open, the above two time ranges don't conatin 
                2017-01-03 23:04:54);
                2. exclude files created before 3 days ago, '[, now-P3D)';
                4. exclude files created in recent 3 days, '[now-P3D,)';
                5. exclude files created in yesterday, '[P1D, midnight-P1D)'.

                Notice: 
                1. cli only supports close-open time range; 
                2. --eclude-time and --include-time cannot be set simultaneously.
            """
    )
    parser_group_time.add_argument(
        '--include-time',
        action='append',
        type=str,
        help="""
             only sync the source files modified or created in
             specified time ranges.
             e.g. --include-time '[start-time1, end-time1]' --include-time '[start-time2, end-time2]'
             the useage is the same with --exclude-time.
            """
    )
    parser.add_argument(
        '--concurrency',
        type=int,
        help='max concurrency for sync, default value is multi upload number'
    )


def build_bos_parser(parser):
    """
    BOS argparser builder
    """

    bos_parser_dict = dict()
    bos_parser_dict["root"] = parser

    sub_parsers = parser.add_subparsers(
        dest='cmd')

    ls_parser = sub_parsers.add_parser(
        'ls',
        help='list buckets or objects.'
    )
    bos_parser_dict["ls"] = ls_parser

    gen_signed_url_parser = sub_parsers.add_parser(
        'gen_signed_url',
        help='generate signed url with given BOS path.'
    )
    bos_parser_dict["gen_signed_url"] = gen_signed_url_parser

    mb_parser = sub_parsers.add_parser(
        'mb',
        help='make bucket.'
    )
    bos_parser_dict["mb"] = mb_parser

    rb_parser = sub_parsers.add_parser(
        'rb',
        help='remove bucket.'
    )
    bos_parser_dict["rb"] = rb_parser

    cp_parser = sub_parsers.add_parser(
        'cp',
        help='copy objects among local and BOS.'
    )
    bos_parser_dict["cp"] = cp_parser

    rm_parser = sub_parsers.add_parser(
        'rm',
        help='remove objects. '
    )
    bos_parser_dict["rm"] = rm_parser

    sync_parser = sub_parsers.add_parser(
        'sync',
        help='synchronize objects between local and BOS or between BOS and BOS.'
    )
    bos_parser_dict["sync"] = sync_parser

    process_parser = sub_parsers.add_parser(
        'process',
        help='data process.'
    )
    bos_parser_dict["process"] = process_parser

    __build_gen_signed_url_parser(gen_signed_url_parser)
    __build_ls_parser(ls_parser)
    __build_mb_parser(mb_parser)
    __build_rb_parser(rb_parser)
    __build_cp_parser(cp_parser)
    __build_rm_parser(rm_parser)
    __build_sync_parser(sync_parser)
    __build_process_parser(process_parser)

    return bos_parser_dict
