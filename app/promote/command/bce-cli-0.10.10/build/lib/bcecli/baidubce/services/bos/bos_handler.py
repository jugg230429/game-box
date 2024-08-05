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
This module provides general http handler functions for processing http responses from bos services.
"""

import httplib
import json
from bcecli.baidubce import utils
from bcecli.baidubce.exception import BceServerError
from bcecli.baidubce.http import handler

def parse_copy_object_response(http_response, response):
    """
    response parser for copy object
    """
    TRANSFER_ENCODING = 'transfer-encoding'
    headers_list = {k: v for k, v in http_response.getheaders()}
    if headers_list.get(TRANSFER_ENCODING, 'not exist') == 'chunked':
        body = http_response.read()
        if body:
            d = json.loads(body)
            if 'code' in d:
                raise BceServerError(d['message'], code=d['code'], request_id=d['requestId'])
            else:
                response.__dict__.update(json.loads(body,
                    object_hook=utils.dict_to_python_object).__dict__)
        else:
            raise BceServerError(http_response.reason, request_id=response.metadata.bce_request_id)
        http_response.close()
        return True
    else:
        return handler.parse_json(http_response, response)


def keep_raw_response_body(http_response, response):
    """
    keeps raw http response body
    """
    body = http_response.read()
    http_response.close()
    if body:
        d = json.loads(body)
        if 'requestId' in d and 'code' in d:
            raise BceServerError(d['message'], code=d['code'], request_id=d['requestId'])
        else:
            response.__dict__["body"] = body
    else:
        raise BceServerError(http_response.reason, request_id=response.metadata.bce_request_id)
    return True


def parse_data_process_response(http_response, response):
    """
    response parser for data process
    """
    body = http_response.read()
    http_response.close()
    if body:
        response.__dict__["body"] = body
    else:
        raise BceServerError(http_response.reason, request_id=response.metadata.bce_request_id)
    return True
