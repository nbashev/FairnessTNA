<?php
/**********************************************************************************
 * This file is part of "FairnessTNA", a Payroll and Time Management program.
 * FairnessTNA is copyright 2013-2017 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * others. For full attribution and copyrights details see the COPYRIGHT file.
 *
 * FairnessTNA is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * FairnessTNA is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *********************************************************************************/


include_once('CA.class.php');

/**
 * @package GovernmentForms
 */
class GovernmentForms_CA_T4A extends GovernmentForms_CA
{
    public $pdf_template = 't4a-flat-11b.pdf';

    public $template_offsets = array(-10, 0);

    public function getOptions($name)
    {
        $retval = null;
        switch ($name) {
            case 'status':
                $retval = array(
                    '-1010-O' => TTi18n::getText('Original'),
                    '-1020-A' => TTi18n::getText('Amended'),
                    '-1030-C' => TTi18n::getText('Cancel'),
                );
                break;
            case 'type':
                $retval = array(
                    'government' => TTi18n::gettext('Government (Multiple Employees/Page)'),
                    'employee' => TTi18n::gettext('Employee (One Employee/Page)'),
                );
                break;
        }

        return $retval;
    }

    //Set the type of form to display/print. Typically this would be:
    // government or employee.

    public function setType($value)
    {
        $this->type = trim($value);
        return true;
    }

    public function setStatus($value)
    {
        $this->status = strtoupper(trim($value));
        return true;
    }

    //Set the submission status. Original, Amended, Cancel.

    public function setShowInstructionPage($value)
    {
        $this->show_instruction_page = (bool)trim($value);
        return true;
    }

    public function getFilterFunction($name)
    {
        $variable_function_map = array(
            'year' => 'isNumeric',
            //'ein' => array( 'stripNonNumeric', 'isNumeric'),
        );

        if (isset($variable_function_map[$name])) {
            return $variable_function_map[$name];
        }

        return false;
    }

    public function _outputXML()
    {

        //Maps other income box codes to XML element names.
        $other_box_code_map = array(
            26 => 'elg_rtir_amt',
            27 => 'nelg_rtir_amt',
            28 => 'oth_incamt',
            30 => 'ptrng_aloc_amt',
            32 => 'rpp_past_srvc_amt',
            34 => 'padj_amt',
            40 => 'resp_aip_amt',
            42 => 'resp_educt_ast_amt',
            46 => 'chrty_dons_amt',
            102 => 'nr_lsp_trnsf_amt',
            104 => 'rsch_grnt_amt',
            105 => 'brsy_amt',
            106 => 'dth_ben_amt',
            107 => 'wag_ls_incamt',
            108 => 'lsp_rpp_nelg_amt',
            109 => 'nrgst_ppln_amt',
            110 => 'pr_71_acr_lsp_amt',
            111 => 'inc_avg_annty_amt',
            115 => 'dpsp_ins_pay_amt',
            116 => 'med_trvl_amt',
            117 => 'loan_ben_amt',
            118 => 'med_prem_ben_amt',
            119 => 'grp_trm_life_amt',
            122 => 'resp_aip_oth_amt',
            123 => 'ins_rvk_dpsp_amt',
            124 => 'brd_wrk_site_amt',
            125 => 'dsblt_ben_amt',
            126 => 'pr_90_rpp_amt',
            127 => 'vtrn_ben_amt',
            129 => 'tx_dfr_ptrng_dvamt',
            130 => 'atp_inctv_grnt_amt',
            131 => 'rdsp_amt',
            132 => 'wag_ptct_pgm_amt',
            133 => 'var_pens_ben_amt',
            134 => 'tfsa_tax_amt',
            135 => 'rcpnt_pay_prem_phsp_amt',
            142 => 'indn_elg_rtir_amt',
            143 => 'indn_nelg_rtir_amt',
            144 => 'indn_oth_incamt',
            146 => 'indn_xmpt_pens_amt',
            148 => 'indn_xmpt_lsp_amt',
            150 => 'lbr_adj_ben_aprpt_act_amt',
            152 => 'subp_qlf_amt',
            154 => 'csh_awrd_pze_payr_amt',
            156 => 'bkcy_sttl_amt',
            158 => 'lsp_nelg_trnsf_amt',
            180 => 'lsp_dpsp_nelg_amt',
            190 => 'lsp_nrgst_pens_amt'
        );

        if (is_object($this->getXMLObject())) {
            $xml = $this->getXMLObject();
        } else {
            return false; //No XML object to append too. Needs T619 form first.
        }

        $xml->Return->addChild('T4A');

        $records = $this->getRecords();
        if (is_array($records) and count($records) > 0) {
            $e = 0;
            foreach ($records as $employee_data) {
                Debug::Arr($employee_data, 'Employee Data: ', __FILE__, __LINE__, __METHOD__, 10);
                $this->arrayToObject($employee_data); //Convert record array to object

                $xml->Return->T4A->addChild('T4ASlip');

                $xml->Return->T4A->T4ASlip[$e]->addChild('RCPNT_NM'); //Employee name
                $xml->Return->T4A->T4ASlip[$e]->RCPNT_NM->addChild('snm', $this->last_name); //Surname
                $xml->Return->T4A->T4ASlip[$e]->RCPNT_NM->addChild('gvn_nm', substr($this->first_name, 0, 12)); //Given name
                if ($this->filterMiddleName($this->middle_name) != '') {
                    $xml->Return->T4A->T4ASlip[$e]->RCPNT_NM->addChild('init', $this->filterMiddleName($this->middle_name));
                }

                $xml->Return->T4A->T4ASlip[$e]->addChild('RCPNT_ADDR'); //Employee Address
                if ($this->address1 != '') {
                    $xml->Return->T4A->T4ASlip[$e]->RCPNT_ADDR->addChild('addr_l1_txt', substr(Misc::stripHTMLSpecialChars($this->address1), 0, 30));
                }
                if ($this->address2 != '') {
                    $xml->Return->T4A->T4ASlip[$e]->RCPNT_ADDR->addChild('addr_l2_txt', substr(Misc::stripHTMLSpecialChars($this->address2), 0, 30));
                }
                if ($this->city != '') {
                    $xml->Return->T4A->T4ASlip[$e]->RCPNT_ADDR->addChild('cty_nm', $this->city);
                }
                if ($this->province != '') {
                    $xml->Return->T4A->T4ASlip[$e]->RCPNT_ADDR->addChild('prov_cd', $this->province);
                }
                $xml->Return->T4A->T4ASlip[$e]->RCPNT_ADDR->addChild('cntry_cd', 'CAN');
                if ($this->postal_code != '') {
                    $xml->Return->T4A->T4ASlip[$e]->RCPNT_ADDR->addChild('pstl_cd', $this->postal_code);
                }

                $xml->Return->T4A->T4ASlip[$e]->addChild('sin', ($this->sin != '') ? $this->sin : '000000000'); //Required
                if ($this->employee_number != '') {
                    $xml->Return->T4A->T4ASlip[$e]->addChild('rcpnt_nbr', substr($this->employee_number, 0, 20));
                }
                $xml->Return->T4A->T4ASlip[$e]->addChild('rcpnt_bn', '000000000RP0000'); //Individual only
                $xml->Return->T4A->T4ASlip[$e]->addChild('bn', $this->payroll_account_number); //Payroll Account Number
                $xml->Return->T4A->T4ASlip[$e]->addChild('rpt_tcd', $this->getStatus()); //Report Type Code: O = Originals, A = Amendment, C = Cancel

                $xml->Return->T4A->T4ASlip[$e]->addChild('T4A_AMT'); //T4A Amounts
                $xml->Return->T4A->T4ASlip[$e]->T4A_AMT->addChild('pens_spran_amt', $this->MoneyFormat($this->l16, false));
                $xml->Return->T4A->T4ASlip[$e]->T4A_AMT->addChild('lsp_amt', $this->MoneyFormat($this->l18, false));
                $xml->Return->T4A->T4ASlip[$e]->T4A_AMT->addChild('self_empl_cmsn_amt', $this->MoneyFormat($this->l20, false));
                $xml->Return->T4A->T4ASlip[$e]->T4A_AMT->addChild('itx_ddct_amt', $this->MoneyFormat($this->l22, false));
                $xml->Return->T4A->T4ASlip[$e]->T4A_AMT->addChild('annty_amt', $this->MoneyFormat($this->l24, false));
                $xml->Return->T4A->T4ASlip[$e]->T4A_AMT->addChild('fee_or_oth_srvc_amt', $this->MoneyFormat($this->l48, false));

                $xml->Return->T4A->T4ASlip[$e]->addChild('OTH_INFO'); //Other Income Fields
                for ($i = 0; $i <= 5; $i++) {
                    if (isset($this->{'other_box_' . $i . '_code'}) and isset($other_box_code_map[$this->{'other_box_' . $i . '_code'}])) {
                        $xml->Return->T4A->T4ASlip[$e]->OTH_INFO->addChild($other_box_code_map[$this->{'other_box_' . $i . '_code'}], $this->MoneyFormat($this->{'other_box_' . $i}, false));
                    }
                }

                $e++;
            }
        }

        return true;
    }

    public function getStatus()
    {
        if (isset($this->status)) {
            return $this->status;
        }

        return 'O'; //Original
    }

    public function _outputPDF()
    {
        //Initialize PDF with template.
        $pdf = $this->getPDFObject();

        if ($this->getShowBackground() == true) {
            $pdf->setSourceFile($this->getTemplateDirectory() . DIRECTORY_SEPARATOR . $this->pdf_template);

            $this->template_index[1] = $pdf->ImportPage(1);
            $this->template_index[2] = $pdf->ImportPage(2);
            //$this->template_index[3] = $pdf->ImportPage(3);
        }

        if ($this->year == '') {
            $this->year = $this->getYear();
        }

        if ($this->getType() == 'government') {
            $employees_per_page = 2;
            $n = 1; //Don't loop the same employee.
        } else {
            $employees_per_page = 1;
            $n = 2; //Loop the same employee twice.
        }

        //Get location map, start looping over each variable and drawing
        $records = $this->getRecords();
        if (is_array($records) and count($records) > 0) {
            $template_schema = $this->getTemplateSchema();

            $e = 0;
            foreach ($records as $employee_data) {
                //Debug::Arr($employee_data, 'Employee Data: ', __FILE__, __LINE__, __METHOD__,10);
                $this->arrayToObject($employee_data); //Convert record array to object

                $template_page = null;

                for ($i = 0; $i < $n; $i++) {
                    $this->page_offsets = array(0, 0);

                    if (($employees_per_page == 1 and $i > 0)
                        or ($employees_per_page == 2 and $e % 2 != 0)
                    ) {
                        $this->page_offsets = array(0, 396);
                    }

                    foreach ($template_schema as $field => $schema) {
                        $this->Draw($this->$field, $schema);
                    }
                }

                if ($employees_per_page == 1 or ($employees_per_page == 2 and $e % $employees_per_page != 0)) {
                    $this->resetTemplatePage();
                    if ($this->getShowInstructionPage() == true) {
                        $this->addPage(array('template_page' => 2));
                    }
                }
                $e++;
            }
        }

        $this->clearRecords();

        return true;
    }

    public function getType()
    {
        if (isset($this->type)) {
            return $this->type;
        }

        return false;
    }

    public function getTemplateSchema($name = null)
    {
        $template_schema = array(

            'year' => array(
                'page' => 1,
                'template_page' => 1,
                'on_background' => true,
                'coordinates' => array(
                    'x' => 349,
                    'y' => 47,
                    'h' => 17,
                    'w' => 57,
                    'halign' => 'C',
                    //'fill_color' => array( 255, 255, 255 ),
                ),
                'font' => array(
                    'size' => 14,
                    'type' => 'B')
            ),

            //Company information
            'company_name' => array(
                'coordinates' => array(
                    'x' => 35,
                    'y' => 32,
                    'h' => 12,
                    'w' => 210,
                    'halign' => 'L',
                ),
                'font' => array(
                    'size' => 8,
                    'type' => 'B')
            ),
            'payroll_account_number' => array(
                'function' => array('filterPayrollAccountNumber', 'drawNormal'),
                'coordinates' => array(
                    'x' => 50,
                    'y' => 95,
                    'h' => 17,
                    'w' => 190,
                    'halign' => 'L',
                ),
                'font' => array(
                    'size' => 8,
                    'type' => '')
            ),
            'recipient_account_number' => array(
                'coordinates' => array(
                    'x' => 200,
                    'y' => 135,
                    'h' => 17,
                    'w' => 100,
                    'halign' => 'C',
                ),
            ),
            //Employee information.
            'sin' => array(
                'coordinates' => array(
                    'x' => 49,
                    'y' => 135,
                    'h' => 17,
                    'w' => 120,
                    'halign' => 'C',
                ),
            ),
            'last_name' => array(
                'coordinates' => array(
                    'x' => 49,
                    'y' => 197,
                    'h' => 14,
                    'w' => 150,
                    'halign' => 'L',
                ),
            ),
            'first_name' => array(
                'coordinates' => array(
                    'x' => 202,
                    'y' => 197,
                    'h' => 14,
                    'w' => 60,
                    'halign' => 'L',
                ),
            ),
            'middle_name' => array(
                'function' => array('filterMiddleName', 'drawNormal'),
                'coordinates' => array(
                    'x' => 270,
                    'y' => 197,
                    'h' => 14,
                    'w' => 30,
                    'halign' => 'R',
                ),
            ),

            'address' => array(
                'function' => array('filterAddress', 'drawNormal'),
                'coordinates' => array(
                    'x' => 49,
                    'y' => 215,
                    'h' => 42,
                    'w' => 270,
                    'halign' => 'L',
                ),
                'font' => array(
                    'size' => 8,
                    'type' => ''),
                'multicell' => true,
            ),
            'l16' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 345,
                        'y' => 99,
                        'h' => 18,
                        'w' => 71,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 416,
                        'y' => 99,
                        'h' => 18,
                        'w' => 33,
                        'halign' => 'C',
                    ),
                ),
            ),

            'l22' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 470,
                        'y' => 99,
                        'h' => 18,
                        'w' => 71,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 541,
                        'y' => 99,
                        'h' => 18,
                        'w' => 33,
                        'halign' => 'C',
                    ),
                ),
            ),

            'l18' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 345,
                        'y' => 141,
                        'h' => 18,
                        'w' => 71,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 416,
                        'y' => 141,
                        'h' => 18,
                        'w' => 33,
                        'halign' => 'C',
                    ),
                ),
            ),

            'l20' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 470,
                        'y' => 141,
                        'h' => 18,
                        'w' => 71,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 541,
                        'y' => 141,
                        'h' => 18,
                        'w' => 33,
                        'halign' => 'C',
                    ),
                ),
            ),

            'l24' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 345,
                        'y' => 185,
                        'h' => 18,
                        'w' => 71,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 416,
                        'y' => 185,
                        'h' => 18,
                        'w' => 33,
                        'halign' => 'C',
                    ),
                ),
            ),

            'l48' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 470,
                        'y' => 185,
                        'h' => 18,
                        'w' => 71,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 541,
                        'y' => 185,
                        'h' => 18,
                        'w' => 33,
                        'halign' => 'C',
                    ),
                ),
            ),

            'other_box_0_code' => array(
                'coordinates' => array(
                    'x' => 330,
                    'y' => 257,
                    'h' => 16,
                    'w' => 22,
                    'halign' => 'C',
                ),
            ),
            'other_box_0' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 355,
                        'y' => 257,
                        'h' => 16,
                        'w' => 62,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 417,
                        'y' => 257,
                        'h' => 16,
                        'w' => 32,
                        'halign' => 'C',
                    ),
                ),
            ),
            'other_box_1_code' => array(
                'coordinates' => array(
                    'x' => 463,
                    'y' => 257,
                    'h' => 16,
                    'w' => 22,
                    'halign' => 'C',
                ),
            ),
            'other_box_1' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 485,
                        'y' => 257,
                        'h' => 16,
                        'w' => 62,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 547,
                        'y' => 257,
                        'h' => 16,
                        'w' => 32,
                        'halign' => 'C',
                    ),
                ),
            ),
            'other_box_2_code' => array(
                'coordinates' => array(
                    'x' => 330,
                    'y' => 291,
                    'h' => 16,
                    'w' => 22,
                    'halign' => 'C',
                ),
            ),
            'other_box_2' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 355,
                        'y' => 291,
                        'h' => 16,
                        'w' => 62,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 417,
                        'y' => 291,
                        'h' => 16,
                        'w' => 32,
                        'halign' => 'C',
                    ),
                ),
            ),
            'other_box_3_code' => array(
                'coordinates' => array(
                    'x' => 463,
                    'y' => 291,
                    'h' => 16,
                    'w' => 22,
                    'halign' => 'C',
                ),
            ),
            'other_box_3' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 485,
                        'y' => 291,
                        'h' => 16,
                        'w' => 62,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 547,
                        'y' => 291,
                        'h' => 16,
                        'w' => 32,
                        'halign' => 'C',
                    ),
                ),
            ),
            'other_box_4_code' => array(
                'coordinates' => array(
                    'x' => 330,
                    'y' => 325,
                    'h' => 16,
                    'w' => 22,
                    'halign' => 'C',
                ),
            ),
            'other_box_4' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 355,
                        'y' => 325,
                        'h' => 16,
                        'w' => 62,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 417,
                        'y' => 325,
                        'h' => 16,
                        'w' => 32,
                        'halign' => 'C',
                    ),
                ),
            ),
            'other_box_5_code' => array(
                'coordinates' => array(
                    'x' => 463,
                    'y' => 325,
                    'h' => 16,
                    'w' => 22,
                    'halign' => 'C',
                ),
            ),
            'other_box_5' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 485,
                        'y' => 325,
                        'h' => 16,
                        'w' => 62,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 547,
                        'y' => 325,
                        'h' => 16,
                        'w' => 32,
                        'halign' => 'C',
                    ),
                ),
            ),
            'other_box_6_code' => array(
                'coordinates' => array(
                    'x' => 330,
                    'y' => 360,
                    'h' => 16,
                    'w' => 22,
                    'halign' => 'C',
                ),
            ),
            'other_box_6' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 355,
                        'y' => 360,
                        'h' => 16,
                        'w' => 62,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 417,
                        'y' => 360,
                        'h' => 16,
                        'w' => 32,
                        'halign' => 'C',
                    ),
                ),
            ),
            'other_box_7_code' => array(
                'coordinates' => array(
                    'x' => 463,
                    'y' => 360,
                    'h' => 16,
                    'w' => 22,
                    'halign' => 'C',
                ),
            ),
            'other_box_7' => array(
                'function' => 'drawSplitDecimalFloat',
                'coordinates' => array(
                    array(
                        'x' => 485,
                        'y' => 360,
                        'h' => 16,
                        'w' => 62,
                        'halign' => 'R',
                    ),
                    array(
                        'x' => 547,
                        'y' => 360,
                        'h' => 16,
                        'w' => 32,
                        'halign' => 'C',
                    ),
                ),
            ),
        );

        if (isset($template_schema[$name])) {
            return $name;
        } else {
            return $template_schema;
        }
    }

    public function getShowInstructionPage()
    {
        if (isset($this->show_instruction_page)) {
            return $this->show_instruction_page;
        }

        return false;
    }
}
