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
This module provide all the task we run in thread.

Authors: xinzhenrui
Date:    2015/08/10
"""

import logging
import math

from os import path

from bcecli.cmd.executor import Executor
from bcecli.cmd.ioHandle import FileHandle
from bcecli.cmd.breakpoint import RecordBreakPoint

_logger = logging.getLogger(__name__)

DefaultPartSize = 10 << 20  # 10M
DefaultCopyPartSize = 50 << 20  # 50M
DefaultReadBufferSize = 10 << 20 # 10M
MaxPartsNum = 10000
MULTI_UPLOAD_MAX_FILE_SIZE = 5 << 40

_logger = logging.getLogger(__name__)

class InitMultiUploadTask(object):
    """
    In this task you can initiate multiupload and get the upload id.
    """
    def __init__(self, client, bucket_name, key, context, storage_class=None,
            source_file_path=None, restart=False, src_file_size=None, src_file_mtime=None):
        self.client = client
        self.bucket_name = bucket_name
        self.key = key
        self.storage_class = storage_class
        self.context = context
        if source_file_path is not None:
            RecordBreakPoint.init(context, source_file_path, bucket_name, key,
                    restart, src_file_size, src_file_mtime)

    def __call__(self):
        _logger.debug("Start to initiate multiupload task, object: %s/%s"
                      % (self.bucket_name, self.key))

        try:
            response = self.client.initiate_multipart_upload(self.bucket_name, self.key,
                    storage_class=self.storage_class)
        except Exception as e:
            _logger.error("Initiate multiupload failed, object: %s/%s, error: %s"
                          % (self.bucket_name, self.key, str(e.message)))
            self.context.announce_cancel_upload_task()
            raise e

        if self.context._upload_id is None:
            self.context.announce_upload_id(response.upload_id)


class MultiUploadPartTask(object):
    """
    Upload part to bos.
    """
    def __init__(self, client, bucket_name, key, part_number, fp, part_size, context):
        """
        :type client: bos client
        :type fp: io handle
        :type context: instance of MultiUploadStreamContext
        """
        self.client = client
        self.bucket_name = bucket_name
        self.key = key
        self.part_number = part_number
        self.fp = fp
        self.part_size = part_size
        self.context = context

    def __call__(self):
        """
        Upload part with part_number and part_data.
        After upload part, add part info to parts_complete in context.
        """
        upload_id = self.context.wait_for_upload_id()
        _logger.debug("Start to upload stream part to object: %s/%s, part_number: %d" %
                      (self.bucket_name, self.key, self.part_number))
        try:
            response = self.client.upload_part(self.bucket_name, self.key,
                                               upload_id=upload_id,
                                               part_number=self.part_number,
                                               part_fp=self.fp,
                                               part_size=self.part_size)
        except Exception as e:
            _logger.error("Upload stream part to %s/%s failed, part_number: %d,"
                          "error: %s"
                          % (self.bucket_name, self.key, self.part_number, str(e.message)))
            self.context.announce_cancel_upload_task()
            if "The specified multipart upload does not exist" in str(e.message):
                self.context.remove_breakpoint_file()
                self.context.change_user_interrupt_state()
            raise e

        etag = response.metadata.etag
        part_number = self.part_number
        self.context.announce_finish_part(part_number=part_number, etag=etag)


class MultiCopyPartTask(object):
    """
    Upload part to bos.
    """
    def __init__(self, client, src_bucket_name, src_object_key, dst_bucket_name,
            dst_object_key, part_number, offset, part_size, context):
        """
        :type client: bos client
        :type context: instance of MultiUploadContext
        """
        self.client = client
        self.src_bucket_name = src_bucket_name
        self.src_object_key = src_object_key
        self.dst_bucket_name = dst_bucket_name
        self.dst_object_key = dst_object_key
        self.part_number = part_number
        self.offset = offset
        self.part_size = part_size
        self.context = context

    def __call__(self):
        """
        Copy part with part_number and part_data.
        After copy part, add part info to parts_complete in context.
        """
        upload_id = self.context.wait_for_upload_id()
        _logger.debug("Start to copy part from %s/%s to object: %s/%s, part_number: %d" %
                      (self.src_bucket_name, self.src_object_key,
                       self.dst_bucket_name, self.dst_object_key, 
                       self.part_number))
        try:
            response = self.client.upload_part_copy(source_bucket_name=self.src_bucket_name, 
                                                    source_key=self.src_object_key,
                                                    target_bucket_name=self.dst_bucket_name,
                                                    target_key=self.dst_object_key,
                                                    upload_id=upload_id,
                                                    part_number=self.part_number,
                                                    part_size=self.part_size,
                                                    offset=self.offset)
        except Exception as e:
            _logger.error("Copy part from %s/%s to %s/%s failed, part_number: %d,"
                          "error: %s"
                          % (self.src_bucket_name, self.src_object_key,
                             self.dst_bucket_name, self.dst_object_key, 
                             self.part_number, str(e.message)))
            self.context.announce_cancel_upload_task()
            if "The specified multipart upload does not exist" in str(e.message):
                self.context.remove_breakpoint_file()
                self.context.change_user_interrupt_state()
            raise e

        etag = response.etag
        part_number = self.part_number
        self.context.announce_finish_part(part_number=part_number, etag=etag)


class CompleteMultiUploadTask(object):
    """
    After finished all the parts, complete multiupload.
    """
    def __init__(self, client, bucket_name, key, context):
        self.client = client
        self.bucket_name = bucket_name
        self.key = key
        self.context = context

    def __call__(self):
        upload_id = self.context.wait_for_upload_id()
        part_list = self.context.wait_for_all_parts_finished()

        _logger.debug("Start to complete multiupload, upload_id: %s" % upload_id)

        try:
            self.client.complete_multipart_upload(self.bucket_name,
                                                  self.key,
                                                  upload_id=upload_id,
                                                  part_list=part_list)  
        except Exception as e:
            _logger.error("Complete multiuplaod task failed, object: %s/%s, "
                          "upload_id: %s, error: %s"
                          % (self.bucket_name, self.key, upload_id, str(e.message)))
            self.context.announce_cancel_upload_task()
            raise e
        self.context.announce_completed_upload_task()
        _logger.debug("Finish to complete multiupload, upload_id: %s" % upload_id)


class MultiUploadTask(object):
    """
    Multiupload object in summary.
    """
    def __init__(self, client, bucket_name, key, storage_class, upload_context):
        self.client = client
        self.bucket_name = bucket_name
        self.key = key
        self.storage_class = storage_class
        self.upload_context = upload_context
        self.executor = Executor()

    def create_multiupload_task(self, source_file_path=None, restart=False,
            src_file_size=None, src_file_mtime=None):
        """
        Append init multiupload task in work list.
        """
        init_task = InitMultiUploadTask(client=self.client,
                                        bucket_name=self.bucket_name,
                                        key=self.key,
                                        storage_class=self.storage_class,
                                        context=self.upload_context,
                                        source_file_path=source_file_path,
                                        restart=restart,
                                        src_file_size=src_file_size,
                                        src_file_mtime=src_file_mtime)
        self.submit(init_task)

    def submit(self, task):
        """Submit task to work list."""
        self.executor.submit(task)

    def complete_multiuploads(self):
        """
        Append complete task in work list.
        """
        complete_task = CompleteMultiUploadTask(client=self.client,
                                                bucket_name=self.bucket_name,
                                                key=self.key,
                                                context=self.upload_context)
        self.submit(complete_task)

    def start(self):
        """Start to execute thread list."""
        self.executor.start()

    def shutdown(self):
        """
        Shut down all the thread list.

        We usually call this function after we cancelled the upload task.
        After calling this function, we won't exit all the thread immediately.
        We will wait for current running thread task finished then exit the thread.
        """
        self.executor.wait_until_shutdown()


class MultiUploadFileTask(MultiUploadTask):
    """Multiupload file to object in summary."""
    def __init__(self, client, bucket_name, key, storage_class, upload_context,
            source_file_path):
        RecordBreakPoint.calculate_md5(upload_context, source_file_path)
        super(MultiUploadFileTask, self).__init__(client, bucket_name, key,
                storage_class, upload_context)

    def submit_file_parts(self, filename, parts_info):
        """Append all the parts task in work list."""
        self.upload_context.announce_expect_parts_num(parts_info.get_all_parts_num())

        if self.upload_context._state_breakpoint:
            parts = self.upload_context._pending_parts_list

        else:
            parts = parts_info.get_part()

        io_handle = FileHandle(filename, self.upload_context)
        for part in parts:
            fp = io_handle.read(part['offset'])
            part_task = MultiUploadPartTask(client=self.client,
                                            bucket_name=self.bucket_name,
                                            key=self.key,
                                            part_number=part['part_number'],
                                            fp=fp,
                                            part_size=part['part_size'],
                                            context=self.upload_context)
            self.submit(part_task)


class MultiCopyTask(MultiUploadTask):
    """Multiupload file to object in summary."""
    def __init__(self, 
            client, 
            src_bucket_name, 
            src_object_key, 
            dst_bucket_name,
            dst_object_key, 
            storage_class, 
            upload_context,
            file_ranges):
        file_context = self._get_range_remote_file_to_str(client, src_bucket_name,
                src_object_key, file_ranges)
        RecordBreakPoint.calculate_md5_of_remote_file(upload_context, file_context)
        super(MultiCopyTask, self).__init__(client, dst_bucket_name, dst_object_key,
                storage_class, upload_context)
        self.src_bucket_name = src_bucket_name
        self.src_object_key = src_object_key
        self.dst_bucket_name = dst_bucket_name
        self.dst_object_key = dst_object_key

    def _get_range_remote_file_to_str(self, client, bucket_name, object_key,
            ranges=None):
        file_context = ""
        for offset_range in ranges: 
            file_context += client.get_object_as_string(
                    bucket_name,
                    object_key,
                    range=offset_range)
        return file_context

    def submit_file_parts(self, parts_info):
        """Append all the parts task in work list."""
        self.upload_context.announce_expect_parts_num(parts_info.get_all_parts_num())

        if self.upload_context._state_breakpoint:
            parts = self.upload_context._pending_parts_list

        else:
            parts = parts_info.get_part()

        for part in parts:
            offset = part['offset']
            part_task = MultiCopyPartTask(client=self.client,
                                            src_bucket_name=self.src_bucket_name,
                                            src_object_key=self.src_object_key,
                                            dst_bucket_name=self.dst_bucket_name,
                                            dst_object_key=self.dst_object_key,
                                            part_number=part['part_number'],
                                            offset=offset,
                                            part_size=part['part_size'],
                                            context=self.upload_context)
            self.submit(part_task)


class MultiUploadStreamTask(MultiUploadTask):
    """Mutliupload stream to object in summary."""
    def __init__(self, client, bucket_name, key, storage_class, upload_context):
        super(MultiUploadStreamTask, self).__init__(client, bucket_name, key,
                storage_class, upload_context)

    def submit_parts(self, first_part_fp, first_part_number, io_handle):
        """
        Append all the parts task in work list.

        If the first part data we have read, put the first part info in parameter
        then add upload first part in work list.
        """
        submit_first_part_task = MultiUploadPartTask(client=self.client,
                                                     bucket_name=self.bucket_name,
                                                     key=self.key,
                                                     part_number=first_part_number,
                                                     fp=first_part_fp,
                                                     part_size=first_part_fp.len,
                                                     context=self.upload_context)
        self.submit(submit_first_part_task)

        while True:
            fp, is_remaining, part_number = io_handle.read()
            if fp.len == 0:
                self.upload_context.announce_expect_parts_num(part_number - 1)
                break

            submit_part_task = MultiUploadPartTask(client=self.client,
                                                   bucket_name=self.bucket_name,
                                                   key=self.key,
                                                   part_number=part_number,
                                                   fp=fp,
                                                   part_size=fp.len,
                                                   context=self.upload_context)
            self.submit(submit_part_task)
            if not is_remaining:
                self.upload_context.announce_expect_parts_num(part_number)
                break


class PartsInfo(object):
    """Get the part info when multiupload file."""
    def __init__(self, filename):
        self.filename = filename
        self.parts_num = -1
        self.part_size = -1
        self.file_size = -1
        self.default_part_size = DefaultPartSize

    def init(self, file_size=None):
        """
        Initiate all the parts need to be uploaded.
        file_size: when file_size is None, cli is executing multi parts upload 
                   otherwise, cli is execluting multi parts copy.
        """
        if file_size is None:
            self.file_size = path.getsize(self.filename)
        else:
            self.file_size = file_size
            self.default_part_size = DefaultCopyPartSize
        self.part_size = self._get_proper_part_size(self.file_size)
        self.parts_num = int(math.ceil(float(self.file_size)/self.part_size))

    def get_all_parts_num(self):
        """Get number of parts which need to be upload"""
        return self.parts_num

    def get_part(self):
        """Get a part need to uploaded."""
        for i in range(1, self.parts_num + 1):
            part = {}
            part['part_number'] = i
            part['offset'] = (i - 1) * self.part_size
            part['part_size'] = min(self.part_size, self.file_size - part['offset'])
            yield part

    def _get_proper_part_size(self, content_length):
        if content_length > MULTI_UPLOAD_MAX_FILE_SIZE:
            raise Exception("Can't upload/copy object which is larger than 5T,"
                            "your file length is %s" % str(content_length))

        proper_part_size = self.default_part_size
        if self.default_part_size * MaxPartsNum < content_length:
            lower_limit = int(math.ceil(float(content_length) / MaxPartsNum))
            proper_part_size = int(math.ceil(float(lower_limit) / self.default_part_size)) \
                               * self.default_part_size
        return proper_part_size
