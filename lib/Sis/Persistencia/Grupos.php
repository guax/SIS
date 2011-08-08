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
 * Faz o controle de persistencia em banco de dados dos grupos de participantes.
 */
final class Sis_Persistencia_Grupos {

    /**
     * Objeto de comunicação com banco de dados
     *
     * @var Zend_Db_Adapter_Abstract
     */
    private $db;

    /**
     * Construtor
     */
    public function __construct() {
        $this->db = Zend_Registry::get('db');
    }

    /**
     * Retorna um array com todos os grupos do banco
     *
     * @return array Sis_Grupo
     */
    public function getAllGrupos() {
        $db = $this->db;
        $query = "SELECT * FROM grupo";
        $result = $db->fetchAll($query);

        $grupos = array();
        foreach ($result as $grupo) {
            $grupos[] = $this->getGrupo($grupo['id']);
        }
        return $grupos;
    }

    /**
     * Retorna um grupo a partir de seu código de desconto.
     *
     * @param String $codigoDesconto
     * @return Sis_Grupo
     */
    public function getByCodigoDesconto( $codigoDesconto ) {
        foreach( $this->getAllGrupos() as $grupo ) {
            if( $grupo->getCodigoDesconto() == $codigoDesconto ) {
                return $grupo;
            }
        }
        return null;
    }

    /**
     * Retorna um grupo do banco de dados
     *
     * @param int $id
     * @return Sis_Grupo grupo
     */
    public function getGrupo( $id ) {
        if( !is_numeric($id) )
            throw new InvalidArgumentException("Not numeric id passed as arg");

        $db = $this->db;
        $query = "SELECT * FROM grupo WHERE id=$id";
        $result = $db->fetchRow($query);
        if( !$result )
            throw new Sis_Persistencia_Exception_NotFound();

        $grupo = new Sis_Grupo($result['nome'], $result['desconto'], $result['numeroParticipantes'], $result['descricao']);
        $grupo->setId($result['id']);
        
        if( $result['valor'] ) {
            $grupo->setValor($result['valor']);
        }

        if( $result['dataLimite'] ) {
            $grupo->setDataLimite(strtotime($result['dataLimite'] . " 23:59:59"));
        }

        $sis = new Sis_Sis();
        $query = "SELECT id FROM participante INNER JOIN membroGrupo ON participante.id = membroGrupo.participante WHERE membroGrupo.grupo=$id";
        $result = $db->fetchAll($query);
        foreach ($result as $participante) {
            $grupo->addParticipante($sis->buscarInscricao($participante['id']));
        }

        return $grupo;
    }

    /**
     * Armazena um grupo no banco de dados.
     *
     * @param Sis_Grupo $grupo
     */
    public function storeGrupo( Sis_Grupo $grupo ) {
        $db = $this->db;
        $data = array(
            "nome" => $grupo->getNome(),
            "descricao" => $grupo->getDescricao(),
            "desconto" => $grupo->getDesconto(),
            "numeroParticipantes" => $grupo->getLimiteParticipantes()
        );

        if( $grupo->getValor() !== null ) {
            $data['valor'] = $grupo->getValor();
        }

        if( $grupo->getDataLimite() !== null ) {
            $data['dataLimite'] = date("Y-m-d", $grupo->getDataLimite());
        }

        $db->insert("grupo", $data);
    }

    /**
     * Atualiza grupo de usuário no banco de dados
     *
     * @param Sis_Grupo $grupo
     */
    public function updateGrupo( Sis_Grupo $grupo ) {
        $db = $this->db;
        $data = array(
            "nome" => $grupo->getNome(),
            "descricao" => $grupo->getDescricao(),
            "numeroParticipantes" => $grupo->getLimiteParticipantes()
        );

        if($grupo->getDataLimite() == 0 ) {
            $data["dataLimite"] = null;
        }
        else {
            $data["dataLimite"] = date("Y-m-d", $grupo->getDataLimite());
        }
        
        $db->update("grupo",$data,"id={$grupo->getId()}");
    }
}
