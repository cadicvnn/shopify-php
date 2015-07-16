# shopify-php

[![Latest Stable Version](https://poser.pugx.org/offshoot/shopify-php/v/stable.png)](https://packagist.org/packages/offshoot/shopify-php) [![Total Downloads](https://poser.pugx.org/offshoot/shopify-php/downloads.png)](https://packagist.org/packages/offshoot/shopify-php) [![Latest Unstable Version](https://poser.pugx.org/offshoot/shopify-php/v/unstable.png)](https://packagist.org/packages/offshoot/shopify-php) [![License](https://poser.pugx.org/offshoot/shopify-php/license.png)](https://packagist.org/packages/offshoot/shopify-php)

A simple [Shopify API](http://api.shopify.com/) client in PHP.

The canoncial repository for this stream of development is
[https://github.com/TeamOffshoot/shopify-php](https://github.com/TeamOffshoot/shopify-php)

This API Client is still in a pre-1.0 state, so you can expect:
* some bugs (feel free to submit a pull request with bug fixes and test coverage)
* possibly some breaking API changes between v0.10 and v1.0

## Requirements

* PHP 5.3 (or higher)
* ext-curl, ext-json

## Development Requirements

* phpunit/phpunit 3.7

## Getting Started

Install shopify-php via [Composer](http://getcomposer.org/)

Create a `composer.json` file if you don't already have one in your projects
root directory and require shopify-php:

    {
      "require": {
        "offshoot/shopify-php": "0.10.x"
      }
    }

To learn more about Composer, including the complete installation process,
visit http://getcomposer.org/

### Using cURL

If you're using a cURL based HttpClient like the `CurlHttpClient`, you will want
to include the cacert.pem file that can be found at
[http://curl.haxx.se/docs/caextract.html](http://curl.haxx.se/docs/caextract.html)

You can add this as a dependency in your composer file. Your `composer.json`
might look something like this:

    {
      "require": {
        "offshoot/shopify-php": "0.10.x",
        "haxx-se/curl": "1.0.0"
      },
      "repositories": [
        {
          "type": "package",
          "package": {
            "name": "haxx-se/curl",
            "version": "1.0.0",
            "dist": {
              "url": "http://curl.haxx.se/ca/cacert.pem",
              "type": "file"
            }
          }
        }
      ]
    }

You will be able to find the cacert.pem file in `vendor/haxx-se/curl/cacert.pem`

## Usage

### Authentication

If you do not already have a Shopify API Permanent Access Token, you will need
you authenticate with the Shopify API first

    $pathToCertificateFile = "vendor/haxx-se/curl/cacert.pem";
    $httpClient = new \Shopify\HttpClient\CurlHttpClient($pathToCertificateFile);

    $redirector = new \Shopify\Redirector\HeaderRedirector();

    $authenticate = new \Shopify\Api\AuthenticationGateway(
        $httpClient, $redirector
    );

    $authenticate->forShopName('mycoolshop')
        ->usingClientId('XXX1234567890') // get this from your Shopify Account
        ->withScope(array('write_products', 'read_orders'))
        ->andReturningTo("http://wherever.you/like")
        ->initiateLogin();

This will redirect your user to a Shopify login screen where they will need
to authenticate with their Shopify credentials. After doing that, Shopify will
perform a GET request to your redirect URI, that will look like:

    GET http://wherever.you/like?code=TEMP_TOKEN

Your application will need to capture the `code` query param from the request
and use that to get the permanent access token from Shopify

    $client = new Shopify\Api\Client($httpClient);
    $client->setClientSecret('ABC123XYZ');

    // validate the Shopify Request
    if ($client->isValidRequest($_GET)) {

        // exchange the token
        $permanentAccessToken = $authenticate->forShopName('mycoolshop')
            ->usingClientId('XXX1234567890')
            ->usingClientSecret('ABC123XYZ')
            ->toExchange($_GET['code']);

    }

#### TODO: build request validation into exchange process
#### TODO: have AuthenticationGateway extend Api\Client

### Interacting with the Shopify API

Once you have a valid Shopify Permanent Access Token, you can start making calls
to the Shopify API

First setup an instance of the Shopify API client.

    $client = new \Shopify\Api\Client($httpClient);
    $client->setAccessToken($permanentAccessToken);
    $client->setClientSecret('ABC123XYZ');
    $client->setShopName('mycoolshop');

Then you're ready to start interacting with the Shopify API. Maybe you want to
get all of the products from your store

    $products = $client->get('/admin/products.json', array(
        'collection_id' => '987654321'
    ));

Maybe you want to get the details of a specific order

    $order = $client->get('/admin/orders/123456789.json');

Or maybe you want to create a new Order

    $order = $client->post('/admin/orders.json', array(
        'order' => array(
            'line_items' => array(
                0 => array(
                    'grams' => 1300,
                    'price' => 74.99,
                    'quantity' => 3,
                    'title' => "Big Brown Bear Boots",
                ),
            ),
            'tax_lines' => array(
                0 => array(
                    'price' => 29.25,
                    'rate' => 0.13,
                    'title' => "HST",
                ),
            ),
            'transactions' => array(
                0 => array(
                    'amount' => 254.22,
                    'kind' => "sale",
                    'status' => "success",
                )
            ),
            'total_tax' => 29.25,
            'currency' => "CAD",
        )
    ));

#### TODO: Implement PUT and DELETE functionality

## Contributing

Contributions are welcome. Just fork the repository and send a pull request.
Please be sure to include test coverage with your pull request. You can learn
more about Pull Requests
[here](https://help.github.com/articles/creating-a-pull-request)

In order to run the test suite, ensure that the development dependencies have
been installed via composer. Then from your command line, simple run:

    vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/

## License

This library is released under the
[MIT License](https://github.com/TeamOffshoot/shopify-php/blob/master/LICENSE.txt)

## Acknowledgements

Thanks to [Sandeep Shetty](https://github.com/sandeepshetty/shopify_api) for
his development of the initial code base.
