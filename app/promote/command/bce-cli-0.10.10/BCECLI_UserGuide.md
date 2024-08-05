# BCE CLI(Command Line Interface) 使用手册

## BCE CLI 介绍

为了方便用户使用百度公有云存储服务的 API，提供了可以通过命令行工具调用 BOS 服务的方式。该工具基于 BCE Python SDK 实现。

## 安装 BCE CLI

### 安装 Python

本命令行工具基于 Python 2.7 开发，根据操作系统安装相应 Python，目前支持 Windows 和 Linux，官网下载链接 [https://www.python.org/downloads/](https://www.python.org/downloads/)

安装完成后将 Python 添加到系统路径中。

### 安装 BCE CLI

将压缩包 bce-cli.zip 解压，在 bce-cli 目录下执行如下命令进行安装：

    python setup.py install
    
为方便使用，可以将 bce-cli 目录添加到系统路径中，就可以在任意目录下执行 bce 命令。

## 使用 BCE CLI

### 配置 BCE CLI

使用 BCE CLI 工具之前，推荐*先设置 Access Key、Secure Key、Region 和 Host。可以通过 -c/--configure 来设置 AK、SK、Region 和 Host 信息，并写入当前用户主目录 ~/.bce 文件夹的credentials和config文件中。

    bce -c

    BOS Access Key ID [None]: Enter Your AK

    BOS Secret Access Key [None]: Enter Your SK

    Default region name [bj]: Enter Your region*

    Default domain [bj.bcebos.com]: Enter Your host*

*即使不设置AK/SK，你也可以通过bcecli访问public权限的bucket

*目前BOS服务只有bj一个Region，指定Region之后你可以不指定Host，让bcecli自动拼接Host为Region.bcebos.com

*如果你指定了Host，那么bcecli不再自动拼接Host，而是访问你指定的Host

### 获得 CLI 帮助信息

可以在任意命令后面添加 `-h` 或 `--help` 来查看该命令的帮助信息。

    bce -h
    bce bos -h
    bce bos ls --help
    
### 命令结构

CLI 使用多层命令结构，所有的命令以 `bce` 开头，`[options]`表示bcecli支持的选项，`<service>` 表示 CLI 所支持的服务，如 BOS 等，每个服务拥有多个特定的子命令。 CLI 一个操作的参数可以以任意顺序给定。

    bce [options] [<service> <command> [parameters]]
    
### 参数赋值

参数的值可以是简单的字符串或数字，如下：

    bce bos cp localfile.txt bos:/mybucket/remotefile.txt
    
如果字符串中包含空格，在 Linux 中需要使用单引号，在 Windows 中需要使用双引号包围。

    bce bos cp 'my object 1' 'bos:/mybucket/my object 1'
    
## 通过 CLI 使用 BOS 服务

### 使用 BOS 服务

BCE CLI 通过 bos 子命令来访问 BOS 服务，管理、操作 Bucket 和 Object。如果 BOS 服务余额不足，再进行操作会提示 Access Denied。

#### 管理 Bucket

1. Make Bucket

        bce bos mb bos:/<bucket-name>
    
2. Remove Bucket

    删除一个 Bucket 要求该 Bucket 为空，也可使用 `-f` 选项强行删除*。

        bce bos rb bos:/<bucket-name>

*注意：强制删除会先删除该bucket下所有的object，再对bucket发起删除操作。
        
3. List Bucket

        bce bos ls bos:/

#### 管理 Object

1. Copy Object

    `cp` 命令支持除了本地到本地之外的对象拷贝。

        bce bos cp <src-path> <dest-path>

2. Remove Object

        bce bos rm <bos-path>

3. List Object

    `ls` 命令可以列出一个 Bucket 内的不超过 1000 条 Objects，可以通过指定 Prefix 来过滤结果。

        bce bos ls bos:/<bucket-name>/[prefix]

## 通过 CLI 使用 CDN 服务

BCE CLI 通过 cdn 子命令来访问 CDN 服务，刷新和预加载缓存

### 刷新缓存

使用`purge`命令提交和查询purge任务，`-h`选项查看帮助

1. 提交purge任务

    使用`--url`或`--directory`选项指定type。

        bce cdn purge --url http://my.domain.com/1.data
        bce cdn purge --directory http://my.domain.com/1/2/

2. 查询purge任务状态

    使用`--query`选项查询purge任务的状态。

        bce cdn purge --query purge-id

### 预加载缓存

使用`prefetch`命令来提交和查询prefetch任务，`-h`选项查看帮助

1. 提交prefetch任务
    
    使用`--url`、`--bos`、`--file`、`--domain`选项指定导入url的方式

        bce cdn prefetch --url http://my.domain.com/1.data

2. 查询prefetch任务状态 

    使用`--query`选项查询prefetch任务的状态。

        bce cdn prefetch --query prefetch-id

## CLI 命令详解

    bce [options] [<service> <subcommand> [parameters]]

### Options

#### 显示帮助 -h/--help

#### 查看、设置AK、SK等配置信息 -c/--configure

#### 查看debug信息 -d/--debug

#### 查看版本信息 -v/--version

### bos

    bce bos <command> [parameters] <args>
    
可用命令：

- ls
- mb
- rb
- cp
- rm

#### ls

列出 Bucket 或 Object或PRE（目录）

    bce bos ls bos:/
    bce bos ls bos:/<bucket-name>
    bce bos ls bos:/<bucket-name>/<prefix>

注意如果有超过 1000 个 Object或者PRE 需要显示，则只会显示前 1000 个。如果需要显示全部的内容，需要配合 `-a, --all` 选项。
    
选项：

- -a, --all: 即便有超过 1000 个 Object 也全部显示出来。

- -r, --recursive: 不显示目录，直接显示下面的Object。

- -s, --summerize: 显示个数以及总大小等统计信息。

#### mb

创建一个 Bucket

    bce bos mb bos:/<bucket-name>

#### rb

删除一个 Bucket，要求 Bucket 内无 Object

    bce bos rb [-y, --yes] [-f, --force] bos:/<bucket-name>
    
选项：

- -y, --yes: 使用该选项时跳过确认步骤
- -f, --force: 使用该选项时，即使 Bucket 不为空也可以将该 Bucket 及其内所有 Objects 一起删除

#### cp

复制 Bucket 或 Object

    bce bos cp <src-path> <dest-path>
    
#### rm

删除 Object

    bce bos rm [-y] bos:/<bucket-name>/<object-key>
    
选项：

- -y, --yes: 使用该选项时掉过确认步骤
