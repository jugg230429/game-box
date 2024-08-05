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
# coding=utf-8
"""
This module provides the major operations on VPC.

Authors: BCE VPC
"""

# built-in
import json
import logging

# bce cli
from bcecli.baidubce.auth.bce_credentials import BceCredentials
from bcecli.baidubce.bce_client_configuration import BceClientConfiguration
from bcecli.baidubce.retry_policy import BackOffRetryPolicy
from bcecli.baidubce.services.vpc.vpc_client import VpcClient
from bcecli.cmd import util
from bcecli.cmd import config
from bcecli.lazy_load_proxy import LazyLoadProxy

_logger = logging.getLogger(__name__)


def __build_vpc_client():
    retry_policy = BackOffRetryPolicy(max_error_retry=3)
    bce_client_config = BceClientConfiguration(
        BceCredentials(
            config.credential_provider.access_key,
            config.credential_provider.secret_key),
        config.server_config_provider.domain,
        retry_policy=retry_policy)
    if config.server_config_provider.use_https_protocol == 'yes':
        bce_client_config.set_use_https_protocol()
    return VpcClient(bce_client_config)

vpc_client = LazyLoadProxy(__build_vpc_client)


def create_vpc(args):
    """
    create vpc
    :param args: require arguments
    """
    return util._safe_invoke(lambda: __create_vpc(args))


def list_vpc(args):
    """
    list vps
    :param args: require arguments
    """
    util._safe_invoke(lambda: __list_vpcs(args))


def delete_vpc(args):
    """
    delete specified vpc
    :param args: require arguments
    """
    util._safe_invoke(lambda: __delete_vpc(args))


def show_vpc(args):
    """
    show specified vpc
    :param args: require arguments
    """
    util._safe_invoke(lambda: __show_vpc(args))


def update_vpc(args):
    """
    update specified vpc
    :param args: require arguments
    """
    util._safe_invoke(lambda: __update_vpc(args))


def __list_vpcs(args):
    """
    create vpc
    :param args: require arguments
    """
    response = vpc_client.list_vpcs(marker=args.marker, max_Keys=args.max_keys,
                                    isDefault=args.is_default)
    vpcs = response.vpcs
    vpcs_dict = vars(response)
    vpcs_dict.pop('metadata')
    vpcs_dict['vpcs'] = map(lambda x: vars(x), vpcs)
    print json.dumps(vpcs_dict, indent=4, ensure_ascii=False)


def __create_vpc(args):
    """
    create vpc
    :param args: require arguments
    """
    response = vpc_client.create_vpc(args.vpc_name, args.cidr, args.description)
    vpc_dict = vars(response)
    vpc_dict.pop('metadata')
    print json.dumps(vpc_dict, indent=4, ensure_ascii=False)
    return response.vpc_id


def __delete_vpc(args):
    """
    delete specified vpc
    :param args: require arguments
    """
    response = vpc_client.delete_vpc(args.vpc_id)
    vpc_dict = vars(response)
    vpc_dict.pop('metadata')
    print json.dumps(vpc_dict, indent=4, ensure_ascii=False)


def __show_vpc(args):
    """
    show specified vpc
    :param args: require arguments
    """
    response_vpc = vpc_client.get_vpc(args.vpc_id)
    vpcinfo = response_vpc.vpc
    vpc_subnets = vpcinfo.subnets
    vpc_dict = vars(response_vpc)
    vpc_dict.pop('metadata')
    vpc_dict['vpc'] = vars(vpc_dict['vpc'])
    vpc_dict['vpc']['subnets'] = map(lambda x: vars(x), vpc_subnets)
    print json.dumps(vpc_dict, indent=4, ensure_ascii=False)


def __update_vpc(args):
    """
    update specified vpc
    :param args: require arguments
    """
    response = vpc_client.update_vpc(args.vpc_id, args.vpc_name, args.description)
    vpc_dict = vars(response)
    vpc_dict.pop('metadata')
    print json.dumps(vpc_dict, indent=4, ensure_ascii=False)


def create_subnet(args):
    """
    create subnet
    :param args: require arguments
    """
    return util._safe_invoke(lambda: __create_subnet(args))


def list_subnet(args):
    """
    list vps
    :param args: require arguments
    """
    util._safe_invoke(lambda: __list_subnets(args))


def delete_subnet(args):
    """
    delete specified subnet
    :param args: require arguments
    """
    util._safe_invoke(lambda: __delete_subnet(args))


def show_subnet(args):
    """
    show specified subnet
    :param args: require arguments
    """
    util._safe_invoke(lambda: __show_subnet(args))


def update_subnet(args):
    """
    update specified subnet
    :param args: require arguments
    """
    util._safe_invoke(lambda: __update_subnet(args))


def __list_subnets(args):
    """
    list vps
    :param args: require arguments
    """
    response = vpc_client.list_subnets(args.marker, args.max_keys, args.vpc_id,
                                       args.zone_name, subnet_type=args.subnet_type)
    subnets = response.subnets
    subnets_dict = vars(response)
    subnets_dict.pop('metadata')
    subnets_dict['subnets'] = map(lambda x: vars(x), subnets)
    print json.dumps(subnets_dict, indent=4, ensure_ascii=False)


def __create_subnet(args):
    """
    create subnet
    :param args: require arguments
    """
    response = vpc_client.create_subnet(args.subnet_name, args.zone_name, args.cidr,
                                        args.vpc_id, subnet_type=args.subnet_type,
                                        description=args.description)
    subnet_dict = vars(response)
    subnet_dict.pop('metadata')
    print json.dumps(subnet_dict, indent=4, ensure_ascii=False)
    return response.subnet_id


def __delete_subnet(args):
    """
    delete specified subnet
    :param args: require arguments
    """
    response = vpc_client.delete_subnet(args.subnet_id)
    subnet_dict = vars(response)
    subnet_dict.pop('metadata')
    print json.dumps(subnet_dict, indent=4, ensure_ascii=False)


def __show_subnet(args):
    """
    show specified subnet
    :param args: require arguments
    """
    response = vpc_client.get_subnet(args.subnet_id)
    subnetinfo = response.subnet

    subnet_dict = vars(response)
    subnet_dict.pop('metadata')
    subnet_dict['subnet'] = vars(subnetinfo)
    print json.dumps(subnet_dict, indent=4, ensure_ascii=False)


def __update_subnet(args):
    """
    update specified subnet
    :param args: require arguments
    """
    response = vpc_client.update_subnet(args.subnet_id, args.subnet_name, args.description)
    subnet_dict = vars(response)
    subnet_dict.pop('metadata')
    print json.dumps(subnet_dict, indent=4, ensure_ascii=False)


def create_route_rule(args):
    """
    create route rule
    :param args: require arguments
    """
    return util._safe_invoke(lambda: __create_route(args))


def list_route_rule(args):
    """
    list route rules
    :param args: require arguments
    """
    util._safe_invoke(lambda: __list_routes(args))


def delete_route_rule(args):
    """
    delete specified route
    :param args: require arguments
    """
    util._safe_invoke(lambda: __delete_route(args))


def __list_routes(args):
    """
    list route rules
    :param args: require arguments
    """
    response = vpc_client.get_route(vpc_id=args.vpc_id,
                               route_table_id=args.route_table_id)
    route_rules = response.route_rules
    rules_dict = vars(response)
    rules_dict.pop('metadata')
    rules_dict['route_rules'] = map(lambda x: vars(x), route_rules)
    print json.dumps(rules_dict, indent=4, ensure_ascii=False)


def __create_route(args):
    """
    create route
    :param args: require arguments
    """
    response = vpc_client.create_route(args.route_table_id, args.source_address, args.destination_address,
                                  args.next_hop_type, args.description, next_hop_id=args.next_hop_id)
    subnet_dict = vars(response)
    subnet_dict.pop('metadata')
    print json.dumps(subnet_dict, indent=4, ensure_ascii=False)
    return response.route_rule_id


def __delete_route(args):
    """
    delete specified route rule
    :param args: require arguments
    """
    response = vpc_client.delete_route(args.route_rule_id)
    subnet_dict = vars(response)
    subnet_dict.pop('metadata')
    print json.dumps(subnet_dict, indent=4, ensure_ascii=False)
