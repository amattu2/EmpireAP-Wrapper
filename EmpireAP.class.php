<?php
/*
 * Produced: Thu Jan 06 2022
 * Author: Alec M.
 * GitHub: https://amattu.com/links/github
 * Copyright: (C) 2022 Alec M.
 * License: License GNU Affero General Public License v3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Namespaces
namespace amattu;

// Exception Classes
class InvalidLoginException extends \Exception {}

/**
 * An Empire Auto Parts API Wrapper Class
 *
 * See README.md for more information.
 */
class EmpireAP {
  /**
   * A list of API endpoints
   *
   * @var array
   */
  private $endpoints = [
    "base" => "https://www.empireap.com/Account/SignIn",
    "parts" => "https://www.empireap.com/Parts",
    "makers" => "https://empireap.com/Parts/_Makers",
    "models" => "https://empireap.com/Parts/_Models",
    "search_results" => "https://empireap.com/Parts/_SearchResults",
    "search_history" => "https://empireap.com/Parts/SearchHistory",
    "invoice" => "https://empireap.com/Invoices/%s/%s",
    "orders" => "https://empireap.com/Orders",
    "item_transactions" => "https://www.empireap.com/Orders/ItemTransactions/%s?itemNumber=NA&status=NA",
  ];

  /**
   * Current Session Attributes
   *
   * @var array
   */
  private $csrf = [
    "cookie" => null,
    "form" => null
  ];

  /**
   * Current cURL Handle
   *
   * @var Resource
   */
  private $ch = null;

  /**
   * Current User Account Email
   *
   * @var string
   */
  private $email = "";

  /**
   * Current User Account Password
   *
   * @var string
   */
  private $password = "";

  /**
   * Current Session ID
   *
   * @var string
   */
  private $SessionId = null;

  /**
   * Current Authentication Status
   *
   * @var string
   */
  private $authenticated = false;

  /**
   * Emulated Request User-Agent
   *
   * @var string
   */
  private $REQUEST_UA = "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7";

  /**
   * Emulated Request Referrer
   *
   * @var string
   */
  private $REQUEST_REFERER = "https://www.google.com";

  /**
   * Request Validation Token
   *
   * @var string
   */
  private $RVT = "__RequestVerificationToken";

  /**
   * Minimum Supported Year
   *
   * @var integer
   */
  private $minimum_year = 1930;

  /**
   * Class Constructor
   *
   * @param string $email
   * @param string $password
   * @throws TypeError
   * @throws InvalidArgumentException
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-14
   */
  public function __construct(string $email, string $password)
  {
    // Validate input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException("Provide a valid account email");
    }
    if (!$password || (strlen($password) <= 6 || strlen($password) > 64)) {
      throw new InvalidArgumentException("Provide a valid account password");
    }

    // Setup Variables
    $this->email = $email;
    $this->password = $password;
    $this->ch = curl_init($this->endpoints["base"]);
  }

  /**
   * Force initiate a login request
   *
   * NOTE:
   *   (1) This function does not need to
   *   be called directly unless you need to
   *   verify your credentials.
   *
   * @return bool login success
   * @throws None
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-14
   */
  public function login() : bool
  {
    // Checks
    if ($this->authenticated())
      return true;
    if (!$this->ch)
      $this->ch = curl_init($this->endpoints["base"]);
    else
      curl_setopt($this->ch, CURLOPT_URL, $this->endpoints["base"]);

    // Initial request to pull CSRF token
    curl_setopt($this->ch, CURLOPT_HEADER, 1);
    curl_setopt($this->ch, CURLOPT_NOBODY, 0);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($this->ch, CURLOPT_USERAGENT, $this->REQUEST_UA);
    curl_setopt($this->ch, CURLOPT_REFERER, $this->REQUEST_REFERER);

    // Check cURL Result
    $result = curl_exec($this->ch);
    if (curl_error($this->ch))
      return false;

    // Extract Cookies
    $cookies = $this->extract_cookies($result);
    if (empty($cookies) || !isset($cookies[$this->RVT]))
      return false;
    else
      $this->csrf["cookie"] = $cookies[$this->RVT];

    // Parse form value
    $this->csrf["form"] = $this->extract_input_value($result, $this->RVT);
    if (empty($this->csrf["form"]))
      return false;

    // Perform actual login request
    curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($this->ch, CURLOPT_POST, 1);
    curl_setopt($this->ch, CURLOPT_COOKIE, "__RequestVerificationToken={$this->csrf["cookie"]}");
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->build_query_string([
      "redirectUrl" => "/Parts",
      "rememberUsername" => "false",
      "username" => $this->email,
      "password" => $this->password,
      "__RequestVerificationToken" => $this->csrf['form']
    ]));

    // Check cURL Result
    $result = curl_exec($this->ch);
    if (curl_error($this->ch))
      return false;

    // Extract Cookies
    $cookies = $this->extract_cookies($result);
    if (empty($cookies) || !isset($cookies["SessionId"]))
      return false;
    else
      $this->SessionId = $cookies["SessionId"];

    // Check Return Result
    if (curl_getinfo($this->ch, CURLINFO_HTTP_CODE) !== 302)
      return false;
    if (curl_getinfo($this->ch, CURLINFO_REDIRECT_URL) !== $this->endpoints["parts"])
      return false;

    // Return
    $this->authenticated = true;
    return true;
  }

  /**
   * Fetch Makes (Manufacturers) by Vehicle Model Year
   *
   * @param int model year
   * @return array supported makes
   * @throws TypeError
   * @throws InvalidLoginException
   * @throws InvalidArgumentException
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-15
   */
  public function search_makers(int $model_year) : array
  {
    // Checks
    if (!$this->login())
      throw new InvalidLoginException("Unable to login to website. Check your credentials");
    if (!$this->ch)
      $this->ch = curl_init($this->endpoints["makers"]);
    else
      curl_setopt($this->ch, CURLOPT_URL, $this->endpoints["makers"]);
    if ($model_year < $this->minimum_year || $model_year > (date("Y") + 2))
      throw new InvalidArgumentException("Unsupported model year provided");

    // Fetch Makes
    curl_setopt($this->ch, CURLOPT_HEADER, 0);
    curl_setopt($this->ch, CURLOPT_NOBODY, 0);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($this->ch, CURLOPT_USERAGENT, $this->REQUEST_UA);
    curl_setopt($this->ch, CURLOPT_REFERER, $this->endpoints["parts"]);
    curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($this->ch, CURLOPT_POST, 1);
    curl_setopt($this->ch, CURLOPT_COOKIE, "__RequestVerificationToken={$this->csrf["cookie"]}; SessionId={$this->SessionId}");
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->build_query_string([
      "year" => $model_year,
    ]));

    // Check cURL Result
    $result = curl_exec($this->ch);
    if (curl_error($this->ch))
      return [];

    // Return makes
    return $this->extract_form_select_options($result);
  }

  /**
   * Fetch Models by Model Year and Model
   *
   * @param int model year
   * @param string maker code
   * @return array supported models
   * @throws TypeError
   * @throws InvalidLoginException
   * @throws InvalidArgumentException
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-15
   */
  public function search_models(int $model_year, string $maker_code) : array
  {
    // Checks
    if (!$this->login())
      throw new InvalidLoginException("Unable to login to website. Check your credentials");
    if (!$this->ch)
      $this->ch = curl_init($this->endpoints["models"]);
    else
      curl_setopt($this->ch, CURLOPT_URL, $this->endpoints["models"]);
    if ($model_year < $this->minimum_year || $model_year > (date("Y") + 2))
      throw new InvalidArgumentException("Unsupported model year provided");
    if (strlen($maker_code) <= 2)
      throw new InvalidArgumentException("Invalid maker provided");

    // Fetch Makes
    curl_setopt($this->ch, CURLOPT_HEADER, 0);
    curl_setopt($this->ch, CURLOPT_NOBODY, 0);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($this->ch, CURLOPT_USERAGENT, $this->REQUEST_UA);
    curl_setopt($this->ch, CURLOPT_REFERER, $this->endpoints["parts"]);
    curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($this->ch, CURLOPT_POST, 1);
    curl_setopt($this->ch, CURLOPT_COOKIE, "__RequestVerificationToken={$this->csrf["cookie"]}; SessionId={$this->SessionId}");
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->build_query_string([
      "year" => $model_year,
      "makerCode" => $maker_code
    ]));

    // Check cURL Result
    $result = curl_exec($this->ch);
    if (curl_error($this->ch))
      return [];

    // Return models
    return $this->extract_form_select_options($result);
  }

  /**
   * Fetch parts by Year, Make, Model
   *
   * @param int model year
   * @param string maker code
   * @param int model id
   * @return array <Note: string, Parts: array, Wheels: array>
   * @throws TypeError
   * @throws InvalidLoginException
   * @throws InvalidArgumentException
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-15
   */
  public function search_results(int $model_year, string $maker_code, int $model_id) : array
  {
    // Checks
    if (!$this->login())
      throw new InvalidLoginException("Unable to login to website. Check your credentials");
    if (!$this->ch)
      $this->ch = curl_init($this->endpoints["search_results"]);
    else
      curl_setopt($this->ch, CURLOPT_URL, $this->endpoints["search_results"]);
    if ($model_year < $this->minimum_year || $model_year > (date("Y") + 2))
      throw new InvalidArgumentException("Unsupported model year provided");
    if (strlen($maker_code) <= 2)
      throw new InvalidArgumentException("Invalid maker (manufacturer) code provided");
    if ($model_id <= 0)
      throw new InvalidArgumentException("Invalid model ID provided");

    // Fetch Makes
    curl_setopt($this->ch, CURLOPT_HEADER, 0);
    curl_setopt($this->ch, CURLOPT_NOBODY, 0);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($this->ch, CURLOPT_USERAGENT, $this->REQUEST_UA);
    curl_setopt($this->ch, CURLOPT_REFERER, $this->endpoints["parts"]);
    curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($this->ch, CURLOPT_POST, 1);
    curl_setopt($this->ch, CURLOPT_COOKIE, "__RequestVerificationToken={$this->csrf["cookie"]}; SessionId={$this->SessionId}");
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->build_query_string([
      "year" => $model_year,
      "makerCode" => $maker_code,
      "makerName" => "NA",
      "modelId" => $model_id,
      "modelName" => "NA",
      "logSearch" => true,
    ]));

    // Check cURL Result
    $result = curl_exec($this->ch);
    if (curl_error($this->ch))
      return [];

    // Return parsed result
    return Array(
    "Note" => "",
    "Parts" => $this->extract_search_parts($result),
    "Wheels" => $this->extract_search_wheels($result),
    );
  }

  /**
   * Fetch an order item transactional history status
   *
   * @param integer $label_num Order item number (label_num)
   * @return array
   */
  public function item_transactions(int $item_num) : array
  {
    // Checks
    if (!$this->login())
      throw new InvalidLoginException("Unable to login to website. Check your credentials");
    if (!$this->ch)
      $this->ch = curl_init(sprintf($this->endpoints["item_transactions"], $item_num));
    else
      curl_setopt($this->ch, CURLOPT_URL, sprintf($this->endpoints["item_transactions"], $item_num));

    // Fetch Makes
    curl_setopt($this->ch, CURLOPT_HEADER, 0);
    curl_setopt($this->ch, CURLOPT_NOBODY, 0);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($this->ch, CURLOPT_USERAGENT, $this->REQUEST_UA);
    curl_setopt($this->ch, CURLOPT_REFERER, $this->endpoints["orders"]);
    curl_setopt($this->ch, CURLOPT_COOKIE, "__RequestVerificationToken={$this->csrf["cookie"]}; SessionId={$this->SessionId}");

    // Check cURL Result
    $result = curl_exec($this->ch);
    if (curl_error($this->ch))
      return [];

    // Return makes
    return $this->extract_item_transactions($result);
  }

  /**
   * Fetch recent website searches
   *
   * @return array recent searchs
   * @throws InvalidLoginException
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-18T09:false51:false11-040
   */
  public function search_history() : array
  {
    // Checks
    if (!$this->login())
      throw new InvalidLoginException("Unable to login to website. Check your credentials");
    if (!$this->ch)
      $this->ch = curl_init($this->endpoints["search_history"]);
    else
      curl_setopt($this->ch, CURLOPT_URL, $this->endpoints["search_history"]);

    // Fetch Search Results
    curl_setopt($this->ch, CURLOPT_HEADER, 0);
    curl_setopt($this->ch, CURLOPT_NOBODY, 0);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($this->ch, CURLOPT_USERAGENT, $this->REQUEST_UA);
    curl_setopt($this->ch, CURLOPT_REFERER, $this->endpoints["parts"]);
    curl_setopt($this->ch, CURLOPT_COOKIE, "__RequestVerificationToken={$this->csrf["cookie"]}; SessionId={$this->SessionId}");

    // Check cURL Result
    $result = curl_exec($this->ch);
    if (curl_error($this->ch))
      return [];

    // Return
    return $this->extract_recent_vehicles($result);
  }

  /**
   * Get a Empire Auto Parts invoice by Invoice Number
   *
   * NOTE:
   *   (1) Invoices aren't limited by company
   *   I.E. you can "accidentially" fetch invoices
   *   for companies other than your own
   *   (2) This function either returns a PDF
   *   stringified or null if the PDF is not available.
   *
   * @param int invoice number
   * @return mixed Base64 PDF | null
   * @throws TypeError
   * @throws InvalidLoginException
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-18
   */
  public function get_invoice(int $invoice_number)
  {
    // Checks
    if (!$this->login())
      throw new InvalidLoginException("Unable to login to website. Check your credentials");
    if ($invoice_number <= 0)
      throw new InvalidArgumentException("Invoice numbers must be greater than 0");
    if (!$this->ch)
      $this->ch = curl_init(sprintf($this->endpoints["invoice"], "Download", $invoice_number));
    else
      curl_setopt($this->ch, CURLOPT_URL, sprintf($this->endpoints["invoice"], "Download", $invoice_number));

    // Fetch PDF
    curl_setopt($this->ch, CURLOPT_HEADER, 0);
    curl_setopt($this->ch, CURLOPT_NOBODY, 0);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($this->ch, CURLOPT_USERAGENT, $this->REQUEST_UA);
    curl_setopt($this->ch, CURLOPT_REFERER, $this->endpoints["orders"]);
    curl_setopt($this->ch, CURLOPT_COOKIE, "__RequestVerificationToken={$this->csrf["cookie"]}; SessionId={$this->SessionId}");

    // Check cURL Result
    $result = curl_exec($this->ch);
    if (curl_error($this->ch))
      return null;
    if (curl_getinfo($this->ch, CURLINFO_HTTP_CODE) !== 200)
      return null;

    // Return
    return $result ?: null;
  }

  /**
   * Return current user authentication status
   *
   * @return bool authenticated
   * @throws None
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-14
   */
  private function authenticated() : bool
  {
    // Checks
    if (!$this->authenticated)
      return false;
    if (!$this->csrf["cookie"])
      return false;
    if (!$this->csrf["form"])
      return false;
    if (!$this->SessionId)
      return false;

    // Default
    return true;
  }

  /**
   * Extract cookies from HTTP header
   *
   * @param string HTTP headers
   * @return array parsed Set-Cookie directive
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-14
   */
  private function extract_cookies(string $header) : array
  {
    // Variables
    $cookies = Array();

    // Find matches
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);

    // Rename array
    foreach($matches[1] as $item) {
      parse_str($item, $cookie);
      $cookies = array_merge($cookies, $cookie);
    }

    // Return result
    return $cookies;
  }

  /**
   * Extract HTML form input value
   *
   * @param string HTML body
   * @param string Input name
   * @return string Input value
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-14
   */
  private function extract_input_value(string $body, string $input_name) : string
  {
    // Disable errors
    libxml_use_internal_errors(true);

    // Load HTTP body
    $document = new \DOMDocument();
    $document->loadHTML($body);
    $xp = new \DomXPath($document);

    // Find Element
    if ($nodes = $xp->query("//input[@name='{$input_name}']"))
      if ($node = $nodes->item(0))
        return $node->getAttribute('value') ?: "";

    // Default
    return "";
  }

  /**
   * Build a CURLOPT_POSTFIELDS valid query string
   *
   * @param array $data
   * @return string query string
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-15
   */
  private function build_query_string(array $data) : string
  {
    // Variables
    $query_string = '';

    // Build String
    foreach($data as $key => $value)
      $query_string .= "{$key}={$value}&";

    // Return
    return rtrim($query_string, '&');
  }

  /**
   * Extract HTML select option element values
   *
   * @param string HTML body
   * @return array option values
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-15
   */
  private function extract_form_select_options(string $body) : array
  {
    // Disable errors
    libxml_use_internal_errors(true);

    // Load HTTP body
    $document = new \DOMDocument();
    $document->loadHTML($body);
    $xp = new \DomXPath($document);
    $options = Array();

    // Find Elements
    if ($nodes = $xp->query("//select/option"))
      foreach ($nodes as $node) {
        // Checks
        if (!$node || !$node->getAttribute("value"))
          continue;

        // Push value
        $options[] = Array(
          "text" => $node->nodeValue,
          "value" => $node->getAttribute("value")
        );
      }

    // Default
    return $options;
  }

  /**
   * Extract HTML listings for aftermarket parts
   *
   * @param string HTML body
   * @return array parsed part listing
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-15
   */
  private function extract_search_parts(string $body) : array
  {
    // Disable errors
    libxml_use_internal_errors(true);

    // Load HTTP body
    $document = new \DOMDocument();
    $document->loadHTML($body);
    $xp = new \DomXPath($document);
    $parts = Array();

    // Find Elements
    if ($rows = $xp->query("//table[@id='table-replacement-parts']/tbody/tr"))
      foreach ($rows as $row)
        if ($children = $xp->query('td', $row))
          $parts[] = Array(
            "Description" => trim($children->item(1)->nodeValue),
            "Quality" => trim($children->item(2)->nodeValue),
            "List" => trim($children->item(3)->nodeValue),
            "Cost" => trim($children->item(4)->nodeValue),
            "OEM_Part_Number" => trim($children->item(5)->nodeValue),
            "Part_Number" => trim($children->item(6)->nodeValue),
          );

    // Default
    return $parts;
  }

  /**
   * Extract HTML listings for OEM wheels
   *
   * @param string HTML body
   * @return array parsed wheel listing
   * @throws TypeError
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-15
   */
  private function extract_search_wheels(string $body) : array
  {
    // Disable errors
    libxml_use_internal_errors(true);

    // Load HTTP body
    $document = new \DOMDocument();
    $document->loadHTML($body);
    $xp = new \DomXPath($document);
    $wheels = Array();

    // Find Elements
    if ($rows = $xp->query("//table[@id='table-replacement-wheels']/tbody/tr"))
      foreach ($rows as $row)
        if ($children = $xp->query('td', $row))
          $wheels[] = Array(
            "Description" => trim($children->item(1)->nodeValue),
            "Quality" => trim($children->item(2)->nodeValue),
            "List" => trim($children->item(3)->nodeValue),
            "Cost" => trim($children->item(4)->nodeValue),
            "OEM_Part_Number" => trim($children->item(5)->nodeValue),
            "Part_Number" => trim($children->item(6)->nodeValue),
          );

    // Default
    return $wheels;
  }

  /**
   * Extract Recent Vehicles from HTML table
   *
   * @param string HTML body
   * @return array recent vehicles
   * @throws None
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-18
   */
  private function extract_recent_vehicles(string $body) : array
  {
    // Disable errors
    libxml_use_internal_errors(true);

    // Load HTTP body
    $document = new \DOMDocument();
    $document->loadHTML($body);
    $xp = new \DomXPath($document);
    $vehicles = Array();

    // Find Elements
    if ($rows = $xp->query("//table/tbody/tr"))
      foreach ($rows as $row)
        $vehicles[] = Array(
          "model_year" => $row->getAttribute("data-year"),
          "maker_code" => $row->getAttribute("data-maker-code"),
          "maker" => $row->getAttribute("data-maker-name"),
          "model_id" => $row->getAttribute("data-model-id"),
          "model" => $row->getAttribute("data-model-name")
        );

    // Return
    return $vehicles;
  }

    /**
   * Extract Order Item Transactions
   *
   * @param string HTML body
   * @return array order item transactions
   * @throws None
   * @author Alec M. <https://amattu.com>
   * @date 2022-01-06
   */
  private function extract_item_transactions(string $body) : array
  {
    // Disable errors
    libxml_use_internal_errors(true);

    // Load HTTP body
    $document = new \DOMDocument();
    $document->loadHTML($body);
    $xp = new \DomXPath($document);
    $txns = Array();

    // Find Elements
    if ($rows = $xp->query("//div//table/tbody/tr"))
      foreach ($rows as $row)
        if ($children = $xp->query('td', $row))
          $txns[] = Array(
            "status" => trim($children->item(0)->nodeValue),
            "date" => trim($children->item(1)->nodeValue)
          );

    // Return
    return $txns;
  }
}
