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
This module provide http request function for bce services.
"""

import logging
import httplib
import sys
import time
import traceback
import urlparse

import bcecli.baidubce
from bcecli.baidubce import utils
from bcecli.baidubce.bce_response import BceResponse
from bcecli.baidubce.exception import BceHttpClientError
from bcecli.baidubce.exception import BceClientError
from bcecli.baidubce.http import http_headers


_logger = logging.getLogger(__name__)


def _get_connection(protocol, host, port, connection_timeout_in_millis):
    """
    :param protocol
    :type protocol: bcecli.baidubce.protocol.Protocol
    :param endpoint
    :type endpoint: str
    :param connection_timeout_in_millis
    :type connection_timeout_in_millis int
    """
    if protocol.name == bcecli.baidubce.protocol.HTTP.name:
        return httplib.HTTPConnection(
            host=host, port=port, timeout=connection_timeout_in_millis / 1000)
    elif protocol.name == bcecli.baidubce.protocol.HTTPS.name:
        return httplib.HTTPSConnection(
            host=host, port=port, timeout=connection_timeout_in_millis / 1000)
    else:
        raise ValueError(
            'Invalid protocol: %s, either HTTP or HTTPS is expected.' % protocol)


def _send_http_request(conn, http_method, uri, headers, body, send_buf_size):
    conn.putrequest(http_method, uri, skip_host=True, skip_accept_encoding=True)

    for k, v in headers.items():
        k = utils.convert_to_standard_string(k)
        v = utils.convert_to_standard_string(v)
        conn.putheader(k, v)
    conn.endheaders()

    if body:
        if isinstance(body, (str, unicode)):
            conn.send(body)
        else:
            total = int(headers[http_headers.CONTENT_LENGTH])
            sent = 0
            while sent < total:
                size = total - sent
                if size > send_buf_size:
                    size = send_buf_size
                buf = body.read(size)
                if not buf:
                    raise BceClientError(
                        'Insufficient data, only %d bytes available while %s is %d' % (
                            sent, http_headers.CONTENT_LENGTH, total))
                conn.send(buf)
                sent += len(buf)

    return conn.getresponse()


def check_headers(headers):
    """
    check value in headers, if \n in value, raise
    :param headers:
    :return:
    """
    for k, v in headers.iteritems():
        if isinstance(v, (str, unicode)) and '\n' in v:
            raise BceClientError(r'There should not be any "\n" in header[%s]:%s' % (k, v))


def send_request(
        config,
        sign_function,
        response_handler_functions,
        http_method, path, body, headers, params):
    """
    Send request to BCE services.

    :param config
    :type config: bcecli.baidubce.BceClientConfiguration

    :param sign_function:

    :param response_handler_functions:
    :type response_handler_functions: list

    :param request:
    :type request: bcecli.baidubce.internal.InternalRequest

    :return:
    :rtype: bcecli.baidubce.BceResponse
    """
    _logger.debug('%s request start: %s %s, %s, %s',
                  http_method, path, headers, params, body)

    headers = headers or {}

    user_agent = 'bce-cli/%s/%s/%s' % (bcecli.CLI_VERSION, sys.version, sys.platform)
    user_agent = user_agent.replace('\n', '')
    headers[http_headers.USER_AGENT] = user_agent
    should_get_new_date = False
    if http_headers.BCE_DATE not in headers:
        should_get_new_date = True
    headers[http_headers.HOST] = config.endpoint

    if isinstance(body, unicode):
        body = body.encode(bcecli.baidubce.DEFAULT_ENCODING)
    if not body:
        headers[http_headers.CONTENT_LENGTH] = 0
    elif isinstance(body, str):
        headers[http_headers.CONTENT_LENGTH] = len(body)
    elif http_headers.CONTENT_LENGTH not in headers:
        raise ValueError('No %s is specified.' % http_headers.CONTENT_LENGTH)

    # store the offset of fp body
    offset = None
    if hasattr(body, "tell") and hasattr(body, "seek"):
        offset = body.tell()

    protocol, host, port = utils.parse_host_port(config.endpoint, config.protocol)

    headers[http_headers.HOST] = host
    if port != config.protocol.default_port:
        headers[http_headers.HOST] += ':' + str(port)
    headers[http_headers.AUTHORIZATION] = sign_function(
        config.credentials, http_method, path, headers, params)

    encoded_params = utils.get_canonical_querystring(params, False)
    if len(encoded_params) > 0:
        uri = path + '?' + encoded_params
    else:
        uri = path
    check_headers(headers)

    retries_attempted = 0
    errors = []
    while True:
        conn = None
        try:
            # restore the offset of fp body when retrying
            if should_get_new_date is True:
                headers[http_headers.BCE_DATE] = utils.get_canonical_time()

            headers[http_headers.AUTHORIZATION] = sign_function(
                config.credentials, http_method, path, headers, params)

            if retries_attempted > 0 and offset is not None:
                body.seek(offset)

            conn = _get_connection(protocol, host, port, config.connection_timeout_in_mills)

            http_response = _send_http_request(
                conn, http_method, uri, headers, body, config.send_buf_size)


            headers_list = http_response.getheaders()
            _logger.debug(
                'request return: status=%d, headers=%s' % (http_response.status, headers_list))

            response = BceResponse()
            response.set_metadata_from_headers(dict(headers_list))

            for handler_function in response_handler_functions:
                if handler_function(http_response, response):
                    break

            return response
        except Exception as e:
            if conn is not None:
                conn.close()

            # insert ">>>>" before all trace back lines and then save it
            errors.append('\n'.join('>>>>' + line for line in traceback.format_exc().splitlines()))

            if config.retry_policy.should_retry(e, retries_attempted):
                delay_in_millis = config.retry_policy.get_delay_before_next_retry_in_millis(
                    e, retries_attempted)
                time.sleep(delay_in_millis / 1000.0)
            else:
                raise BceHttpClientError('Unable to execute HTTP request. Retried %d times. '
                                         'All trace backs:\n%s' % (retries_attempted,
                                                                   '\n'.join(errors)), e)

        retries_attempted += 1
