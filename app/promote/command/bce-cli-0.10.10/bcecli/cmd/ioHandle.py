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
This module provide io handle for multi task.

Authors: xinzhenrui
Date:    2015/08/10
"""
import logging
import StringIO
import sys
import threading

DefaultPartSize = 10 << 20  # 10M
DefaultReadBufferSize = 10 << 20  # 10M
MaxStreamPartNum = 512
MaxPartsNum = 10000
MULTI_UPLOAD_MAX_FILE_SIZE = 5 << 40
_logger = logging.getLogger(__name__)


class StreamHandle(object):
    """IO handle."""
    def __init__(self, context):
        self.context = context
        self._lock = threading.Lock()
        self._part_data_condition = threading.Condition(self._lock)

    def read(self, chunk_size=DefaultPartSize):
        """
        Read data from standard input. Then get part number.
        When data size is not equal to chunk size, the stream is ended.

        :returns: fp, is_remaining, part_number
        :rtype fp:              String handle
        :rtype is_remaining:    bool
        :rtype part_number:     in
        """
        with self._part_data_condition:
            chunk = sys.stdin.read(chunk_size)
            fp = StringIO.StringIO(chunk)
            part_number = self.context.part_number
            if fp.len != 0 and part_number > MaxStreamPartNum:
                _logger.error("stream size is more than 5G")
                raise Exception("upload: stream is too large. more than 5G.")
        _logger.debug("Read part data success. Part_number: %d, part_size: %d, is_remaining: %s" %
                      (part_number, fp.len, fp.len == chunk_size))
        return fp, fp.len == chunk_size, part_number


class FileHandle(object):
    """IO handle."""
    def __init__(self, file_name, context):
        self.context = context
        self.file_name = file_name

    def read(self, offset):
        """
        Get the file handle.
        """
        try:
            fp = open(self.file_name, "rb")
        except IOError:
            raise IOError("Open file: %s failed.", self.file_name)

        try:
            fp.seek(offset)
        except IOError:
            raise IOError("Seek file %s to %d failed." % (self.file_name, offset))
        return fp
