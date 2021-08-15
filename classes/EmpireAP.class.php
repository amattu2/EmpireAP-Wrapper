<?php
/*
  Produced 2021
  By https://amattu.com/links/github
  Copy Alec M.
  License GNU Affero General Public License v3.0
*/

// Namespaces
namespace amattu;

// Exception Classes
class InvalidLoginException extends \Exception {}

// Empire Auto Parts Website Wrapper
class EmpireAP {
  // Variables
  private $endpoints = [
    "base" => "https://www.empireap.com/Account/SignIn",
    "parts" => "https://www.empireap.com/Parts",
    "makers" => "https://empireap.com/Parts/_Makers",
  ];
  private $csrf = [
    "cookie" => null,
    "form" => null
  ];
  private $ch = null;
  private $email = "";
  private $password = "";
  private $SessionId = null;
  private $authenticated = false;
  private $REQUEST_UA = "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7";
  private $REQUEST_REFERER = "https://www.google.com";
  private $RVT = "__RequestVerificationToken";
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
   * Not inherently required
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
   * @throws InvalidLoginException
   * @throws InvalidArgumentException
   * @author Alec M. <https://amattu.com>
   * @date 2021-08-15T09:03:false14-040
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
   * @date 2021-08-15T08:false23:false44-040
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
}
