<?php
/*
 * This file is part of the Appejoom project.
 *
 * (c) Belkacem Alidra <belkacem.alidra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This class is based on the TiCons project of Fokke Zandbergen, see https://github.com/FokkeZB/TiCons
 * which is Licensed under the Apache License, Version 2.0 (the "License")
 */

namespace Econcepto\PHPTi\Medias;

use Exception;
use Imagick;
use ImagickPixel;
use InvalidArgumentException;

define('ICON_PATH', 0);
define('ICON_SIZE', 1);
define('ICON_DPI', 2);
define('ICON_RADIUS', 3);
define('SPLASH_PATH', 0);
define('SPLASH_WIDTH', 1);
define('SPLASH_HEIGHT', 2);
define('SPLASH_DPI', 3);
define('SPLASH_ROTATE', 4);

/**
 * Class PhpTIcons
 *
 * Generates Titanium icons and splash screens, and put them in the application project directory
 * No zip creation !
 *
 * Based on https://github.com/FokkeZB/TiCons
 *
 * @package Econcepto\PHPTi\Medias
 */
class PhpTIcons
{
	/**
	 * Increasing level for OptiPNG compression resulting in 10% to 30% reduction on all PNG's
	 * and set a compression quality percentage ranging from 80% to 50% on JPEG's.
	 */
	const COMPRESSION_NONE      = 100;
	const COMPRESSION_LOW       = 80;
	const COMPRESSION_MEDIUM    = 65;
	const COMPRESSION_HIGH      = 50;

	/**
	 * Platforms for which to generate the images
	 */
	const PLATFORM_IPHONE       = 'iphone';
	const PLATFORM_IPAD         = 'ipad';
	const PLATFORM_ANDROID      = 'android';
	const PLATFORM_MOBILEWEB    = 'mobileweb';
	const PLATFORM_BLACKBERRY   = 'blackberry';
	const PLATFORM_TIZEN        = 'tizen';

	/**
	 * Orientations for which to generate the images
	 */
	const ORIENTATION_PORTRAIT  = 'portrait';
	const ORIENTATION_LANDSCAPE = 'landscape';

	/**
	 * 1024x1024 PNG with no rounded corners or transparency
	 *
	 * @var string
	 */
	protected $icon;

	/**
	 * Alternative 512x512 PNG icon for Android, Mobile Web, BlackBerry and Tizen.
	 * These platforms do not apply any default effects and promote using transparency to create unique shapes.
	 *
	 * @var string
	 */
	protected $iconTransparent;

	/**
	 * 2208x2208 PNG where the logo or other important artwork is placed within the center 1000x1000 pixels or so.
	 *
	 * @var string
	 */
	protected $splash;

	/**
	 * Percentage between 0 and 50 for a border radius to apply to the default icon for Android.
	 *
	 * @var int
	 */
	protected $radius;

	/**
	 * ISO 639-1 language code to write iOS and Android splash screens to localized paths.
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * Increasing level for OptiPNG compression resulting in 10% to 30% reduction on all PNG's
	 * and set a compression quality percentage ranging from 80% to 50% on JPEG's.
	 *
	 * @var string
	 */
	protected $compression;

	/**
	 * Platforms for which to generate the images
	 *
	 * @var array
	 */
	protected $platforms;

	/**
	 * Orientations for which to generate the images
	 *
	 * @var array
	 */
	protected $orientations;

	/**
	 * Conforms to Apple's specs for launch images rather than Appcelerator's.
	 * This fixes the splash-shift caused by differences in iPad and iPhone 4 portrait dimensions.
	 *
	 * @var boolean
	 */
	protected $apple;

	/**
	 * Writes to app/assets instead of Resources.
	 *
	 * @var boolean
	 */
	protected $alloy;

	/**
	 * Output directory
	 *
	 * @var string
	 */
	protected $outputDir;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this
			->setAlloy(true)
			->setApple(true)
			->setCompression(self::COMPRESSION_MEDIUM)
			->setLanguage('')
			->setOrientations([self::ORIENTATION_PORTRAIT, self::ORIENTATION_LANDSCAPE])
			->setPlatforms([self::PLATFORM_IPHONE, self::PLATFORM_IPAD, self::PLATFORM_ANDROID])
			->setOutputDir(__DIR__.'/output')
			->setRadius(0);
	}

	/**
	 * Generates the application icons based on the $icon file
	 *
	 * @param string $icon
	 * @param string $iconTransparent
	 *
	 * @return boolean True on succes, False otherwise
	 */
	public function icons($icon = null, $iconTransparent = null)
	{
		if (!empty($icon))
		{
			$this->setIcon($icon);
		}

		if (!empty($iconTransparent))
		{
			$this->setIconTransparent($iconTransparent);
		}

		try
		{
			set_time_limit(0);

			if (empty($this->icon))
			{
				throw new Exception('Missing icon file');
			}

			if (empty($this->platforms))
			{
				throw new Exception('Select at least one platform.');
			}

			if (empty($this->orientations))
			{
				throw new Exception('Select at least one orientation.');
			}

			$assets_path    = $this->isAlloy() ? '/app/assets' : '/Resources';
			$compress       = array();
			$sizes          = array();

			// iPhone & iPad
			if (in_array('iphone', $this->platforms) || in_array('ipad', $this->platforms))
			{
				// iTunes Connect
				$sizes[] = array('/iTunesConnect.png', 1024, 72);
				// iTunes Artwork
				$sizes[] = array($assets_path . '/iphone/iTunesArtwork', 512, 72);
				$sizes[] = array($assets_path . '/iphone/iTunesArtwork@2x', 1024, 72);
				// Spotlight & Settings
				$sizes[] = array($assets_path . '/iphone/appicon-Small@2x.png', 58, 72);
				// Spotlight (iOS7)
				$sizes[] = array($assets_path . '/iphone/appicon-Small-40.png', 40, 72);
				$sizes[] = array($assets_path . '/iphone/appicon-Small-40@2x.png', 80, 72);
				// App (default)
				$sizes[] = array($assets_path . '/iphone/appicon.png', 57, 72);
				// iPhone
				if (in_array('iphone', $this->platforms))
				{
					// App
					$sizes[] = array($assets_path . '/iphone/appicon@2x.png', 114, 72);
					// Spotlight && Settings
					$sizes[] = array($assets_path . '/iphone/appicon-Small.png', 29, 72);

					// Settings (iPhone 6 Plus)
					$sizes[] = array($assets_path . '/iphone/appicon-Small@3x.png', 87, 72);
					// App (iOS7)
					$sizes[] = array($assets_path . '/iphone/appicon-60.png', 60, 72);
					$sizes[] = array($assets_path . '/iphone/appicon-60@2x.png', 120, 72);
					$sizes[] = array($assets_path . '/iphone/appicon-60@3x.png', 180, 72);
				}
				// iPad
				if (in_array('ipad', $this->platforms))
				{
					// App
					$sizes[] = array($assets_path . '/iphone/appicon-72.png', 72, 72);
					$sizes[] = array($assets_path . '/iphone/appicon-72@2x.png', 144, 72);
					// Spotlight && Settings
					$sizes[] = array($assets_path . '/iphone/appicon-Small-50.png', 50, 72);
					$sizes[] = array($assets_path . '/iphone/appicon-Small-50@2x.png', 100, 72);
					// App (iOS7)
					$sizes[] = array($assets_path . '/iphone/appicon-76.png', 76, 72);
					$sizes[] = array($assets_path . '/iphone/appicon-76@2x.png', 152, 72);
				}
			}

			foreach ($sizes as $size)
			{
				$file       = $this->outputDir . $size[ICON_PATH];
				$dir        = dirname($file);
				$compress[] = $file;

				if (is_dir($dir) == false)
				{
					mkdir($dir, 0777, true);
				}

				$image = new Imagick();
				$image->setResolution($size[ICON_DPI], $size[ICON_DPI]);
				$image->readImage($this->icon);
				$image->setImageFormat('png');
				$image->cropThumbnailImage($size[ICON_SIZE], $size[ICON_SIZE]);
				$image->setImageResolution($size[ICON_DPI], $size[ICON_DPI]);
				$image->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);

				$image->writeImage($file);
			}

			$hasIconTrans   = !empty($this->iconTransparent);
			$icon           = $hasIconTrans ? $this->iconTransparent : $this->icon;
			$sizes          = array();

			// Android
			if (in_array('android', $this->platforms))
			{
				$sizes[] = array('' . $assets_path . '/android/appicon.png', 128, 72, !$hasIconTrans);
				$sizes[] = array('/platform/android/res/drawable-ldpi/appicon.png', 36, 120, !$hasIconTrans);
				$sizes[] = array('/platform/android/res/drawable-mdpi/appicon.png', 48, 160, !$hasIconTrans);
				$sizes[] = array('/platform/android/res/drawable-hdpi/appicon.png', 72, 240, !$hasIconTrans);
				$sizes[] = array('/platform/android/res/drawable-xhdpi/appicon.png', 96, 320, !$hasIconTrans);
				$sizes[] = array('/platform/android/res/drawable-xxhdpi/appicon.png', 144, 480, !$hasIconTrans);
				$sizes[] = array('/platform/android/res/drawable-xxxhdpi/appicon.png', 192, 640, !$hasIconTrans);
				$sizes[] = array('/GooglePlay.png', 512, 72, !$hasIconTrans);
			}
			// Mobile Web
			if (in_array('mobileweb', $this->platforms))
			{
				$sizes[] = array($assets_path . '/mobileweb/appicon.png', 128, 72);
			}
			// Tizen
			if (in_array('tizen', $this->platforms))
			{
				$sizes[] = array($assets_path . '/tizen/appicon.png', 96, 72);
			}
			// BlackBerry
			if (in_array('blackberry', $this->platforms))
			{
				$sizes[] = array($assets_path . '/blackberry/appicon.png', 114, 72);
			}

			foreach ($sizes as $size)
			{
				$file       = $this->outputDir . $size[ICON_PATH];
				$dir        = dirname($file);
				$compress[] = $file;

				if (is_dir($dir) == false)
				{
					mkdir($dir, 0777, true);
				}

				$image = new Imagick();
				$image->setResolution($size[ICON_DPI], $size[ICON_DPI]);
				$image->readImage($icon);
				$image->setImageFormat('png');
				$image->cropThumbnailImage($size[ICON_SIZE], $size[ICON_SIZE]);

				if ($size[ICON_RADIUS] && $this->radius > 0)
				{
					$px = round(($size[ICON_SIZE] / 100) * $this->radius);
					$image->roundCorners($px, $px);
				}

				$image->setImageResolution($size[ICON_DPI], $size[ICON_DPI]);
				$image->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);
				$image->writeImage($file);
			}

			if ($this->compression < 100)
			{
				$o = 2;
				switch ($this->compression)
				{
					case self::COMPRESSION_LOW:
						$o = 1;
						break;
					case self::COMPRESSION_MEDIUM:
						$o = 2;
						break;
					case self::COMPRESSION_HIGH:
						$o = 3;
						break;
				}
				shell_exec('optipng -v -o ' . $o . ' "' . implode('" "', $compress) . '"');
			}
		}
		catch (Exception $e)
		{
			return false;
			//$error = $e->getMessage();
		}

		return true;
	}

	/**
	 * Generates the application splash screens based on the $splash file
	 *
	 * @param string $splash
	 *
	 * @return boolean True on succes, False otherwise
	 */
	public function splash($splash = null)
	{
		if (!empty($splash))
		{
			$this->setSplash($splash);
		}

		try
		{
			set_time_limit(0);

			if (empty($this->splash))
			{
				throw new Exception('Missing icon file');
			}

			if (empty($this->platforms))
			{
				throw new Exception('Select at least one platform.');
			}

			if (empty($this->orientations))
			{
				throw new Exception('Select at least one orientation.');
			}

			$assets_path    = $this->isAlloy() ? '/app/assets' : '/Resources';
			$compress       = array();
			$sizes          = array();
			$ios_path       = !empty($this->language) ? '/i18n/' . $this->language : $assets_path . '/iphone';
			$android_prefix = !empty($this->language) ? $this->language . '-' : '';

			// iPhone
			if (in_array('iphone', $this->platforms))
			{
				$sizes[] = array($ios_path . '/Default.png', 320, $this->isApple() ? 480 : 460, 72);
				$sizes[] = array($ios_path . '/Default@2x.png', 640, 960, 72);
				$sizes[] = array($ios_path . '/Default-568h@2x.png', 640, 1136, 72);

				// iPhone 6
				$sizes[] = array($ios_path . '/Default-667h@2x.png', 750, 1334, 72);
				$sizes[] = array($ios_path . '/Default-Portrait-736h@3x.png', 1242, 2208, 72);
				$sizes[] = array($ios_path . '/Default-Landscape-736h@3x.png', 2208, 1242, 72);
			}
			// iPad
			if (in_array('ipad', $this->platforms))
			{
				$sizes[] = array($ios_path . '/Default-Landscape.png', 1024, $this->isApple() ? 768 : 748, 72);
				$sizes[] = array($ios_path . '/Default-Portrait.png', 768, $this->isApple() ? 1024 : 1044, 72);
				$sizes[] = array($ios_path . '/Default-Landscape@2x.png', 2048, $this->isApple() ? 1536 : 1496, 72);
				$sizes[] = array($ios_path . '/Default-Portrait@2x.png', 1536, $this->isApple() ? 2048 : 2008, 72);
			}
			// Android
			if (in_array('android', $this->platforms))
			{
				$sizes[] = array('/GooglePlayFeature.png', 1024, 500, 72);
				$sizes[] = array($assets_path . '/android/default.png', 320, 480, 72);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-land-xxxhdpi/default.png', 1920, 1280, 640);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-land-xxhdpi/default.png', 1600, 960, 480);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-land-xhdpi/default.png', 960, 640, 320);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-land-hdpi/default.png', 800, 480, 240);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-land-mdpi/default.png', 480, 320, 160);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-land-ldpi/default.png', 400, 240, 120);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-port-xxxhdpi/default.png', 1280, 1920, 640);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-port-xxhdpi/default.png', 960, 1600, 480);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-port-xhdpi/default.png', 640, 960, 320);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-port-hdpi/default.png', 480, 800, 240);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-port-mdpi/default.png', 320, 480, 160);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'long-port-ldpi/default.png', 240, 400, 120);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-xxxhdpi/default.png', 1920, 1280, 640);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-xxhdpi/default.png', 1600, 960, 480);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-xhdpi/default.png', 960, 640, 320);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-hdpi/default.png', 800, 480, 240);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-mdpi/default.png', 480, 320, 160);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-land-ldpi/default.png', 320, 240, 120);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-xxxhdpi/default.png', 1280, 1920, 320);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-xxhdpi/default.png', 960, 1600, 320);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-xhdpi/default.png', 640, 960, 320);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-hdpi/default.png', 480, 800, 240);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-mdpi/default.png', 320, 480, 160);
				$sizes[] = array($assets_path . '/android/images/res-' . $android_prefix . 'notlong-port-ldpi/default.png', 240, 320, 120);
			}
			// Mobile Web
			if (in_array('mobileweb', $this->platforms))
			{
				$sizes[] = array($assets_path . '/mobileweb/apple_startup_images/Default.jpg', 320, 460, 72);
				$sizes[] = array($assets_path . '/mobileweb/apple_startup_images/Default.png', 320, 460, 72);
				$sizes[] = array($assets_path . '/mobileweb/apple_startup_images/Default-Landscape.jpg', 748, 1024, 72, 90);
				$sizes[] = array($assets_path . '/mobileweb/apple_startup_images/Default-Landscape.png', 748, 1024, 72, 90);
				$sizes[] = array($assets_path . '/mobileweb/apple_startup_images/Default-Portrait.jpg', 768, 1004, 72);
				$sizes[] = array($assets_path . '/mobileweb/apple_startup_images/Default-Portrait.png', 768, 1004, 72);
			}
			// BlackBerry
			if (in_array('blackberry', $this->platforms))
			{
				// same name, only fix size
				$sizes[] = array($assets_path . '/blackberry/splash-600x1024.png', 768, 1280, 72);
				// Q10 / Q5 support
				$sizes[] = array($assets_path . '/blackberry/splash-720x720.png', 720, 720, 72);
				// maybe Appc rename it in the future
				//$sizes[] = array( $assets_path . '/blackberry/splash-768x1280.png', 768, 1280, 72 );
			}

			$portrait   = in_array('portrait', $this->orientations);
			$landscape  = in_array('landscape', $this->orientations);

			foreach ($sizes as $size)
			{
				if ((!$portrait && $size[SPLASH_WIDTH] < $size[SPLASH_HEIGHT]) ||
					(!$landscape && $size[SPLASH_WIDTH] > $size[SPLASH_HEIGHT]))
				{
					continue;
				}

				$file   = $this->outputDir . $size[ICON_PATH];
				$dir    = dirname($file);

				if (is_dir($dir) == false)
				{
					mkdir($dir, 0777, true);
				}

				$ext    = substr($size[SPLASH_PATH], strrpos($size[SPLASH_PATH], '.') + 1);
				$image  = new Imagick();
				$image->setResolution($size[SPLASH_DPI], $size[SPLASH_DPI]);
				$image->readImage($this->splash);
				$image->stripImage();

				if ($ext == 'jpg')
				{
					switch ($this->compression)
					{
						case self::COMPRESSION_LOW:
							$cq = 80;
							break;
						case self::COMPRESSION_MEDIUM:
							$cq = 65;
							break;
						case self::COMPRESSION_HIGH:
							$cq = 50;
							break;
						default:
							$cq = 100;
							break;
					}

					$image->setImageFormat('jpeg');
					$image->setImageCompression(Imagick::COMPRESSION_JPEG);
					$image->setImageCompressionQuality($cq);
				}
				else
				{
					$image->setImageFormat('png');
					$compress[] = $file;
				}

				if (isset($size[SPLASH_ROTATE]))
				{
					$image->rotateImage(new ImagickPixel('none'), $size[SPLASH_ROTATE]);
				}

				$image->cropThumbnailImage($size[SPLASH_WIDTH], $size[SPLASH_HEIGHT]);
				$image->setImageResolution($size[SPLASH_DPI], $size[SPLASH_DPI]);
				$image->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);

				$image->writeImage($file);
			}

			if ($this->compression < 100)
			{
				$o = 2;
				switch ($this->compression)
				{
					case self::COMPRESSION_LOW:
						$o = 1;
						break;
					case self::COMPRESSION_MEDIUM:
						$o = 2;
						break;
					case self::COMPRESSION_HIGH:
						$o = 3;
						break;
				}
				shell_exec('optipng -v -o ' . $o . ' "' . implode('" "', $compress) . '"');
			}
		}
		catch (Exception $e)
		{
			return false;
			//$error = $e->getMessage();
		}

		return true;
	}

	/**
	 * @return string
	 */
	public function getIcon()
	{
		return $this->icon;
	}

	/**
	 * @param string $icon
	 * @return PhpTIcons
	 */
	public function setIcon($icon)
	{
		if (!file_exists($icon)) {
			throw new InvalidArgumentException('File ' . $icon . ' not found');
		}

		$this->icon = $icon;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIconTransparent()
	{
		return $this->iconTransparent;
	}

	/**
	 * @param string $iconTransparent
	 * @return PhpTIcons
	 */
	public function setIconTransparent($iconTransparent)
	{
		if (!file_exists($iconTransparent)) {
			throw new InvalidArgumentException('File ' . $iconTransparent . ' not found');
		}

		$this->iconTransparent = $iconTransparent;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSplash()
	{
		return $this->splash;
	}

	/**
	 * @param string $splash
	 * @return PhpTIcons
	 */
	public function setSplash($splash)
	{
		if (!file_exists($splash)) {
			throw new InvalidArgumentException('File ' . $splash . ' not found');
		}

		$this->splash = $splash;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getRadius()
	{
		return $this->radius;
	}

	/**
	 * @param int $radius
	 * @return PhpTIcons
	 */
	public function setRadius($radius)
	{
		if ($radius < 0 || $radius > 50) {
			throw new InvalidArgumentException('Border radius should be between 0 and 50.');
		}

		$this->radius = $radius;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * @param string $language
	 * @return PhpTIcons
	 */
	public function setLanguage($language)
	{
		if (!empty($language) && !preg_match('/^[a-z]{2}$/', $language))
		{
			throw new Exception('Invalid ISO 639-1 language code.');
		}

		$this->language = $language;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCompression()
	{
		return $this->compression;
	}

	/**
	 * @param string $compression
	 * @return PhpTIcons
	 */
	public function setCompression($compression)
	{
		if (!in_array($compression, [
			self::COMPRESSION_HIGH, self::COMPRESSION_MEDIUM,
			self::COMPRESSION_LOW, self::COMPRESSION_NONE]))
		{
			throw new InvalidArgumentException('Invalid compression');
		}

		$this->compression = $compression;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getPlatforms()
	{
		return $this->platforms;
	}

	/**
	 * @param array $platforms
	 * @return PhpTIcons
	 */
	public function setPlatforms($platforms)
	{
		$this->platforms = $platforms;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getOrientations()
	{
		return $this->orientations;
	}

	/**
	 * @param array $orientations
	 * @return PhpTIcons
	 */
	public function setOrientations($orientations)
	{
		$this->orientations = $orientations;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isApple()
	{
		return $this->apple;
	}

	/**
	 * @param boolean $apple
	 * @return PhpTIcons
	 */
	public function setApple($apple)
	{
		$this->apple = $apple;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isAlloy()
	{
		return $this->alloy;
	}

	/**
	 * @param boolean $alloy
	 * @return PhpTIcons
	 */
	public function setAlloy($alloy)
	{
		$this->alloy = $alloy;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getOutputDir()
	{
		return $this->outputDir;
	}

	/**
	 * @param string $outputDir
	 * @return PhpTIcons
	 */
	public function setOutputDir($outputDir)
	{
		if (!file_exists($outputDir) && !mkdir($outputDir, 0777, true))
		{
			throw new InvalidArgumentException("Can't create temporary directory ".$outputDir);
		}

		$this->outputDir = $outputDir;
		return $this;
	}
}