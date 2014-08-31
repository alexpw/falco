Falco
=====

Falco is a FunctionAL COmposition library for PHP 5.3+.

## Status

Unstable, alpha, everything is subject to change and is largely untested.

## TLDR;

1. PHP? Yes
2. Curried whenever possible
3. Composition and point-free / tacit programming
4. Lazy evaluation (opt-in explicitly)
5. Extendable to your needs

### PHP? yes

Partly because I:

* wanted to see what the result would look like in PHP.
* had a FP itch and I'm paid to write more PHP/JS than Clojure.
* found no equivalent in PHP (non-exhaustive search).

Oddly enough, when I encountered an obscure bug in xdebug, it served as evidence
to suggest this is breaking new ground.  It turns out that the way I optimize for
specific arities maybe the reason.

This library not only delivers the power of PHP-flavored functional programming,
but aims to do it through a consistent API, which PHP fails to do with
(haystack, needle) and (needle, haystack).

PHP's flexible definition of Callable is surprisingly powerful, and everything I
need is available in 5.3 to stay compatible for older setups.

### A port of what?

Is this a port of [Clojure](http://clojure.org), [Rambda](https://github.com/CrossEye/ramda),
[Functional.js](http://functionaljs.com/), etc, for PHP?

No, but it is strongly influenced by all of them and others and borrows anything and
everything that can work well with PHP.

### Curried whenever possible

If you'd like more info, one of the best intros is Hugh Jackson's [Why Curry Helps](http://hughfdjackson.com/javascript/why-curry-helps/).

I'll just add that currying is often confused with partial function application,
but in practice, you need not care.

### Composition and point-free / tacit programming

Falco provides higher-order curried functions with a consistent order to their arguments,
such that the function is first, and the data is last.  They are designed to work well together.

By having the function as the first argument, you can easily reuse and compose the
steps you need to perform on your data.

This lets you pivot quickly when your requirements change, and create a DRY'r code base.

For a great example and library in JS, see [Why Rambda](http://fr.umio.us/why-ramda/).

A port of that example is in [test/why-falco.php](https://github.com/alexpw/falco/blob/master/test/why-falco.php).

### Lazy evaluation (opt-in explicitly)

As soon as you write:
```
$filterOdd = F::filter(F::isOdd());
$firstOdd  = F::first($filterOdd(F::range(1, 1000)));
```
You realize that this will create a 1000 element array, then foreach over all of them,
creating a new array containing all of the odd numbered elements.  Then take the first.

All you wanted was the first!  This can be very inefficient (but don't just guess, always measure).

The idea of [lazy evaluation](https://en.wikipedia.org/wiki/Lazy_evaluation), as opposed to strict, is that you only pay for what you use.

We asked for the first, and range(1, 1000) returns 1 as the first element, which 
$filterOdd's test will pass, so we will only compute the first.

But, instead of being lazy all of the time, Falco asks that you be clear about it, so
others know what to expect when they visit your code.

To start being lazy, you simply wrap your input data in ```F::lazy()``` or use ```F::lazyrange()```.

Then, you either explicitly ask for:
* ```F::value($result)``` and turn the lazy iterator into an array.
* ```F::first($result)``` and return the first element.

or you foreach over the result.
* ```foreach (F::take(5, $result) as $val)```
* ```foreach ($result as $val)``` and ```break;``` appropriately.

See examples elsewhere.
```
$filterOdd = F::filter(F::isOdd());
$firstOdd  = F::first($filterOdd(F::lazyrange(1, 1000)));
```
Lazyness is achieved through iterators instead of generators (which require 5.5).

### Extendable to your needs

Inspired by JS libs, the Falco\Falco facade allows monkey patching using set_fn($name, $fn).

# Install

At your own risk, this isn't stable yet.

Clone the source

or

Composer: TBD

# Docs

Annotated source with examples in progress.
