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
This module provide the major operations on BOS configuration.

Authors: fuqaing03
Date:    2015/06/08
"""

import sys
import argparse
import logging


class DebugAction(argparse.Action):
    """
    Customized arg action for -d/--debug
    """
    def __call__(self, parser, namespace, values, option_string=None):
        logger = logging.getLogger(None)
        sh = logging.StreamHandler(sys.stdout)
        sh.setLevel(logging.DEBUG)
        formatter = logging.Formatter(
            fmt="%(levelname)s: %(asctime)s: %(filename)s:%(lineno)d * %(thread)d %(message)s",
            datefmt="%m-%d %H:%M:%S")
        sh.setFormatter(formatter)
        logger.addHandler(sh)
        logger.setLevel(logging.DEBUG)
