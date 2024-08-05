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
This module in charge of all the threads.

Authors: xinzhenrui
Date:    2015/08/10
"""
import logging
import Queue
import threading

from bcecli.cmd import config
from bcecli.cmd import util

_logger = logging.getLogger(__name__)


class Executor(object):
    """Center of management of thead."""
    try:
        THREAD_NUM = int(config.server_config_provider.multi_upload_thread_num)
    except Exception as e:
        print "please check your config thread num: %s" % str(
                config.server_config_provider.multi_upload_thread_num)
        THREAD_NUM = config.DEFAULT_MULTI_UPLOAD_THREAD_NUM

    def __init__(self):
        self.queue = Queue.Queue(maxsize=self.THREAD_NUM * 2)
        self.thread_list = []

    def submit(self, task):
        """Add task to queue, each worker get task from queue."""
        self.queue.put(task)

    def start(self):
        """Build thread list and start thread."""
        for i in range(self.THREAD_NUM):
            worker = Worker(queue=self.queue)
            self.thread_list.append(worker)
            worker.setDaemon(True)
            worker.start()

    def wait_until_shutdown(self):
        """
        For each thread in thread list, we won't cancel the thread immediately.

        Once we get a new task and detected the state is CANCELED, we raise an Exception,
        then the thread is ended.
        """
        _logger.debug("Start to shutdown all the thread.")
        for thread in self.thread_list:
            thread.join()
            _logger.debug("Thread has been shutdown: %s" % thread)


class Worker(threading.Thread):
    """
    This in charge of performing the tasks in queue.
    """
    def __init__(self, queue):
        threading.Thread.__init__(self)
        self.queue = queue

    def run(self):
        """Start to run a tasks."""
        while True:
            try:
                function = self.queue.get(True, timeout=1)
            except Queue.Empty:
                break

            if callable(function):
                try:
                    util._safe_invoke(function)
                except Exception as e:
                    _logger.error("Work failed with exception: %s" % str(e))
