# __php global function helpers

This library introduces dozens of functions, all prefixed with `__` to the global PHP namespace.

These routines are extensions, helpers, shorthand, wrappers around existing core PHP functions.


## Example

A prime example is `json_encode` which has some legacy defaults.
We simply change those, with our "magic" function, adding two `__`.

    __json_encode($d);


## Isn't __ Reserved?

It's (sorta) true! [PHP says so](https://www.php.net/manual/en/language.oop5.magic.php).

> PHP reserves all function names starting with __ as magical.
> It is recommended that you do not use function names with __ in PHP unless you want some documented magic functionality.

So, it's reserved, but explicitly for magic.
And this library is doing "magic" in a similar way, just for functions not OO.
And documented (sorta).


