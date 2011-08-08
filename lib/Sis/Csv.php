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
 * Classe que traz facilidades na leitura de arquivos e strings no formato CSV.
 *
 * @author Henrique Grolli Bassotto
 */
class Sis_Csv {

    /**
     * Array contendo o csv já processado.
     *
     * @var array csv
     */
    protected $csv;

    public function __construct() {
        $this->csv = array();
    }

    public function fromFile( $filepath ) {
        $this->fromString(file_get_contents($filepath));
    }

    /**
     * Faz o processamento de uma string com dados no formato CSV para um array.
     *
     * TODO: trabalhar com delimitadores. ex: lala, "sdfsd,sdfsdf", 123.12
     *
     * @param string $rawcsv
     */
    public function fromString( $rawcsv ) {
        $csv = array();
        $rawcsv = trim($rawcsv, "\n");
        foreach (explode("\n", $rawcsv) as $row) {
            $csv[] = explode(",", $row);
        }

        $this->csv = $csv;
    }

    public function getAsArray() {
        return $this->csv;
    }

}
