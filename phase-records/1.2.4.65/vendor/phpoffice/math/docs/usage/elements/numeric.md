# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

## Usage

To create a numeric, use the `PhpOffice\Math\Element\Numeric` class.

### Methods
#### getValue

The method has no parameter.

#### setValue

The method has one parameter : 

* `float` **$value**

## Example

### Math
<math display="block">
  <mn>3</mn>
</math>

### XML
``` xml
<math display="block">
  <mn>3</mn>
</math>
```

### PHP

``` php
<?php

use PhpOffice\Math\Element;
use PhpOffice\Math\Math;

$math = new Math();

$identifier = new Element\Numeric(3);

$math->add($identifier);
```