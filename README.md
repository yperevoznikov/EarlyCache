# Early Cache PHP module [![Build Status](https://travis-ci.org/yperevoznikov/EarlyCache.svg?branch=develop)](https://travis-ci.org/yperevoznikov/EarlyCache)
Early stage cache implementation for fast web app response. 
Caches page content with headers and response code.

# Supported frameworks
This code can be integrated into any web app but examples area and code have been tested with these frameworks:  
- Kohana ([Kohana readme](examples/kohana/readme.md))   

# Installation
In order to install this package use composer:  
```
composer require yperevoznikov/earlycache
```

# Unit Testing
To run all unit tests, in component directory (where this file is placed) 
copy `phpunit.xml.dist` to `phpunit.xml` and run in command line:  
```  
php phpunit.phar
```
