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
This module record breakpoint info for multi task.

Authors: fengjingchao
Date:    2015/12/09
"""
import logging
import os
import math
import json
import time
import fnmatch
from os import path
from hashlib import md5
from bcecli.cmd import config

_logger = logging.getLogger(__name__)

class RecordBreakPoint(object):
    """
    Record breakpoint.
    """
    @staticmethod
    def init(context, file_path, bucket_name, key, restart, file_size=None,
            file_mtime=None):
        """ Check whether breakpoint file legal."""

        RecordBreakPoint().get_file_info(context, file_path, bucket_name, key,
                file_size, file_mtime)
        if os.sep in file_path:
            file_path = file_path.split(os.sep)[-1]
        if '/' in file_path:
            file_path = file_path.split('/')[-1]
        context._info_name = md5(file_path).hexdigest() \
                + "_" + str(hash(bucket_name + "/" + key))
        if os.path.exists(config.multiupload_folder):
            # remove expired breakpoint folder
            folderList = os.listdir(config.multiupload_folder)
            localtime = time.time()
            for f in folderList:
                if f.isdigit():
                    try:
                        timeArray = time.strptime(f, "%Y%m%d")
                        filetime = int(time.mktime(timeArray))
                        if int(config.server_config_provider.breakpoint_file_expiration) < 0 and \
                            int(config.server_config_provider.breakpoint_file_expiration) is not -1:
                            print "Check your breakpoint_file_expiration config:" + \
                                str(config.server_config_provider.breakpoint_file_expiration)
                            sys.exit()
                        if int(localtime) - int(filetime) > \
                            int(config.server_config_provider.breakpoint_file_expiration)*86400 \
                            and \
                            int(config.server_config_provider.breakpoint_file_expiration) is not -1:
                            __import__('shutil').rmtree(config.multiupload_folder + f)
                    except Exception as e:
                        print "Check your breakpoint_file_expiration config"
                        raise

            for p in RecordBreakPoint().iterfindfiles(config.multiupload_folder,
                                                      context._info_name):
                context._info_path = p
            if os.path.exists(context._info_path):
                if restart is True:
                    RecordBreakPoint.remove_file(context)
                    return 
                else:
                    try:
                        json_str = json.load(file(context._info_path))
                    except Exception as e:
                        _logger.error("Error openning file: %d,"
                            "error: %s"
                            % (str(context._info_path), str(e.message)))
                        RecordBreakPoint.remove_file(context)   
                        raise
                    if context._md5 == json_str['md5'] and \
                        context._file_size == json_str['file_size'] and \
                        context._mtime == json_str['file_modify_time'] and \
                        context.check_state_cancelled() is False:

                        sum_list=len(json_str['complete_part_list']) + \
                                len(json_str['pending_part_list'])

                        if sum_list == json_str['parts_num'] and \
                                len(json_str['pending_part_list'])>0:
                            print "Contiune multi-upload task from breakpoint file"
                            # read breakpoint info
                            context._state_breakpoint = True
                            context._upload_id = json_str['upload_id']
                            context._completed_parts_list = json_str['complete_part_list']
                            context._pending_parts_list = json_str['pending_part_list']
                            return
            RecordBreakPoint.remove_file(context)
        else:
            os.makedirs(config.multiupload_folder)            

    def iterfindfiles(self, path, fnexp):
        """Find breakpoint file."""
        for root, dirs, files in os.walk(path):
            for filename in fnmatch.filter(files, fnexp):
                yield os.path.join(root, filename)

    def get_file_info(self, context, file_path, bucket_name, key, 
            file_size=None, file_mtime=None): 
        """
        Get the upload file information.
        If file_path is a file on BOS, file_size and file_mtime must not be
        None. 
        """
        from bcecli.cmd import task 

        parts_info = task.PartsInfo(file_path)
        parts_info.init(file_size = file_size) 
        context._parts_all_list = parts_info.get_part()
        if file_size is None:
            context._file_size = path.getsize(file_path)
        else:
            context._file_size = file_size
        if file_mtime is None:
            context._mtime = os.path.getmtime(file_path) 
        else:
            context._mtime = file_mtime
        context._parts_num = int(math.ceil(float(context._file_size)/
                    parts_info._get_proper_part_size(context._file_size)))

    @staticmethod    
    def calculate_md5(context, file_path):
        """Calculate file MD5."""
        with open(file_path, 'rb') as f:
            m = md5()
            f.seek(-1024 * 1024, 2)
            m.update(f.read())
            context._md5 = m.hexdigest()

    @staticmethod
    def calculate_md5_of_remote_file(context, file_context):
        """Calculate the MD5 of remote file"""
        if file_context:
            m = md5()
            m.update(file_context)
            context._md5 = m.hexdigest()

    @staticmethod        
    def create_file(context):
        """Create breakPoint upload file."""
        s = set(part_completed['partNumber'] for part_completed in context._completed_parts_list) 
        _fail_parts_list=[part for part in context._parts_all_list if part['part_number'] not in s]
        if len(_fail_parts_list) > 0:
            # init breakpoint info
            bp_info={'file_modify_time':context._mtime,
                'file_size':context._file_size,
                'parts_num':context._parts_num,
                'upload_id':context._upload_id,
                'md5':context._md5,
                'pending_part_list': _fail_parts_list,
                'complete_part_list': context._completed_parts_list
                }
            if os.path.exists(context._info_path):
                RecordBreakPoint.remove_file(context)
            # create breakpoint file
            try:
                folder = time.strftime('%Y%m%d', time.localtime(time.time()))
                folderList = os.listdir(config.multiupload_folder)
                for f in folderList:
                    if folder == f:
                        json.dump(bp_info, open(config.multiupload_folder + folder +
                                                os.sep + context._info_name, 'w+'))
                        # /home/work/.bce/multiupload_infos/ak/
                        break
                else:
                    os.mkdir(config.multiupload_folder + folder)
                    json.dump(bp_info, open(config.multiupload_folder + folder +
                                            os.sep + context._info_name, 'w+'))
                    
            except Exception as e:
                _logger.error("Error writing file: %s,"
                        "error: %s"
                        % (str(config.multiupload_folder + context._info_name), str(e)))
                raise

        elif len(_fail_parts_list) == 0 and len(context._completed_parts_list) == 0:
            return
        elif len(context._completed_parts_list) == context._parts_num:
            print "\nBreakpoint upload has completed."
            RecordBreakPoint.remove_file(context)

    @staticmethod
    def remove_file(context):
        """Remove breakPoint file."""
        if os.path.exists(context._info_path):
            os.remove(context._info_path)  

    @staticmethod
    def get_index(context):
        """Get breakPoint index."""
        if os.path.exists(context._info_path):
            try:
                json_str = json.load(file(context._info_path))
                return len(json_str['complete_part_list'])
            except Exception as e:
                _logger.error("Error openning file: %s,"
                    "error: %s" 
                    % (str(context._info_path), str(e.message)))
                RecordBreakPoint.remove_file(context) 
                raise
        else:
            return 0

