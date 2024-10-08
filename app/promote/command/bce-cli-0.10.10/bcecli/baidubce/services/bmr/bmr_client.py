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
This module provides a client class for BMR.
"""

import copy
import logging
import json

import bcecli.baidubce
from bcecli.baidubce.auth import bce_v1_signer
from bcecli.baidubce import bce_base_client
from bcecli.baidubce.http import bce_http_client
from bcecli.baidubce.http import handler
from bcecli.baidubce.http import http_content_types
from bcecli.baidubce.http import http_headers
from bcecli.baidubce.http import http_methods
from bcecli.baidubce.utils import required


_logger = logging.getLogger(__name__)


class BmrClient(bce_base_client.BceBaseClient):
    """
    Bmr sdk client
    """

    prefix = '/v1'

    def __init__(self, config=None):
        bce_base_client.BceBaseClient.__init__(self, config)

    @required(image_type=(str, unicode),
              image_version=(str, unicode),
              instance_groups=list)
    def create_cluster(self,
                       image_type,
                       image_version,
                       instance_groups,
                       client_token=None,
                       applications=None,
                       auto_terminate=None,
                       log_uri=None,
                       name=None,
                       steps=None):
        """
        Create cluster

        :param image_type: the type of virtual machine image
        :type image_type: ENUM {'hadoop', 'spark'}

        :param image_version: the version of virtual machine image
        :type image_version: string

        :param instance_groups: instance groups for cluster
        :type instance_groups: array

        :return:
        :rtype bcecli.baidubce.bce_response.BceResponse
        """
        path = '/cluster'
        params = None
        if client_token is not None:
            params = {
                'clientToken': client_token
            }
        body = {
            'imageType': image_type,
            'imageVersion': image_version,
            'instanceGroups': instance_groups
        }
        if applications is not None:
            body['applications'] = applications
        if auto_terminate is not None:
            body['autoTerminate'] = auto_terminate
        if name is not None:
            body['name'] = name
        if log_uri is not None:
            body['logUri'] = log_uri
        if steps is not None:
            body['steps'] = steps

        return self._send_request(http_methods.POST, path, params=params, body=json.dumps(body))

    def list_clusters(self, marker=None, max_keys=None):
        """
        List clusters

        :param marker: 
        :type params: string

        :param max_keys: max records returned.
        :type params: int

        :return:
        :rtype bcecli.baidubce.bce_response.BceResponse 
        """
        path = '/cluster'
        params = None
        if marker is not None or max_keys is not None:
            params = {}
        if marker is not None:
            params['marker'] = marker
        if max_keys is not None:
            params['maxKeys'] = max_keys
        
        return self._send_request(http_methods.GET, path, params=params)

    @required(cluster_id=(str, unicode))
    def get_cluster(self, cluster_id):
        """
        Get cluster

        :param cluster_id: cluster id
        :type cluster_id: string

        :return:
        :rtype bcecli.baidubce.bce_response.BceResponse
        """
        path = '/cluster/%s' % cluster_id
        return self._send_request(http_methods.GET, path)

    @required(cluster_id=(str, unicode))
    def terminate_cluster(self, cluster_id):
        """
        Terminate cluster

        :param cluster_id: cluster id
        :type cluster_id: string

        :return:
        :rtype bcecli.baidubce.bce_response.BceResponse
        """
        path = '/cluster/%s' % cluster_id
        return self._send_request(http_methods.DELETE, path)

    @required(cluster_id=(str, unicode), steps=list)
    def add_steps(self, cluster_id, steps, client_token=None):
        """
        Add steps

        :param cluster_id: cluster id
        :type cluster_id: string

        :param steps: steps to be added
        :type steps: Array

        :return:
        :rtype bcecli.baidubce.bce_response.BceResponse
        """
        path = '/cluster/%s/step' % cluster_id
        params = None
        if client_token is not None:
            params = {
                'clientToken': client_token
            }
        body = json.dumps({'steps': steps})
        return self._send_request(http_methods.POST, path, params=params, body=body)

    @required(cluster_id=(str, unicode))
    def list_steps(self, cluster_id, marker=None, max_keys=None):
        """
        List step

        :param cluster_id: cluster id
        :type cluster_id: string

        :param marker: 
        :type params: string

        :param max_keys: max records returned.
        :type params: int

        :return:
        :rtype bcecli.baidubce.bce_response.BceResponse 
        """
        path = '/cluster/%s/step' % cluster_id
        params = None
        if marker is not None or max_keys is not None:
            params = {}
        if marker is not None:
            params['marker'] = marker
        if max_keys is not None:
            params['maxKeys'] = max_keys

        return self._send_request(http_methods.GET, path, params=params)

    @required(cluster_id=(str, unicode), step_id=(str, unicode))
    def get_step(self, cluster_id, step_id):
        """
        Get step

        :param cluster_id: cluster id
        :type cluster_id: string

        :param step_id: step id
        :type step_id: string

        :return:
        :rtype bcecli.baidubce.bce_response.BceResponse 
        """
        path = '/cluster/%s/step/%s' % (cluster_id, step_id)
        return self._send_request(http_methods.GET, path)

    def _merge_config(self, config=None):
        if config is None:
            return self.config
        else:
            new_config = copy.copy(self.config)
            new_config.merge_non_none_values(config)
            return new_config

    def _send_request(self, http_method, path,
                      body=None, headers=None, params=None,
                      config=None, body_parser=None):
        config = self._merge_config(config)
        if body_parser is None:
            body_parser = handler.parse_json

        return bce_http_client.send_request(
            config, sign_wrapper(['host', 'x-bce-date']), [handler.parse_error, body_parser],
            http_method, BmrClient.prefix + path, body, headers, params)


def sign_wrapper(headers_to_sign):
    """wrapper the bce_v1_signer.sign()."""
    def _wrapper(credentials, http_method, path, headers, params):
        return bce_v1_signer.sign(credentials, http_method, path, headers, params,
                                  headers_to_sign=headers_to_sign)
    return _wrapper


def instance_group(group_type, instance_type, instance_count, name=None):
    """
    Construct instance group

    :param group_type: instance group type
    :type group_type: ENUM {'Master', 'Core'}

    :param instance_type
    :type instance_type: ENUM {'g.small', 'c.large', 'm.medium', 's.medium'}

    :param instance_count
    :type instance_count: int

    :param name: instance group name
    :type name: string
    """
    instance_group = {
        'type': group_type,
        'instanceCount': instance_count,
        'instanceType': instance_type
    }
    if name is not None:
        instance_group['name'] = name

    return instance_group


def application(name, version, properties=None):
    """
    Construct application

    :param name: application type 
    :type name: ENUM {'hive', 'pig', 'hbase', 'hue'}

    :param version: application version
    :type version: string 

    :param properties: application properties 
    :type properties: dict
    """
    application = {
        'name': name,
        'version': version
    }
    if properties is not None:
        application['properties'] = properties
    return application     
       

def step(step_type, action_on_failure, properties, name=None):
    """
    Create step

    :param step_type: the type of step
    :type step_type: Enum {'Java','Streaming','Hive','Pig'}

    :param action_on_failure
    :type actionOnFailure: Enum {'Continue','TerminateCluster','CancelAndWait'}

    :param properties: step properties
    :type properties: map

    :param name: the name of the step
    :type name: string
    """
    step = {
        'actionOnFailure': action_on_failure,
        'type': step_type,
        'properties': properties
    }
    if name is not None:
        step['name'] = name
    return step


def java_step_properties(jar, main_class, arguments=None):
    """
    Create java step properties

    :param jar: the path of .jar file
    :type jar: string

    :param main_class: the package path for main class 
    :type main_class: string

    :param arguments: arguments for the step
    :type arguments: string

    :return:
    :rtype map
    """
    java_step = {
        'jar': jar, 
        'mainClass': main_class
    }
    if arguments is not None:
        java_step['arguments'] = arguments
    return java_step


def streaming_step_properties(input, output, mapper, reducer=None, arguments=None):
    """
    Create streaming step properties

    :param input: the input path of step 
    :type input: string

    :param output: the output path of step 
    :type output: string

    :param mapper: the mapper program of step 
    :type mapper: string

    :param reducer: the reducer program of step 
    :type reducer: string

    :param arguments: arguments for the step
    :type arguments: string

    :return:
    :rtype map
    """
    streaming_step = {
        'mapper': mapper,
        'reducer': '',
        'input': input,
        'output': output
    }
    if reducer is not None:
        streaming_step['reducer'] = reducer
    if arguments is not None:
        streaming_step['arguments'] = arguments

    return streaming_step


def pig_step_properties(script, arguments=None, input=None, output=None):
    """
    Create pig step properties

    :param script: the script path of step
    :type script: string

    :param arguments: arguments for the step
    :type arguments: string

    :param input: the input path of step 
    :type input: string

    :param output: the output path of step 
    :type output: string

    :return:
    :rtype map
    """
    pig_step = {
        'script': script
    }
    if arguments is not None:
        pig_step['arguments'] = arguments
    if input is not None:
        pig_step['input'] = input
    if output is not None:
        pig_step['output'] = output
    return pig_step


def hive_step_properties(script, arguments=None, input=None, output=None):
    """
    Create hive step properties

    :param script: the script path of step
    :type script: string

    :param arguments: arguments for the step
    :type arguments: string

    :param input: the input path of step 
    :type input: string

    :param output: the output path of step 
    :type output: string

    :return:
    :rtype map 
    """
    hive_step = {
        'script': script
    }
    if arguments is not None:
        hive_step['arguments'] = arguments
    if input is not None:
        hive_step['input'] = input
    if output is not None:
        hive_step['output'] = output
    return hive_step
