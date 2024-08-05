
"""
Sync info structure

Authors: BCE BOS
"""

class FileSyncInfo(object):
    """
    Descriptor which describes files to sync
    """
    def __init__(self, name=None, full_path=None, size=-1, last_modified=None,
            file_type=None, compare_key=None, sync_func=None):
        self.name = name
        self.size = size
        self.last_modified = last_modified
        self.file_type = file_type
        self.full_path = full_path
        self.dst_path = None
        self.src_path = None
        self.compare_key = self.name if compare_key is None else compare_key
        self.sync_func = sync_func

    def __repr__(self):
        return str(self.__dict__)
