# Laravel Helpers 

[![Coverage Status](https://coveralls.io/repos/github/RonasIT/laravel-helpers/badge.svg?branch=master)](https://coveralls.io/github/RonasIT/laravel-helpers?branch=master)

This plugin provides set of helpers functions, services and traits. 

## Installation

### Composer
 1. Run `composer require ronasit/laravel-helpers`
 1. For Laravel <= 5.5 add `RonasIT\Support\HelpersServiceProvider::class` to config `app.providers` list

## Features  

### Https schema 

The package is forcing the `HTTPS` scheme for your app. Generally, it affects the URL generation logic.  
  
This allows to use many web-related packages like Telescope, Nova, etc.  

## Usage
 - [Helper functions][1]
 - [Traits][2]
 - [Services][3]
 - [Iterators][4]

## Migration guids
 - [1.1][5]
 - [2.0.0][6]
 - [2.0.8][7]

[1]:./documentation/helpers.md
[2]:./documentation/traits.md
[3]:./documentation/services.md
[4]:./documentation/iterators.md
[5]:./documentation/migration.md#1.1
[6]:./documentation/migration.md#2.0.0
[7]:./documentation/migration.md#2.0.8
