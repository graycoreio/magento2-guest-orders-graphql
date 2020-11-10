# magento2-guest-orders-graphql

[![Build Status](https://dev.azure.com/graycore/open-source/_apis/build/status/graycoreio.magento2-guest-orders-graphql?branchName=main)](https://dev.azure.com/graycore/open-source/_build/latest?definitionId=17&branchName=main)

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

### Schema

Refer to the [GraphQL schema](etc/schema.graphqls) for documentation about the types available in the queries.
