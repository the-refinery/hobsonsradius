# HobsonsRadius plugin for Craft CMS 3.x

Integration with HobsonsRadius

## Requirements

This plugin requires Craft CMS 3.0.0 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Manually add the repository to your composer.json since this plugin is not listed on packagist

		"repositories": [
			{
				"type": "git",
				"url": "https://github.com/the-refinery/hobsonsradius.git",
				"reference": "origin/master"
			}
		]

3. Then install the plugin:

		composer require therefinery/hobsons-radius

4. In the Control Panel, go to Settings → Plugins and click the “Install” button for HobsonsRadius.