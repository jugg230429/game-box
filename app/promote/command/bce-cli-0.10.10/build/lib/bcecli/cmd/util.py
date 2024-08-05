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
This module provide common utils.

Authors: xinzhenrui
Date:    2015/08/10
"""

import threading

import bcecli.baidubce
from bcecli.baidubce.exception import BceHttpClientError
from bcecli.baidubce.exception import BceServerError
from bcecli.baidubce.utils import required


def _safe_invoke(action, show=True):
    try:
        return action()
    except BceHttpClientError as e:
        if e.last_error and hasattr(e.last_error, 'code') and e.last_error.code == 'AccessDenied':
            raise Exception('AccessDenied. The account may not have permission or enough balance.')
        else:
            if show:
                print(_unwrap_last_message(e))
            raise e


def _unwrap_last_message(e):
    while True:
        if hasattr(e, 'last_error') and e.last_error:
            e = e.last_error
        else:
            return str(e)


def trim_trailing_slash(path):
    """
    Trims all slashes of the given path
    """
    if not path.endswith('/') or (not isinstance(path, str) \
                and not isinstance(path, unicode)):
        return path
    i = len(path) - 1
    while i >= 0:
        if path[i] != '/':
            break
        i -= 1
    return path[0:i + 1]


print_lock = threading.Lock()
def safe_print(content, thread_safe=True):
    """
    print content with builtin print()
    default thread safe
    """
    if thread_safe:
        with print_lock:
            print(content)
    else:
        print(content)


class AtmoicInteger(object):
    """
    atmoic integer
    """
    @required(value=int)
    def __init__(self, value=0):
        self.lock = threading.Lock()
        self.value = value

    @required(value=int)
    def increment_and_get(self, value=1):
        """
        """
        self.lock.acquire()
        self.value += value
        self.lock.release()
        return self.value

    @required(value=int)
    def decrement_and_get(self, value=1):
        """
        """
        self.lock.acquire()
        self.value -= value
        self.lock.release()
        return self.value

    def get(self):
        """
        """
        return self.value

    @required(value=int)
    def set(self, value):
        """
        """
        self.value = value

