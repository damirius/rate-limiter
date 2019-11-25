# Rate Limiter Symfony Bundle
This bundle lets you Rate Limit specific requests. While main usage is for limiting end user access to API endpoints, this bundle gives you access to the service which can limit access to any part of the code.
It can be used in controllers but also any other services if needed. While this service gives you tools to see if given limit was reached it does not provide further logic. Consumer will have to decide what to do and implement necessary functionality.
# Configuration
To enable Rate Limiting for specific request, new service should be registered.
To create a service simply add new configuration options for rate limit bundle in new `config/rate-limiter.yml` file:
``` yaml
# config/rate-limiter.yml
damirius_rate_limiter:
    domains:
        default: # name of the domain
            limit: 10 # Request limit
            period: 60 # Request time window in seconds
            service: App\Service\RateLimiterStorage\YourStorageService # Storage service
```
`limit` (`int`, default: `10`).

`period` (`int`, default: `60`).

`service` (service of `RateLimiterStorageInterface` type). 

If you want to have different limits for different requests/parts of your code you can register multiple services with a different domains.
Different domains don't share their limits between them.
- Note: Domains are unique per storage! Using the same domain with a different storage will behave like a different domain.

If default configuration example was used new limiter service will be available in the service container: `damirius_rate_limiter.limiter.default`.

Since you can have multiple services of the same class we can't rely on type-hinting injection.
Instead in your `config/services.yaml` you can either make a default alias.
``` yaml
# config/services.yaml
# ...
Damirius\RateLimiter\Service\RateLimiter: '@damirius_rate_limiter.limiter.default'
```
Then use different one when needed like this.
``` yaml
# config/services.yaml
# ...
#our other custom app service
App\Service\Custom
    arguments:
        $rateLimiter: '@damirius_rate_limiter.limited.specialdomain'
```

Or you can use quite similar argument binding by name or type.
``` yaml
# config/services.yaml
services:
    _defaults:
        bind:
            Damirius\RateLimiter\Service\RateLimiter: '@damirius_rate_limiter.limiter.default'
# ...
```

Then override specific bind for our custom service or even group of services (i.e controllers).
``` yaml
# config/services.yaml
services:
    # _defaults config as before
    App\Controller\:
        resource: '../src/Controller'
        tags: ['controller.service_arguments']
        bind:
            Damirius\RateLimiter\Service\RateLimiter: '@damirius_rate_limiter.limiter.controller'
# ...
```
For more information about customizing service container and configuration check Service Container link under References.

**Name will always be `damirius_rate_limiter.limiter.DOMAINNAME` where `DOMAINNAME` is the name of the node in the configuration.**
# Usage

Usage is simple, we can call `RateLimiter::checkAndReturn($identifier)`, where only parameter we need to send is unique identifier. 
This can be IP address if we want to limit access by IP, but it can also be anything else like username or some different string.

Method will make necessary steps to store our current call and return number of remaining calls in our window.
If that number is 0 it means that this client hit the rate limit, consumer can then limit further access if needed.

There are couple more publicly exposed methods which can help managing rate limits.

`RateLimiter::getResetTime($identifier)` will get time in seconds till the rate limit resets for specific client identifier.

`RateLimiter::reset($identifier)` will reset any usage for specific client identifier.

# Algorithm
This bundle uses Token Bucket algorithm. It's enough to say that it's simple enough that we can store two values per client for limiting their requests.
One thing that we save is time of last request and other is `allowance` which indirectly represent number of tries left.

# Storage
Storage options can be introduced by implementing `Storage\RateLimiterStorageInterface`.
Storage should be quick and simple since only thing we need to store are key->value pairs.

# References

- http://en.wikipedia.org/wiki/Token_bucket
- https://symfony.com/doc/current/service_container.html
