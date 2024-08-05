"""
Comparator
"""

class Comparator(object):
    """
    Comparator for sync
    """

    def __init__(self,
            sync_info,
            file_at_both_side_sync_strategy,
            file_not_at_src_sync_strategy,
            file_not_at_dst_sync_strategy):
        """
        :sync_info: a dict contains basic sync info for comparison
        :file_at_both_side_sync_strategy: update sync strategy
        :file_not_at_src_sync_strategy: file not at src sync strategy
        :file_not_at_dst_sync_strategy: file not at dst sync strategy
        """
        self.sync_info = sync_info
        self.file_at_both_side_sync_strategy = file_at_both_side_sync_strategy
        self.file_not_at_src_sync_strategy = file_not_at_src_sync_strategy
        self.file_not_at_dst_sync_strategy = file_not_at_dst_sync_strategy

    def compare(self, src_files, dst_files):
        """
        Compares and generates files `FileSyncInfo` to be processed with specific sync strategy
        the generated file includes opration name
        :src_files: src file `FileSyncInfo` generator
        :dst_files: dst file `FileSyncInfo` generator
        """
        src_all_itered = False
        dst_all_itered = False
        take_next_src = True
        take_next_dst = True
        cnt = 0
        while True:
            try:
                if (not src_all_itered) and take_next_src:
                    src = src_files.next()
            except StopIteration as ex:
                src = None
                src_all_itered = True
            try:
                if (not dst_all_itered) and take_next_dst:
                    dst = dst_files.next()
            except StopIteration as ex:
                dst = None
                dst_all_itered = True

            if (not src_all_itered) and (not dst_all_itered):
                file_existence = self.compare_file_existence(src, dst)
                if file_existence == "at_both_side":
                    take_next_src = True
                    take_next_dst = True
                    if self.file_at_both_side_sync_strategy.should_sync(src, dst):
                        src.src_path = src.full_path
                        src.dst_path = dst.full_path
                        src.sync_func = \
                                self.file_at_both_side_sync_strategy.gen_sync_func(src)
                        yield src
                elif file_existence == "not_at_dst":
                    take_next_src = True
                    take_next_dst = False
                    if self.file_not_at_dst_sync_strategy is not None \
                            and self.file_not_at_dst_sync_strategy.should_sync(src, None):
                        src.src_path = src.full_path
                        src.dst_path = self.deduce_dst_full_path(src)
                        src.sync_func = \
                                self.file_not_at_dst_sync_strategy.gen_sync_func(src)
                        yield src
                elif file_existence == "not_at_src":
                    take_next_src = False
                    take_next_dst = True
                    if self.file_not_at_src_sync_strategy is not None \
                            and self.file_not_at_src_sync_strategy.should_sync(None, dst):
                        dst.dst_path = dst.full_path
                        dst.sync_func = \
                            self.file_not_at_src_sync_strategy.gen_sync_func(dst)
                        yield dst
            elif src_all_itered and (not dst_all_itered):
                # if no 'not_at_src' strategy is not specified and all src iterated
                # no need to iterate all dst files
                if self.file_not_at_src_sync_strategy is None:
                    break
                take_next_dst = True
                if self.file_not_at_src_sync_strategy.should_sync(None, dst):
                    dst.dst_path = dst.full_path
                    dst.sync_func = \
                        self.file_not_at_src_sync_strategy.gen_sync_func(dst)
                    yield dst
            elif (not src_all_itered) and dst_all_itered:
                # if no 'not_at_dst' strategy is not specified and all dst iterated
                # no need to iterate all src files
                if self.file_not_at_dst_sync_strategy is None:
                    break
                take_next_src = True
                if self.file_not_at_dst_sync_strategy.should_sync(src, None):
                    src.src_path = src.full_path
                    src.dst_path = self.deduce_dst_full_path(src)
                    src.sync_func = \
                            self.file_not_at_dst_sync_strategy.gen_sync_func(src)
                    yield src
            else:
                break

    def compare_file_existence(self, src, dst):
        """
        Compares src file and dst file to determine file existence
        """
        if src.compare_key == dst.compare_key:
            return "at_both_side"
        elif src.compare_key < dst.compare_key:
            return "not_at_dst"
        else:
            return "not_at_src"

    def deduce_dst_full_path(self, src_file_sync_info):
        """
        Deduces the full path of dst from src file sync info
        """
        path_prefix = self.sync_info["dst_path"]
        if self.sync_info["dst_type"] == "local":
            import os
            path_sep = os.path.sep
        else:
            path_sep = '/'

        while path_prefix.endswith(path_sep):
            path_prefix = path_prefix[:-1]
        return path_prefix + path_sep + src_file_sync_info.name
