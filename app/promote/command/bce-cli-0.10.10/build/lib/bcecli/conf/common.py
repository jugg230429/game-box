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
This module provide the common helper functions.

Authors: zhangshuai14
Date:    2015/01/26
"""


def config_try_get_option(config, section_name, option_name):
    """
    :param config: ConfigParser object
    :param section_name: The configuration section name
    :param option_name: The option name
    :return:
    """
    if config.has_option(section_name, option_name):
        return config.get(section_name, option_name)
    else:
        return None
