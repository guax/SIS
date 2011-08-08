<?php
/**
 *  This file is part of SIS.
 *
 *  SIS is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  SIS is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with SIS.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Application Boot class.
 *
 * Here the magic begins.
 *
 * @see Zend_Application
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {
    
    protected $acl;

    protected function _initAcl() {
        $acl = new Zend_Acl();

        // Main roles
        $acl->addRole(new Zend_Acl_Role('all')); // Everyone
        $acl->addRole(new Zend_Acl_Role('admin'),'all'); // Administrators
        $acl->addRole(new Zend_Acl_Role('users'),'all'); // Users
        $acl->addRole(new Zend_Acl_Role('guest'),'all'); // Non authenticated users

        // Dynamic roles
        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity()) {
            $group = $auth->getStorage()->read()->admin == '1' ? 'admin' : 'users';
            $acl->addRole(new Zend_Acl_Role($auth->getIdentity()->email),$group); // Authenticated user
        }

        // System resources
        $acl->add(new Zend_Acl_Resource('default')); // Unispecified resource
        $acl->add(new Zend_Acl_Resource('error'));
        $acl->add(new Zend_Acl_Resource('index'));
        $acl->add(new Zend_Acl_Resource('auth'));
        $acl->add(new Zend_Acl_Resource('cadastro'));
        $acl->add(new Zend_Acl_Resource('pagamentos'));
        $acl->add(new Zend_Acl_Resource('grupos'));
        $acl->add(new Zend_Acl_Resource('billing'));
        $acl->add(new Zend_Acl_Resource('certificate'));
        $acl->add(new Zend_Acl_Resource('certificate/verify'));
        $acl->add(new Zend_Acl_Resource('certificate/confirm'));

        // Default permissions
        $acl->deny('all'); // Deny every access
        $acl->allow('admin'); // Allowing administrators
        $acl->allow('users'); // Allowing users
        $acl->deny('users', 'pagamentos');
        $acl->deny('users', 'grupos');
        $acl->deny('users', 'certificate/confirm');
        $acl->allow(null,'index');
        $acl->allow(null,'certificate/verify');
        $acl->allow(null,'auth');
        $acl->allow(null,'error');
        $acl->allow(null,'cadastro', 'novo');

        $this->acl = $acl;
    }

    protected function _initPlugins() {
        $front = Zend_Controller_Front::getInstance();
        
        // Defining Role
        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity()) {
            $role = $auth->getIdentity()->email;
        }
        else {
            $role = 'guest';
        }

        $front->registerPlugin(new Sis_Acl_Plugin($this->acl, $role));
    }

    protected function _initRoutes() {
        $front = Zend_Controller_Front::getInstance();
        $router = $front->getRouter();

        $router->addRoute('conf_pgto',
            new Zend_Controller_Router_Route(
                '/pagamentos/confirmar/:userId',
                array(
                    'controller' => 'pagamentos',
                    'action'     => 'confirmar'
                )
            )
        );

        $router->addRoute('editar_grupo',
            new Zend_Controller_Router_Route(
                '/grupos/editar/:groupId',
                array(
                    'controller' => 'grupos',
                    'action'     => 'editar'
                )
            )
        );

        $router->addRoute('certificate_verify',
            new Zend_Controller_Router_Route(
                '/certificate/verify/:userId',
                array(
                    'controller' => 'certificate',
                    'action'     => 'verify'
                )
            )
        );
    }

    protected function _initDB() {
        $resource = $this->getPluginResource('db');
        Zend_Registry::set('db', $resource->getDbAdapter());
    }

    protected function _initViewHelpers() {
        // Initialize view
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $view = $layout->getView();

        $view->doctype('XHTML1_STRICT');
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html;charset=utf-8');
        $view->headTitle()->setSeparator(' - ');
        $view->headTitle('SIS');

        // Return it, so that it can be stored by the bootstrap
        return $view;
    }
}
?>
