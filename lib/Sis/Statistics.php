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
 * Modelo responsável por sintetizar e facilitar o acesso a estatisticas do
 * sistema.
 */
class Sis_Statistics {

    private $db;

    public function __construct() {
        $this->db = Zend_Registry::get('db');
    }

    public function getNumeroCadastrados() {
        return $this->db->fetchOne("SELECT count(*) FROM participante;");
    }

    /**
     * Retorna os pagamentos requisitados mas ainda sem pagamento confirmado.
     *
     * @return int Quantidade de pagamentos em aberto
     */
    public function getPagamantosAbertos() {
        return $this->getNumeroCadastrados() - $this->getPagamentosEfetuados();
    }

    /**
     * Retorna número de pagamentos já confirmados no sistema.
     *
     * @return int Cardinalidade de pagamentos efetuados
     */
    public function getPagamentosEfetuados() {
        return $this->db->fetchOne("SELECT count(dt_pagamento) FROM participante;");
    }
    
}
