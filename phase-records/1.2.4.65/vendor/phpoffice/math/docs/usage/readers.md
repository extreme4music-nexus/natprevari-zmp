# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.


## Readers
### MathML
The name of the reader is `MathML`.

``` php
<?php

use PhpOffice\Math\Reader\MathML;

$reader = new MathML();
$math = $reader->read(
   '<?xml version="1.0" encoding="UTF-8"?>
    <!DOCTYPE math PUBLIC "-//W3C//DTD MathML 2.0//EN" "http://www.w3.org/Math/DTD/mathml2/mathml2.dtd">
    <math xmlns="http://www.w3.org/1998/Math/MathML">
        <mi> a </mi>
    </math>'
);
```

### OfficeMathML
The name of the reader is `OfficeMathML`.

``` php
<?php

use PhpOffice\Math\Reader\OfficeMathML;

$reader = new OfficeMathML();
$math = $reader->read(
   '<m:oMathPara xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math">
      <m:oMath>
        <m:f>
          <m:num><m:r><m:t>π</m:t></m:r></m:num>
          <m:den><m:r><m:t>2</m:t></m:r></m:den>
        </m:f>
      </m:oMath>
    </m:oMathPara>'
);
```

## Methods

### read

The method has one parameter :

* `string` **$content**

The method returns a `PhpOffice\Math\Math` object.