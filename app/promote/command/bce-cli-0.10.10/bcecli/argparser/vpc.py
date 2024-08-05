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
This module provide the sub-argparser for VPC.

Authors: BCE VPC
Date:    2017/09/28
"""

import bcecli

from bcecli.cmd import vpc


def __build_list_vpc_parser(parser):
    parser.set_defaults(func=vpc.list_vpc)
    parser.add_argument(
        '-a',
        '--all',
        action='store_true',
        help='list all vpcs.'
    )

    parser.add_argument(
        '-mkr',
        '--marker',
        help='Specify where in the results to begin listing.'
    )

    parser.add_argument(
        '-mky',
        '--max_keys',
        action='store',
        type=int,
        default=1000,
        help='The max number of list result.'
    )

    parser.add_argument(
        '-isd',
        '--is_default',
        action='store',
        choices=['True', 'False'],
        help='The option param demotes whether the vpc is default vpc.'
    )


def __build_create_vpc_parser(parser):
    parser.set_defaults(func=vpc.create_vpc)

    parser.add_argument(
        'vpc_name',
        metavar='NAME',
        help='Name of vpc to create.')

    parser.add_argument(
        'cidr',
        metavar='CIDR',
        help='CIDR of vpc to create.')

    parser.add_argument(
        '-des',
        '--description',
        metavar='DESCRIPTION',
        help='Description of vpc to create.')


def __build_show_vpc_parser(parser):
    parser.set_defaults(func=vpc.show_vpc)

    parser.add_argument(
        'vpc_id',
        metavar='VPC_ID',
        help='ID of vpc to show.')


def __build_delete_vpc_parser(parser):
    parser.set_defaults(func=vpc.delete_vpc)

    parser.add_argument(
        'vpc_id',
        metavar='VPC_ID',
        help='ID of vpc to delete.')


def __build_update_vpc_parser(parser):
    parser.set_defaults(func=vpc.update_vpc)

    parser.add_argument(
        'vpc_id',
        metavar='VPC_ID',
        help='ID of vpc to delete.')

    parser.add_argument(
        'vpc_name',
        metavar='NAME',
        help='Name of vpc to update.')

    parser.add_argument(
        '-des',
        '--description',
        metavar='DESCRIPTION',
        help='Description of vpc to update.')


def __build_list_subnet_parser(parser):
    parser.set_defaults(func=vpc.list_subnet)
    parser.add_argument(
        '-a',
        '--all',
        action='store_true',
        help='list all subnets.'
    )

    parser.add_argument(
        '-vi',
        '--vpc_id',
        help='list all subnets.'
    )

    parser.add_argument(
        '-mkr',
        '--marker',
        help='Specify where in the results to begin listing.'
    )

    parser.add_argument(
        '-mky',
        '--max_keys',
        action='store',
        type=int,
        default=1000,
        help='The max number of list result.'
    )

    parser.add_argument(
        '-zn',
        '--zone_name',
        help='Specify zone name of subnet.ee.g. "cn-gz-a"  "cn-gz-b"'
    )

    parser.add_argument(
        '-st',
        '--subnet_type',
        default='BCC',
        choices=['BCC', 'BBC'],
        help='Specify type of subnet.'
    )


def __build_create_subnet_parser(parser):
    parser.set_defaults(func=vpc.create_subnet)

    parser.add_argument(
        'vpc_id',
        metavar='VPC_ID',
        help='VPC ID or name this subnet belongs to.')

    parser.add_argument(
        'subnet_name',
        metavar='SUBNET_NAME',
        help='Subnet name of subnet to create.')

    parser.add_argument(
        'zone_name',
        metavar='ZONE_NAME',
        help='Zone name of subnet to create. ee.g. "cn-gz-a"  "cn-gz-b"')

    parser.add_argument(
        'cidr',
        metavar='CIDR',
        help='CIDR of subnet to create.')

    parser.add_argument(
        '-st',
        '--subnet_type',
        default='BCC',
        choices=['BCC', 'BBC'],
        metavar='SUBNET_TYPE',
        help='Type of subnet to create. Type:BCC or BBC')

    parser.add_argument(
        '-des',
        '--description',
        metavar='DESCRIPTION',
        help='Description of subnet to create.')


def __build_show_subnet_parser(parser):
    parser.set_defaults(func=vpc.show_subnet)

    parser.add_argument(
        'subnet_id',
        metavar='SUBNET_ID',
        help='ID of subnet to show.')


def __build_delete_subnet_parser(parser):
    parser.set_defaults(func=vpc.delete_subnet)

    parser.add_argument(
        'subnet_id',
        metavar='SUBNET_ID',
        help='ID of subnet to delete.')


def __build_update_subnet_parser(parser):
    parser.set_defaults(func=vpc.update_subnet)

    parser.add_argument(
        'subnet_id',
        metavar='SUBNET_ID',
        help='ID of subnet to delete.')

    parser.add_argument(
        'subnet_name',
        metavar='NAME',
        help='Name of subnet to update.')

    parser.add_argument(
        '-des',
        '--description',
        metavar='DESCRIPTION',
        help='Description of subnet to update.')


def __build_list_route_rule_parser(parser):
    parser.set_defaults(func=vpc.list_route_rule)
    parser.add_argument(
        '-vi',
        '--vpc_id',
        help='list the specific vpc\'s routes.'
    )

    parser.add_argument(
        '-rti',
        '--route_table_id',
        help='list the specific route table\'s id all routes.'
    )


def __build_create_route_rule_parser(parser):
    parser.set_defaults(func=vpc.create_route_rule)

    parser.add_argument(
        'route_table_id',
        metavar='ROUTE_TABLE_ID',
        help='The id of the route table.')

    parser.add_argument(
        'source_address',
        metavar='SOURCE_ADDRESS',
        help='The source address of the route.')

    parser.add_argument(
        'destination_address',
        metavar='DESTINATION_ADDRESS',
        help='The destination address of the route.')

    parser.add_argument(
        'next_hop_type',
        metavar='NEXT_HOP_TYPE',
        choices=['custom', 'vpn', 'nat'],
        help='Route type: {custom, vpn, nat}.')

    parser.add_argument(
        '-nhi',
        '--next_hop_id',
        metavar='NEXT_HOP_ID',
        help='Next hop id of the route.')

    parser.add_argument(
        'description',
        metavar='DESCRIPTION',
        help='Description of route.')


def __build_delete_route_rule_parser(parser):
    parser.set_defaults(func=vpc.delete_route_rule)

    parser.add_argument(
        'route_rule_id',
        metavar='ROUTE_RULE_ID',
        help='ID of  the  specific route rule.')


def build_vpc_parser(parser):
    """
    VPC argparser builder
    """

    vpc_parser_dict = dict()
    vpc_parser_dict["root"] = parser

    sub_parsers = parser.add_subparsers(
        dest='cmd')

    create_vpc_parser = sub_parsers.add_parser(
        'create-vpc',
        help='Create a vpc.'
    )
    vpc_parser_dict["create-vpc"] = create_vpc_parser

    list_vpc_parser = sub_parsers.add_parser(
        'list-vpc',
        help='List vpcs.'
    )
    vpc_parser_dict["list-vpc"] = list_vpc_parser

    delete_vpc_parser = sub_parsers.add_parser(
        'delete-vpc',
        help='Delete a given vpc.'
    )
    vpc_parser_dict["delete-vpc"] = delete_vpc_parser

    show_vpc_parser = sub_parsers.add_parser(
        'show-vpc',
        help='Show information of a given vpc.'
    )
    vpc_parser_dict["show-vpc"] = show_vpc_parser

    update_vpc_parser = sub_parsers.add_parser(
        'update-vpc',
        help='Update vpc\'s information.'
    )
    vpc_parser_dict["update-vpc"] = update_vpc_parser

    create_subnet_parser = sub_parsers.add_parser(
        'create-subnet',
        help='Create a subnet for a given tenant.'
    )
    vpc_parser_dict["create-subnet"] = create_subnet_parser

    list_subnet_parser = sub_parsers.add_parser(
        'list-subnet',
        help='List subnets that belong to a given tenant.'
    )
    vpc_parser_dict["list-subnet"] = list_subnet_parser

    delete_subnet_parser = sub_parsers.add_parser(
        'delete-subnet',
        help='Delete a given subnet.'
    )
    vpc_parser_dict["delete-subnet"] = delete_subnet_parser

    show_subnet_parser = sub_parsers.add_parser(
        'show-subnet',
        help='Show information of a given subnet.'
    )
    vpc_parser_dict["show-subnet"] = show_subnet_parser

    update_subnet_parser = sub_parsers.add_parser(
        'update-subnet',
        help='Update subnet\'s information.'
    )
    vpc_parser_dict["update-subnet"] = update_subnet_parser

    create_route_rule_parser = sub_parsers.add_parser(
        'create-route-rule',
        help='Create a route.'
    )
    vpc_parser_dict["create-route-rule"] = create_route_rule_parser

    list_route_rule_parser = sub_parsers.add_parser(
        'list-route-rule',
        help='List routes.'
    )
    vpc_parser_dict["list-route-rule"] = list_route_rule_parser

    delete_route_rule_parser = sub_parsers.add_parser(
        'delete-route-rule',
        help='Delete a given route.'
    )
    vpc_parser_dict["delete-route-rule"] = delete_route_rule_parser

    __build_list_vpc_parser(list_vpc_parser)
    __build_create_vpc_parser(create_vpc_parser)
    __build_delete_vpc_parser(delete_vpc_parser)
    __build_show_vpc_parser(show_vpc_parser)
    __build_update_vpc_parser(update_vpc_parser)

    __build_list_subnet_parser(list_subnet_parser)
    __build_create_subnet_parser(create_subnet_parser)
    __build_delete_subnet_parser(delete_subnet_parser)
    __build_show_subnet_parser(show_subnet_parser)
    __build_update_subnet_parser(update_subnet_parser)

    __build_list_route_rule_parser(list_route_rule_parser)
    __build_create_route_rule_parser(create_route_rule_parser)
    __build_delete_route_rule_parser(delete_route_rule_parser)
    return vpc_parser_dict
