<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
 *
 * Fairness is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * Fairness is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 ********************************************************************************/


/**
 * @package Modules\PayStub
 */
class PayStub extends PayStubFactory
{
    protected $tmp_data = null;

    public function childConstruct()
    {
        $this->StartTransaction();

        return true;
    }


    public function Done()
    {
        Debug::Arr($this->tmp_data, 'Pay Stub TMP Data: ', __FILE__, __LINE__, __METHOD__, 10);
        //Call pre-save() first, so calculates the totals.
        $this->setEnableCalcTotal(true);
        $this->preSave();

        if ($this->Validate()) {
            $this->CommitTransaction();
            //$this->FailTransaction();
            return true;
        }

        $this->FailTransaction(); //Fails Transaction
        $this->CommitTransaction(); //Rollback occurs here. This is important when looping over many employees that may have a pay stub that fails.

        return false;
    }
}