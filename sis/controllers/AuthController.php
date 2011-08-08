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
 * Description of AuthController
 *
 * @author guaxinim
 */
class AuthController extends Zend_Controller_Action {
    /**
         * Ação principal do controlador, não é usado então direcionamos as
         * requisições para o index controller.
         */
        public function indexAction() {
            // Index não é usada aqui.
            $this->_redirect('/');
        }

        /**
         * Ação de login, mostra a view padrão, caso a requisição seja do tipo
         * POST ela faz a checagem da credencial e autentica (ou não) o usuário.
         */
        public function loginAction() {
            // Não precisamos fazer login se ja estamos logados
            $auth = Zend_Auth::getInstance();


            if($auth->hasIdentity()) {
                $this->_redirect('cadastro/editar');
            }

            if($this->_request->getParam('novocadastro', false)) {
                $this->view->message = "A senha foi enviada para o e-mail fornecido no cadastro.";
                $this->view->msgclass = 'success';
            }

            if ($this->_request->isPost()) {
                // Filtrando informações do usuário
                $f = new Zend_Filter_StripTags();
                $username = $f->filter($this->_request->getPost('email'));
                $password = sha1($this->_request->getPost('senha'));
                if (empty($username)) {
                    $this->view->message = $this->view->translate("Por favor insira um nome de usuário");
                    $this->view->msgclass = 'failure';
                }
                else {
                    $db = Zend_Registry::get('db');

                    // criando adaptador de autorização
                    $authAdapter = new Zend_Auth_Adapter_DbTable($db);

                    // informações das tabelas
                    $authAdapter->setTableName('participante');
                    $authAdapter->setIdentityColumn('email');
                    $authAdapter->setCredentialColumn('senha');

                    // Valores vindos do usuário como credencial
                    $authAdapter->setIdentity($username);
                    $authAdapter->setCredential($password);

                    // autenticação
                    $auth = Zend_Auth::getInstance();
                    $result = $auth->authenticate($authAdapter);

                    // tratando resultados
                    switch ($result->getCode()) {
                        case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
                            case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:

                            $this->view->message = $this->view->translate('Usuário ou senha inválida');
                            $this->view->msgclass = 'failure';
                            break;

                        case Zend_Auth_Result::SUCCESS:
                            // Armazenando informação caso a autenticação tenha sido bem sucedida
                            $data = $authAdapter->getResultRowObject(null, 'password');
                            $auth->getStorage()->write($data);
                            $this->_redirect('cadastro/editar');
                            break;

                        default:
                            $this->view->message = $this->view->translate('Falha na autenticação');
                            $this->view->msgclass = 'failure';
                            break;
                    }
                }
            }
        }

        public function resetAction() {
            $hash = $this->getRequest()->getParam('hash');
            if($hash !== null) {
                $db = Zend_Registry::get('db');
                $userid = $db->query("SELECT user FROM recovery_hashes WHERE `hash`='$hash'")->fetchObject();
                if($userid !== false) {
                    $config = new Zend_Config_Xml('../sis/forms/password_recover.xml');
                    $form = new Zend_Form($config->reset);
                    $form->setAction($this->view->baseUrl() . "/auth/reset/hash/$hash");
                    $this->view->form = $form;

                    if($this->getRequest()->isPost()) {
                        $isValid = $form->isValid($_POST);

                        if($isValid && $form->getValue('newpassword') !== $form->getValue('confirmation')) {
                            $isValid = false;
                            $form->getElement('confirmation')->addError("A confirmação de senha não confere com a senha informada.");
                        }

                        if($isValid) {
                            $sis = new Sis_Sis();
                            $sis->definirSenha($userid->user, $form->getValue('newpassword'));
                            $db->delete('recovery_hashes',"user='$userid->user'");
                            $this->_redirect('index/recovered');
                        }
                    }
                }
                else {
                    $this->view->invalidhash = true;
                }
            }
        }

        public function recoverAction() {
            $config = new Zend_Config_Xml('../sis/forms/password_recover.xml');
            $form = new Zend_Form($config->request);
            $form->setAction($this->view->baseUrl() . "/auth/recover");

            if($this->_request->isPost()) {
                $isValid = $form->isValid($_POST);

                if($isValid) {
                    $sis = new Sis_Sis();
                    try {
                        $inscricao = $sis->buscarInscricaoEmail($form->getValue('email'));
                    }
                    catch(Sis_Exception_InscricaoInvalida $ex) {
                        $form->getElement('email')->addError("Este email não está cadastrado no sistema");
                        $isValid = false;
                    }
                }

                if($isValid) {
                    $hash = $inscricao->id . sha1(microtime());

                    $db = Zend_Registry::get('db');
                    $data = array(
                        "request_date" => new Zend_Db_Expr("NOW()"),
                        "user" => $inscricao->id,
                        "hash" => $hash
                    );
                    $db->insert('recovery_hashes', $data);

                    $mail = new Zend_Mail("utf8");
                    $mailbody = file_get_contents("../sis/mail/password_recovery.html");
                    $mailbody = str_replace("%hash%", $hash, $mailbody);

                    $mail->setBodyHtml($mailbody);
                    $mail->addTo($form->getValue('email'));
                    $mail->setFrom("no-reply@solisc.org.br");
                    $mail->setSubject("Recuperação de Senha");
                    $mail->send();
                    $this->view->message = "Requisição enviada, verifique seu email dentro de alguns instantes.";
                }
            }

            $this->view->form = $form;
        }

        /**
         * Ação que remove a autenticação do usuário.
         */
        public function logoutAction() {
            Zend_Auth::getInstance()->clearIdentity();
            $this->_redirect('/');
        }
}
