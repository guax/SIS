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
 * Controle de pagamentos
 */
class BillingController extends Zend_Controller_Action {

    public function indexAction() {
        $sis = new Sis_Sis();
        $user = $sis->buscarInscricaoEmail(Zend_Auth::getInstance()->getIdentity()->email);

        if ($user->data_pagamento === "") {
            $status = Sis_Payment::UNPAID;

            $config = new Zend_Config_Xml('../sis/forms/billing.xml');
            $form = new Zend_Form($config->promocode);
            $form->setAction($this->view->baseUrl() . "/billing");
            $this->view->promocode_form = $form;

            if ($this->getRequest()->isPost()) {
                if ($form->isValid($_POST)) {
                    $code = $form->getValue('code');
                    $caravana = $sis->buscarCaravana($code);
                    if ($caravana === false) {
                        $grupos = new Sis_Persistencia_Grupos();

                        $grupo = $grupos->getByCodigoDesconto($code);
                        if ($grupo !== null) {
                            // Vincular ao grupo
                            $user->setGroupId($grupo->getId());
                            $sis->atualizarInscricao($user);
                        } else {
                            $form->getElement('code')->addError("Código inválido");
                        }
                    } else {
                        // Vincular a caravana
                        $user->id_caravana = $caravana->id;
                        $sis->atualizarInscricao($user);
                    }
                }
            }

            $pag = $sis->pagamento($user->email);
            if(is_object($pag)) {
                $this->view->pag = $pag->mostra(array("print" => false, "btn_submit" => 2));
                $this->view->value = $pag->_itens[0]["valor"];
                $category = $user->estudante ? "Estudante" : "Normal";
                $category = $user->id_grupo ? "Grupo" : $category;
                $category = $user->id_caravana ? "Caravana" : $category;
                $this->view->category = $category;
            }
            else {
                $sis->quitarPagamento($user->email, strtotime("now"), 0.0);
                $status = Sis_Payment::PAID;
            }
        } else if ($user->id_caravana !== null && $sis->buscarCaravanaId($user->id_caravana)->organizador->id == $user->id) {
            $status = Sis_Payment::OPEN;
        } else {
            $status = Sis_Payment::PAID;
        }
        $this->view->status = $status;
    }

}

