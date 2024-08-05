# Copyright (c) 2014 Baidu.com, Inc. All Rights Reserved
#
# Licensed under the Apache License, Version 2.0 (the "License"); you may not
# use this file
# except in compliance with the License. You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the
# License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS
# OF ANY KIND,
# either express or implied. See the License for the specific language governing
# permissions
# and limitations under the License.
#coding=utf-8
"""
This module provide cache for bucket_name=>region.
"""

import os
import time
from ConfigParser import SafeConfigParser

CACHE_SECTION_NAME = "caches"


class BucketToRegionCache(object):
    """
    Read the region of buckets from cache.
    Write the region of buckets to cache.
    """

    def __init__(self, cache_file_path):
        self.__buckets = {}
        self.__cache_file_path = cache_file_path
        self.__dirty = False
        
        try:
            if os.path.exists(self.__cache_file_path):
                cache = SafeConfigParser()
                cache.read(cache_file_path)
                self.__buckets = self.__try_to_get_buckets(cache,
                    CACHE_SECTION_NAME)
            else:
                self.__init_cache()
        except Exception as e:
            raise e

    def __init_cache(self):
        pass

    def __try_to_get_buckets(self, cache, section_name):
        """
        Get region from cache file.
        Remove overdue bucket.
        """
        dicts = {}
        if cache.has_section(section_name):
            buckets_t = cache.items(section_name) 
            for k, v in buckets_t:
                region, expire = self.__split_region_and_expire(v)
                if region and expire and not self.__check_expire_second(expire):
                    dicts[k] = v
                else:
                    self.__dirty = True
        return dicts

    def __split_region_and_expire(self, value):
        if value is None:
            return None, None
        value = value.strip()
        values = value.split("|")
        if len(values) != 2:
            return None, None
        return values[0], int(values[1])

    def __check_expire_second(self, expire_time):
        if expire_time > int(time.time()):
            return False
        else:
            return True

    def get(self, bucket_name):
        """
        return: the region of bucket.
        """
        if self.__buckets and (bucket_name in self.__buckets):
            region, expire = self.__split_region_and_expire(
                self.__buckets[bucket_name])
            if region and expire and not self.__check_expire_second(expire):
                return region
        return None

    def save(self):
        """
        save cache into file
        """
        if self.__dirty:
            cache = SafeConfigParser()
            cache.read(self.__cache_file_path)
            if cache.has_section(CACHE_SECTION_NAME):
                cache.remove_section(CACHE_SECTION_NAME)
            cache.add_section(CACHE_SECTION_NAME)
            if self.__buckets:
                for k, v in self.__buckets.iteritems():
                    cache.set(CACHE_SECTION_NAME, k, v)
            with open(self.__cache_file_path, 'w') as cache_file:
                cache.write(cache_file)
            self.__dirty = False

    def write(self, bucket_name, region, expire=3600):
        """
        Write bucket info into cache.
        The default expire time is one hour
        """
        if not bucket_name or not region:
            return False
        values = region + "|" + str(int(time.time())+expire)
        self.__buckets[bucket_name] = values
        self.__dirty = True
        return True

    def delete(self, bucket_name):
        """
        In cache: delete the region of bucket_name
        """
        if self.__buckets and (bucket_name in self.__buckets):
            del self.__buckets[bucket_name]
            self.__dirty = True

