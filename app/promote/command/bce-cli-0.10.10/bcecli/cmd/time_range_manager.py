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
#coding=utf-8

"""
This module is be used to parse time ranges.
"""

import re
import datetime
import logging
import time

_logger = logging.getLogger(__name__)

def _add_month(old, month):
    month += (old - 1)
    carry = month / 12
    month = month % 12 + 1
    return carry, month


def _max_day_in_month(year, month):
    '''
    Determines the number of days of a specific month in a specific year.
    '''
    if month in (1, 3, 5, 7, 8, 10, 12):
        return 31
    if month in (4, 6, 9, 11):
        return 30
    if ((year % 400) == 0) or ((year % 100) != 0) and ((year % 4) == 0):
        return 29
    return 28


class Duration(object):
    """
    This class provides a extend function of timedelta, as Duration support add
    and sub years and months.
    """
    def __init__(self, years=0, months=0, weeks=0, days=0, hours=0, minutes=0,
            seconds=0, microseconds=0, milliseconds=0):
        self.years = years
        self.months = months
        self.timedelta_temp = datetime.timedelta(days, seconds, microseconds,
                milliseconds, minutes, hours, weeks)

    def __radd__(self, datetime_input):
        if not isinstance(datetime_input, (datetime.date, datetime.datetime)):
            raise TypeError("Duration must add with datetime")
        carry, new_month = _add_month(datetime_input.month, self.months)
        new_year = datetime_input.year + self.years + carry
        max_day = _max_day_in_month(new_year, new_month)
        if datetime_input.day > max_day: 
            new_day = max_day
        else:
            new_day = datetime_input.day
        datetime_input = datetime_input.replace(year=new_year, month=new_month, day=new_day)
        datetime_input += self.timedelta_temp
        return datetime_input

    def __rsub__(self, datetime_input):
        if not isinstance(datetime_input, (datetime.date, datetime.datetime)):
            raise TypeError("Duration must add with datetime")
        carry, new_month = _add_month(datetime_input.month, -self.months)
        new_year = datetime_input.year - self.years + carry
        max_day = _max_day_in_month(new_year, new_month)
        if datetime_input.day > max_day: 
            new_day = max_day
        else:
            new_day = datetime_input.day
        datetime_input = datetime_input.replace(year=new_year, month=new_month, day=new_day)
        datetime_input -= self.timedelta_temp
        return datetime_input

    def __eq__(self, duration_input):
        if not isinstance(duration_input, Duration):
            return False
        if (self.years * 12 + self.months == \
                duration_input.years * 12 + duration_input.months) and \
                self.timedelta_temp == duration_input.timedelta_temp:
            return True
        else:
            return False

    def __ne__(self, duration_input):
        if not isinstance(duration_input, Duration):
            return True 
        if (self.years * 12 + self.months != duration_input.years * 12 + duration_input.months) \
                or self.timedelta_temp != duration_input.timedelta_temp:
            return True
        else:
            return False


class TimeRangeManager(object):
    """
    this class is used to manage time ranges:
        1. merge time ranges
        2. sort time ranges
        3. check a time whether in these time ranges
    """
    #TODO I don't implement this class, because the amount of time ranges is small
    def __init__(self):
        """
        when is_include is True, execute include time 
        when is_include is False, execute exclude time
        """
        self._is_include = False
        self._time_filter_enable = False
        self._time_ranges = []

    def set_time_filter_is_include(self, is_include):
        """set include or exclude"""
        if isinstance(is_include, bool):
            self._is_include = is_include
        else:
            raise TypeError("is_include must be bool type")

    def _set_enable(self):
        self._time_filter_enable = True

    def is_time_filter_enable(self):
        """check whether enable time filter"""
        return self._time_filter_enable

    def time_range_insert(self, time_range):
        """
        insert time range into manager
        """
        start_time, end_time = self._parse_time_range(time_range)
        self._time_ranges.append([start_time, end_time])
        self._set_enable()

    def time_filter(self, mtime):
        """
        check whether a time in time ranges
        """
        if not isinstance(mtime, int):
            raise TypeError("mtime must be int")
        for time_range in self._time_ranges:
            if mtime >= time_range[0] and mtime <= time_range[1]:
                if self._is_include:
                    return False
                else:
                    return True
        if self._is_include:
            return True
        else:
            return False

    @staticmethod
    def _iso_parse_duration(groups):
        time_now = None
        if groups["now"] is not None:
            time_now = datetime.datetime.now()
            if groups["now"] == 'midnight':
                time_now = (time_now + datetime.timedelta(days = 1)).replace( \
                        hour = 0, minute = 0, second = 0, microsecond = 0)
    
        for k, v in groups.items():
            if k not in ['now', 'separator', 'sign']:
                if v is None:
                    groups[k] = 0
                else:
                    groups[k] = int(v)
        
        duration_temp = Duration(years=groups["years"], months=groups["months"], 
                        weeks=groups["weeks"], days=groups["days"], hours=groups["hours"],
                        minutes=groups["minutes"], seconds=groups["seconds"])
    
        if time_now is None:
            return duration_temp
        elif groups["sign"] == '-':
            time_now -= duration_temp
        else:
            time_now += duration_temp
        return time_now
     
    @staticmethod
    def _iso_parse_utc(utc_strand_str):
        utc_time = datetime.datetime.strptime(utc_strand_str, '%Y-%m-%dT%H:%M:%SZ')
        #TODO utc_local_offset is approximately equal to the diff between utc and local
        utc_local_offset = datetime.datetime.utcnow() - datetime.datetime.now()
        local_time = utc_time - utc_local_offset
        return local_time
    
    @staticmethod
    def _iso_parse_local(local_time_str):
        return datetime.datetime.strptime(local_time_str, '%Y-%m-%d %H:%M:%S')
    
    @staticmethod
    def _iso_parse_timestamp(timestamp_str):
        if isinstance(timestamp_str, str):
            timestamp = int(timestamp_str)
        return datetime.datetime.fromtimestamp(timestamp)
    
    @staticmethod
    def _iso_parse(string):
        """
        parse string to datetime: 
            duration: P1Y3M20DT23H59M22S or PT4H or now-PT4H
            utc standard: 2017-09-26T13:07:10Z
            local time: 2017-09-26 13:07:10
        """
        if not string:
            raise ValueError("time is empty") 
        re_timestamp = re.compile(r"^(?P<stamp>([0-9]{1,20}))$")
    
        #like PnnYnnMnnWnnDTnnHnnMnnS, now+PnnYnnMnnWnnDTnnHnnMnnS
        re_duration = re.compile(r"^(?![+-])(?P<now>now|midnight)?"
                       r"("
                           r"(?P<sign>[+-])?"
                           r"P(?!\b)"
                           r"((?P<years>[0-9]+)Y)?"
                           r"((?P<months>[0-9]+)M)?"
                           r"((?P<weeks>[0-9]+)W)?"
                           r"((?P<days>[0-9]+)D)?"
                           r"("
                               r"(?P<separator>T)"
                               r"((?P<hours>[0-9]+)H)?"
                               r"((?P<minutes>[0-9]+)M)?"
                               r"((?P<seconds>([0-9]+))S)?"
                           r")?"
                       r")?$")
        #like YYYY-mm-DDTHH:MM:SSZ
        re_utc = re.compile(r"((?P<years>[0-9]{4})-)"
                       r"((?P<months>[0-9]{2})-)"
                       r"((?P<days>[0-9]{2}))"
                       r"(?P<separator>[T])"
                       r"((?P<hours>[0-9]{2}):)"
                       r"((?P<minutes>[0-9]{2}):)"
                       r"((?P<seconds>[0-9]{2}))"
                       r"(?P<utc>[z|Z])$")
        #like YYYY-mm-DD HH:MM:SS
        re_local = re.compile(r"((?P<years>[0-9]{4})-)"
                       r"((?P<months>[0-9]{2})-)"
                       r"((?P<days>[0-9]{2}))"
                       r"\s*"
                       r"((?P<hours>[0-9]{2}):)"
                       r"((?P<minutes>[0-9]{2}):)"
                       r"((?P<seconds>[0-9]{2}))$")
    
        re_match = re_timestamp.match(string)
        if re_match:
            return TimeRangeManager._iso_parse_timestamp(string)
        re_match = re_duration.match(string)
        if re_match:
            return TimeRangeManager._iso_parse_duration(re_match.groupdict()) 
        re_match = re_utc.match(string)
        if re_match:
            return TimeRangeManager._iso_parse_utc(string)
        re_match = re_local.match(string)
        if re_match:
            return TimeRangeManager._iso_parse_local(string)
        raise ValueError("invalid time: %s" % (string)) 
    
    @staticmethod
    def _preprocess_time_range(time_range):
        err   = None
        start = None
        end   = None
        if not time_range or len(time_range)<4:
            err = "invalid time-range, the start time and end time can't both be empty"
        elif time_range[0] != '['  or time_range[-1] != ')':
            err = "bad time-range, time range should be wrapped by '[' and ')', " \
                  "such as '[start-time, end-time)'"
        else:
            range_arr = time_range[1:-1].split(",")
            if len(range_arr) != 2:
                err =  "bad time-range, should be '[start-time, end-time)' or '[," \
                    "end-time)' or '[start-time, )'"
            else:
                start = range_arr[0].strip()
                end = range_arr[1].strip()
        return [start, end, err]
    
    @staticmethod
    def _parse_time_range(time_range):
        """
        Parse time range into timestamp
        Time range should be '[start-time, end-time)' or '[ , end-timetime)' or '[start-time, )'"
        """
        start, end, err = \
                TimeRangeManager._preprocess_time_range(time_range.strip())
        if err:
            raise ValueError(err)
        start_timestamp = float("-inf")
        end_timestamp = float("inf")
        start_time = None
        end_time = None
        if start:
            start_time = TimeRangeManager._iso_parse(start)
        if end:
            end_time = TimeRangeManager._iso_parse(end)
        if (not isinstance(start_time, datetime.datetime)) and \
                (not isinstance(end_time, datetime.datetime)):
            raise ValueError("One of start time and end time must be absolute time")
        elif isinstance(start_time, Duration):
            start_time = end_time - start_time
        elif isinstance(end_time, Duration):
            end_time = start_time + end_time
    
        if isinstance(start_time, datetime.datetime):
            start_timestamp = int(time.mktime(start_time.timetuple()))
        if isinstance(end_time, datetime.datetime):
            end_timestamp = int(time.mktime(end_time.timetuple()))
    
        # time range is close-open
        end_timestamp -= 1
        if start_timestamp > end_timestamp:
            raise ValueError("End time must greater than start time !")
    
        _logger.debug("time range: %s %s", start_time, end_time)
        _logger.debug("timestamp range: %s %s", start_timestamp, end_timestamp)
        return start_timestamp, end_timestamp
