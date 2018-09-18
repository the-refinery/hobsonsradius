<?php
/**
 * HobsonsRadius plugin for Craft CMS 3.x
 *
 * Integration with HobsonsRadius
 *
 * @link      http://the-refinery.io
 * @copyright Copyright (c) 2018 The Refinery
 */

namespace therefinery\hobsonsradius\services;

use Craft;
use craft\base\Component;
use craft\helpers\Template;
use DateTime;
use therefinery\hobsonsradius\HobsonsRadius;


/**
 * HobsonsRadiusService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    The Refinery
 * @package   HobsonsRadius
 * @since     2.0.0
 */
class HobsonsRadiusService extends Component
{
	private $eventFields = array(
		"Hosts",
		"Capacity Cutoff Message",
		"Naviance Event Views",
		"Capacity",
		"Entity ID",
		"Common Status",
		"End DateTime",
		"Start DateTime",
		"No of Attendees",
		"Registration cut-off message",
		"Presenters",
		"Modified Time",
		"Lifecycle Stage",
		"Event Recurrence ID",
		"Allow Overflow Registrations",
		"Created By",
		"Recurrence Settings",
		"Description",
		"Category",
		"Form",
		"Naviance Event Detail Views",
		"Registration cut-off date",
		"Overflow Registration Message",
		"Modified By",
		"Recurrence Description",
		"Publish to Naviance",
		"Event Owner",
		"Event Status",
		"Publish",
		"Lifecycle Role",
		"Event Name",
		"Is Recurring",
		"Attendee Status",
		"Maximum Waitlist",
		"Form URL",
		"Created Time",
		"Location"
	);

	protected function sendRequest($method, $endpoint, $params)
	{
		try
		{
			$request = $this->buildApiRequest($method, $endpoint, $params);

			$response = curl_exec($request);

			// Validate CURL status
			if(curl_errno($request))
			{
				$response = '{"status":500,"message":'.curl_errno($request).'}';
			}

			// Currently there's no need for this.
			// // Validate HTTP status code (user/password credential issues)
			// $status_code = curl_getinfo($request, CURLINFO_HTTP_CODE);
			// if ($status_code != 200)
			// {
			//
			// }
			// else
			// {
			//
			// }
		}
		catch(Exception $ex)
		{
		    if ($request != null) { curl_close($request); }
		    throw new Exception($ex);
		}

		if ($request != null) { curl_close($request); }

		return json_decode($response, true);
	}

	/**
	 * Build API request
	 *
	 * @param
	 * @return cURL handle
	 */
	protected function buildApiRequest($method, $endpoint, $params = array())
	{
		$settings = Craft::$app->plugins->getPlugin('hobsons-radius')->getSettings();

		$url = rtrim($settings->apiBaseUrl, '/') . $endpoint;
		$username = $settings->webServiceUsername;
		$password = $settings->webServicePassword;

		$options = array(
			CURLOPT_URL							=> $url,
			CURLOPT_HTTPHEADER			=> array(
				'Content-Type: application/json',
				'Connection: Keep-Alive'
			),
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_USERPWD					=> $username . ":" . $password,
			CURLOPT_HTTPAUTH				=> CURLAUTH_DIGEST
		);

		if (strtoupper($method) == 'POST')
		{
			$options[CURLOPT_URL] = $url;
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = json_encode($params);
		}
		else
		{
			$query = http_build_query($params);
			$query = empty($query) ? '' : "?{$query}";
			$options[CURLOPT_URL] = "{$url}{$query}";
		}

		$ch = curl_init();

		curl_setopt_array( $ch, $options );
		
		return $ch;
	}

	protected static function camelCase($str, $noStrip = array())
	{
		$str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
		$str = trim($str);
		// uppercase the first character of each word
		$str = ucwords($str);
		$str = str_replace(" ", "", $str);
		$str = lcfirst($str);

		return $str;
	}

	protected function camelCaseKeys($arr)
	{
		$retArr = array();

		foreach ($arr as $key => $value) {
			$tag = self::camelCase($key);
			$retArr[$tag] = $value;
		}

		return $retArr;
	}

	protected function resolveDateValues($entity, $dateFields)
	{
		foreach ($dateFields as $field)
		{
			$date = $entity[$field];
			$datetime = null;

			if(strpos($date, 'null') === false)
			{
				//$datetime = (new DateTime($date))->format(DateTime::ATOM);
				try {
					$parts = strptime($date, '%m/%d/%Y %l:%M %p');
					$year = 1900 + $parts['tm_year'];
					$mon = 1 + $parts['tm_mon'];
					$day = $parts['tm_mday'];
					$hour = $parts['tm_hour'];
					$min = $parts['tm_min'];

					// Add leading zeros
					$lmon = (int)$mon < 10 ? "0{$mon}" : $mon;
					$lday = (int)$day < 10 ? "0{$day}" : $day;
					$lhour = (int)$hour < 10 ? "0{$hour}" : $hour;
					$lmin = (int)$min < 10 ? "0{$min}" : $min;

					$datetime = "{$year}-{$lmon}-{$lday}T{$lhour}:{$lmin}";
				} catch (Exception $e) {
					$datetime = strftime('%Y-%m-%dT%H:%M');
				}
			}
			else
			{
				//$datetime = (new DateTime())->format(DateTime::ATOM);
				$datetime = strftime('%Y-%m-%dT%H:%M');
			}

			$entity[$field] = $datetime;
		}

		return $entity;
	}

	protected function parseResponse($response)
	{
		try {

			if (isset($response['payload']))
			{
				$response['payload'] = $this->camelCaseKeys($response['payload']);
				$entities = $response['payload']['entities'];
				$parsedEntities = array();

				if (isset($entities) && !empty($entities))
				{
					foreach ($entities as $entity)
					{
						$parsedEntity = $this->camelCaseKeys($entity);
						$parsedEntity = $this->resolveDateValues($parsedEntity, array(
							'startDateTime',
							'endDateTime',
							'registrationCutOffDate'
						));
						$parsedEntities[] = $parsedEntity;
					}
				}

				$response['payload']['entities'] = $parsedEntities;
			}

		} catch (Exception $ex) {

		}

		return $response;
	}

	/**
	 * Gets the field map by the module name
	 *
	 * @param String module ex: Events
	 * @param Array query
	 */
	protected function getFieldMap($module)
	{
		return array();
	}

	/**
	 * Get fields to display
	 *
	 * @param String module ex: Events
	 * @param Array query
	 */
	protected function getFields($module, $details = false)
	{
		$params = $details ? array('includeDetails' => true) : array();
		$endpoint = "/modules/{$module}/fields";
		$response = $this->sendRequest('GET', $endpoint, $params);
		$fields = $response['payload'];
		return $fields;
	}

	protected function hasParam($params, $key)
	{
		return is_array($params) && array_key_exists($key, $params);
	}

	protected function getParam($params, $key, $default = null)
	{
		if ($this->hasParam($params, $key))
		{
			$param = $params[$key];
			return isset($param) ? $param : $default;
		}
		
		return $default;
	}

	/**
	 * Build query string form param array
	 *
	 * @param Array params
	 */
	protected function buildSearchQueryString($params)
	{
		$searchKeys = array(
			'page' => 1,
			'pageSize' => 50,
			'queryId' => null
		);
		$query = array();

		if (!isset($params['queryId']))
		{
			if (isset($params['pageSize']))
			{
				$query['pageSize'] = $params['pageSize'];
			}
			else
			{
				$query['pageSize'] = $searchKeys['pageSize'];
			}
		}
		else
		{
			foreach ($searchKeys as $key => $value) {
				
				if (isset($params[$key]))
				{
					$query[$key] = $params[$key];
				}
				elseif (isset($value))
				{
					$query[$key] = $value;
				}
			}
		}

		$queryStr = http_build_query($query);

		return empty($queryStr) ? '' : "?{$queryStr}";
	}

	protected function buildSearchBody($module, $params)
	{
		$requiredKeys = array('returnFields', 'newerThan');
		$bodyKeys = array_merge($requiredKeys, array(
			'searchFields',	'updatedSince'
		));
		$body = array();

		foreach ($bodyKeys as $key) {
			if (isset($params[$key]))
			{
				$body[$key] = $params[$key];
			}
		}

		if (!isset($body['returnFields']))
		{
			$body['returnFields'] = $this->getFields($module);
		}

		if (!isset($body['newerThan']))
		{
			$date = (new DateTime())->modify('-1 year');
			$body['newerThan'] = $date->format('n/j/Y h:i A');
		}

		return $body;
	}

	// protected function getCachedSearch($module)
	// {
	// 	return Craft::$app->cache->get($module);
	// }

	// protected function setCachedSearch($module, $value)
	// {
	// 	Craft::$app->cache->set($module, $value, 86400);
	// }

	/**
	 * Searching module entries
	 *
	 * @param String module - ex: Events
	 * @param Array query
	 * @param String format - ex: 'json'
	 */
	public function search($module, $params = array(), $format = null)
	{
		// $response = $this->getCachedSearch($module);

		$response = null;

		if ($response == false)
		{
			$endpoint = "/modules/{$module}/search";
			$body = $this->buildSearchBody($module, $params);
			$query = $this->buildSearchQueryString($params);
			$url = "{$endpoint}{$query}";
			$response = $this->parseResponse(
				$this->sendRequest('POST', $url, $body)
			);
			$payload = $response['payload'];
			$totalPages = (int)$payload['totalPages'];

			if ($totalPages > 1)
			{
				for ($p = 2; $p <= $totalPages; $p++)
				{
					$query = $this->buildSearchQueryString(array(
						'page' => $p,
						'pageSize' => $this->getParam($params, 'pageSize', 50),
						'queryId' => $payload['queryId']
					));

					$url = "{$endpoint}{$query}";
					$nextResponse = $this->parseResponse(
						$this->sendRequest('POST', $url, $body)
					);

					if (isset($nextResponse['payload']))
					{
						if (isset($nextResponse['payload']['entities']))
						{
							$entities = $nextResponse['payload']['entities'];
							$payload['entities'] = array_merge($payload['entities'], $entities);
						}
					}
				}
			}

			$response['payload'] = $payload;
			// $this->setCachedSearch($module, $response);
		}

		if ($format == 'json')
		{
			return Template::raw(json_encode($response));
		}
		else
		{
			return $response;
		}
	}
}
