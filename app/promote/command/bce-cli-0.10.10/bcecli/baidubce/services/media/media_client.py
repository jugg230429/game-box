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
This module provides a client class for Media.
"""

import copy
import json
import logging
from bcecli.baidubce import bce_base_client
from bcecli.baidubce.auth import bce_v1_signer
from bcecli.baidubce.http import bce_http_client
from bcecli.baidubce.http import handler
from bcecli.baidubce.http import http_content_types
from bcecli.baidubce.http import http_headers
from bcecli.baidubce.http import http_methods
from bcecli.baidubce.exception import BceClientError
from bcecli.baidubce.exception import BceServerError
from bcecli.baidubce.utils import required

_logger = logging.getLogger(__name__)


class MediaClient(bce_base_client.BceBaseClient):
    """
    sdk client
    """

    prefix = '/v3'

    def __init__(self, config=None):
        bce_base_client.BceBaseClient.__init__(self, config)

    @required(pipeline_name=(str, unicode))
    def list_jobs(self,
                  pipeline_name,
                  job_status=None,
                  begin=None,
                  end=None,
                  config=None):
        """
        List jobs by pipelineName and jobStatus

        :param pipelineName: The pipeline name
        :type  pipelineName: string or unicode
        :param job_status: The job status, will not filter if None
        :type  job_status: string or unicode
        :param begin: The createTime should be later than or equals with begin, will not check if None
        :type  begin: string or unicode
        :param end: The createTime should be earlier than or equals with end, will not check if None
        :type  end: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if pipeline_name == '':
            raise BceClientError('pipeline_name can\'t be empty string')
        my_params={
                'pipelineName': pipeline_name
        }
        if job_status is not None:
            my_params["jobStatus"] = job_status
        if begin is not None:
            my_params["begin"] = begin
        if end is not None:
            my_params["end"] = end
        return self._send_request(
            http_methods.GET, 
            '/job/transcoding',
            params=my_params,
            config=config)

    @required(pipeline_name=(str, unicode), source=dict, target=dict)
    def create_job(self, pipeline_name, source, target, config=None):
        """
        Create a job

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param source: source
        :type source: array
        :param target: source
        :type target: array
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if pipeline_name == '':
            raise BceClientError('pipeline_name can\'t be empty string')
        return self._send_request(
            http_methods.POST,
            '/job/transcoding',
            body=json.dumps({
                'pipelineName': pipeline_name,
                'source': source,
                'target': target}),
            headers={http_headers.CONTENT_TYPE: http_content_types.JSON},
            config=config)

    @required(job_id=(str, unicode))
    def get_job(self, job_id, config=None):
        """
        Get the specific job information

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if job_id == '':
            raise BceClientError('job_id can\'t be empty string')
        return self._send_request(
            http_methods.GET, 
            '/job/transcoding/' + job_id, 
            config=config)

    @required(bucket=(str, unicode), key=(str, unicode))
    def get_mediainfo_of_file(self, bucket, key, config=None):
        """
        Get the media info of media information

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if bucket == '':
            raise BceClientError('bucket can\'t be empty string')
        if key == '':
            raise BceClientError('key can\'t be empty string')
        return self._send_request(
            http_methods.GET,
            '/mediainfo',
            params={
                'bucket': bucket,
                'key': key},
            config=config)

    def list_pipelines(self, config=None):
        """
        List pipelines

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        return self._send_request(http_methods.GET, '/pipeline', config=config)

    @required(
        pipeline_name=(str, unicode), 
        source_bucket=(str, unicode), 
        target_bucket=(str, unicode))
    def create_pipeline(
            self,
            pipeline_name,
            source_bucket,
            target_bucket,
            description=None,
            pipeline_config=None,
            config=None):
        """
        Create a pipeline

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        body_content = {}
        if description is not None:
            body_content['description'] = description
        else:
            body_content['description'] = ""
        if pipeline_config is not None:
            body_content['config'] = pipeline_config
        else:
            body_content['config'] = {'capacity': 20}
        if pipeline_name == '':
            raise BceClientError('pipeline_name can\'t be empty string')
        if source_bucket == '':
            raise BceClientError('source_bucket can\'t be empty string')
        if target_bucket == '':
            raise BceClientError('target_bucket can\'t be empty string')
        body_content['pipelineName'] = pipeline_name
        body_content['sourceBucket'] = source_bucket
        body_content['targetBucket'] = target_bucket
        return self._send_request(
            http_methods.POST,
            '/pipeline',
            body=json.dumps(body_content),
            headers={http_headers.CONTENT_TYPE: http_content_types.JSON},
            config=config)

    @required(pipeline_name=(str, unicode))
    def get_pipeline(self, pipeline_name, config=None):
        """
        Get the specific pipeline information

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if pipeline_name == '':
            raise BceClientError('pipeline_name can\'t be empty string')
        return self._send_request(http_methods.GET, '/pipeline/' + pipeline_name, config=config)

    @required(pipeline_name=(str, unicode))
    def delete_pipeline(self, pipeline_name, config=None):
        """
        Delete the specific pipeline

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if pipeline_name == '':
            raise BceClientError('pipeline_name can\'t be empty string')
        return self._send_request(http_methods.DELETE, '/pipeline/' + pipeline_name, config=config)

    def list_presets(self, config=None):
        """
        List presets

        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        return self._send_request(http_methods.GET, '/preset', config=config)

    @required(preset_name=(str, unicode), container=(str, unicode))
    def create_preset(
            self,
            preset_name,
            container,
            transmux=None,
            description=None,
            clip=None,
            audio=None,
            video=None,
            encryption=None,
            watermark_id=None,
            config=None):
        """
        Create a preset

        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """

        body_content = dict()
        if preset_name == '':
            raise BceClientError('preset_name can\'t be empty string')
        if container == '':
            raise BceClientError('container can\'t be empty string')
        body_content['presetName'] = preset_name
        body_content['container'] = container
        if description is None:
            body_content['description'] = ''
        else:
            body_content['description'] = description
        if transmux is not None:
            body_content['transmux'] = transmux
        if clip is not None:
            body_content['clip'] = clip
        if audio is not None:
            body_content['audio'] = audio
        if video is not None:
            body_content['video'] = video
        if encryption is not None:
            body_content['encryption'] = encryption
        if watermark_id is not None:
            body_content['watermarkId'] = watermark_id
        return self._send_request(
            http_methods.POST,
            '/preset',
            body=json.dumps(body_content),
            headers={http_headers.CONTENT_TYPE: http_content_types.JSON},
            config=config)

    @required(preset_name=(str, unicode))
    def get_preset(self, preset_name, config=None):
        """
        Get the specific preset information

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if preset_name == '':
            raise BceClientError('preset_name can\'t be empty string')
        return self._send_request(http_methods.GET, '/preset/' + preset_name, config=config)

    @required(preset_name=(str, unicode))
    def delete_preset(self, preset_name, config=None):
        """
        Delete a preset

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if preset_name == '':
            raise BceClientError('preset_name can\'t be empty string')
        return self._send_request(http_methods.DELETE, '/preset/' + preset_name, config=config)

    @required(pipeline_name=(str, unicode), source=dict)
    def create_thumbnail_job(self, pipeline_name, source, target=None, capture=None, config=None):
        """
        Create thumbnail job

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        body_content = {}
        if pipeline_name == '':
            raise BceClientError('pipeline_name can\'t be empty string')
        if target is not None:
            body_content['target'] = target
        if capture is not None:
            body_content['capture'] = capture
        body_content['pipelineName'] = pipeline_name
        body_content['source'] = source
        return self._send_request(
            http_methods.POST,
            '/job/thumbnail',
            body=json.dumps(body_content),
            headers={http_headers.CONTENT_TYPE: http_content_types.JSON},
            config=config)

    @required(job_id=(str, unicode))
    def get_thumbnail_job(self, job_id, config=None):
        """
        Get thumbnail job

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if job_id == '':
            raise BceClientError('job_id can\'t be empty string')
        return self._send_request(http_methods.GET, '/job/thumbnail/' + job_id, config=config)

    @required(pipeline_name=(str, unicode))
    def list_thumbnail_jobs_by_pipeline(self,
                                        pipeline_name,
                                        job_status=None,
                                        begin=None,
                                        end=None,
                                        config=None):
        """
        List thumbnail jobs by pipelineName

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param job_status: The thumbnail job status, will not filter if None
        :type  job_status: string or unicode
        :param begin: The createTime should be later than or equals with begin, will not check if None
        :type  begin: string or unicode
        :param end: The createTime should be earlier than or equals with end, will not check if None
        :type  end: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        
        if pipeline_name == '':
            raise BceClientError('pipeline_name can\'t be empty string')
        my_params={
                'pipelineName': pipeline_name
        }
        if job_status is not None:
            my_params["jobStatus"] = job_status
        if begin is not None:
            my_params["begin"] = begin
        if end is not None:
            my_params["end"] = end
        return self._send_request(
            http_methods.GET,
            '/job/thumbnail',
            params=my_params,
            config=config)

    @required(
        bucket=(str, unicode),
        key=(str, unicode),
        vertical_offset_in_pixel=int,
        hori_offset_in_pixel=int)
    def create_watermark(
            self,
            bucket,
            key,
            vertical_alignment=None,
            horizontal_alignment=None,
            vertical_offset_in_pixel=None,
            hori_offset_in_pixel=None,
            config=None):
        """
        Create watermark

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if bucket == '':
            raise BceClientError('bucket can\'t be empty string')
        if key == '':
            raise BceClientError('key can\'t be empty string')
        body_content = {}
        body_content['bucket'] = bucket
        body_content['key'] = key
        if vertical_alignment is not None:
            body_content['verticalAlignment'] = vertical_alignment
        if horizontal_alignment is not None:
            body_content['horizontalAlignment'] = horizontal_alignment
        if vertical_offset_in_pixel is not None:
            body_content['verticalOffsetInPixel'] = vertical_offset_in_pixel
        if hori_offset_in_pixel is not None:
            body_content['horizontalOffsetInPixel'] = hori_offset_in_pixel
        return self._send_request(
            http_methods.POST,
            '/watermark',
            body=json.dumps(body_content),
            headers={http_headers.CONTENT_TYPE: http_content_types.JSON},
            config=config)

    @required(watermark_id=(str, unicode))
    def get_watermark(self, watermark_id, config=None):
        """
        Get the specific watermark information

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if watermark_id == '':
            raise BceClientError('watermark_id can\'t be empty string')
        return self._send_request(http_methods.GET, '/watermark/' + watermark_id, config=config)

    def list_watermarks(self, config=None):
        """
        List watermarks

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        return self._send_request(http_methods.GET, '/watermark', config=config)

    @required(watermark_id=(str, unicode))
    def delete_watermark(self, watermark_id, config=None):
        """
        Delete the specific watermark

        :param pipelineName: The pipeline name
        :type pipelineName: string or unicode
        :param config: None
        :type config: BceClientConfiguration
        :returns:
        :rtype: bcecli.baidubce.bce_response.BceResponse
        """
        if watermark_id == '':
            raise BceClientError('watermark_id can\'t be empty string')
        return self._send_request(http_methods.DELETE, '/watermark/' + watermark_id, config=config)

    def _get_config_parameter(self, config, attr):
        result = None
        if config is not None:
            result = getattr(config, attr)
        if result is not None:
            return result
        return getattr(self.config, attr)

    @staticmethod
    def _merge_config(self, config):
        if config is None:
            return self.config
        else:
            new_config = copy.copy(self.config)
            new_config.merge_non_none_values(config)
            return new_config

    def _send_request(
            self, http_method, path,
            body=None, headers=None, params=None,
            config=None,
            body_parser=None):
        config = self._merge_config(self, config)
        if body_parser is None:
            body_parser = handler.parse_json

        return bce_http_client.send_request(
            config, bce_v1_signer.sign, [handler.parse_error, body_parser],
            http_method, MediaClient.prefix + path, body, headers, params)
