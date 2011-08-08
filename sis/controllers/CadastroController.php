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
 * Description of Cadastro
 */
class CadastroController extends Zend_Controller_Action {
    
    public function novoAction() {
        $participante = new Sis_Participante();
        $dados = $this->_request->getPost();
        
        if($dados['insc_type'] == 'estudante_ufsc') {
            $participante->estudante_ufsc = true;
            $participante->inst_ensino = 'UFSC';
            $participante->estudante = true;
        }
        else if($dados['insc_type'] == 'estudante') {
            $participante->estudante = true;
        }
        unset($dados['insc_type']);

        foreach ($dados as $campo => $valor) {
            $participante->{$campo} = $valor;
        }

        $sis = new Sis_Sis();
        $error = false;
        try {
            $sis->novaInscricao($participante);
        }
        catch(Sis_Exception_InscricaoInvalida $ex) {
            $this->view->title = "Falha na inscrição";
            $this->view->message = "Erro: {$ex->getMessage()}";
            $this->view->sidebar = false;
            $error = true;
        }

        if(!$error) {
            $this->view->message = "A senha foi enviada para $participante->email";
            $this->_redirect('auth/login/novocadastro/true');
        }
    }

    public function senhaAction() {
        if($this->_request->isPost()) {
            $auth = Zend_Auth::getInstance();
            $user = $auth->getIdentity();

            $sis = new Sis_Sis();
            $senha_atual = $this->_request->getPost('senha_atual');
            $senha_nova  = $this->_request->getPost('nova_senha');
            try {
                $sis->alterarSenha($user->email, $senha_atual, $senha_nova);
                $this->view->mensagem = "Senha alterada com sucesso.";
                $this->view->msgclass = "success";
            }
            catch(Sis_Exception_Persistencia $ex) {
                $this->view->mensagem = $ex->getMessage();
                $this->view->msgclass = "failure";
            }
        }
    }

    public function editarAction() {
        $sis = new Sis_Sis();
        $user = Zend_Auth::getInstance()->getIdentity();
        $participante = $sis->buscarInscricaoEmail($user->email);

        if($this->getRequest()->isPost()) {
            $dados = $this->_request->getPost();

            if($dados['insc_type'] == 'estudante_ufsc') {
                $participante->estudante_ufsc = true;
                $participante->inst_ensino = 'UFSC';
                $participante->estudante = true;
            }
            else if($dados['insc_type'] == 'estudante') {
                $participante->estudante_ufsc = false;
                $participante->estudante = true;
            }
            else {
                $participante->inst_ensino = '';
                $participante->estudante_ufsc = false;
                $participante->estudante = false;
            }
            unset($dados['insc_type']);

            foreach ($dados as $campo => $valor) {
                $participante->{$campo} = $valor;
            }

            $sis->atualizarInscricao($participante);

            $this->view->msg = "Dados atualizados com sucesso";
            $this->view->msgclass = "success";
        }

        $this->view->participante = $participante;
    }
}
