#! usr/bin/python
# coding=utf-8

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
This module provides a client class for SMS.
"""

import copy
import logging
import json

from bcecli.baidubce import utils
from bcecli.baidubce.auth import bce_v1_signer
from bcecli.baidubce.bce_base_client import BceBaseClient
from bcecli.baidubce.http import bce_http_client
from bcecli.baidubce.http import handler
from bcecli.baidubce.http import http_headers
from bcecli.baidubce.http import http_methods
from bcecli.baidubce.utils import required
from bcecli.baidubce.services import sms
import httplib
from bcecli.baidubce.exception import BceClientError
from bcecli.baidubce.exception import BceServerError
from bcecli.baidubce.bce_client_configuration import BceClientConfiguration

_logger = logging.getLogger(__name__)

def _parse_result(http_response, response):
    if http_response.status / 100 == httplib.CONTINUE / 100:
        raise BceClientError('Can not handle 1xx http status code')
    bse = None
    body = http_response.read()
    if body:
        d = json.loads(body)
        
        if 'message' in d and 'code' in d and 'requestId' in d:
            bse = BceServerError(d['message'], code=d['code'], request_id=d['requestId'])
        elif http_response.status / 100 == httplib.OK / 100:
            response.__dict__.update(json.loads(body, \
                    object_hook=utils.dict_to_python_object).__dict__)
            http_response.close()
            return True
    elif http_response.status / 100 == httplib.OK / 100:
        return True
    
    if bse is None:
        bse = BceServerError(http_response.reason, request_id=response.metadata.bce_request_id)
    bse.status_code = http_response.status
    raise bse


class SmsClient(BceBaseClient):
    """
    Sms sdk client
    """
    def __init__(self, config=None):
        if config is not None:
            self._check_config_type(config)
        BceBaseClient.__init__(self, config)
    
    @required(config=BceClientConfiguration)
    def _check_config_type(self, config):
        return True            
    
    @required(template_id=(str, unicode), 
	          receiver_list=list, 
			  content_var_dict=dict)
    def send_message(self, template_id, receiver_list, content_var_dict, config=None):
        """
        send a short message to a group of users
        
        :param template_id: template id used to send this message        
        :type template_id: string or unicode
        
        :param receiver_list: receivers to which this message will be send
        :type reciver_list: list
        
        :param content_var_dict: variable values to be replaced
        :type content_var_dict: dict
        
        :param config: None
        :type config: BceClientConfiguration
        
        :return: message result as following format
            {
                "messageId": "123456789abefghiqwertioplkjhgfds",
                "sendStat": {
                                "sendCount":2,
                                "successCount":1,
                                "failList":["13800138001", "13800138000"]
                            }
            }

        :rtype: bcecli.baidubce.bce_response.BceResponse
        """

        data = {
                    'templateId': template_id,
                    'receiver': receiver_list,
                    'contentVar': json.dumps(content_var_dict)
               }
        return self._send_request(http_methods.POST, 'message', \
                                  body=json.dumps(data), config=config)
        
    @required(message_id=(str, unicode))
    def query_message_detail(self, message_id, config=None):
        """
        Get the message detail.

        :param message_id: the id of message to be queried
        :type message_id: string or unicode
        
        :param config: None
        :type config: BceClientConfiguration

        :return: detailed message as following format
            {
                'messageId': '123456789abefghiqwertioplkjhgfds',
                'content': 'this is JDMALL, your code is 123456',
                'receiver': ['13800138000'],
                'sendTime': '2014-06-12T10:08:22Z'
            }
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        
        return self._send_request(http_methods.GET, 'message', message_id, config=config)

    @required(name=(str, unicode), content=(str, unicode))
    def create_template(self, name, content, config=None):
        """
        Create template with specific name and content

        :param name: the name of template
        :type name: string or unicode
        
        :param content: the content of template,such as 'this is ${APP}, your code is ${VID}'
        :type content: string or unicode
        
        :param config: None
        :type config: BceClientConfiguration
        
        :return: create result as following format
            {
                'templateId': 'brn:bce:sms:cn-n1:123456:smsTpl:6nHdNumZ4ZtGaKO'
            }
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        data = {'name': name, 'content': content}
        
        return self._send_request(http_methods.POST, 'template', \
                                  body=json.dumps(data), config=config)

    @required(template_id=(str, unicode))
    def delete_template(self, template_id, config=None):
        """
        delete an existing template by given id

        :param template_id: id of template to be deleted
        :type template_id: string or unicode
        
        :param config: None
        :type config: BceClientConfiguration
        
        :return: None
        """
        self._send_request(http_methods.DELETE, 'template', template_id, config=config)

    @required(template_id=(str, unicode))
    def get_template_detail(self, template_id, config=None):
        """
        get detailed information of template by id

        :param template_id: the template id to be queried
        :type template_id: string or unicode
        
        :param config: None
        :type config: BceClientConfiguration
        
        :return: detailed template  as following format
            {
                'templateId': 'smsTpl:6nHdNumZ4ZtGaKO',
                'name: 'verifyID',
                'content': 'this is ${APP}, your code is ${VID}',
                'status: 'VALID',
                'createTime': '2014-06-12T10:08:22Z',
                'updateTime': '2014-06-12T10:08:22Z'
            }
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        return self._send_request(http_methods.GET, 'template', template_id, config=config)

    def get_template_list(self, config=None):
        """
        query all templates
        
        :param config: None
        :type config: BceClientConfiguration

        :return: template list as following format
            {
                "templateList": {    
                    'templateId': 'smsTpl:6nHdNumZ4ZtGaKO',
                    'name: 'verifyID',
                    'content': 'this is ${APP}, your code is ${VID}',
                    'status: 'VALID',
                    'createTime': '2014-06-12T10:08:22Z',
                    'updateTime': '2014-06-12T10:08:22Z'
                },
                ...
            }
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        return self._send_request(http_methods.GET, 'template', config=config)

    def query_quota(self, config=None):
        """
        query quota information of user
        
        :param config: None
        :type config: BceClientConfiguration
        
        :return: quota information as following format
            {
                'maxSendPerDay': 10000,
                'maxReceivePerPhoneNumberDay': 10,
                'sentToday': 8000
            }
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        return self._send_request(http_methods.GET, 'quota', config=config)
    
    @required(receiver=(str, unicode))
    def stat_receiver(self, receiver, config=None):
        """
        query quota information of receiver
        
        :param receiver: receiver to be queried
        :type receiver: string or unicode
        
        :param config: None
        :type config: BceClientConfiguration
        
        :return: quota information as following format
            {
                'maxReceivePerPhoneNumberDay': 10,
                'receivedToday': 8
            }
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        return self._send_request(http_methods.GET, 'receiver', receiver, config=config)
 
    
    @staticmethod
    def _get_path(config, function_name=None, key=None):
        return utils.append_uri(sms.URL_PREFIX, function_name, key)
    
    @staticmethod
    def _bce_sms_sign(credentials, http_method, path, headers, params,
         timestamp=0, expiration_in_seconds=1800,
         headers_to_sign=None):
        
        headers_to_sign_list = ["host",
                               "content-md5",
                               "content-length",
                               "content-type"]

        if headers_to_sign is None or len(headers_to_sign) == 0:
            headers_to_sign = []
            for k in headers:
                k_lower = k.strip().lower()
                if k_lower.startswith(http_headers.BCE_PREFIX) or k_lower in headers_to_sign_list:
                    headers_to_sign.append(k_lower)
            headers_to_sign.sort()
        else:
            for k in headers:
                k_lower = k.strip().lower()
                if k_lower.startswith(http_headers.BCE_PREFIX):
                    headers_to_sign.append(k_lower)
            headers_to_sign.sort()

        return bce_v1_signer.sign(credentials,
                                  http_method,
                                  path,
                                  headers,
                                  params,
                                  timestamp,
                                  expiration_in_seconds,
                                  headers_to_sign)

    def _merge_config(self, config):
        if config is None:
            return self.config
        else:
            self._check_config_type(config)
            new_config = copy.copy(self.config)
            new_config.merge_non_none_values(config)
            return new_config
        
    def _send_request(
            self, http_method, function_name=None, key=None,
            body=None, headers=None, params=None,
            config=None,
            body_parser=None):
        config = self._merge_config(config)
        path = SmsClient._get_path(config, function_name, key)
        if body_parser is None:
            body_parser = _parse_result
            
        if headers is None:
            headers = {'Accept': '*/*', 'Content-Type': 'application/json;charset=utf-8'}

        return bce_http_client.send_request(
            config, SmsClient._bce_sms_sign, [body_parser],
            http_method, path, body, headers, params)
