# magento2-guest-orders-graphql

<div align="center">

[![Packagist Downloads](https://img.shields.io/packagist/dm/graycore/magento2-guest-orders-graphql?color=blue)](https://packagist.org/packages/graycore/magento2-guest-orders-graphql/stats)
[![Packagist Version](https://img.shields.io/packagist/v/graycore/magento2-guest-orders-graphql?color=blue)](https://packagist.org/packages/graycore/magento2-guest-orders-graphql)
[![Packagist License](https://img.shields.io/packagist/l/graycore/magento2-guest-orders-graphql)](https://github.com/graycoreio/magento2-guest-orders-graphql/blob/master/LICENSE)
[![Unit Test](https://github.com/graycoreio/magento2-guest-orders-graphql/actions/workflows/unit.yaml/badge.svg)](https://github.com/graycoreio/magento2-guest-orders-graphql/actions/workflows/unit.yaml)
[![Integration Test](https://github.com/graycoreio/magento2-guest-orders-graphql/actions/workflows/integration.yaml/badge.svg)](https://github.com/graycoreio/magento2-guest-orders-graphql/actions/workflows/integration.yaml)
[![Installation Test](https://github.com/graycoreio/magento2-guest-orders-graphql/actions/workflows/install.yaml/badge.svg)](https://github.com/graycoreio/magento2-cors/actions/workflows/install.yaml)

</div>

A Magento 2 module that adds a GraphQL orders endpoint for guest carts. This closely follows the API for the official customer orders endpoint.

This should only be used for >= Magento 2.4.1. If you need an orders endpoint for < Magento 2.4.1, please use [magento2-orders-graphql](https://github.com/graycoreio/magento2-orders-graphql).

## Installation

```sh
composer require graycore/magento2-guest-orders-graphql
```

## Usage

### Guest Orders

For guest carts, use the `graycoreGuestOrders` query and pass in the cart ID as `cartId`:

```gql
query GetGuestOrders {
  graycoreGuestOrders(cartId: "dsfg67dsfg65sd6fgs8dhffdgs") {
    items {
      id
    }
  }
}
```

> [!IMPORTANT]  
> As of Magento v2.4.7, this package is no longer necessary. However, it is still usable with v2.4.7. It will no longer be usable in v2.4.8. As such, this package is archived.

### Schema

Refer to the [GraphQL schema](etc/schema.graphqls) for documentation about the types available in the queries.
