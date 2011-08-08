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
 * Description of ErrorController
 */
class ErrorController extends Zend_Controller_Action {
    public function errorAction() {
        $errors = $this->_getParam('error_handler');

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->message = 'A pagina a qual você está procurando não foi encontrada.';
                $this->view->title = '404 - Não Encontrado';
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->title = '500 - Erro Interno';
                $this->view->sidebar = false;
                $this->view->message = 'Por favor, contate o suporte o administrador do sistema.';
                break;
        }

        $this->view->exception = $errors->exception;
        $this->view->request   = $errors->request;
        $this->view->headTitle($this->view->title, 'PREPEND');
    }

    public function deniedAction() {
        $this->view->headTitle('Acesso Negado', 'PREPEND');
    }
}
