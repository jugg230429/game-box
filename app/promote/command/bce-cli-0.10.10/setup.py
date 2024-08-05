from distutils.core import setup

setup(
    name='bcecli',
    version='0.10.10',
    packages=['bcecli',
              'bcecli.cmd',
              'bcecli.cmd.bos_impl',
              'bcecli.conf',
              'bcecli.baidubce',
              'bcecli.baidubce.auth',
              'bcecli.baidubce.http',
              'bcecli.baidubce.services',
              'bcecli.baidubce.services.bos',
              'bcecli.baidubce.services.cdn',
              'bcecli.baidubce.services.vpc',
              'bcecli.argparser',
              'progress'],
    scripts=['bce'],
    url='',
    license='',
    author='',
    author_email='',
    description=''
)
