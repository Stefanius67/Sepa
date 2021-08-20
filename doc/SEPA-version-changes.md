# Changes in the SEPA versions

## From 2.6 to 2.9
> Versions 2.7 and 2.8 are intermediate versions that were finalized in Version 2.9 !

### CCT

| change                                   | relevance                                        |
|------------------------------------------|--------------------------------------------------|
|`<Othr><Id>` as alt for `<BIC>` added     | no (we won't support the *other* identification) |

> The *other* identification is not implemented because no meaningful description for its use could be found! 

=> nothing more to do than changing the namespace and schema to pain.001.003.03

### CDD

| change                                   | relevance                                        |
|------------------------------------------|--------------------------------------------------|
|`<Othr><Id>` as alt for `<BIC>` added     | no (we won't support the *other* identification) |
|new type of a direct debit `COR1`         | discuss                                          |

1. The *other* identification is not implemented because no meaningful description for its use could be found! 
2. So far we only support the `CORE` direct debit type. Check, if we should support the new `COR1` (CORE with reduced 
   execution time cycle) and the `B2B` type.

=> nothing more to do than changing the namespace and schema to pain.008.003.02

## From 2.9 to 3.0
No relevant changes related to the features supported by the package !

### CCT
=> nothing more to do than changing the namespace and schema to pain.001.001.03
### CDD
=> nothing more to do than changing the namespace and schema to pain.008.001.02
