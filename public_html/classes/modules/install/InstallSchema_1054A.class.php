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
 * @package Modules\Install
 */
class InstallSchema_1054A extends InstallSchema_Base
{
    public function preInstall()
    {
        Debug::text('preInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        return true;
    }

    public function postInstall()
    {
        Debug::text('postInstall: ' . $this->getVersion(), __FILE__, __LINE__, __METHOD__, 9);

        //Update Social Security rates. Can't do this in the 1020T as that is executed before 1031A, which will cause an error due to missing columns.
        $clf = TTnew('CompanyListFactory');
        $clf->getAll();
        if ($clf->getRecordCount() > 0) {
            foreach ($clf as $c_obj) {
                Debug::text('Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                if ($c_obj->getStatus() != 30 and $c_obj->getCountry() == 'US') {
                    //Get PayStub Link accounts
                    $pseallf = TTnew('PayStubEntryAccountLinkListFactory');
                    $pseallf->getByCompanyId($c_obj->getID());
                    if ($pseallf->getRecordCount() > 0) {
                        $psea_obj = $pseallf->getCurrent();
                    } else {

                        // @codingStandardsIgnoreStart
                        Debug::text('Failed getting PayStubEntryLink for Company ID: ' . $company_id, __FILE__, __LINE__, __METHOD__, 10);
                        //leaving debugs alone.
                        // @codingStandardsIgnoreEnd
                        continue;
                    }

                    $include_pay_stub_accounts = false;
                    $exclude_pay_stub_accounts = false;

                    $cdlf = TTnew('CompanyDeductionListFactory');
                    $cdlf->getByCompanyIdAndName($c_obj->getID(), 'Medicare - Employee');
                    if ($cdlf->getRecordCount() == 1) {
                        $cd_obj = $cdlf->getCurrent();
                        Debug::text('Found Medicare Employee Tax / Deduction, ID: ' . $c_obj->getID(), __FILE__, __LINE__, __METHOD__, 9);
                        if ($cd_obj->getCalculation() == 10) {
                            //Copy filing status from Federal Income Tax.
                            $cdlf = TTnew('CompanyDeductionListFactory');
                            $cdlf->getByCompanyIdAndName($c_obj->getID(), 'Federal Income Tax');
                            if ($cdlf->getRecordCount() == 1) {
                                $tmp_cd_obj = $cdlf->getCurrent();
                            } else {
                                $cdlf = TTnew('CompanyDeductionListFactory');
                                $cdlf->getByCompanyIdAndName($c_obj->getID(), 'US - Federal Income Tax');
                                if ($cdlf->getRecordCount() == 1) {
                                    $tmp_cd_obj = $cdlf->getCurrent();
                                }
                            }

                            $filing_status = array();
                            if (is_object($tmp_cd_obj)) {
                                $udlf = TTnew('UserDeductionListFactory');
                                $udlf->getByCompanyIdAndCompanyDeductionId($c_obj->getID(), $tmp_cd_obj->getID());
                                if ($udlf->getRecordCount() > 0) {
                                    foreach ($udlf as $ud_obj) {
                                        $filing_status[$ud_obj->getUser()] = $ud_obj->getUserValue1();
                                    }
                                }
                            }
                            unset($udlf, $ud_obj);

                            $cd_obj->setCalculation(82);
                            $cd_obj->setUserValue1(10); //Single
                            $cd_obj->setUserValue2('');
                            Debug::text('Medicare Employee Tax / Deduction Matches... Adjusting specific formula Percent...', __FILE__, __LINE__, __METHOD__, 9);
                            $include_pay_stub_accounts = $cd_obj->getIncludePayStubEntryAccount();
                            $exclude_pay_stub_accounts = $cd_obj->getExcludePayStubEntryAccount();

                            if ($cd_obj->isValid()) {
                                //Delete existing UserDeduction rows, so we can create new ones.
                                $udlf = TTnew('UserDeductionListFactory');
                                $udlf->getByCompanyIdAndCompanyDeductionId($c_obj->getID(), $cd_obj->getID());
                                if ($udlf->getRecordCount() > 0) {
                                    Debug::text('Deleting ' . $udlf->getRecordCount() . ' User Deductions assigned to Company Deduction ID: ' . $cd_obj->getID(), __FILE__, __LINE__, __METHOD__, 9);
                                    foreach ($udlf as $ud_obj) {
                                        $ud_obj->setUserValue1((isset($filing_status[$ud_obj->getUser()])) ? $filing_status[$ud_obj->getUser()] : 10); //Default to single.
                                        $ud_obj->setUserValue2('');

                                        $ud_obj->ignore_column_list = true; //Prevents SQL errors due to new columns being added later on.
                                        if ($ud_obj->isValid()) {
                                            $ud_obj->Save();
                                        }
                                    }
                                }
                                unset($udlf, $ud_obj);

                                $cd_obj->ignore_column_list = true; //Prevents SQL errors due to new columns being added later on.
                                if ($cd_obj->isValid()) {
                                    $cd_obj->Save();
                                }
                            }
                        }
                    } else {
                        Debug::text('Failed to find Medicare Employee Tax / Deduction for Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                    }
                    unset($cdlf, $cd_obj);

                    $cdlf = TTnew('CompanyDeductionListFactory');
                    $cdlf->getByCompanyIdAndName($c_obj->getID(), 'Medicare - Employer');
                    if ($cdlf->getRecordCount() == 1) {
                        $cd_obj = $cdlf->getCurrent();
                        Debug::text('Found Medicare Employer Tax / Deduction, ID: ' . $c_obj->getID(), __FILE__, __LINE__, __METHOD__, 9);
                        if ($cd_obj->getCalculation() == 10) {
                            $cd_obj->setCalculation(83);
                            $cd_obj->setUserValue1('');
                            $cd_obj->setUserValue2('');
                            Debug::text('Medicare Employer Tax / Deduction Matches... Adjusting specific formula Percent...', __FILE__, __LINE__, __METHOD__, 9);
                            $include_pay_stub_accounts = $cd_obj->getIncludePayStubEntryAccount();
                            $exclude_pay_stub_accounts = $cd_obj->getExcludePayStubEntryAccount();

                            $cd_obj->ignore_column_list = true; //Prevents SQL errors due to new columns being added later on.
                            if ($cd_obj->isValid()) {
                                $cd_obj->Save();
                            }
                        }
                    } else {
                        Debug::text('Failed to find Medicare Employer Tax / Deduction for Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                    }
                    unset($cdlf, $cd_obj);

                    $cdlf = TTnew('CompanyDeductionListFactory');
                    $cdlf->getByCompanyIdAndName($c_obj->getID(), 'Social Security - Employee');
                    if ($cdlf->getRecordCount() == 1) {
                        $cd_obj = $cdlf->getCurrent();
                        Debug::text('Found SS Employee Tax / Deduction, ID: ' . $c_obj->getID(), __FILE__, __LINE__, __METHOD__, 9);
                        if ($cd_obj->getCalculation() == 15) {
                            $cd_obj->setCalculation(84);
                            $cd_obj->setUserValue1('');
                            $cd_obj->setUserValue2('');
                            Debug::text('SS Employee Tax / Deduction Matches... Adjusting specific formula Percent...', __FILE__, __LINE__, __METHOD__, 9);
                            $include_pay_stub_accounts = $cd_obj->getIncludePayStubEntryAccount();
                            $exclude_pay_stub_accounts = $cd_obj->getExcludePayStubEntryAccount();

                            $cd_obj->ignore_column_list = true; //Prevents SQL errors due to new columns being added later on.
                            if ($cd_obj->isValid()) {
                                $cd_obj->Save();
                            }
                        }
                    } else {
                        Debug::text('Failed to find SS Employee Tax / Deduction for Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                    }
                    unset($cdlf, $cd_obj);

                    $cdlf = TTnew('CompanyDeductionListFactory');
                    $cdlf->getByCompanyIdAndName($c_obj->getID(), 'Social Security - Employer');
                    if ($cdlf->getRecordCount() == 1) {
                        $cd_obj = $cdlf->getCurrent();

                        Debug::text('Found SS Employer Tax / Deduction, ID: ' . $c_obj->getID() . ' Percent: ' . $cd_obj->getUserValue1() . ' Wage Base: ' . $cd_obj->getUserValue2(), __FILE__, __LINE__, __METHOD__, 9);

                        if (($cd_obj->getCalculation() == 10) or ($cd_obj->getCalculation() == 15)) {
                            $cd_obj->setCalculation(85);
                            $cd_obj->setUserValue1('');
                            $cd_obj->setUserValue2('');
                            Debug::text('SS Employer Tax / Deduction Matches... Adjusting specific formula Percent...', __FILE__, __LINE__, __METHOD__, 9);
                            if ($include_pay_stub_accounts !== false) {
                                Debug::text('Matching Include/Exclude accounts with SS Employee Entry...', __FILE__, __LINE__, __METHOD__, 9);
                                //Match include accounts with SS employee entry.
                                $cd_obj->setIncludePayStubEntryAccount($include_pay_stub_accounts);
                                $cd_obj->setExcludePayStubEntryAccount($exclude_pay_stub_accounts);
                            } else {
                                Debug::text('NOT Matching Include/Exclude accounts with SS Employee Entry...', __FILE__, __LINE__, __METHOD__, 9);
                                $cd_obj->setIncludePayStubEntryAccount(array($psea_obj->getTotalGross()));
                            }

                            $cd_obj->ignore_column_list = true; //Prevents SQL errors due to new columns being added later on.
                            if ($cd_obj->isValid()) {
                                $cd_obj->Save();
                            }
                        }
                    } else {
                        Debug::text('Failed to find SS Employer Tax / Deduction for Company: ' . $c_obj->getName(), __FILE__, __LINE__, __METHOD__, 9);
                    }
                }
            }
        }

        return true;
    }
}