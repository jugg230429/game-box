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
This module provide context for multi task.

Authors: xinzhenrui
Date:    2015/08/10
"""
import logging
import threading
from bcecli.cmd.breakpoint import RecordBreakPoint

_logger = logging.getLogger(__name__)

class UploadCancelledError(Exception):
    """Raised when upload task has been cancelled."""
    pass


class MultiUploadContext(object):
    """Context for multiupload task."""
    _UNSTARTED = '_UNSTARTED'
    _STARTED = '_STARTED'
    _CANCELLED = '_CANCELLED'
    _COMPLETED = '_COMPLETED'

    def __init__(self):
        self._state = self._UNSTARTED
        self._state_breakpoint = False
        self._user_interrupt = False

        self._file_size = None
        self._parts_num = None
        self._mtime = None
        self._md5 = None
        self._upload_id = None
        self._part_number = 0
        self._info_name = ''
        self._info_path = ''
        self.goto = True

        self._lock = threading.Lock()
        self._upload_id_condition = threading.Condition(self._lock)
        self._part_number_condition = threading.Condition(self._lock)
        self._parts_condition = threading.Condition(self._lock)
        self._complete_condition = threading.Condition(self._lock)

        self._completed_parts_list = []
        self._pending_parts_list = []
        self._parts_all_list = []

    def announce_upload_id(self, upload_id):
        """
        Set upload_id of a multiupload task.
        The upload id comes from the response of initiation multiupload operation.
        """
        with self._upload_id_condition:
            if self.check_state_cancelled():
                raise UploadCancelledError("Upload has been cancelled.")
            self._upload_id = upload_id
            self._state = self._STARTED
            _logger.debug("Announce:  upload id: %s", self._upload_id)
            self._upload_id_condition.notifyAll()

    def announce_finish_part(self, part_number, etag):
        """
        Announce a part has been finished.
        In this operation, we append part info to parts_completed.

        Part info is a dict contains two keys: {"eTag": etag, "partNumber": part_number}
        """
        with self._parts_condition:
            self._completed_parts_list.append({'eTag': etag, 'partNumber': part_number})
            _logger.debug("Announce:  finished upload part, part_number: %s, etag: %s"
                          % (part_number, etag))
            self._parts_condition.notifyAll()

    def announce_expect_parts_num(self, expect_parts_num):
        """
        Announce the parts num of a multiupload task.

        We can get parts in two ways.
        1. For upload from stream, get parts num when the stream is ended.
        2. For upload from file, get parts num from split data chunk in the first operation.
        """
        with self._parts_condition:
            self._expect_parts_num = expect_parts_num
            _logger.debug("Announce:  expect parts num: %s" % expect_parts_num)
            self._parts_condition.notifyAll()

    def announce_completed_upload_task(self):
        """
        Announce multiupload has been completed.
        Transfer state to COMPLETE.
        """
        with self._lock:
            if self.check_state_cancelled() is True:
                raise UploadCancelledError("Upload has been cancelled.")
            self._state = self._COMPLETED
            _logger.debug("Announce: complete upload task.")
            self._upload_id_condition.notifyAll()
            self._parts_condition.notifyAll()
            self._complete_condition.notifyAll()

    def change_user_interrupt_state(self):
        """
        Multiupload has been interrupted.
        Interrupt state to True.
        """
        _logger.debug("User has interrupted upload task.")
        self._user_interrupt = True

    def create_file(self):
        "Create breakpoint file"
        RecordBreakPoint.create_file(self)

    def remove_breakpoint_file(self):
        "Remove breakpoint file"
        RecordBreakPoint.remove_file(self)

    def announce_cancel_upload_task(self):
        """
        Cancel multiupload task.
        Transfer state to "CANCELLED", when thread get a new task with state is "CANCELLED",
        the thread will exit immediately.
        """
        _logger.debug("Pending to cancel multiupload task.")
        with self._lock:
            self._state = self._CANCELLED
            _logger.debug("Announce:  cancel upload task")
            self._upload_id_condition.notifyAll()
            self._parts_condition.notifyAll()
            self._complete_condition.notifyAll()

    def wait_for_upload_id(self):
        """
        Waiting until upload id is announced.
        This operation can prevent from doing upload part task ahead of init task.
        """
        with self._upload_id_condition:
            while self._upload_id is None and self._state != self._CANCELLED:
                self._upload_id_condition.wait(timeout=1)
            if self.check_state_cancelled() is True:
                raise UploadCancelledError("Upload has been cancelled.")
        return self._upload_id

    def wait_for_all_parts_finished(self):
        """
        Waiting for all the parts finish.
        Do this operation before complete multiupload task.

        When parts_num has been announced, and part_completed equal to part_num, do next step.

        :return: All the parts info ordered by partNumber.
        :rtype: list of dict. Each dict contains two keys: {"eTag": etag, "partNumber": partNumber}
        """
        with self._parts_condition:
            while self.check_state_cancelled() is False and \
                (self._expect_parts_num == -1 or
                         len(self._completed_parts_list) < self._expect_parts_num):

                _logger.debug("waitting for all parts finished. "
                              "Current num of finished part is %d, "
                              "expext parts num is %d"
                              % (len(self._completed_parts_list), self._expect_parts_num))
                self._parts_condition.wait()

            if self.check_state_cancelled():
                _logger.error("Multiupload task has been cancelled.upload_id: %s", self._upload_id)
                raise UploadCancelledError("Upload has been cancelled.")

            _logger.debug("All parts have finished!!!")

            return list(sorted(self._completed_parts_list, key=lambda part: part["partNumber"]))

    def check_state_cancelled(self):
        """Check whether state is CANCELLED."""
        return self._state == self._CANCELLED

    def check_user_interrupt_state(self):
        """Check whether interrupt state is False."""
        return self._user_interrupt


class MultiUploadStreamContext(MultiUploadContext):
    """
    Context for multiupload stream task.
    """
    def __init__(self):
        super(MultiUploadStreamContext, self).__init__()

    @property
    def part_number(self):
        """
        Getter of part number. In putting object from stream, part numbers are sequence
        in strict ascending order. For convenience, part number increases by 1 when you
        get the part number attribute.
        """
        with self._part_number_condition:
            self._part_number += 1
            return self._part_number


class MultiUploadFileContext(MultiUploadContext):
    """
    Context for multiupload file task.
    """
    def __init__(self, progress_bar, quiet=False):
        super(MultiUploadFileContext, self).__init__()
        self.progress_bar = progress_bar
        self.quiet = quiet
        
    def announce_finish_part(self, part_number, etag):
        """
        Announce a part has been finished.
        In this operation, we append part info to parts_completed.

        Part info is a dict contains two keys: {"eTag": etag, "partNumber": part_number}
        After finished a part, change the bar.
        """
        if self.goto is True and not self.quiet:
            self.progress_bar.goto(RecordBreakPoint.get_index(self))
            self.goto = False

        with self._parts_condition:
            if self.check_user_interrupt_state() is False:
                self._completed_parts_list.append({'eTag': etag, 'partNumber': part_number})
                _logger.debug("Announce: finished upload part, part_number: %s, etag: %s"
                              % (part_number, etag))
                if not self.quiet:
                    self.progress_bar.next()
                self._parts_condition.notifyAll()
