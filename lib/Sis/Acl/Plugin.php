<?php
/**
 * Zion Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zion
 * @package    Zion_Controller
 * @subpackage Plugins
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Acl.php 58 2008-01-12 10:46:55Z aldemar $
 */

/**
 * Front Controller Plugin
 * Modifications made to proper work with SIS system. original copyright note
 * kept above.
 *
 * @uses       Zend_Controller_Plugin_Abstract
 * @category   Zion
 * @package    Zion_Controller
 * @subpackage Plugins
 */
class Sis_Acl_Plugin extends Zend_Controller_Plugin_Abstract {

    /**
     * @var Zend_Acl
     **/
    protected $_acl;

    /**
     * @var string
     **/
    protected $_roleName;

    /**
     * @var array
     **/
    protected $_errorPage;

    /**
     * Constructor
     *
     * @param mixed $aclData
     * @param $roleName
     * @return void
     **/
    public function __construct(Zend_Acl $aclData, $roleName = 'guest')
    {
        $this->_errorPage = array('module' => 'default',
                                  'controller' => 'error',
                                  'action' => 'denied');

        $this->_roleName = $roleName;

        if (null !== $aclData) {
            $this->setAcl($aclData);
        }
    }

    /**
     * Sets the ACL object
     *
     * @param mixed $aclData
     * @return void
     **/
    public function setAcl(Zend_Acl $aclData)
    {
        $this->_acl = $aclData;
    }

    /**
     * Returns the ACL object
     *
     * @return Zend_Acl
     **/
    public function getAcl()
    {
        return $this->_acl;
    }

    /**
     * Sets the ACL role to use
     *
     * @param string $roleName
     * @return void
     **/
    public function setRoleName($roleName)
    {
        $this->_roleName = $roleName;
    }

    /**
     * Returns the ACL role used
     *
     * @return string
     * @author
     **/
    public function getRoleName()
    {
        return $this->_roleName;
    }

    /**
     * Sets the error page
     *
     * @param string $action
     * @param string $controller
     * @param string $module
     * @return void
     **/
    public function setErrorPage($action, $controller = 'error', $module = null)
    {
        $this->_errorPage = array('module' => $module,
                                  'controller' => $controller,
                                  'action' => $action);
    }

    /**
     * Returns the error page
     *
     * @return array
     **/
    public function getErrorPage()
    {
        return $this->_errorPage;
    }

    /**
     * Predispatch
     * Checks if the current user identified by roleName has rights to the requested url (module/controller/action)
     * If not, it will call denyAccess to be redirected to errorPage
     *
     * @return void
     **/
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $resourceName = '';

        if ($request->getModuleName() != 'default' && $request->getModuleName() != null) {
            $resourceName .= $request->getModuleName() . ':';
        }

        $resourceName .= $request->getControllerName();

        if($resourceName == "certificate" && ($request->getActionName() == "verify" || $request->getActionName() == "confirm")) {
            $resourceName = "certificate/" . $request->getActionName();
        }

        // Modification by henrique grolli bassotto
        if(!$this->getAcl()->has($resourceName)) {
            $resourceName = 'default';
        }

        /** Check if the controller/action can be accessed by the current user */
        if (!$this->getAcl()->isAllowed($this->_roleName, $resourceName, $request->getActionName())) {
            /** Redirect to access denied page */
            $this->denyAccess();
        }
    }

    /**
     * Deny Access Function
     * Redirects to errorPage, this can be called from an action using the action helper
     *
     * @return void
     **/
    public function denyAccess()
    {
        $this->_request->setModuleName($this->_errorPage['module']);
        $this->_request->setControllerName($this->_errorPage['controller']);
        $this->_request->setActionName($this->_errorPage['action']);
    }

}
