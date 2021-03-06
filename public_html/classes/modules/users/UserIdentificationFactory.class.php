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
 * @package Modules\Users
 */
class UserIdentificationFactory extends Factory
{
    public $user_obj = null;
        protected $table = 'user_identification'; //PK Sequence name
protected $pk_sequence_name = 'user_identification_id_seq';

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                $retval = array(
                    1 => TTi18n::gettext('Employee Sequence'), //Company specific employee sequence number, primarily for timeclocks. Should be less than 65535.
                    5 => TTi18n::gettext('Password History'), //Web interface password history
                    10 => TTi18n::gettext('iButton'),
                    20 => TTi18n::gettext('USB Fingerprint'),
                    //25	=> TTi18n::gettext('LibFingerPrint'),
                    30 => TTi18n::gettext('Barcode'), //For barcode readers and USB proximity card readers.
                    35 => TTi18n::gettext('QRcode'), //For cameras to read QR code badges.
                    40 => TTi18n::gettext('Proximity Card'), //Mainly for proximity cards on timeclocks.
                    70 => TTi18n::gettext('Face Image'), //Raw image of cropped face in as high of quality as possible.
                    75 => TTi18n::gettext('Facial Recognition'), //Luxand v5 SDK templates.
                    100 => TTi18n::gettext('TimeClock FingerPrint (v9)'), //TimeClocks v9 algo
                    101 => TTi18n::gettext('TimeClock FingerPrint (v10)'), //TimeClocks v10 algo
                );
                break;

        }

        return $retval;
    }

    public function getUserObject()
    {
        if (is_object($this->user_obj)) {
            return $this->user_obj;
        } else {
            $ulf = TTnew('UserListFactory');
            $this->user_obj = $ulf->getById($this->getUser())->getCurrent();

            return $this->user_obj;
        }
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function setUser($id)
    {
        $id = trim($id);

        $ulf = TTnew('UserListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('user',
                $ulf->getByID($id),
                TTi18n::gettext('Invalid User')
            )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function setType($type)
    {
        $type = trim($type);

        //This needs to be stay as FairnessTNA Client application still uses names rather than IDs.
        $key = Option::getByValue($type, $this->getOptions('type'));
        if ($key !== false) {
            $type = $key;
        }

        if ($this->Validator->inArrayKey('type',
            $type,
            TTi18n::gettext('Incorrect Type'),
            $this->getOptions('type'))
        ) {
            $this->data['type_id'] = $type;

            return true;
        }

        return false;
    }

    public function setValue($value)
    {
        $value = trim($value);

        if (
        $this->Validator->isLength('value',
            $value,
            TTi18n::gettext('Value is too short or too long'),
            1,
            256000) //Need relatively large face images.
        ) {
            $this->data['value'] = $value;

            return true;
        }

        return false;
    }

    /*
        For fingerprints,
            10 = Fingerprint 1	Pass 0.
            11 = Fingerprint 1	Pass 1.
            12 = Fingerprint 1	Pass 2.

            20 = Fingerprint 2	Pass 0.
            21 = Fingerprint 2	Pass 1.
            ...
    */

    public function getExtraValue()
    {
        if (isset($this->data['extra_value'])) {
            return $this->data['extra_value'];
        }

        return false;
    }

    public function setExtraValue($value)
    {
        $value = trim($value);

        if (
        $this->Validator->isLength('extra_value',
            $value,
            TTi18n::gettext('Extra Value is too long'),
            1,
            256000)
        ) {
            $this->data['extra_value'] = $value;

            return true;
        }

        return false;
    }

    public function Validate($ignore_warning = true)
    {
        if ($this->getValue() == false) {
            $this->Validator->isTRUE('value',
                false,
                TTi18n::gettext('Value is not defined'));
        } else {
            $this->Validator->isTrue('value',
                $this->isUniqueValue($this->getUser(), $this->getType(), $this->getValue()),
                TTi18n::gettext('Value is already in use, please enter a different one'));
        }
        return true;
    }

    public function getValue()
    {
        if (isset($this->data['value'])) {
            return $this->data['value'];
        }

        return false;
    }

    public function isUniqueValue($user_id, $type_id, $value)
    {
        $ph = array(
            'user_id' => (int)$user_id,
            'type_id' => (int)$type_id,
            'value' => (string)$value,
        );

        $uf = TTnew('UserFactory');

        $query = 'select a.id
					from ' . $this->getTable() . ' as a,
						' . $uf->getTable() . ' as b
					where a.user_id = b.id
						AND b.company_id = ( select z.company_id from ' . $uf->getTable() . ' as z where z.id = ? and z.deleted = 0 )
						AND a.type_id = ?
						AND a.value = ?
						AND ( a.deleted = 0 AND b.deleted = 0 )';
        $id = $this->db->GetOne($query, $ph);
        //Debug::Arr($id, 'Unique Value: '. $value, __FILE__, __LINE__, __METHOD__, 10);

        if ($id === false) {
            return true;
        } else {
            if ($id == $this->getId()) {
                return true;
            }
        }

        return false;
    }

    public function getType()
    {
        if (isset($this->data['type_id'])) {
            return (int)$this->data['type_id'];
        }

        return false;
    }

    public function preSave()
    {
        if ($this->getNumber() == '') {
            $this->setNumber(0);
        }

        return true;
    }

    public function getNumber()
    {
        if (isset($this->data['number'])) {
            return $this->data['number'];
        }

        return false;
    }

    public function setNumber($value)
    {
        $value = trim($value);

        //Pull out only digits
        $value = $this->Validator->stripNonNumeric($value);

        if ($this->Validator->isFloat('number',
            $value,
            TTi18n::gettext('Incorrect Number'))
        ) {
            $this->data['number'] = $value;

            return true;
        }

        return false;
    }

    public function postSave()
    {
        $this->removeCache($this->getId());

        return true;
    }

    public function addLog($log_action)
    {
        //Don't do detail logging for this, as it will store entire figerprints in the log table.
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Employee Identification - Employee') . ': ' . UserListFactory::getFullNameById($this->getUser()) . ' ' . TTi18n::getText('Type') . ': ' . Option::getByKey($this->getType(), $this->getOptions('type')), null, $this->getTable());
    }
}
