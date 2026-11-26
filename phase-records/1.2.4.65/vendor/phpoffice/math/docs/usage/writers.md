# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.


## Writers
### MathML
The name of the writer is `MathML`.

``` php
<?php

use PhpOffice\Math\Element;
use PhpOffice\Math\Math;
use PhpOffice\Math\Writer\MathML;

$math = new Math();
$math->add(new Element\Operator('+'));

$writer = new MathML();
$output = $writer->write($math);
```

### OfficeMathML

The name of the writer is `OfficeMathML`.

``` php
<?php

use PhpOffice\Math\Element;
use PhpOffice\Math\Math;
use PhpOffice\Math\Writer\OfficeMathML;

$math = new Math();
$math->add(new Element\Operator('+'));

$writer = new OfficeMathML();
$output = $writer->write($math);
```

## Methods

### writer

The method has one parameter :

* `PhpOffice\Math\Math` **$math**

The method returns a `string`.