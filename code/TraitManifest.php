<?php

namespace sgn;

class TraitManifest extends \SS_ClassManifest {
	protected $traits = array();

	/**
	 * @return TokenisedRegularExpression
	 */
	public static function get_trait_parser() {
		return new \TokenisedRegularExpression(array(
			0 => T_TRAIT,
			1 => T_WHITESPACE,
			2 => array(T_STRING, 'save_to' => 'traitName')
		));
	}

	/**
	 * Constructs and initialises a new trait manifest, either loading the data
	 * from the cache or re-scanning for traits.
	 *
	 * @param string $base The manifest base path.
	 * @param bool   $forceRegen Force the manifest to be regenerated.
	 */
	public function __construct($base, $forceRegen = false) {
		parent::__construct($base, false, $forceRegen, true);

		$cacheClass = defined('SS_MANIFESTCACHE') ? SS_MANIFESTCACHE : 'ManifestCache_File';

		$this->cache = new $cacheClass('traitmanifest');
		$this->cacheKey = 'traits';

		if (!$forceRegen && $data = $this->cache->load($this->cacheKey)) {
			$this->traits = $data['traits'];
		} else {
			$this->regenerateTrait(true);
		}
	}

	/**
	 * Returns the file path to a class or interface if it exists in the
	 * manifest.
	 *
	 * @param  string $name
	 * @return string|null
	 */
	public function getItemPath($name) {
		$name = strtolower($name);

		if (isset($this->traits[$name])) {
			return $this->traits[$name];
		}
	}

	/**
	 * Completely regenerates the manifest file.
	 *
	 * @param bool $cache Cache the result.
	 */
	public function regenerateTrait($cache = true) {
		$this->traits = array();

		$finder = new \ManifestFileFinder();
		$finder->setOptions(array(
			'name_regex'    => '/^(_config.php|[^_].*\.php)$/',
			'ignore_files'  => array('index.php', 'main.php', 'cli-script.php', 'SSTemplateParser.php'),
			'ignore_tests'  => false,
			'file_callback' => array($this, 'handleTraitFile'),
		));
		$finder->find($this->base);

		if ($cache) {
			$data = array(
				'traits' => $this->traits
			);
			$this->cache->save($data, $this->cacheKey);
		}
	}

	public function handleTraitFile($basename, $pathname, $depth) {
		$traits = null;

		// The results of individual file parses are cached, since only a few
		// files will have changed and TokenisedRegularExpression is quite
		// slow. A combination of the file name and file contents hash are used,
		// since just using the datetime lead to problems with upgrading.
		$file = file_get_contents($pathname);
		$key = preg_replace('/[^a-zA-Z0-9_]/', '_', $basename) . '_' . md5($file);

		if ($data = $this->cache->load($key)) {
			$valid = isset($data['traits']) && isset($data['namespace']) &&
						is_array($data['traits']) && is_string($data['namespace']);

			if ($valid) {
				$traits = $data['traits'];
				$namespace = $data['namespace'];
			}
		}

		if (!$traits) {
			$tokens = token_get_all($file);
			
			$namespace = self::get_namespace_parser()->findAll($tokens);
			if($namespace) {
				$namespace = implode('', $namespace[0]['namespaceName']) . '\\';
			} else {
				$namespace = '';
			}

			$traits = self::get_trait_parser()->findAll($tokens);

			$cache = array('traits' => $traits, 'namespace' => $namespace);
			$this->cache->save($cache, $key);
		}

		foreach ($traits as $trait) {
			$this->traits[strtolower($namespace . $trait['traitName'])] = $pathname;
		}
	}
}
