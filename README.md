# RateLimitBundle
This bundle lets you Rate Limit specific requests.
# Configuration
To enable Rate Limiting for specific request, new service should be registered.
To create a service simply add new configuration options for rate limit bundle in your `app/config/config.yml` file:
```
damirius_rate_limiter:
    domains:
        default: # name of the domain
            limit: 10 # Request limit
            period: 60 # Request time window in seconds
            service: '@yourservice.storage.redis' # Storage service
```
First argument is request limit (`int`, default: `10`).
Second is time window in seconds (`int`, default: `60`).
Third is storage service (`string` id of service of type `RateLimiterStorageInterface`). 
Bundle supports Redis storage, but there is a possibility of creating new one.
If you want to have different limits for different requests/parts of your code you can register multiple services with different domains.
Also different domains don't share their limits between them. 

So if you register service with domain name `'api1'` with limit `5` and time period `10`.
Making requests to that domain will not use up rates of other domains.
- Note: Domains are unique per storage! Using the same domain with different storages will behave like different domains.

If you configured this properly new limiter service will be available in the service container: `damirius_rate_limiter.limiter.default`.
**So the name will always be `damirius_rate_limiter.limiter.DOMAINNAME` where `DOMAINNAME` is the name of the node in the configuration.**
# Usage
When service is registered, we can inject it wherever we want to use it, or just fetch from service container in Controllers or other parts of the code that have access to the container.

Usage is simple, we can call `RateLimiter::checkAndReturn($identifier)`, where only parameter we need to send is unique identifier. 
This can be IP address if we want to limit access by IP, but it can also be anything else like username or some different string.

Method will make necessary steps to store our call and return number of remaining calls in our window.
If that number is 0 it means that this client hit the rate limit, consumer can then limit further access if needed.

There are couple more publicly exposed methods which can help managing rate limits.

`RateLimiterer::getResetTime($identifier)` will get time in seconds till the rate limit resets for specific client identifier.

`RateLimiterer::reset($identifier)` will reset any usage for specific client identifier.

# Algorithm
This bundle uses Token Bucket algorithm. It's enough to say that it's simple enough that we can store two values per client for limiting their requests.
One thing that we save is time of last request and other is `allowance` which indirectly represent number of tries left.

# Storage
This bundle is dependant on SncRedisBundle and it uses it for storage.

New storage options can be introduced by implementing `Storage\RateLimitStorageInterface`.
Storage should be quick and simple since only thing we need to store are key->value pairs.

# References

- http://en.wikipedia.org/wiki/Token_bucket
