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
class PayrollDeduction_US_CO extends PayrollDeduction_US
{
    public $state_income_tax_rate_options = array(
        20170101 => array(
            10 => array(
                array('income' => 2300, 'rate' => 0, 'constant' => 0),
                array('income' => 2300, 'rate' => 4.63, 'constant' => 0),
            ),
            20 => array(
                array('income' => 8650, 'rate' => 0, 'constant' => 0),
                array('income' => 8650, 'rate' => 4.63, 'constant' => 0),
            ),
        ),
        20150101 => array(
            10 => array(
                array('income' => 2300, 'rate' => 0, 'constant' => 0),
                array('income' => 2300, 'rate' => 4.63, 'constant' => 0),
            ),
            20 => array(
                array('income' => 8600, 'rate' => 0, 'constant' => 0),
                array('income' => 8600, 'rate' => 4.63, 'constant' => 0),
            ),
        ),
        20130101 => array(
            10 => array(
                array('income' => 2200, 'rate' => 0, 'constant' => 0),
                array('income' => 2200, 'rate' => 4.63, 'constant' => 0),
            ),
            20 => array(
                array('income' => 8300, 'rate' => 0, 'constant' => 0),
                array('income' => 8300, 'rate' => 4.63, 'constant' => 0),
            ),
        ),
        20110101 => array(
            10 => array(
                array('income' => 2100, 'rate' => 0, 'constant' => 0),
                array('income' => 2100, 'rate' => 4.63, 'constant' => 0),
            ),
            20 => array(
                array('income' => 7900, 'rate' => 0, 'constant' => 0),
                array('income' => 7900, 'rate' => 4.63, 'constant' => 0),
            ),
        ),
        20090101 => array(
            10 => array(
                array('income' => 2050, 'rate' => 0, 'constant' => 0),
                array('income' => 2050, 'rate' => 4.63, 'constant' => 0),
            ),
            20 => array(
                array('income' => 7750, 'rate' => 0, 'constant' => 0),
                array('income' => 7750, 'rate' => 4.63, 'constant' => 0),
            ),
        ),
        20070101 => array(
            10 => array(
                array('income' => 1900, 'rate' => 0, 'constant' => 0),
                array('income' => 1900, 'rate' => 4.63, 'constant' => 0),
            ),
            20 => array(
                array('income' => 7200, 'rate' => 0, 'constant' => 0),
                array('income' => 7200, 'rate' => 4.63, 'constant' => 0),
            ),
        ),
        20060101 => array(
            10 => array(
                array('income' => 1850, 'rate' => 0, 'constant' => 0),
                array('income' => 1850, 'rate' => 4.63, 'constant' => 0),
            ),
            20 => array(
                array('income' => 7000, 'rate' => 0, 'constant' => 0),
                array('income' => 7000, 'rate' => 4.63, 'constant' => 0),
            ),
        ),
    );

    public $state_options = array(
        20170101 => array( //2017
            'allowance' => 4050,
        ),
        20150101 => array( //2015
            'allowance' => 4000,
        ),
        20130101 => array( //2013
            'allowance' => 3900,
        ),
        20110101 => array( //2011
            'allowance' => 3700,
        ),
        20090101 => array( //2009
            'allowance' => 3650,
        ),
        20070101 => array(
            'allowance' => 3400,
        ),
        20060101 => array(
            'allowance' => 3300,
        )
    );

    public function getStatePayPeriodDeductionRoundedValue($amount)
    {
        return $this->RoundNearestDollar($amount);
    }

    public function getStateTaxPayable()
    {
        $annual_income = $this->getStateAnnualTaxableIncome();

        $retval = 0;

        if ($annual_income > 0) {
            $rate = $this->getData()->getStateRate($annual_income);
            $state_constant = $this->getData()->getStateConstant($annual_income);
            $state_rate_income = $this->getData()->getStateRatePreviousIncome($annual_income);

            $retval = bcadd(bcmul(bcsub($annual_income, $state_rate_income), $rate), $state_constant);
            //$retval = bcadd( bcmul( $annual_income, $rate ), $state_constant );
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
        $state_allowance = $this->getStateAllowanceAmount();

        $income = bcsub($annual_income, $state_allowance);

        Debug::text('State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10);

        return $income;
    }

    public function getStateAllowanceAmount()
    {
        $retarr = $this->getDataFromRateArray($this->getDate(), $this->state_options);
        if ($retarr == false) {
            return false;
        }

        $allowance_arr = $retarr['allowance'];

        $retval = bcmul($this->getStateAllowance(), $allowance_arr);

        Debug::text('State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10);

        return $retval;
    }
}
