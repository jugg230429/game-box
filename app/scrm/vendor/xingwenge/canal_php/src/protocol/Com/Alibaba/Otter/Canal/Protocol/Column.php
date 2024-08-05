<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: EntryProtocol.proto

namespace Com\Alibaba\Otter\Canal\Protocol;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 **每个字段的数据结构*
 *
 * Generated from protobuf message <code>com.alibaba.otter.canal.protocol.Column</code>
 */
final class Column extends \Google\Protobuf\Internal\Message
{
    /**
     **字段下标*
     *
     * Generated from protobuf field <code>int32 index = 1;</code>
     */
    private $index = 0;
    /**
     **字段java中类型*
     *
     * Generated from protobuf field <code>int32 sqlType = 2;</code>
     */
    private $sqlType = 0;
    /**
     **字段名称(忽略大小写)，在mysql中是没有的*
     *
     * Generated from protobuf field <code>string name = 3;</code>
     */
    private $name = '';
    /**
     **是否是主键*
     *
     * Generated from protobuf field <code>bool isKey = 4;</code>
     */
    private $isKey = false;
    /**
     **如果EventType=UPDATE,用于标识这个字段值是否有修改*
     *
     * Generated from protobuf field <code>bool updated = 5;</code>
     */
    private $updated = false;
    /**
     **预留扩展*
     *
     * Generated from protobuf field <code>repeated .com.alibaba.otter.canal.protocol.Pair props = 7;</code>
     */
    private $props;
    /**
     ** 字段值,timestamp,Datetime是一个时间格式的文本 *
     *
     * Generated from protobuf field <code>string value = 8;</code>
     */
    private $value = '';
    /**
     ** 对应数据对象原始长度 *
     *
     * Generated from protobuf field <code>int32 length = 9;</code>
     */
    private $length = 0;
    /**
     **字段mysql类型*
     *
     * Generated from protobuf field <code>string mysqlType = 10;</code>
     */
    private $mysqlType = '';
    protected $isNull_present;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $index
     *          *字段下标*
     *     @type int $sqlType
     *          *字段java中类型*
     *     @type string $name
     *          *字段名称(忽略大小写)，在mysql中是没有的*
     *     @type bool $isKey
     *          *是否是主键*
     *     @type bool $updated
     *          *如果EventType=UPDATE,用于标识这个字段值是否有修改*
     *     @type bool $isNull
     *     @type \Com\Alibaba\Otter\Canal\Protocol\Pair[]|\Google\Protobuf\Internal\RepeatedField $props
     *          *预留扩展*
     *     @type string $value
     *          * 字段值,timestamp,Datetime是一个时间格式的文本 *
     *     @type int $length
     *          * 对应数据对象原始长度 *
     *     @type string $mysqlType
     *          *字段mysql类型*
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\EntryProtocol::initOnce();
        parent::__construct($data);
    }

    /**
     **字段下标*
     *
     * Generated from protobuf field <code>int32 index = 1;</code>
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     **字段下标*
     *
     * Generated from protobuf field <code>int32 index = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setIndex($var)
    {
        GPBUtil::checkInt32($var);
        $this->index = $var;

        return $this;
    }

    /**
     **字段java中类型*
     *
     * Generated from protobuf field <code>int32 sqlType = 2;</code>
     * @return int
     */
    public function getSqlType()
    {
        return $this->sqlType;
    }

    /**
     **字段java中类型*
     *
     * Generated from protobuf field <code>int32 sqlType = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setSqlType($var)
    {
        GPBUtil::checkInt32($var);
        $this->sqlType = $var;

        return $this;
    }

    /**
     **字段名称(忽略大小写)，在mysql中是没有的*
     *
     * Generated from protobuf field <code>string name = 3;</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     **字段名称(忽略大小写)，在mysql中是没有的*
     *
     * Generated from protobuf field <code>string name = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, True);
        $this->name = $var;

        return $this;
    }

    /**
     **是否是主键*
     *
     * Generated from protobuf field <code>bool isKey = 4;</code>
     * @return bool
     */
    public function getIsKey()
    {
        return $this->isKey;
    }

    /**
     **是否是主键*
     *
     * Generated from protobuf field <code>bool isKey = 4;</code>
     * @param bool $var
     * @return $this
     */
    public function setIsKey($var)
    {
        GPBUtil::checkBool($var);
        $this->isKey = $var;

        return $this;
    }

    /**
     **如果EventType=UPDATE,用于标识这个字段值是否有修改*
     *
     * Generated from protobuf field <code>bool updated = 5;</code>
     * @return bool
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     **如果EventType=UPDATE,用于标识这个字段值是否有修改*
     *
     * Generated from protobuf field <code>bool updated = 5;</code>
     * @param bool $var
     * @return $this
     */
    public function setUpdated($var)
    {
        GPBUtil::checkBool($var);
        $this->updated = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool isNull = 6;</code>
     * @return bool
     */
    public function getIsNull()
    {
        return $this->readOneof(6);
    }

    /**
     * Generated from protobuf field <code>bool isNull = 6;</code>
     * @param bool $var
     * @return $this
     */
    public function setIsNull($var)
    {
        GPBUtil::checkBool($var);
        $this->writeOneof(6, $var);

        return $this;
    }

    /**
     **预留扩展*
     *
     * Generated from protobuf field <code>repeated .com.alibaba.otter.canal.protocol.Pair props = 7;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getProps()
    {
        return $this->props;
    }

    /**
     **预留扩展*
     *
     * Generated from protobuf field <code>repeated .com.alibaba.otter.canal.protocol.Pair props = 7;</code>
     * @param \Com\Alibaba\Otter\Canal\Protocol\Pair[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setProps($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Com\Alibaba\Otter\Canal\Protocol\Pair::class);
        $this->props = $arr;

        return $this;
    }

    /**
     ** 字段值,timestamp,Datetime是一个时间格式的文本 *
     *
     * Generated from protobuf field <code>string value = 8;</code>
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     ** 字段值,timestamp,Datetime是一个时间格式的文本 *
     *
     * Generated from protobuf field <code>string value = 8;</code>
     * @param string $var
     * @return $this
     */
    public function setValue($var)
    {
        GPBUtil::checkString($var, True);
        $this->value = $var;

        return $this;
    }

    /**
     ** 对应数据对象原始长度 *
     *
     * Generated from protobuf field <code>int32 length = 9;</code>
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     ** 对应数据对象原始长度 *
     *
     * Generated from protobuf field <code>int32 length = 9;</code>
     * @param int $var
     * @return $this
     */
    public function setLength($var)
    {
        GPBUtil::checkInt32($var);
        $this->length = $var;

        return $this;
    }

    /**
     **字段mysql类型*
     *
     * Generated from protobuf field <code>string mysqlType = 10;</code>
     * @return string
     */
    public function getMysqlType()
    {
        return $this->mysqlType;
    }

    /**
     **字段mysql类型*
     *
     * Generated from protobuf field <code>string mysqlType = 10;</code>
     * @param string $var
     * @return $this
     */
    public function setMysqlType($var)
    {
        GPBUtil::checkString($var, True);
        $this->mysqlType = $var;

        return $this;
    }

    /**
     * @return string
     */
    public function getIsNullPresent()
    {
        return $this->whichOneof("isNull_present");
    }

}

