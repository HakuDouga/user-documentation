There are times when you are using a paradigm that returns multiple values (e.g., `list()` or `foreach`) where you don't care about one or more of those values. The placeholder variable operator allows you to use these paradigms without having to create dummy variables that will never be used.

This is the placeholder variable:

```
$_
```

You can use the placeholder variable when iterating over a key/value pair, but you don't care, for example, about the value of that pair. 

@@ placeholder-variable-examples/iteration.php @@

Another common case for the placeholder variable is when you are using `list()` and you don't care about one or more of the variables that are being set.

@@ placeholder-variable-examples/list.php @@

## Typechecker Semantics

The type of `$_` is `void`. This allows the typechecker to treat this operator with the same rules as it would for anything of type `void`. This is how the typechecker can ensure that the value associated with the placeholder is not actually being used in a read context, for example.

@@ placeholder-variable-examples/read-context.php.type-errors @@
