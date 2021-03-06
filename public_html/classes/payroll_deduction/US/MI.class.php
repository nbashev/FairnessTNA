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


/**
 * @package PayrollDeduction\US
 */
class PayrollDeduction_US_MI extends PayrollDeduction_US
{
    public $state_options = array(
        20140101 => array( //01-Jan-14
            'rate' => 4.25,
            'allowance' => 4000
        ),
        20130101 => array( //01-Jan-13
            'rate' => 4.25,
            'allowance' => 3950
        ),
        20110101 => array( //01-Jan-11
            'rate' => 4.35,
            'allowance' => 3700
        ),
        20090101 => array( //01-Jan-09
            'rate' => 4.35,
            'allowance' => 3600
        ),
        20071001 => array( //01-Oct-07
            'rate' => 4.35,
            'allowance' => 3400
        ),
        20070101 => array(
            'rate' => 3.9,
            'allowance' => 3400
        ),
        20060101 => array(
            'rate' => 3.9,
            'allowance' => 3300
        )
    );

    public function getStateTaxPayable()
    {
        $annual_income = $this->getStateAnnualTaxableIncome();

        $retval = 0;

        if ($annual_income > 0) {
            $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
            if ($retarr == false) {
                return false;
            }

            $rate = bcdiv($retarr['rate'], 100);
            $retval = bcmul($annual_income, $rate);
        }

        if ($retval < 0) {
            $retval = 0;
        }

        Debug::text('State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }

    public function getStateAnnualTaxableIncome()
    {
        $annual_income = $this->getAnnualTaxableIncome();

        $allowance = $this->getStateAllowanceAmount();

        $income = bcsub($annual_income, $allowance);

        Debug::text('State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10);

        return $income;
    }

    public function getStateAllowanceAmount()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $allowance = $retarr['allowance'];

        $retval = bcmul($this->getStateAllowance(), $allowance);

        Debug::text('State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }
}
