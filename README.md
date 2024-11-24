# gRPC Protoset Converter

[![Latest Stable Version](https://poser.pugx.org/srwiez/grpc-protoset/v)](https://packagist.org/packages/srwiez/grpc-protoset)
[![Total Downloads](https://poser.pugx.org/srwiez/grpc-protoset/downloads)](https://packagist.org/packages/srwiez/grpc-protoset)
[![License](https://poser.pugx.org/srwiez/grpc-protoset/license)](https://packagist.org/packages/srwiez/grpc-protoset)
[![PHP Version Require](https://poser.pugx.org/srwiez/grpc-protoset/require/php)](https://packagist.org/packages/srwiez/grpc-protoset)
[![GitHub Workflow Status (with event)](https://img.shields.io/github/actions/workflow/status/srwiez/grpc-protoset/test.yml?label=Tests)](https://github.com/srwiez/grpc-protoset/actions/workflows/test.yml)

A simple PHP library to convert a protoset file to proto files.

If you're here, you likely attempted to use a gRPC API and need to generate a PHP client. However, you may be stuck because you have a protoset file instead of a proto file.

This library will assist you in converting the protoset file to proto files.

It works well with gRPC servers that have the reflection service enabled. To learn more, check out the use case example below.

## 🚀 Installation

```bash
composer require srwiez/grpc-protoset
```

## 📚 Usage

Either use the `ProtosetConverter` class directly.
```php
use SRWieZ\GrpcProtoset\ProtosetConverter;

$protoset = new ProtosetConverter();
$protoset->setOutputDir('./proto');
$protoset->convert('starlink.protoset');
```

or use the script provided by the package.
```bash
php cli/converter.php "starlink.protoset" ./proto
```

or if you have installed the package globally, you can use the following command:
```bash
protoset-converter "starlink.protoset" ./proto
```

## 🎁 Use case example (Starlink)
How to generate a PHP client for the Starlink API.

You will need to install the following dependencies:
```bash
brew install protobuf
brew install grpc
brew install grpcurl
```

First, get the protoset file from your Starlink device.
```bash
grpcurl -plaintext -protoset-out "starlink.protoset" "192.168.100.1:9200" describe SpaceX.API.Device.Device
```

Then, convert the protoset file to proto files.
```bash
protoset-converter "starlink.protoset" ./proto
```

Finally, generate the PHP client.
```bash
protoc --php_out=./generated/ proto/spacex/api/device/device.proto
```

Bonus, edit your composer.json file to autoload the generated PHP client.
```json
{
    "autoload": {
        "psr-4": {
          "SpaceX\\API\\": "generated/SpaceX/API",
          "GPBMetadata\\Spacex\\Api\\": "generated/GPBMetadata/Spacex/Api"
        }
    }
}
```


## 📋 TODO
Contributions are welcome!

- Write tests by using the starlink protoset file


## 🤝 Contributing
Clone the project and run `composer update` to install the dependencies.

Before pushing your changes, run `composer qa`. 

This will run [pint](http://github.com/laravel/pint) (code style), [phpstan](http://github.com/phpstan/phpstan) (static analysis), and [pest](http://github.com/pestphp/pest) (tests).

## 👥 Credits

gRPC Protoset Converter was created by Eser DENIZ.

The following projects inspired this library and served as a reference:

- https://github.com/ewilken/starlink-rs
- https://github.com/sparky8512/starlink-grpc-tools/tree/main
- https://github.com/fullstorydev/grpcurl

## 📝 License

gRPC Protoset Converter is licensed under the MIT License. See LICENSE for more information.
