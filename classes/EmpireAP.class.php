<?php
/*
	Produced 2021
	By https://amattu.com/links/github
	Copy Alec M.
	License GNU Affero General Public License v3.0
*/

// Namespaces
namespace amattu;

// Empire Auto Parts Website Wrapper
class EmpireAP {
	// Variables
	private $endpoints = [
		"base" => "https://www.empireap.com/Account/SignIn"
	];
	private $csrf = [
		"cookie" => null,
		"form" => null
	];
	private $ch = null;
	private $email = "";
	private $password = "";
	private $authenticated = false;
	private $REQUEST_UA = "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7";
	private $REQUEST_REFERER = "https://www.google.com";
	private $RVT = "__RequestVerificationToken";

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

		// Setup cURL Handle
		$this->ch = curl_init($this->endpoints["base"]);
	}

	/**
	 * Force initiate a login request
	 * Not inherently required
	 *
	 * @return bool login success
	 * @throws
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

		// Initial request to pull CSRF token
		curl_setopt($this->ch, CURLOPT_HEADER, 1);
		curl_setopt($this->ch, CURLOPT_NOBODY, 0);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->REQUEST_UA);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_REFERER, $this->REQUEST_REFERER);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);

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
		curl_setopt($this->ch, CURLOPT_HEADER, 0);
		curl_setopt($this->ch, CURLOPT_NOBODY, 0);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($this->ch, CURLOPT_COOKIE, "__RequestVerificationToken={$this->csrf["cookie"]}");
		curl_setopt($this->ch, CURLOPT_USERAGENT, $this->REQUEST_UA);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->ch, CURLOPT_REFERER, $this->REQUEST_REFERER);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, "rememberUsername=false&username={$this->email}&password={$this->password}&__RequestVerificationToken={$this->csrf[form]}");
		$result = curl_exec($this->ch);
		curl_close($this->ch);

		/** TBD **/
		echo $result;

		// Return
		return true;
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
}
