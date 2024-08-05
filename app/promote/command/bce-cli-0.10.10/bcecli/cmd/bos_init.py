# Copyright (c) 2014 Baidu.com, Inc. All Rights Reserved
#
# Licensed under the Apache License, Version 2.0 (the "License"); you may not
# use this file
# except in compliance with the License. You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the
# License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS
# OF ANY KIND,
# either express or implied. See the License for the specific language governing
# permissions
# and limitations under the License.
#coding=utf-8
"""
This module is used to init bos.
This module also provide two functions to get bucket's region.
"""

from bcecli.cmd.bos_client_wrapper import BosClientWrapper
from bcecli.baidubce.services.bos.bos_client import BosClient
from bcecli.baidubce.retry_policy import BackOffRetryPolicy
from bcecli.baidubce.bce_client_configuration import BceClientConfiguration
from bcecli.baidubce.auth.bce_credentials import BceCredentials
from bcecli.cmd import config
from bcecli.lazy_load_proxy import LazyLoadProxy


def __build_bos_client():
    """ init bos """
    retry_policy = BackOffRetryPolicy(max_error_retry=10)
    bce_client_config = BceClientConfiguration(
        BceCredentials(
            config.credential_provider.access_key,
            config.credential_provider.secret_key,
            config.credential_provider.security_token),
        config.server_config_provider.domain,
        retry_policy=retry_policy)
    if config.server_config_provider.use_https_protocol == 'yes':
        bce_client_config.set_use_https_protocol()
    if config.server_config_provider.use_auto_switch_domain == 'yes':
        return BosClientWrapper(bce_client_config)
    else:
        return BosClient(bce_client_config)

bos = LazyLoadProxy(__build_bos_client)
dst_config = BceClientConfiguration()
src_config = BceClientConfiguration()
