<?php
/**
 * Magento Module developed by NoStress Commerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@nostresscommerce.cz so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you have special needs for this module, please
 * contact us at info@nostresscommerce.cz for more information.
 *
 * @category    Nostress
 * @package     Nostress_Gpwebpay
 * @copyright   Copyright (c) 2012 NoStress Commerce (http://www.nostresscommerce.cz)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Transaction configuration model
 *
 * @category   Nostress
 * @package    Nostress_Gpwebpay
 * @author     NoStress Commerce Team <info@nostresscommerce.cz>
 */
class Nostress_Gpwebpay_Model_Transactions_Config extends Mage_Core_Model_Config_Base
{
	/**
	* Statuses per state array
	*
	* @var array
	*/
	protected $_stateStatuses;
	
	private $_states;
	
	public function __construct() {
		parent::__construct(Mage::getConfig()->getNode('global/transactions'));
	}
	
	protected function _getStatus($status) {
		return $this->getNode('statuses/'.$status);
	}
	
	protected function _getState($state) {
		return $this->getNode('states/'.$state);
	}
	
	/**
	* Retrieve default status for state
	*
	* @param   string $state
	* @return  string
	*/
	public function getStateDefaultStatus($state) {
		$status = false;
		if ($stateNode = $this->_getState($state)) {
			$status = Mage::getModel('nostress_gpwebpay/transactions_status')
				->loadDefaultByState($state);
			$status = $status->getStatus();
		}
		return $status;
	}
	
	/**
	* Retrieve status label
	*
	* @param   string $code
	* @return  string
	*/
	public function getStatusLabel($code) {
		$status = Mage::getModel('nostress_gpwebpay/transactions_status')
			->load($code);
		return $status->getStoreLabel();
	}
	
	/**
	* State label getter
	*
	* @param   string $state
	* @return  string
	*/
	public function getStateLabel($state) {
		if ($stateNode = $this->_getState($state)) {
			$state = (string) $stateNode->label;
			return Mage::helper('nostress_gpwebpay')->__($state);
		}
		return $state;
	}
	
	/**
	* Retrieve all statuses
	*
	* @return array
	*/
	public function getStatuses() {
		$statuses = Mage::getResourceModel('nostress_gpwebpay/transactions_status_collection')
			->toOptionHash();
		return $statuses;
	}
	
	/**
	* Order states getter
	*
	* @return array
	*/
	public function getStates() {
		$states = array();
		foreach ($this->getNode('states')->children() as $state) {
			$label = (string) $state->label;
			$states[$state->getName()] = Mage::helper('nostress_gpwebpay')->__($label);
		}
		return $states;
	}
	
    /**
     * Retrieve statuses available for state
     * Get all possible statuses, or for specified state, or specified states array
     * Add labels by default. Return plain array of statuses, if no labels.
     *
     * @param mixed $state
     * @param bool $addLabels
     * @return array
     */
    public function getStateStatuses($state, $addLabels = true)
    {
        $key = $state . $addLabels;
        if (isset($this->_stateStatuses[$key])) {
            return $this->_stateStatuses[$key];
        }
        $statuses = array();
        if (empty($state) || !is_array($state)) {
            $state = array($state);
        }
        foreach ($state as $_state) {
            if ($stateNode = $this->_getState($_state)) {
                $collection = Mage::getResourceModel('nostress_gpwebpay/transactions_status_collection')
                    ->addStateFilter($_state)
                    ->orderByLabel();
                foreach ($collection as $status) {
                    $code = $status->getStatus();
                    if ($addLabels) {
                        $statuses[$code] = $status->getStoreLabel();
                    } else {
                        $statuses[] = $code;
                    }
                }
            }
        }
        $this->_stateStatuses[$key] = $statuses;
        return $statuses;
    }

    /**
     * Retrieve states which are visible on front end
     *
     * @return array
     */
    public function getVisibleOnFrontStates()
    {
        $this->_getStates();
        return $this->_states['visible'];
    }

    /**
     * Get order states, visible on frontend
     *
     * @return array
     */
    public function getInvisibleOnFrontStates()
    {
        $this->_getStates();
        return $this->_states['invisible'];
    }

    private function _getStates()
    {
        if (null === $this->_states) {
            $this->_states = array(
                'all'       => array(),
                'visible'   => array(),
                'invisible' => array(),
                'statuses'  => array(),
            );
            foreach ($this->getNode('states')->children() as $state) {
                $name = $state->getName();
                $this->_states['all'][] = $name;
                $isVisibleOnFront = (string)$state->visible_on_front;
                if ((bool)$isVisibleOnFront || ($state->visible_on_front && $isVisibleOnFront == '')) {
                    $this->_states['visible'][] = $name;
                }
                else {
                    $this->_states['invisible'][] = $name;
                }
                foreach ($state->statuses->children() as $status) {
                    $this->_states['statuses'][$name][] = $status->getName();
                }
            }
        }
    }
}
