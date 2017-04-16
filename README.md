Stauros
=======

[![Build Status](https://travis-ci.org/ircmaxell/Stauros.svg?branch=master)](https://travis-ci.org/ircmaxell/Stauros)

A fast XSS sanitation library for PHP.

## IMPORTANT

# **THIS IS AN EXPERIMENTAL LIBRARY, USE AT YOUR OWN RISK**

## How to use it

With the default settings, simply call `Stauros->scanHTML()`:

    $stauros = new Stauros;
    $clean = $stauros->scanHTML($dirty);

Easy as that

## Working with streams

Stauros supports streaming content as well. You can use a stream as input, getting a string as output:

    $clean = $stauros->scanHTMLStreamToString($stream);

Or you can use it as a stream to stream process:

    $stauros->scanHTMLStreamToStream($input, $output);

## Advanced Usage

The configuration class (`Stauros\HTML\Config`) allows you to specify html tag whitelists, as well as attribute whitelist and implement an attribute callback for further customization.

