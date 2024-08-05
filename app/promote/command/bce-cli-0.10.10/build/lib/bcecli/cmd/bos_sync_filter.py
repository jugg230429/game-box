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
This module provide filter to bos sync.
"""

import fnmatch

from bcecli.cmd.time_range_manager import  TimeRangeManager

BOS_PATH_PREFIX = 'bos:/'

class BosSyncPathFilter(object):
    """
    This module is used to filter file by path
    """

    def __init__(self):
        self._path_filter_is_include = False
        self._path_filter_enable = False
        self._patterns = []

    def _set_enable_path_filter(self):
        self._path_filter_enable = True

    def is_path_filter_enable(self):
        """ check whether enable filter path"""
        return self._path_filter_enable

    def set_path_filter_is_include(self, is_include):
        """set include or exclude"""
        if isinstance(is_include, bool):
            self._path_filter_is_include = is_include
        else:
            raise TypeError("is_include must be bool type")

    def _remove_bos_prefix(self, bos_path):
        if bos_path is None:
            return None
        if bos_path.startswith(BOS_PATH_PREFIX):
            return bos_path[BOS_PATH_PREFIX.__len__():]
        else:
            return bos_path

    def path_pattern_insert(self, pattern):
        """insert path pattern"""
        pattern_path = self._remove_bos_prefix(pattern)
        if pattern_path is not None:
            self._patterns.append(pattern_path)
            self._set_enable_path_filter()

    def pattern_filter(self, file_path):
        """filter path with path pattern"""
        file_path = self._remove_bos_prefix(file_path)
        for p in self._patterns:
            if file_path.endswith('/') and not p.endswith('/'):
                p += '/'
            if fnmatch.fnmatch(file_path, p):
                if self._path_filter_is_include:
                    return False
                else:
                    return True
        if self._path_filter_is_include:
            return True
        else:
            return False


class BosSyncFilter(TimeRangeManager, BosSyncPathFilter):
    """ 
    This module is used to filter local file and bos file when sync.
    """
    
    def __init__(self):
        TimeRangeManager.__init__(self)
        BosSyncPathFilter.__init__(self)
