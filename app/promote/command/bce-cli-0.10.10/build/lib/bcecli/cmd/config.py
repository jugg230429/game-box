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
This module provide the major operations on BOS configuration.

Authors: zhangshuai14
Date:    2015/01/26
"""

import os
import argparse
from ConfigParser import SafeConfigParser

from bcecli.conf.server import FileServerConfigProvider
from bcecli.conf.server import DefaultRegionServerConfigProvider
from bcecli.conf.server import ChainServerConfigProvider
from bcecli.conf.server import SERVER_SECTION_NAME
from bcecli.conf.server import DEFAULT_USE_AUTO_SWITCH_DOMAIN
from bcecli.conf.credential import FileCredentialProvider
from bcecli.conf.credential import DefaultCredentialProvider
from bcecli.conf.credential import RaiseExceptionCredentialProvider
from bcecli.conf.credential import ChainCredentialProvider
from bcecli.conf.credential import CREDENTIAL_SECTION_NAME
from bcecli.conf.bucket_region_cache import BucketToRegionCache

DEFAULT_REGION = 'bj'
EMPTY_STRING = 'none'
DEFAULT_USE_HTTPS_PROTOCOL = 'no'
DEFAULT_MULTI_UPLOAD_THREAD_NUM = '10'

config_folder = os.path.expanduser('~') + os.sep + '.bce'
credential_path = config_folder + os.sep + 'credentials'
config_path = config_folder + os.sep + 'config'
bucket_region_cache_path = config_folder + os.sep + 'bucket_region_cache'
multiupload_folder = reduce(os.path.join, [os.path.expanduser('~'), '.bce',
                            'multiupload_infos', 'ak', ""]) 

credential_file_provider = FileCredentialProvider(credential_path)
credential_provider = ChainCredentialProvider([
    credential_file_provider,
    DefaultCredentialProvider(),
    RaiseExceptionCredentialProvider()
])
server_config_file_provider = FileServerConfigProvider(config_path)
server_config_provider = ChainServerConfigProvider([
    server_config_file_provider,
    DefaultRegionServerConfigProvider(),
    RaiseExceptionCredentialProvider()
])
bucket_region_cache = BucketToRegionCache(bucket_region_cache_path)

def __init_credential_config(credential_path):
    credential_file_provider = FileCredentialProvider(credential_path)
    credential_file_provider.access_key = ""
    credential_file_provider.secret_key = ""
    credential_file_provider.save()


def __init_server_config(config_path):
    server_config_file_provider = FileServerConfigProvider(config_path)
    server_config_file_provider.region = DEFAULT_REGION 
    server_config_file_provider.domain = ""
    server_config_file_provider.use_auto_switch_domain = DEFAULT_USE_AUTO_SWITCH_DOMAIN
    server_config_file_provider.breakpoint_file_expiration = "7" 
    server_config_file_provider.use_https_protocol = DEFAULT_USE_HTTPS_PROTOCOL
    server_config_file_provider.multi_upload_thread_num = DEFAULT_MULTI_UPLOAD_THREAD_NUM
    server_config_file_provider.save()


def init_conf_folder(config_folder):
    """
    Init conf folder and files
    """
    credential_path = config_folder + os.sep + 'credentials'
    config_path = config_folder + os.sep + 'config'
    if os.path.exists(config_folder):
        if os.path.isdir(config_folder):
            if not os.path.exists(credential_path):
                __init_credential_config(credential_path)
            if not os.path.exists(config_path):
                __init_server_config(config_path)
        else:
            raise Exception("Cannot create directory '%s': File exists. please check..."
                            % config_folder)
    else:
        os.mkdir(config_folder)
        __init_credential_config(credential_path)
        __init_server_config(config_path)


def config_interactive(config_folder):
    """
    Provide interactive configuration with user
    """
    init_conf_folder(config_folder)
    credential_path = config_folder + os.sep + 'credentials'
    config_path = config_folder + os.sep + 'config'
    credential_file_provider = FileCredentialProvider(credential_path)
    server_config_file_provider = FileServerConfigProvider(config_path)

    credential_provider = ChainCredentialProvider([
        credential_file_provider,
        DefaultCredentialProvider(),
        RaiseExceptionCredentialProvider()
    ])
    server_config_provider = ChainServerConfigProvider([
        server_config_file_provider,
        DefaultRegionServerConfigProvider(),
        RaiseExceptionCredentialProvider()
    ])

    # config ak
    ak = credential_provider.access_key if len(credential_provider.access_key) > 0 else "None"
    ak_prompt = "BOS Access Key ID [%s]: " % ak
    new_ak = raw_input(ak_prompt).strip()
    if len(new_ak) > 0:
        if new_ak.lower() == EMPTY_STRING:
            credential_file_provider.access_key = "" 
        else:
            credential_file_provider.access_key = new_ak 

    # config sk
    sk = credential_provider.secret_key if len(credential_provider.secret_key) > 0 else "None"
    sk_prompt = "BOS Secret Access Key [%s]: " % sk
    new_sk = raw_input(sk_prompt).strip()
    if len(new_sk) > 0:
        if new_sk.lower() == EMPTY_STRING:
            credential_file_provider.secret_key = "" 
        else:
            credential_file_provider.secret_key = new_sk

    #config security token
    security_token = credential_provider.security_token if len(credential_provider.security_token) \
            > 0 else "None"
    security_token_prompt = "BCE Security Token [%s]: " % security_token
    new_security_token = raw_input(security_token_prompt).strip()
    if len(new_security_token) > 0:
        if new_security_token.lower() == EMPTY_STRING:
            credential_file_provider.security_token = "" 
        else:
            credential_file_provider.security_token = new_security_token

    # config region
    region = server_config_provider.region if len(server_config_provider.region) > 0 else "None"
    region_prompt = "Default region name [%s]: " % region
    new_region = raw_input(region_prompt).strip()
    if len(new_region) > 0:
        if new_region.lower() == EMPTY_STRING:
            server_config_file_provider.region = ""
        else:
            server_config_file_provider.region = new_region

    # config domain
    domain = server_config_provider.domain if len(server_config_provider.domain) > 0 else "None"
    domain_prompt = "Default domain [%s]: " % domain
    new_domain = raw_input(domain_prompt).strip()
    if len(new_domain) > 0:
        if new_domain.lower() == EMPTY_STRING:
            server_config_file_provider.domain = ""
        else:
            server_config_file_provider.domain = new_domain
    
    # config use auto switch domain
    use_auto_switch_domain = server_config_provider.use_auto_switch_domain \
            if len(server_config_provider.use_auto_switch_domain) > 0 else "None"
    use_auto_switch_domain_prompt = "Default use auto switch domain [%s]: " % use_auto_switch_domain
    new_use_auto_switch_domain = raw_input(use_auto_switch_domain_prompt).strip()
    if len(new_use_auto_switch_domain) > 0:
        temp_use_auto_switch_domain =  new_use_auto_switch_domain.lower()
        if temp_use_auto_switch_domain in ["yes", "no"]:
            server_config_file_provider.use_auto_switch_domain = temp_use_auto_switch_domain
        else:
            print("Only support 'no' and 'yes', [%s] is invalid. Default value is used." 
                    %new_use_auto_switch_domain)
            server_config_file_provider.use_auto_switch_domain = ""

    # config breakpoint_file_expiration
    breakpoint_file_expiration = server_config_provider.breakpoint_file_expiration \
    if len(server_config_provider.breakpoint_file_expiration) > 0 else "None"

    breakpoint_file_expiration_prompt = "Default breakpoint_file_expiration [%s] days: " \
                                        % breakpoint_file_expiration

    new_breakpoint_file_expiration = raw_input(breakpoint_file_expiration_prompt).strip()
    if len(new_breakpoint_file_expiration) > 0:
        if new_breakpoint_file_expiration.lower() == EMPTY_STRING:
            server_config_file_provider.breakpoint_file_expiration = ""
        else:
            try:
                server_config_file_provider.breakpoint_file_expiration = \
                        new_breakpoint_file_expiration
            except ValueError as ve:
                print("File expiration must be positive integer, [%s] is not valid." %
                      new_breakpoint_file_expiration)
                print("User default value instead.")
                server_config_file_provider.breakpoint_file_expiration = ""

    # config use https protocol
    use_https_protocol = server_config_provider.use_https_protocol \
                         if len(server_config_provider.use_https_protocol) > 0 else "None"
    use_https_protocol_prompt = "Default use https protocol [%s]: " % use_https_protocol
    new_use_https_protocol = raw_input(use_https_protocol_prompt).strip()
    if len(new_use_https_protocol) > 0:
        temp_new_use_https_protocol = new_use_https_protocol.lower()
        if (temp_new_use_https_protocol == EMPTY_STRING) or \
           (temp_new_use_https_protocol not in ["yes", "no"]):
            print("Only support http(no) and https(yes), [%s] is invalid. Default value is used." %
                  new_use_https_protocol)
            server_config_file_provider.use_https_protocol = ""
        else:
            server_config_file_provider.use_https_protocol = temp_new_use_https_protocol

    # config multi upload thread num
    multi_upload_thread_num = server_config_provider.multi_upload_thread_num \
                              if len(server_config_provider.multi_upload_thread_num) > 0 else "None"
    multi_upload_thread_num_prompt = "Default multi upload thread num [%s]: " % \
                                     multi_upload_thread_num
    new_multi_upload_thread_num = raw_input(multi_upload_thread_num_prompt).strip()
    if len(new_multi_upload_thread_num) > 0:
        if new_multi_upload_thread_num.lower() == EMPTY_STRING:
            server_config_file_provider.multi_upload_thread_num = ""
        else:
            try:
                thread_num = int(new_multi_upload_thread_num)
                if thread_num <= 0:
                    raise ValueError
                server_config_file_provider.multi_upload_thread_num = repr(thread_num)
            except ValueError as ve:
                print("Input multi upload thread num must be a positive integer, [%s] is not valid"
                      % new_multi_upload_thread_num)
                print("Use default multi upload thread num [%s] instead" % multi_upload_thread_num)
                server_config_file_provider.multi_upload_thread_num = ""

    credential_file_provider.save()
    server_config_file_provider.save()


class ConfigAction(argparse.Action):
    """
    Customized arg action for -c/--configure
    """
    def __call__(self, parser, namespace, values, option_string=None):
        config_interactive(os.path.expanduser(values))
        parser.exit()


class ReloadConfAction(argparse.Action):
    """
    Reload conf from path
    """
    def __call__(self, parser, namespace, values, option_string=None):
        global config_folder
        global credential_provider
        global server_config_provider
        global multiupload_folder
        global credential_file_provider
        global server_config_file_provider
        global bucket_region_cache

        config_folder = os.path.expanduser(values)
        credential_path = config_folder + os.sep + 'credentials'
        config_path = config_folder + os.sep + 'config'
        bucket_region_cache_path = config_folder + os.sep + 'bucket_region_cache'
        credential_file_provider = FileCredentialProvider(credential_path)
        credential_provider = ChainCredentialProvider([
            credential_file_provider,
            DefaultCredentialProvider(),
            RaiseExceptionCredentialProvider()
        ])
        server_config_file_provider = FileServerConfigProvider(config_path)
        server_config_provider = ChainServerConfigProvider([
            server_config_file_provider,
            DefaultRegionServerConfigProvider(),
            RaiseExceptionCredentialProvider()
            ])
        multiupload_folder = reduce(os.path.join, [config_folder, 
                                    'multiupload_infos', 'ak', ""]) 
        bucket_region_cache = BucketToRegionCache(bucket_region_cache_path)


def destruct_conf_folder():
    """
    If the conf of cache and config are changed.
    We do not save the change to file, when run time.
    We save the change into file when exit!
    """
    try:
        bucket_region_cache.save()
        server_config_file_provider.save()
    except Exception as e:
        raise e

