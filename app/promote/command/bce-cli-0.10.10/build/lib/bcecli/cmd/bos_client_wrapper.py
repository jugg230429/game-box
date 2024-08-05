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
This is a middle module between BOS CMD and BOS client.
"""

import logging
import copy
import traceback
import os

import bcecli.baidubce
from bcecli.baidubce import utils
from bcecli.cmd import config as bceconfig
from bcecli.baidubce.exception import BceClientError
from bcecli.baidubce.exception import BceServerError
from bcecli.baidubce.exception import BceHttpClientError
from bcecli.baidubce.services import bos
from bcecli.baidubce.services.bos.bos_client import BosClient
from bcecli.baidubce.bce_client_configuration import BceClientConfiguration
from bcecli.baidubce.utils import required
from bcecli.baidubce.http import http_methods

_logger = logging.getLogger(__name__)


class BosClientWrapper(BosClient):
    """
    sdk client
    """
    def __init__(self, config=None):
        super(BosClientWrapper, self).__init__(config)

    @required(bucket_name=(str, unicode))
    def _get_region_from_bos(self, bucket_name, config=None):
        """
        get region from bos.
        """
        _logger.debug("_get_region_from_bos bucket_name:%s config is None:%s",
                bucket_name, config is None)
        try:
            region = self.get_bucket_location(bucket_name)
            _logger.debug("region is: %s", region)
            if region is None:
                return False
            bceconfig.bucket_region_cache.write(bucket_name, region)
            domain = bceconfig.server_config_provider.get_domain_by_region(region)
            _logger.debug("domain is: %s", domain)
            #save region and domain
            if config is not None:
                config.endpoint = domain
                config.region = region
            else:
                self.config.endpoint = domain
                self.config.region = region
            return True
        except BceHttpClientError as e:
            if e.last_error and hasattr(e.last_error, 'code') and \
                    e.last_error.code == 'NoSuchBucket':
                return False
            else:
                raise e
        except Exception as e:
            raise e

    @required(bucket_name=(str, unicode))
    def _get_region_from_cache(self, bucket_name, config=None):
        """
        First, getting region from cache.
        If failed, getting region from bos.
        """
        _logger.debug("_get_region_from_cache bucket_name:%s config is None:%s",
                bucket_name, config is None)
        region = bceconfig.bucket_region_cache.get(bucket_name)
        if region is not None:
            domain = bceconfig.server_config_provider.get_domain_by_region(region)
            _logger.debug("_get_region_from_cache region:%s domain:%s", region, domain)
            if config is not None:
                config.endpoint = domain
                config.region = region
            else:
                self.config.endpoint = domain
                self.config.region = region
            return True
        else:
            _logger.debug("can't get region from cache")
            return self._get_region_from_bos(bucket_name, config)

    def _middle_merge_config(self, old_config, config):
        if config is None:
            return old_config
        else:
            new_config = copy.copy(old_config)
            new_config.merge_non_none_values(config)
            return new_config

    @required(bucket_name=(str, unicode))
    def get_bucket_location(self, bucket_name, config=None):
        """
        Get the region which the bucket located in.

        :param bucket_name: the name of bucket
        :type bucket_name: string or unicode
        :param config: None
        :type config: BceClientConfiguration

        :return: region of the bucket
        :rtype: str
        """
        params = {'location': ''}
        #we need to use the _send_request of father class
        #because function get_bucket_location will be used by _get_region_from_bos
        response = super(BosClientWrapper, self)._send_request(http_methods.GET,
                bucket_name, params=params, config=config)
        return response.location_constraint

    @required(bucket_name=(str, unicode))
    def create_bucket(self, bucket_name, region=None, config=None):
        """
        Create bucket with specific name

        :param bucket_name: the name of bucket
        :type bucket_name: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if region is not None:
            #This region maybe don't exist in BOS. However, if the make bucket 
            #operator failed, the change will not save to configuration file.
            domain = bceconfig.server_config_provider.get_domain_by_region(region)
            self.config.endpoint = domain
            self.config.region = region
        else:
            region = bceconfig.server_config_provider.region
        #this bucket has not yet stored in bos.
        #therefore, we need to use father class's _send_request
        ret = super(BosClientWrapper, self)._send_request(http_methods.PUT,
                bucket_name, config=config)
        bceconfig.bucket_region_cache.write(bucket_name, region)
        return ret

    @required(bucket_name=(str, unicode))
    def delete_bucket(self, bucket_name, config=None):
        """
        Delete a Bucket(Must Delete all the Object in Bucket before)

        :type bucket: string
        :param bucket: None
        :return:
            **HttpResponse Class**
        """
        ret =  self._send_request(http_methods.DELETE, 
                bucket_name, config=config)
        bceconfig.bucket_region_cache.delete(bucket_name)
        return ret

    @required(bucket_name=(str, unicode), key=(str, unicode))
    def generate_pre_signed_url(self,
                                bucket_name,
                                key,
                                timestamp=0,
                                expiration_in_seconds=1800,
                                headers=None,
                                params=None,
                                headers_to_sign=None,
                                protocol=None,
                                config=None):
        """
        Get an authorization url with expire time

        :type timestamp: int
        :param timestamp: None

        :type expiration_in_seconds: int
        :param expiration_in_seconds: None

        :type options: dict
        :param options: None

        :return:
            **URL string**
        """
        #before generate url, we need to get the domain of this bucket
        #if the region of this bucket in cache is incorrect, the generated url
        #will incorrect.
        if self._get_region_from_cache(bucket_name):
            return super(BosClientWrapper, self).generate_pre_signed_url(
                    bucket_name,
                    key,
                    timestamp,
                    expiration_in_seconds,
                    headers,
                    params,
                    headers_to_sign,
                    protocol,
                    config)
        else:
            raise Exception('Bucket %s does not exist!' % bucket_name)
 
    def _does_need_retry(self, bucket_name=None, config=None):
        # if user have specified config and have config.endpoint or config.region
        # we don't get the region of bucket and don't retry
        if bucket_name is None:
            return False
        if config is not None and (config.endpoint is not None 
                or  config.region is not None):
            return False
        return True

    @required(bucket_name=(str, unicode))
    def does_bucket_exist(self, bucket_name, config=None):
        """
        Check whether there is a bucket with specific name

        :param bucket_name: None
        :type bucket_name: str
        :return:True or False
        :rtype: bool
        """
        if not self._does_need_retry(bucket_name, config):
            return super(BosClientWrapper, self).does_bucket_exist(bucket_name, config)
        else:
            temp_config = BceClientConfiguration()
            return self._get_region_from_cache(bucket_name, temp_config)

    def _send_request(self, http_method, bucket_name=None, key=None, body=None, 
            headers=None, params=None, config=None, body_parser=None):
        _logger.debug("in  _send_request_wrapper")
        if not self._does_need_retry(bucket_name, config):
            _logger.debug("don't need retry")
            #if bucket name is None or users have specified config, we don't retry
            return super(BosClientWrapper, self)._send_request(http_method, bucket_name, key, 
                    body, headers, params, config, body_parser)
        _logger.debug("need retry")
        #save offset
        temp_offset = None
        if hasattr(body, "tell") and hasattr(body, "seek"):
            temp_offset = body.tell() 
        temp_config = BceClientConfiguration()
        try:
            _logger.debug("retry first")
            if not self._get_region_from_cache(bucket_name, temp_config):
                raise Exception('The specified bucket does not exist: %s' % bucket_name)
            if config is not None:
                temp_config.merge_non_none_values(config)
            _logger.debug("endpoint=>%s", temp_config.endpoint)
            return super(BosClientWrapper, self)._send_request(http_method, bucket_name, key, 
                    body, headers, params, temp_config, body_parser)
        except (BceHttpClientError, BceServerError) as e:
            _logger.debug("retry second")
            if self._get_region_from_bos(bucket_name, temp_config):
                if config is not None:
                    temp_config.merge_non_none_values(config)
                _logger.debug("endpoint=>%s", temp_config.endpoint)
                if temp_offset is not None:
                    body.seek(temp_offset)
                ret = super(BosClientWrapper, self)._send_request(http_method, bucket_name, 
                        key, body, headers, params, temp_config, body_parser)
                return ret
            else:
                raise e
