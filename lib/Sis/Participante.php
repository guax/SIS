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

class Sis_Participante {
    public $id = 0;
    public $email = ""; // obrigatorio
    public $forma_tratamento = "";
    public $nome = ""; // obrigatorio
    public $sexo = "M";

    // Endereço
    public $pais = "Brasil";
    public $uf = "SC";
    public $cidade = "";
    public $logradouro = "";
    public $bairro = "";
    public $cep = "";

    public $tipo_doc = ""; // CPF, CI, PASSAPORTE, ...
    public $num_doc = ""; // número do documento

    public $estudante = False;
    public $estudante_ufsc = False;

    public $inst_ensino = ""; // obrigatório para estudante
    public $curso = ""; // obrigatório para estudante
    public $nivel_ensino = ""; // básico, médio, graduação, pós-graduação

    public $empresa = "";
    public $cargo = "";

    public $cod_desconto = ""; // código de desconto: CARXXXXXXX para caravana
                            // e GRUXXXXXXX para grupo de usuários

    public $data_inscricao = "";

    public $valor_pagamento = 0.0; // valor calculado pelo modelo a partir dos dados
                                // da inscrição

    public $data_pagamento = ""; // data obtida do pagSeguro
    public $dt_vencimento = ""; // data de vencimento do pagamento (limite do desconto).

    public $id_caravana = null; // não usado na view
    public $id_grupo = null; // não usado na view
    public $senha = ""; // não usada diretamente na view

    public function setGroupId($groupId) {
        $this->id_grupo = $groupId;
    }

    /**
     * @return int grupo id
     */
    public function getGroupId() {
        return $this->id_grupo;
    }
}
