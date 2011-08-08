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
 * Abstração de grupo de participantes.
 *
 * Grupos de participantes agrupam categorias de inscrição fornecendo descontos
 * sobre os valores oficiais de inscrição.
 */
class Sis_Grupo {

    protected $dataLimite;

    protected $desconto;

    protected $descricao;

    protected $id;

    protected $limiteParticipantes;

    protected $nome;

    protected $participantes;

    protected $valor;

    function __construct($nome, $desconto, $limiteParticipantes, $descricao = "") {
        $this->setDesconto($desconto);
        $this->setDescricao($descricao);
        $this->setLimiteParticipantes($limiteParticipantes);
        $this->setNome($nome);
        $participantes = array();
        $this->id = null;
        $this->dataLimite = null;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Adiciona um participante na lista de participantes do grupo
     *
     * @param Sis_Participante $participante
     */
    public function addParticipante( Sis_Participante $participante ) {
        $this->participantes[] = $participante;
    }

    /**
     * Retorna um array com todos os participantes que fazem parte do grupo.
     *
     * @return array Sis_Participante
     */
    public function getListaParticipantes() {
        return $this->participantes;
    }

    public function getDesconto() {
        return $this->desconto;
    }

    public function setDesconto($desconto) {
        if($desconto > 100 || $desconto < 0) {
            throw new InvalidArgumentException("Desconto deve ser um numero de 0 a 100");
        }
        $this->desconto = $desconto;
    }

    public function getDescricao() {
        return $this->descricao;
    }

    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }

    public function getCodigoDesconto() {
        return $this->getCodigo();
    }

    public function getCodigo() {
        if( !is_numeric($this->getId()) ) {
            throw new Exception("Grupo sem ID, impossivel gerar codigo de desconto");
        }
        $prefix = "GRU";
        $str = sprintf("%s",$this->getId());
        return sprintf ('%s%08X', $prefix, crc32($prefix.$str) );
    }

    public function getLimiteParticipantes() {
        return $this->limiteParticipantes;
    }

    public function setLimiteParticipantes($limiteParticipantes) {
        $this->limiteParticipantes = (int) $limiteParticipantes;
    }

    public function getNome() {
        return $this->nome;
    }

    public function setNome($nome) {
        $this->nome = $nome;
    }

    public function getDataLimite() {
        return $this->dataLimite;
    }

    public function setDataLimite($dataLimite) {
        $this->dataLimite = $dataLimite;
    }

    /**
     * Responde se o grupo já está cheio ou não.
     *
     * @return boolean isFull
     */
    public function isFull() {
        if($this->getLimiteParticipantes() > 0 && count($this->getListaParticipantes()) >= $this->getLimiteParticipantes()) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Verifica se o grupo ainda é valido para novas inscrições de acordo com sua data.
     *
     * @return boolean isValid
     */
    public function isValid() {
        if($this->getDataLimite() === null) {
            return true;
        }
        else if(strtotime("now") <= $this->getDataLimite()) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Calcula valor de inscrição baseado no desconto ou valor absoluto.
     *
     * A referencia é o valor normal da inscrição que será aplicado o desconto.
     *
     * Caso o grupo tenha valor absoluto definido, este será retornado.
     *
     * @param float $referencia de calculo
     * @return float $valor calculado
     */
    public function calculaValorInscricao( $referencia = 0.0 ) {
        if( $this->getValor() != null ) {
            return $this->getValor();
        }
        else {
            $desconto = (100 - $this->getDesconto() ) / 100;
            return $referencia * $desconto;
        }
    }

    public function getValor() {
        return $this->valor;
    }

    public function setValor($valor) {
        $this->valor = (float) str_replace(",", ".", $valor);
    }
    
}
