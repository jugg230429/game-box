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
Provides access to the BCE credentials used for accessing BCE services: BCE access key ID and
secret access key.
These credentials are used to securely sign requests to BCE services.
"""


class BceCredentials(object):
    """
    Provides access to the BCE credentials used for accessing BCE services:
    BCE access key ID and secret access key.
    """
    def __init__(self, access_key_id, secret_access_key, security_token=None):
        self.access_key_id = access_key_id
        self.secret_access_key = secret_access_key
        self.security_token = security_token
