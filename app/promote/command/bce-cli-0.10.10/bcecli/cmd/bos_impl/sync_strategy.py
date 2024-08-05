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
This module provide the major sync strategies for BOS.

Authors: BCE BOS
"""
class BaseSync(object):
    """
    Base sync strategy, it is an interface whos subclasss should implement
    should_sync and gen_sync_func method
    """
    def should_sync(self, src_file, dst_file):
        """
        **Subclasses should implement this method.**
        This function takes two ``FileSyncInfo`` objects (one from the source and
        one from the destination).  Then makes a decision on whether a given
        operation (e.g. a upload, copy, download) should be allowed
        to take place.

        The function currently raises a ``NotImplementedError``.  So this
        method must be overwritten when this class is subclassed.  Note
        that this method must return a Boolean as documented below.

        :type src_file: ``FileSyncInfo`` object
        :param src_file: A representation of the opertaion that is to be
            performed on a specfic file existing in the source.  Note if
            the file does not exist at the source, ``src_file`` is None.

        :type dst_file: ``FileSyncInfo`` object
        :param dst_file: A representation of the operation that is to be
            performed on a specific file existing in the destination. Note if
            the file does not exist at the destination, ``dst_file`` is None.

        :rtype: Boolean
        :return: True if an operation based on the ``FileSyncInfo`` should be
            allowed to occur.
            False if an operation based on the ``FileSyncInfo`` should not be
            allowed to occur. Note the operation being referred to depends on
            the ``sync_type`` of the sync strategy:

            'file_at_src_and_dst': refers to ``src_file``

            'file_not_at_dst': refers to ``src_file``

            'file_not_at_src': refers to ``dst_file``
         """

        raise NotImplementedError("should_sync")

    def gen_sync_func(self, file_sync_info):
        """
        Generates a function which describes how to sync the given file info
        :file_sync_info: a `FileSyncInfo`
        :rtype: String
        :return: a string to describe the sync operation
        """
        raise NotImplementedError("gen_sync_func")


class SizeAndLastModifiedSync(BaseSync):
    """
    Compares size and last modified time only
    """
    def should_sync(self, src, dst):
        """
        Compare time and size only
        """
        debug = False
        if debug:
            print("src size: %d, dst size %d" % (src.size, dst.size))
            print("src last_modified: %d, dst last_modified %d" % \
                    (src.last_modified, dst.last_modified))
        if src.last_modified > dst.last_modified \
                or (src.last_modified == dst.last_modified and \
                    src.size != dst.size):
            if debug:
                print("should sync")
            return True
        else:
            if debug:
                print("should NOT sync")
            return False

    def gen_sync_func(self, file_sync_info):
        return "cp"


class DeleteDstSync(BaseSync):
    """
    Deletes the sync dst
    """
    def should_sync(self, src, dst):
        """
        Compare existence
        """
        if dst is not None and src is None:
            dst.src_path = None
            return True
        else:
            return False

    def gen_sync_func(self, file_sync_info):
        return "delete"


class NeverSync(BaseSync):
    """
    Does nothing
    """
    def should_sync(self, src, dst):
        return False

    def gen_sync_func(self, file_sync_info):
        return "never_sync"


class AlwaysSync(BaseSync):
    """
    Always sync the src to the dst
    """
    def should_sync(self, src, dst):
        if src is not None:
            return True
        else:
            return False

    def gen_sync_func(self, file_sync_info):
        return "cp"
