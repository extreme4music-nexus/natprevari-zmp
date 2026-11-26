# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

## Usage

To create an identifier, use the `PhpOffice\Math\Element\Identifier` class.

### Methods
#### getValue

The method has no parameter.

#### setValue

The method has one parameter : 

* `string` **$value**

## Example

### Math
<math display="block">
  <mi>a</mi>
</math>

### XML
``` xml
<math display="block">
  <mi>a</mi>
</math>
```

### PHP

``` php
<?php

use PhpOffice\Math\Element;
use PhpOffice\Math\Math;

$math = new Math();

$identifier = new Element\Identifier('a');

$math->add($identifier);
```