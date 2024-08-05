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
This module provide customized ArgumentParser for BCECLI.

Authors: fuqiang03
Date:    2015/06/08
"""

from argparse import ArgumentParser


class BcecliParser(ArgumentParser):
    """
    Specialized Argument Parser, print help message instead of usage when error.
    """
    def error(self, message):
        """error(message: string)

        Prints a usage message incorporating the message to stderr and
        exits.

        If you override this in a subclass, it should not return -- it
        should either exit or raise an exception.
        """
        import sys as _sys
        from gettext import gettext as _

        print _('%s: error: %s') % (self.prog, message)
        self.print_help(_sys.stderr)
        self.exit(2)
