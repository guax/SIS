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
 * Controlador para controle dos pagamentos efetuados no sistema.
 */
class PagamentosController extends Zend_Controller_Action {

    /**
     * Ação principal do controlador.
     *
     * Fornece por padrão algumas estatísticas dos pagamentos.
     */
    public function indexAction() {
        $sis_stats = new Sis_Statistics();
        $this->view->pgtos_efetuados = $sis_stats->getPagamentosEfetuados();
        $this->view->pgtos_abertos = $sis_stats->getPagamantosAbertos();
    }

    /**
     * Fornece informações sobre pagamentos em aberto;
     */
    public function abertosAction() {
        $sis = new Sis_Sis();
        $this->view->inscricoesAbertas = $sis->getInscricoesAbertas();
    }

    /**
     * Confirma pagamento de inscrição.
     */
    public function confirmarAction() {
        $sis = new Sis_Sis();
        $this->view->participante = $sis->buscarInscricao($this->getRequest()->getParam('userId'));
        if($this->view->participante->data_pagamento) {
            $this->view->status = "quitado";
        }
        else if($this->getRequest()->isPost()) {
            $sis->quitarPagamento($this->view->participante->email, time(), $this->view->participante->valor_pagamento);
            $this->view->status = "quitado";
        }
    }

    /**
     * Importa arquivo CSV para efetuar a confirmação de pagamentos.
     */
    public function importcsvAction() {}

    private function validateFile($fileinfo) {
        if( is_array($fileinfo['name']) ) {
            throw new Zend_File_Transfer_Exception("Quantidade inválida de arquivos enviados.");
        }
        if( $fileinfo['size'] == 0 ) {
            throw new Zend_File_Transfer_Exception("Nenhum arquivo enviado: tamanho é 0 bytes.");
        }
        if( $fileinfo['type'] != 'text/csv' ) {
            throw new Zend_File_Transfer_Exception("Tipo de arquivo inválido. Necessário um arquivo de texto com extensão .csv");
        }
    }

    public function processcsvAction() {
        if(Zend_Session::namespaceIsset('pagamentos_csvimport')) {
            $csvsession = new Zend_Session_Namespace('pagamentos_csvimport');
            $sis = new Sis_Sis();

            foreach ($csvsession->data as $client) {
                $sis->quitarPagamento($client[0], strtotime($client[2]), $client[1]);
            }

            Zend_Session::namespaceUnset('pagamentos_csvimport');
        }
        else {
            $this->_redirect("pagamentos/importcsv");
        }
    }

    public function confirmcsvAction() {
        if( $this->getRequest()->isPost() ) {
            $fileinfo = $_FILES['csvfile'];

            // Toda a mágica de validação acontece aqui.
            $this->validateFile($fileinfo);

            $csv = new Sis_Csv();
            $csv->fromFile($fileinfo['tmp_name']);

            if(Zend_Session::namespaceIsset('pagamentos_csvimport')) {
                Zend_Session::namespaceUnset('pagamentos_csvimport');
            }

            $csvsession = new Zend_Session_Namespace('pagamentos_csvimport');

            $aquitar = array();
            $todos = array();
            $sis = new Sis_Sis();
            foreach ($csv->getAsArray() as $cliente) {
                try {
                    $sis->buscarInscricaoEmail( $cliente[0] );
                    $aquitar[] = $cliente;
                    $todos[] = array(
                        $cliente[0],
                        $cliente[1],
                        $cliente[2],
                        true
                    );
                }
                catch( Sis_Exception_InscricaoInvalida $ex) {
                    $todos[] = array(
                        $cliente[0],
                        $cliente[1],
                        $cliente[2],
                        false
                    );
                }
            }
            $csvsession->data = $aquitar;

            $this->view->dados = $todos;
        }
        else {
            $this->_redirect("pagamentos/importcsv/");
        }
    }

}
