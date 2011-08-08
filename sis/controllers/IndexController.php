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
 * Description of IndexController
 *
 * @author guaxinim
 */
class IndexController extends Zend_Controller_Action {
    public function indexAction() {
        $auth = Zend_Auth::getInstance();

        if($auth->hasIdentity()) {
            $this->_redirect('cadastro/editar');
        }
    }

    public function recoveredAction() {
        $auth = Zend_Auth::getInstance();
        
        if($auth->hasIdentity()) {
            $this->_redirect('cadastro/editar');
        }
    }
}

