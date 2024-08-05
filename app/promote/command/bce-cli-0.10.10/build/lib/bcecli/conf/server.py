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
This module provide the major operations on BOS server configuration.

Authors: zhangshuai14
Date:    2015/01/
"""
from os import path
from ConfigParser import SafeConfigParser

from bcecli.conf.common import config_try_get_option


SERVER_SECTION_NAME = 'defaults'
DOMAIN_OPTION_NAME = 'domain'
REGION_OPTION_NAME = 'region'
USE_AUTO_SWITCH_DOMAIN_OPTION_NAME = 'auto_switch_domain'
BREAKPIONT_FILE_EXPIRATION_OPTION_NAME = 'breakpoint_file_expiration'
USE_HTTPS_OPTION_NAME = 'https'
MULTI_UPLOAD_THREAD_NUM_NAME = 'multi_upload_thread_num'
DEFAULT_DOMAIN_SUFFIX = '.bcebos.com'
DEFAULT_REGION = 'bj'
DEFAULT_USE_AUTO_SWITCH_DOMAIN = 'yes'
DEFAULT_BREAKPIONT_FILE_EXPIRATION = "7"
DEFAULT_USE_HTTPS_PROTOCOL = 'no'
DEFAULT_MULTI_UPLOAD_THREAD_NUM = '10'
WILL_USE_AUTO_SWTICH_DOMAIN = 'yes'
AOLLOWED_CONFIRM_OPTIONS = ['yes', 'no']

DEFAULT_DOMAINS = {
    "bj": "bj.bcebos.com",
    "gz": "gz.bcebos.com",
    "su": "su.bcebos.com",
    "hk02": "hk-2.bcebos.com",
    "yq": "bos.yq.baidubce.com"
}
DOMAINS_SECTION_NAME = "domains"

class FileServerConfigProvider(object):
    """
    Read server configuration from a file
    """

    def __init__(self, config_file_path):
        self.__config_file_path = config_file_path
        self.__domain = None
        self.__region = None
        self.__use_auto_switch_domain = None
        self.__breakpoint_file_expiration = None
        self.__use_https_protocol = None
        self.__multi_upload_thread_num = None
        self.__dirty = False
        self.__domains = None
        try:
            if path.exists(config_file_path):
                config = SafeConfigParser()
                config.read(config_file_path)
                self.__domain = config_try_get_option(
                    config,
                    SERVER_SECTION_NAME,
                    DOMAIN_OPTION_NAME)
                self.__region = config_try_get_option(
                    config,
                    SERVER_SECTION_NAME,
                    REGION_OPTION_NAME)
                self.__use_auto_switch_domain = config_try_get_option(
                    config,
                    SERVER_SECTION_NAME,
                    USE_AUTO_SWITCH_DOMAIN_OPTION_NAME)
                self.__breakpoint_file_expiration = config_try_get_option(
                    config,
                    SERVER_SECTION_NAME,
                    BREAKPIONT_FILE_EXPIRATION_OPTION_NAME)
                self.__use_https_protocol = config_try_get_option(
                    config,
                    SERVER_SECTION_NAME,
                    USE_HTTPS_OPTION_NAME)
                self.__multi_upload_thread_num = config_try_get_option(
                    config,
                    SERVER_SECTION_NAME,
                    MULTI_UPLOAD_THREAD_NUM_NAME)
                self.__domains = self.__try_get_domains_to_dict(
                    config,
                    DOMAINS_SECTION_NAME)
                self.__compatibly_old_conf()

        except Exception:
            # log error
            # promote user to check config file.
            raise

    def __try_get_domains_to_dict(self, config, section_name):
        """
        :param section_name: Get domains into a dict from conf
        :return: Domains which are stored in a dict
        """
        dicts = {}
        if config.has_section(section_name):
            items = config.items(section_name)
            for (v, k) in items:
                dicts[v] = k
        return dicts

    def __does_need_insert_into_domains(self, region, domain):
        if region and domain:
            if region in self.__domains and self.__domains[region] == domain:
                return False
            else:
                return True
        else:
            return False
    
    def __compatibly_old_conf(self):
        """
        The old configuration file may not have mapping table (self.__domains).
        But in the new code, we get endpoint from mapping table instead of
        config.domain.
        If the existed configuration file have configured with region and domain,
        we need add this relation into the mapping table.
        """
        # config auto_switch_domain
        if self.__use_auto_switch_domain not in AOLLOWED_CONFIRM_OPTIONS:
            self.__use_auto_switch_domain = DEFAULT_USE_AUTO_SWITCH_DOMAIN
            self.__dirty = True
        if self.__use_auto_switch_domain != WILL_USE_AUTO_SWTICH_DOMAIN or not self.__domain:
            return
        # config domains
        if not self.__domains:
            self.__domains = {}
        if self.__does_need_insert_into_domains(self.__region, self.__domain):
            self.__domains[self.__region] = self.__domain
            self.__dirty = True

    def get_domain_by_region(self, region): #get
        """
        Get server domain by region
        :return: The domian of region
        """
        if not region:
            return None
        if self.__domains and (region in self.__domains):
            return self.__domains[region]
        elif region in DEFAULT_DOMAINS:
            return DEFAULT_DOMAINS[region]
        else:
            return None

    def get_domain(self):
        """
        :return: Server domain address
        """
        if self.__domain: # domain is set(Not None and Not empty string
            return self.__domain
        elif self.__region: # domain is not set and region is set
            domain = self.get_domain_by_region(self.__region)
            if domain:
                return domain
            else:
                return self.__region + DEFAULT_DOMAIN_SUFFIX
        else: # both of domain and region is not set
            return None

    def get_domains(self):
        """
        :return: Server domains address
        """
        if self.__domains: # domain is set(Not None and Not empty dict
            return self.__domains
        else: # both of domain and region is not set
            return None

    def get_region(self):
        """
        :return: Server region
        """
        if self.__region:
            return self.__region
        else:
            return None

    def get_use_auto_switch_domain(self):
        """
        :return use auto siwitch domain ('yes' or 'no')
        """
        if self.__use_auto_switch_domain in AOLLOWED_CONFIRM_OPTIONS: 
            return self.__use_auto_switch_domain
        else:
            return None

    def get_breakpoint_file_expiration(self):
        """
        :return: Breakpoint file expiration
        """
        if self.__breakpoint_file_expiration:
            return self.__breakpoint_file_expiration
        else:
            return None

    def get_use_https_protocol(self):
        """
        :return: Server is use https protocol
        """
        if self.__use_https_protocol:
            return self.__use_https_protocol
        else:
            return None

    def get_multi_upload_thread_num(self):
        """
        :return: Server is multi upload thread num
        """
        if self.__multi_upload_thread_num:
            return self.__multi_upload_thread_num
        else:
            return None

    def set_domain(self, domain):
        """
        :param domain: Set server domain address
        """
        if domain != self.__domain:
            self.__domain = domain
            self.__dirty = True

    def set_domains(self, domains):
        """
        :param domain: Set server domains address
        """
        if domains:
            self.__domains = domains
            self.__dirty = True

    def del_domain_in_domains(self, regions): #delete
        if not self.__domains:
            return
        for region in regions:
            if region in self.__domains:
                del self.__domains[region]
                self.__dirty = True

    def insert_domain_into_domains(self, region, domain): #update and insert
        """
        :param region: Set server domain by region
        :return: The domian of server
        """
        if not region or not domain:
            return False
        if not self.__domains:
            self.__domains = {}
        if (region in self.__domains) and self.__domains[region] == domain:
            return False
        self.__domains[region] = domain
        self.__dirty = True
        return True

    def set_region(self, region):
        """
        :param region: Set server region
        """
        if region != self.__region:
            self.__region = region
            self.__dirty = True
    
    def set_use_auto_switch_domain(self, use_auto_switch_domain):
        """
        :set use auto siwitch domain ('yes' or 'no' or '')
        """
        if use_auto_switch_domain != self.__use_auto_switch_domain:
            self.__use_auto_switch_domain = use_auto_switch_domain
            self.__dirty = True

    def set_breakpoint_file_expiration(self, breakpoint_file_expiration):
        """
        :param breakpoint_file_expiration: Set breakpoint file expiration
        """
        if breakpoint_file_expiration != self.__breakpoint_file_expiration:
            self.__breakpoint_file_expiration = breakpoint_file_expiration
            self.__dirty = True

    def set_use_https_protocol(self, use_https_protocol):
        """
        :param use_https_protocol
        """
        if use_https_protocol != self.__use_https_protocol:
            self.__use_https_protocol = use_https_protocol
            self.__dirty = True

    def set_multi_upload_thread_num(self, multi_upload_thread_num):
        """
        :param multi_upload_thread_num
        """
        if multi_upload_thread_num != self.__multi_upload_thread_num:
            self.__multi_upload_thread_num = multi_upload_thread_num
            self.__dirty = True

    def save(self):
        """
        Save changes into associated file
        """
        if self.__dirty:
            config = SafeConfigParser()
            config.read(self.__config_file_path)

            if not config.has_section(SERVER_SECTION_NAME):
                config.add_section(SERVER_SECTION_NAME)
            #clear DOMAINS_SECTION_NAME
            if config.has_section(DOMAINS_SECTION_NAME):
                config.remove_section(DOMAINS_SECTION_NAME)
            config.add_section(DOMAINS_SECTION_NAME)

            if self.__domain is not None:
                config.set(SERVER_SECTION_NAME, DOMAIN_OPTION_NAME, self.__domain)
            if self.__region is not None:
                config.set(SERVER_SECTION_NAME, REGION_OPTION_NAME, self.__region)
            if self.__use_auto_switch_domain is not None:
                config.set(SERVER_SECTION_NAME, USE_AUTO_SWITCH_DOMAIN_OPTION_NAME,
                        self.__use_auto_switch_domain)
            if self.__breakpoint_file_expiration is not None:
                config.set(SERVER_SECTION_NAME, BREAKPIONT_FILE_EXPIRATION_OPTION_NAME,
                 self.__breakpoint_file_expiration)
            if self.__use_https_protocol is not None:
                config.set(SERVER_SECTION_NAME, USE_HTTPS_OPTION_NAME, self.__use_https_protocol)
            if self.__multi_upload_thread_num is not None:
                config.set(SERVER_SECTION_NAME, MULTI_UPLOAD_THREAD_NUM_NAME,
                        self.__multi_upload_thread_num)

            if self.__domains is not None:
                for k, v in self.__domains.iteritems():
                    config.set(DOMAINS_SECTION_NAME, k, v)
            with open(self.__config_file_path, 'w') as config_file:
                config.write(config_file)

    domain = property(get_domain, set_domain)
    domains = property(get_domains, set_domains)
    region = property(get_region, set_region)
    use_auto_switch_domain = property(get_use_auto_switch_domain, set_use_auto_switch_domain)
    breakpoint_file_expiration = property(get_breakpoint_file_expiration,
         set_breakpoint_file_expiration)
    use_https_protocol = property(get_use_https_protocol, set_use_https_protocol)
    multi_upload_thread_num = property(get_multi_upload_thread_num, set_multi_upload_thread_num)


class DefaultRegionServerConfigProvider(object):
    """
    Provide default value for server configuration
    """

    @classmethod
    def get_domain(cls):
        """
        :return: Server domain address (Always be False)
        """
        return DEFAULT_REGION + DEFAULT_DOMAIN_SUFFIX 

    @classmethod
    def get_domain_by_region(cls, region):
        """
        :return: Server domain address (Always be False)
        """
        if region:
            return region + DEFAULT_DOMAIN_SUFFIX
        else:
            return DEFAULT_REGION + DEFAULT_DOMAIN_SUFFIX

    @classmethod
    def get_region(cls):
        """
        :return: Server region (Always be 'bj')
        """
        return DEFAULT_REGION

    @classmethod
    def get_use_auto_switch_domain(cls):
        """
        :return use auto siwitch domain (Always be 'yes')
        """
        return DEFAULT_USE_AUTO_SWITCH_DOMAIN

    @classmethod
    def get_breakpoint_file_expiration(cls):
        """
        :return: breakpoint file expiration
        """
        return DEFAULT_BREAKPIONT_FILE_EXPIRATION

    @classmethod
    def get_use_https_protocol(cls):
        """
        :return: Server use https
        """
        return DEFAULT_USE_HTTPS_PROTOCOL

    @classmethod
    def get_multi_upload_thread_num(cls):
        """
        :return: Server multi upload thread num
        """
        return DEFAULT_MULTI_UPLOAD_THREAD_NUM

    domain = property(get_domain)
    region = property(get_region)
    use_auto_switch_domain = property(get_use_auto_switch_domain)
    breakpoint_file_expiration = property(get_breakpoint_file_expiration)
    use_https_protocol = property(get_use_https_protocol)
    multi_upload_thread_num = property(get_multi_upload_thread_num)


class RaiseExceptionServerConfigProvider(object):
    """
    Raise exception when reading any server configurations
    """

    @classmethod
    def get_domain(cls):
        """
        :return: Server domain address, always raise exception
        """
        raise RuntimeError('There is no domain found!')
    
    @classmethod
    def get_domain_by_region(cls):
        """
        :return: Server domain address, always raise exception
        """
        raise RuntimeError('There is no domain found!')


    @classmethod
    def get_region(cls):
        """
        :return: Server region, always raise exception
        """
        raise RuntimeError('There is no region found!')

    @classmethod
    def get_use_auto_switch_domain(cls):
        """
        :return use auto siwitch domain (Always be 'no')
        """
        raise RuntimeError('There is no use_auto_switch_domain found!')


    @classmethod
    def get_breakpoint_file_expiration(cls):
        """
        :return: breakpoint file expiration
        """
        raise RuntimeError('There is no region found!')

    @classmethod
    def get_use_https_protocol(cls):
        """
        :return Server use https, always raise exception
        """
        raise RuntimeError('There is no use_https found!')

    @classmethod
    def get_multi_upload_thread_num(cls):
        """
        :return Server multi upload thread num, always raise exception
        """
        raise RuntimeError('There is no multi_upload_thread_num found!')

    domain = property(get_domain)
    region = property(get_region)
    use_auto_switch_domain = property(get_use_auto_switch_domain)
    breakpoint_file_expiration = property(get_breakpoint_file_expiration)
    use_https_protocol = property(get_use_https_protocol)
    multi_upload_thread_num = property(get_multi_upload_thread_num)


class ChainServerConfigProvider(object):
    """
    Query server configurations from a responsible chain.
    """

    def __init__(self, chain):
        self.__chain = chain

    def get_domain(self):
        """
        :return: Server domain address
        """
        for provider in self.__chain:
            if provider.get_domain() is not None:
                return provider.get_domain()

        assert False # Should not get here.

    def get_domain_by_region(self, region):
        """
        :return: domain
        """
        for provider in self.__chain:
            domain = provider.get_domain_by_region(region)
            if domain  is not None:
                return domain
        assert False

    def get_region(self):
        """
        :return: Server region
        """
        for provider in self.__chain:
            if provider.get_region() is not None:
                return provider.get_region()

        assert False # Should not get here.

    def get_use_auto_switch_domain(self):
        """
        :return: 'yes' or 'no' 
        """
        for provider in self.__chain:
            if provider.get_use_auto_switch_domain() is not None:
                return provider.get_use_auto_switch_domain()

        assert False # Should not get here.


    use_auto_switch_domain = property(get_use_auto_switch_domain)
    def get_breakpoint_file_expiration(self):
        """
        :return: breakpoint file expiration
        """
        for provider in self.__chain:
            if provider.get_breakpoint_file_expiration() is not None:
                return provider.get_breakpoint_file_expiration()

        assert False # Should not get here.

    def get_use_https_protocol(self):
        """
        :return Server use https protocol
        """
        for provider in self.__chain:
            if provider.get_use_https_protocol() is not None:
                return provider.get_use_https_protocol()

        assert False # Should not get here.

    def get_multi_upload_thread_num(self):
        """
        :return Server multi upload thread num
        """
        for provider in self.__chain:
            if provider.get_multi_upload_thread_num() is not None:
                return provider.get_multi_upload_thread_num()

        assert False # Should not get here.

    domain = property(get_domain)
    region = property(get_region)
    use_auto_switch_domain = property(get_use_auto_switch_domain)
    breakpoint_file_expiration = property(get_breakpoint_file_expiration)
    use_https_protocol = property(get_use_https_protocol)
    multi_upload_thread_num = property(get_multi_upload_thread_num)
