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
This module defines a common configuration class for BCE.
"""

import bcecli.baidubce.protocol
import bcecli.baidubce.region
from bcecli.baidubce.retry_policy import BackOffRetryPolicy


class BceClientConfiguration(object):
    """Configuration of Bce client."""

    def __init__(self,
                 credentials=None,
                 endpoint=None,
                 protocol=None,
                 region=None,
                 connection_timeout_in_mills=None,
                 send_buf_size=None,
                 recv_buf_size=None,
                 retry_policy=None):
        self.credentials = credentials
        self.endpoint = endpoint
        self.protocol = protocol
        self.region = region
        self.connection_timeout_in_mills = connection_timeout_in_mills
        self.send_buf_size = send_buf_size
        self.recv_buf_size = recv_buf_size
        if retry_policy is None:
            self.retry_policy = BackOffRetryPolicy()
        else:
            self.retry_policy = retry_policy

    def merge_non_none_values(self, other):
        """

        :param other:
        :return:
        """
        for k, v in other.__dict__.items():
            if v is not None:
                self.__dict__[k] = v

    def set_use_https_protocol(self):
        """
        Set the client protocol to HTTPS
        """
        self.protocol = bcecli.baidubce.protocol.HTTPS

DEFAULT_PROTOCOL = bcecli.baidubce.protocol.HTTP
DEFAULT_REGION = bcecli.baidubce.region.BEIJING
DEFAULT_CONNECTION_TIMEOUT_IN_MILLIS = 50 * 1000
DEFAULT_SEND_BUF_SIZE = 1024 * 1024
DEFAULT_RECV_BUF_SIZE = 10 * 1024 * 1024
DEFAULT_CONFIG = BceClientConfiguration(
    protocol=DEFAULT_PROTOCOL,
    region=DEFAULT_REGION,
    connection_timeout_in_mills=DEFAULT_CONNECTION_TIMEOUT_IN_MILLIS,
    send_buf_size=DEFAULT_SEND_BUF_SIZE,
    recv_buf_size=DEFAULT_RECV_BUF_SIZE,
    retry_policy=BackOffRetryPolicy())
