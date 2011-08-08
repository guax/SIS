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
 * Controlador para gerencia do cadastro de gruopos de participantes.
 */
class GruposController extends Zend_Controller_Action {
    /**
     * Ação principal do controle de grupos
     */
    public function indexAction() {
        $grupos = new Sis_Persistencia_Grupos();
        $this->view->grupos = $grupos->getAllGrupos();
    }

    /**
     * Cadastro de novo grupo
     */
    public function cadastrarAction() {
        $request = $this->getRequest();
        if( $request->isPost() ) {
            $grupo = new Sis_Grupo($request->getPost("nome"), $request->getPost("desconto"), $request->getPost("limite"), $request->getPost("descricao"));
            $grupos = new Sis_Persistencia_Grupos();
            if( $request->getPost('tipo') == 'valor' ) {
                $grupo->setValor($request->getPost('valor'));
            }
            if( $request->getPost('dataLimite') != '' ) {
                $datePieces = explode("/", $request->getPost('dataLimite'));
                $grupo->setDataLimite(strtotime(sprintf("%s-%s-%s", $datePieces[2], $datePieces[1], $datePieces[0])));
            }
            $grupos->storeGrupo($grupo);
            $this->_redirect("grupos/");
        }
    }

    /**
     * Editar um grupo existente
     */
    public function editarAction() {
        $request = $this->getRequest();

        $grupos = new Sis_Persistencia_Grupos();
        $grupo = $grupos->getGrupo($request->getParam("groupId"));

        if( $request->isPost() ) {

            if( $request->getPost('dataLimite') == '' ) {
                $grupo->setDataLimite(0);
            }
            else {
                $datePieces = explode("/", $request->getPost('dataLimite'));
                $newDate = strtotime(sprintf("%s-%s-%s", $datePieces[2], $datePieces[1], $datePieces[0]));

                if( $grupo->getDataLimite() != $newDate ) {
                    $grupo->setDataLimite($newDate);
                }
            }

            $grupo->setDescricao($request->getPost('descricao'));

            if( is_numeric($request->getPost('limite')) && $request->getPost('limite') >= 0 ) {
                $grupo->setLimiteParticipantes($request->getPost('limite'));
            }

            $grupos->updateGrupo($grupo);

            $this->_redirect("grupos/");
        }
        else {
            $this->view->grupo = $grupo;
        }
    }
}
