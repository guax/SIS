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
 * Controle para cadastro de caravanas.
 */
class CaravanaController extends Zend_Controller_Action {
    /**
     * Ação para criação de caravana.
     */
    public function novaAction() {
        $sis = new Sis_Sis();
        $participante = $sis->buscarInscricaoEmail(Zend_Auth::getInstance()->getIdentity()->email);
        if($participante->id_caravana !== null || $participante->id_grupo !== null) {
            $this->_redirect('caravana/cadastrada');
        }
        $this->view->cidadeParticipante = Zend_Auth::getInstance()->getIdentity()->cidade;
    }

    /**
     * Disponibiliza informações sobre a caravana cadastrada.
     */
    public function cadastradaAction() {
        $sis = new Sis_Sis();
        $this->view->grupo = false;
        $participante = $sis->buscarInscricaoEmail(Zend_Auth::getInstance()->getIdentity()->email);
        if($participante->id_grupo !== null) {
            $grupos = new Sis_Persistencia_Grupos();
            $this->view->grupo = true;
            $this->view->codigoCaravana = $grupos->getGrupo($participante->id_grupo)->getCodigoDesconto();
        }
        else if($participante->id_caravana !== null) {
            $this->view->codigoCaravana = $sis->buscarCaravanaId($participante->id_caravana)->cod_desconto;
        }
        else {
            $this->_redirect('caravana/nova');
        }
    }

    /**
     * Ação que confirma e associa a caravana a um usuário.
     */
    public function confirmarAction() {
        $sis = new Sis_Sis();
        $participante = $sis->buscarInscricaoEmail(Zend_Auth::getInstance()->getIdentity()->email);
        if($participante->id_caravana !== null || $participante->id_grupo !== null) {
            $this->_redirect('caravana/cadastrada');
        }
        if($this->_request->isPost()) {
            $sis = new Sis_Sis();
            $caravana = $sis->novaCaravana($sis->buscarInscricaoEmail(Zend_Auth::getInstance()->getStorage()->read()->email), $this->_request->getParam('cidade'));
            $this->view->codigoCaravana = $caravana->cod_desconto;
        }
        else {
            // Ação protegida para Posts, um 500 bem feito é mostrado caso as
            // informações não sejam fornecidas em Post request.
            throw new Exception("Informações de caravana não informadas.");
        }
    }
}
