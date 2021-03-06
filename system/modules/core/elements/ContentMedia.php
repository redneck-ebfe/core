<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace Contao;


/**
 * Class ContentMedia
 *
 * Content element "mediaelement".
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    Core
 */
class ContentMedia extends \ContentElement
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_player';

	/**
	 * Files object
	 * @var \FilesModel
	 */
	protected $objFiles;


	/**
	 * Extend the parent method
	 * @return string
	 */
	public function generate()
	{
		if ($this->playerSRC == '')
		{
			return '';
		}

		$source = deserialize($this->playerSRC);

		if (!is_array($source) || empty($source))
		{
			return '';
		}

		$objFiles = \FilesModel::findMultipleByUuidsAndExtensions($source, array('mp4','m4v','mov','wmv','webm','ogv','m4a','mp3','wma','mpeg','wav','ogg'));

		if ($objFiles === null)
		{
			return '';
		}

		// Display a list of files in the back end
		if (TL_MODE == 'BE')
		{
			$return = '<ul>';

			while ($objFiles->next())
			{
				$objFile = new \File($objFiles->path, true);
				$return .= '<li><img src="' . TL_ASSETS_URL . 'assets/contao/images/' . $objFile->icon . '" width="18" height="18" alt="" class="mime_icon"> <span>' . $objFile->name . '</span> <span class="size">(' . $this->getReadableSize($objFile->size) . ')</span></li>';
			}

			return $return . '</ul>';
		}

		$this->objFiles = $objFiles;
		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		global $objPage;
		$this->Template->size = '';

		// Set the size
		if ($this->playerSize != '')
		{
			$size = deserialize($this->playerSize);

			if (is_array($size))
			{
				$this->Template->size = ' width="' . $size[0] . '" height="' . $size[1] . '"';
			}
		}

		$this->Template->poster = false;

		// Optional poster
		if ($this->posterSRC != '')
		{
			if (($objFile = \FilesModel::findByUuid($this->posterSRC)) !== null)
			{
				$this->Template->poster = $objFile->path;
			}
		}

		// Pre-sort the array by preference
		if (in_array($this->objFiles->extension , array('mp4','m4v','mov','wmv','webm','ogv')))
		{
			$this->Template->isVideo = true;
			$arrFiles = array('mp4'=>null, 'm4v'=>null, 'mov'=>null, 'wmv'=>null, 'webm'=>null, 'ogv'=>null);
		}
		else
		{
			$this->Template->isVideo = false;
			$arrFiles = array('m4a'=>null, 'mp3'=>null, 'wma'=>null, 'mpeg'=>null, 'wav'=>null, 'ogg'=>null);
		}

		$this->objFiles->reset();

		// Convert the language to a locale (see #5678)
		$strLanguage = str_replace('-', '_', $objPage->language);

		// Pass File objects to the template
		while ($this->objFiles->next())
		{
			$arrMeta = deserialize($this->objFiles->meta);

			if (is_array($arrMeta) && isset($arrMeta[$strLanguage]))
			{
				$strTitle = $arrMeta[$strLanguage]['title'];
			}
			else
			{
				$strTitle = $this->objFiles->name;
			}

			$objFile = new \File($this->objFiles->path, true);
			$objFile->title = specialchars($strTitle);

			$arrFiles[$objFile->extension] = $objFile;
		}

		$this->Template->files = array_values(array_filter($arrFiles));
		$this->Template->autoplay = $this->autoplay;
	}
}
