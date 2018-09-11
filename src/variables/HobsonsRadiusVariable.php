<?php
/**
 * HobsonsRadius plugin for Craft CMS 3.x
 *
 * Integration with HobsonsRadius
 *
 * @link      http://the-refinery.io
 * @copyright Copyright (c) 2018 The Refinery
 */

namespace therefinery\hobsonsradius\variables;

use Craft;

use therefinery\hobsonsradius\HobsonsRadius as Plugin;
use therefinery\hobsonsradius\services\HobsonsRadiusService;

/**
 * HobsonsRadius Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.hobsonsRadius }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    The Refinery
 * @package   HobsonsRadius
 * @since     2.0.0
 */
class HobsonsRadiusVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Whatever you want to output to a Twig template can go into a Variable method.
     * You can have as many variable functions as you want.  From any Twig template,
     * call it like this:
     *
     *     {{ craft.hobsonsRadius.exampleVariable }}
     *
     * Or, if your variable requires parameters from Twig:
     *
     *     {{ craft.hobsonsRadius.exampleVariable(twigValue) }}
     *
     * @param null $optional
     * @return string
     */

	public function searchvariable($module, $params = array())
	{
		$format = null;

		if (array_key_exists('format', $params))
		{
			$format = $params['format'];
			unset($params['format']);
		}

		// return craft()->hobsonsRadius->search($module, $params, $format);
		return HobsonsRadiusService::search($module, $params, $format);
	}
}
