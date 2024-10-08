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
Lazy load proxy component.
http://code.activestate.com/recipes/578014-lazy-load-object-proxying/
"""


class LazyLoadProxy(object):
    """
    Modified from http://code.activestate.com/recipes/496741-object-proxying/
    """
    __slots__ = ["_obj_fn", "__weakref__", "__proxy_storage"]

    def __init__(self, fn, storage=None):
        object.__setattr__(self, "_obj_fn", fn)
        object.__setattr__(self, "__proxy_storage", storage)

    #
    # proxying (special cases)
    #   
    def __getattribute__(self, name):
        return getattr(object.__getattribute__(self, "_obj_fn")(), name)

    def __delattr__(self, name):
        delattr(object.__getattribute__(self, "_obj_fn")(), name)

    def __setattr__(self, name, value):
        setattr(object.__getattribute__(self, "_obj_fn")(), name, value)

    def __getitem__(self, index):
        return object.__getattribute__(self, "_obj_fn")().__getitem__(index)

    def __nonzero__(self):
        return bool(object.__getattribute__(self, "_obj_fn")())

    def __str__(self):
        return str(object.__getattribute__(self, "_obj_fn")())

    def __repr__(self):
        return repr(object.__getattribute__(self, "_obj_fn")())

    def __len__(self):
        return len(object.__getattribute__(self, "_obj_fn")())

    #   
    # factories
    #   
    _special_names = [
        '__abs__', '__add__', '__and__', '__call__', '__cmp__', '__coerce__',
        '__contains__', '__delitem__', '__delslice__', '__div__', '__divmod__',
        '__eq__', '__float__', '__floordiv__', '__ge__',  #'__getitem__',
        '__getslice__', '__gt__', '__hash__', '__hex__', '__iadd__', '__iand__',
        '__idiv__', '__idivmod__', '__ifloordiv__', '__ilshift__', '__imod__',
        '__imul__', '__int__', '__invert__', '__ior__', '__ipow__', '__irshift__',
        '__isub__', '__iter__', '__itruediv__', '__ixor__', '__le__',  #'__len__',
        '__long__', '__lshift__', '__lt__', '__mod__', '__mul__', '__ne__',
        '__neg__', '__oct__', '__or__', '__pos__', '__pow__', '__radd__',
        '__rand__', '__rdiv__', '__rdivmod__', '__reduce__', '__reduce_ex__',
        '__repr__', '__reversed__', '__rfloorfiv__', '__rlshift__', '__rmod__',
        '__rmul__', '__ror__', '__rpow__', '__rrshift__', '__rshift__', '__rsub__',
        '__rtruediv__', '__rxor__', '__setitem__', '__setslice__', '__sub__',
        '__truediv__', '__xor__', 'next',
    ]

    @classmethod
    def _create_class_proxy(cls, theclass):
        """creates a proxy for the given class"""

        def __make_method(name):
            def __method(self, *args, **kw):
                return getattr(object.__getattribute__(self, "_obj_fn")(), name)(*args, **kw)

            return __method

        namespace = {}
        for name in cls._special_names:
            if hasattr(theclass, name):
                namespace[name] = __make_method(name)
        return type("%s(%s)" % (cls.__name__, theclass.__name__), (cls,), namespace)

    def __new__(cls, obj, *args, **kwargs):
        """
        creates an proxy instance referencing `obj`. (obj, *args, **kwargs) are
        passed to this class' __init__, so deriving classes can define an
        __init__ method of their own.
        note: _class_proxy_cache is unique per deriving class (each deriving
        class must hold its own cache)
        """
        try:
            cache = cls.__dict__["_class_proxy_cache"]
        except KeyError:
            cls._class_proxy_cache = cache = {}
        try:
            theclass = cache[obj.__class__]
        except KeyError:
            cache[obj.__class__] = theclass = cls._create_class_proxy(obj.__class__)
        ins = object.__new__(theclass)
        theclass.__init__(ins, obj, *args, **kwargs)
        return ins
