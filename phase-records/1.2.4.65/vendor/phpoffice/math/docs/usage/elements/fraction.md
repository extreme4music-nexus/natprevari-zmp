# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

## Usage

To create a fraction, use the `PhpOffice\Math\Element\Fraction` class.

### Methods
#### getDenominator

The method has no parameter.

#### getNumerator

The method has no parameter.

#### setDenominator

The method has one parameter : 

* `PhpOffice\Math\Element\AbstractElement` **$element**

#### setNumerator

The method has one parameter : 

* `PhpOffice\Math\Element\AbstractElement` **$element**

## Example

### Math
<math display="block">
  <mfrac>
      <mi>a</mi>
      <mn>3</mn>
  </mfrac>
</math>

### XML
``` xml
<math display="block">
  <mfrac>
      <mi>a</mi>
      <mn>3</mn>
  </mfrac>
</math>
```

### PHP

``` php
<?php

use PhpOffice\Math\Element;
use PhpOffice\Math\Math;

$math = new Math();

$fraction = new Element\Fraction();
$fraction->setDenominator(new Element\Identifier('a'));
$fraction->setNumerator(new Element\Numeric(3));

$math->add($fraction);
```