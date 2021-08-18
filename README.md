# Introduction
This is a simple [Empire Auto Parts](https://empireap.com) digital catalog PHP wrapper. It provides easy functionality behind the authentication, catalog listing, and account orders website functionality. It should be noted, this wrapper is designed to work in place of the non-existent API, and acts as a traditional browser client. Use with caution; no authorization has been provided to use their website in this manner.

# To-Do
- https://empireap.com/Orders

# Usage
See `index.php` for most updated examples, or `EmpireAP.class.php` for the function PHPDoc.

## Setup the project
```PHP
// Include required class file
require("classes/EmpireAP.class.php");

// Wrapper handle
$wrapper = new amattu\EmpireAP("EMAIL", "PASSWORD");
```

## Force login
This function is not required unless you are wanting to test your authentication credentials. It is automatically called before each function that requires authentication.

```PHP
echo $wrapper->login() ?
	"Login success" :
	"Login failed";
```

## Fetch makes
This function will pull all of the supported makes by Empire Auto Parts for the selected model year.

```PHP
$makes = $wrapper->search_makers(2015);
```

Successful return result
```JSON
Array
(
  [0] => Array
  (
    ["text"] => Chevrolet-GMC
    ["value"] => CHEVY
  )

  [1] => Array
  (
    ["text"] => Chrysler-Dodge-Plym
    ["value"] => DODGE
  )

  [2] => Array
  (
    ["text"] => Ford-Mercury
    ["value"] => FORD
  )

  ...
```

**Note**: The `value` attribute is used for all functions where `makerCode` is required.

## Fetch models
This function pulls the supported models by the model year and maker (`makerCode`).

```PHP
$models = $wrapper->search_models(2015, "TOYOTA");
```

Successful return result
```JSON
Array
(
  [0] => Array
  (
    ["text"] => 4Runner
    ["value"] => 2297
  )

  [1] => Array
  (
    ["text"] => Avalon
    ["value"] => 2094
  )

  [2] => Array
  (
    ["text"] => Camry (Non-Hybrid)
    ["value"] => 2505
  )

  ...
```

**Note**: The `value` attribute is used where `modelId` is required.

## Fetch parts
This function uses the previous two functions to pull all available body parts and wheels and returns a organized array containing the two.

```PHP
$wrapper->search_results(2015, "TOYOTA", 2505);
```

Successful return result
```JSON

Array
(
  ["Note"] => String
  ["Parts"] => Array
  (
    [0] => Array
    (
      ["Description"] => FRONT BUMPER COVER (PRIMED)
      ["Quality"] => CAPA
      ["List"] => $230.77
      ["Cost"] => $173.00
      ["OEM_Part_Number"] => 5211907912
      ["Part_Number"] => T0169
    )

    [1] => Array
    (
      ["Description"] => FRONT BUMPER COVER (PRIMED)
      ["Quality"] =>
      ["List"] => $207.69
      ["Cost"] => $156.00
      ["OEM_Part_Number"] => 5211907912
      ["Part_Number"] => T6340
    )

    ...
  )
  ["Wheels"] => Array
  (
    [0] => Array
    (
      ["Description"] => STEEL WHEEL (16X7, 20 HOLE, BLACK)
      ["Quality"] => OE
      ["List"] => $153.33
      ["Cost"] => $115.00
      ["OEM_Part_Number"] => 4261106B10
      ["Part_Number"] => XX103
    )

    [1] => Array
    (
      ["Description"] => WHEEL COVER (16, 10 SPOKE, SILVER)
      ["Quality"] => OE Recon
      ["List"] => $96.00
      ["Cost"] => $72.00
      ["OEM_Part_Number"] => 4260206120
      ["Part_Number"] => ZX540
    )

    [2] => Array
    (
      ["Description"] => WHEEL COVER (16, 10 SPOKE, SILVER)
      ["Quality"] => OE
      ["List"] => $74.67
      ["Cost"] => $56.00
      ["OEM_Part_Number"] => 4260206120
      ["Part_Number"] => ZX533
    )

    ...
  )
)
```

## Recent Searches
This pulls the listing of recent vehicle searches.

```PHP
$wrapper->search_history();
```

Successful return result
```JSON
Array
(
  [0] => Array
  (
    ["model_year"] => 2013
    ["maker_code"] => TOYOTA
    ["maker"] => Toyota
    ["model_id"] => 1922
    ["model"] => Corolla Sedan
  )

  [1] => Array
  (
    ["model_year"] => 2018
    ["maker_code"] => TOYOTA
    ["maker"] => Toyota
    ["model_id"] => 2297
    ["model"] => 4Runner
  )
```

## Invoice
Retrieve a Empire AP receipt/invoice by invoice number. Please see the PHPDoc for important details about this function.

```PHP
$PDF = $wrapper->get_invoice(30846609);
```

Eg.
```PHP
if ($pdf = $wrapper->get_invoice(30846609)) {
  // No Caching
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  header("Cache-Control: no-cache");
  header("Pragma: no-cache");

  // PDF Output
  header("Content-type:application/pdf");
  header("Content-Disposition:inline;filename='Invoice.pdf'");

  // Output function fetched PDF
  echo $pdf;
}
```

# Requirements & Dependencies
- PHP
- cURL (Library)
- DOMDocument
