<?php
/**
* @package   Widgetkit Component
* @file      installer.php
* @version   1.0.0 BETA 8 August 2011
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) 2007 - 2011 YOOtheme GmbH
* @license   YOOtheme Proprietary Use License (http://www.yootheme.com/license)
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

/*
	Class: InstallerScript
		Installer script
*/
class InstallerScript {

	public function install($parent) {

		// init vars
		$installer  = $parent->parent;
		$extensions = $this->getAdditionalExtensions($installer);

		// install additional extensions
		foreach ($extensions as $extension) {
			
			if (!$extension->install()) {

				// rollback on installation errors
				$installer->abort(JText::_('Component').' '.JText::_('Install').': '.JText::_('Error'), 'component');
				foreach ($extensions as $extension) {
					if ($extension->status) {
						$extension->abort();
					}
				}

				break;
			}
			
			if ($extension->type == 'plugin') {
				$extension->enable();
			}
			
		}

		// display table
		if ($extensions) {
			self::displayAdditionalExtensions($extensions);
		}
	}

	public function uninstall($parent) {

		// init vars
		$installer  = $parent->parent;
		$extensions = $this->getAdditionalExtensions($installer);

		// uninstall additional extensions
		foreach ($extensions as $extension) {
			$extension->uninstall();
		}

		// display table
		if ($extensions) {
			self::displayAdditionalExtensions($extensions);
		}
	}

	public function update($parent) {
		return $this->install($parent);
	}

	public function preflight($type, $parent) {}

	public function postflight($type, $parent) {}

	protected function getAdditionalExtensions($installer) {

		// init vars
		$manifest   = simplexml_load_file($installer->getPath('manifest'));
		$extensions = array();
		
		// additional extensions
		if ($additional = $manifest->xpath('additional/*')) {
			foreach ($additional as $data) {
				$extensions[] = new AdditionalExtension($installer, $data);
			}
		}

		return $extensions;
	}
	
	public static function displayAdditionalExtensions($extensions) {
		?>
		<table class="adminlist">
			<thead>
				<tr>
					<th class="title"><?php echo JText::_('Extension'); ?></th>
					<th width="60%"><?php echo JText::_('Status'); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
			</tfoot>
			<tbody>
				<?php
					foreach ($extensions as $i => $extension) : ?>
					<tr class="row<?php echo $i % 2; ?>">
						<td class="key"><?php echo $extension->name; ?></td>
						<td>
							<?php $style = $extension->status ? 'font-weight: bold; color: green;' : 'font-weight: bold; color: red;'; ?>
							<span style="<?php echo $style; ?>"><?php echo $extension->message; ?></span>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}	

}

/*
	Class: AdditionalExtension
		Additional extension class
*/
class AdditionalExtension {

	public $name;
	public $element;
	public $type;
	public $status;
	public $message;
	public $data;
	public $parent;
	public $installer;
	public $database;

	public function __construct($parent, $data) {
		
		// init vars
		$this->name = (string) $data;
		$this->element = (string) $data->attributes()->name;
		$this->type = $data->getName();
		$this->status = false;
		$this->data = $data;
		$this->parent = $parent;
		$this->installer = new JInstaller();
		$this->database = JFactory::getDBO();
		
	}
	
	public function install() {

		// set message
		if ($this->status = $this->installer->install($this->parent->getPath('source').'/'.$this->data->attributes()->folder)) {
			$this->message = JText::_('Installed successfully');
		} else {
			$this->message = JText::_('NOT Installed');
		}

		return $this->status;
	}

	public function uninstall() {

		// get extension id and client id
		$result    = $this->load();
		$ext_id    = isset($result->id) ? $result->id : 0;
		$client_id = isset($result->client_id) ? $result->client_id : 0;

		// set message
		if ($this->status = $ext_id > 0 && $this->installer->uninstall($this->type, $ext_id, $client_id)) {
			$this->message = JText::_('Uninstalled successfully');
		} else {
			$this->message = JText::_('Uninstall FAILED');
		}

		return $this->status;
	}

	public function abort() {
		$this->installer->abort(JText::_($this->type).' '.JText::_('Install').': '.JText::_('Error'), $this->type);
		$this->status = false;
	}

	public function load() {

		// set queries
		$query['plugin'] = "SELECT * FROM #__plugins WHERE element='%s'";
		$query['module'] = "SELECT * FROM #__modules WHERE module='%s'";
		
		$this->database->setQuery(sprintf($query[$this->type], $this->element));
		return $this->database->loadObject();
	}

	public function enable() {
		$this->database->setQuery(sprintf("UPDATE #__plugins SET published=1 WHERE element='%s'", $this->element));
		$this->database->query();
	}
	
}