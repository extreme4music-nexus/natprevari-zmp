# 🚨 OWNER MANDATE — READ FIRST: 2026-11-24 PASS IS FINAL FOR ITS EXACT CHECK SCOPE. NEVER REOPEN OR REPEAT IT, OR COUNT AN OLDER NOT RUN/FAIL FOR THAT SAME SCOPE AS OPEN, UNLESS NEW CONTRADICTORY EVIDENCE EXISTS.
# 🚨 ROLE STACK: EVERY ZMP MEMBER MUST HAVE SUBSCRIBER; TEACHER IS OPTIONAL AND ADDITIVE. PRESIDENT MUST HAVE TEACHER AND SUBSCRIBER.

# The Serializer (Writer) Model

The serializer roughly follows sections _8.1 Writing HTML documents_ and section
_8.3 Serializing HTML fragments_ by converting DOMDocument, DOMDocumentFragment,
and DOMNodeList into HTML5.

       [ HTML5 ]   // Interface for saving.
          ||
     [ Traverser ]   // Walk the DOM
          ||
       [ Rules ]     // Convert DOM elements into strings.
          ||
       [ HTML5 ]     // HTML5 document or fragment in text.


## HTML5 Class

Provides the top level interface for saving.

## The Traverser

Walks the DOM finding each element and passing it off to the output rules to
convert to HTML5.

## Output Rules

The output rules are defined in the RulesInterface which can have multiple
implementations. Currently, the OutputRules is the default implementation that
converts a DOM as is into HTML5.

## HTML5 String

The output of the process it HTML5 as a string or saved to a file.