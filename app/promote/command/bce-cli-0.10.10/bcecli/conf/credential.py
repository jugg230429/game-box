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
This module provide the major operations on BOS credential configuration.

Authors: zhangshuai14
Date:    2015/01/26
"""

from os import path
from ConfigParser import SafeConfigParser

from bcecli.conf.common import config_try_get_option


CREDENTIAL_SECTION_NAME = 'defaults'
ACCESS_KEY_OPTION_NAME = 'bce_access_key_id'
SECRET_KEY_OPTION_NAME = 'bce_secret_access_key'
SECURITY_TOKEN_OPTION_NAME = 'bce_security_token'


class FileCredentialProvider(object):
    """
    Read credential configuration from a file
    """

    def __init__(self, config_file_path):
        self.__config_file_path = config_file_path
        self.__ak = None
        self.__sk = None
        self.__security_token = None
        self.__dirty = False
        try:
            if path.exists(config_file_path):
                config = SafeConfigParser()
                config.read(config_file_path)
                self.__ak = config_try_get_option(
                    config,
                    CREDENTIAL_SECTION_NAME,
                    ACCESS_KEY_OPTION_NAME)
                self.__sk = config_try_get_option(
                    config,
                    CREDENTIAL_SECTION_NAME,
                    SECRET_KEY_OPTION_NAME)
                self.__security_token = config_try_get_option(
                    config,
                    CREDENTIAL_SECTION_NAME,
                    SECURITY_TOKEN_OPTION_NAME)

        except Exception as ex:
            # log error
            # promote user to check config file.
            raise

    def get_access_key(self):
        """
        :return: Access Key
        """
        return self.__ak

    def get_secret_key(self):
        """
        :return: Secret Key
        """
        return self.__sk

    def get_security_token(self):
        """
        :return: security token 
        """
        return self.__security_token

    def set_access_key(self, access_key):
        """
        :param access_key: Set new Access Key
        """
        if access_key != self.__ak:
            self.__ak = access_key
            self.__dirty = True

    def set_secret_key(self, secret_key):
        """
        :param secret_key: Set new Secret Key
        """
        if secret_key != self.__sk:
            self.__sk = secret_key
            self.__dirty = True

    def set_security_token(self, security_token):
        """
        :param secret_key: Set new security token 
        """
        if security_token != self.__security_token:
            self.__security_token = security_token
            self.__dirty = True

    def save(self):
        """
        Save changes into associated file
        """
        if self.__dirty:
            config = SafeConfigParser()
            config.read(self.__config_file_path)

            if not config.has_section(CREDENTIAL_SECTION_NAME):
                config.add_section(CREDENTIAL_SECTION_NAME)

            if self.__ak is not None:
                config.set(CREDENTIAL_SECTION_NAME, ACCESS_KEY_OPTION_NAME, self.__ak)
            if self.__sk is not None:
                config.set(CREDENTIAL_SECTION_NAME, SECRET_KEY_OPTION_NAME, self.__sk)
            if self.__security_token is not None:
                config.set(CREDENTIAL_SECTION_NAME, SECURITY_TOKEN_OPTION_NAME,
                        self.__security_token)

            with open(self.__config_file_path, 'w') as config_file:
                config.write(config_file)

    access_key = property(get_access_key, set_access_key)
    secret_key = property(get_secret_key, set_secret_key)
    security_token = property(get_security_token, set_security_token)


class DefaultCredentialProvider(object):
    """
    Provide default value for credential
    """

    @classmethod
    def get_access_key(cls):
        """
        :return: Default value of Access Key
        """
        return ""

    @classmethod
    def get_secret_key(cls):
        """
        :return: Default value of Secret Key
        """
        return ""

    @classmethod
    def get_security_token(self):
        """
        :return: security token 
        """
        return ""

    access_key = property(get_access_key)
    secret_key = property(get_secret_key)
    security_token = property(get_security_token)


class RaiseExceptionCredentialProvider(object):
    """
    Raise exception when reading any credential configurations
    """

    @classmethod
    def get_access_key(cls):
        """
        :return: Access Key, always raise exception
        """
        raise RuntimeError('There is no Access Key found!')

    @classmethod
    def get_secret_key(cls):
        """
        :return: Secret Key, always raise exception
        """
        raise RuntimeError('There is no Secret Key found!')

    @classmethod
    def get_security_token(self):
        """
        :return: security token 
        """
        raise RuntimeError('There is no security toke found!')

    access_key = property(get_access_key)
    secret_key = property(get_secret_key)
    security_token = property(get_security_token)


class ChainCredentialProvider(object):
    """
    Query credential configurations from a responsible chain.
    """

    def __init__(self, chain):
        self.__chain = chain

    def get_access_key(self):
        """
        :return: Access Key
        """
        for provider in self.__chain:
            if provider.get_access_key() is not None:
                return provider.get_access_key()

        assert False  # Should not get here.

    def get_secret_key(self):
        """
        :return: Secret Key
        """
        for provider in self.__chain:
            if provider.get_secret_key() is not None:
                return provider.get_secret_key()

        assert False  # Should not get here.

    def get_security_token(self):
        """
        :return: security token 
        """
        for provider in self.__chain:
            if provider.get_security_token() is not None:
                return provider.get_security_token()

        assert False  # Should not get here.

    access_key = property(get_access_key)
    secret_key = property(get_secret_key)
    security_token = property(get_security_token)
