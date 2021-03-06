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
 * @package Modules\Message
 */
class MessageFactory extends Factory
{
    protected $table = 'message';
    protected $pk_sequence_name = 'message_id_seq'; //PK Sequence name
    protected $obj_handler = null;

    public function _getFactoryOptions($name, $parent = null)
    {
        $retval = null;
        switch ($name) {
            case 'type':
                $retval = array(
                    5 => 'email',
                    //10 => 'default_schedule',
                    //20 => 'schedule_amendment',
                    //30 => 'shift_amendment',
                    40 => 'authorization',
                    50 => 'request',
                    60 => 'job',
                    70 => 'job_item',
                    80 => 'client',
                    90 => 'timesheet',
                    100 => 'user' //For notes assigned to users?
                );
                break;
            case 'object_name':
                $retval = array(
                    5 => TTi18n::gettext('Email'), //Email from user to another
                    10 => TTi18n::gettext('Recurring Schedule'),
                    20 => TTi18n::gettext('Schedule Amendment'),
                    30 => TTi18n::gettext('Shift Amendment'),
                    40 => TTi18n::gettext('Authorization'),
                    50 => TTi18n::gettext('Request'),
                    60 => TTi18n::gettext('Job'),
                    70 => TTi18n::gettext('Task'),
                    80 => TTi18n::gettext('Client'),
                    90 => TTi18n::gettext('TimeSheet'),
                    100 => TTi18n::gettext('Employee') //For notes assigned to users?
                );
                break;

            case 'folder':
                $retval = array(
                    10 => TTi18n::gettext('Inbox'),
                    20 => TTi18n::gettext('Sent')
                );
                break;
            case 'status':
                $retval = array(
                    10 => TTi18n::gettext('UNREAD'),
                    20 => TTi18n::gettext('READ')
                );
                break;
            case 'priority':
                $retval = array(
                    10 => TTi18n::gettext('LOW'),
                    50 => TTi18n::gettext('NORMAL'),
                    100 => TTi18n::gettext('HIGH'),
                    110 => TTi18n::gettext('URGENT')
                );
                break;

        }

        return $retval;
    }

    public function setParent($id)
    {
        $id = trim($id);

        if (empty($id)) {
            $id = 0;
        }

        $mlf = TTnew('MessageListFactory');

        if ($id == 0
            or $this->Validator->isResultSetWithRows('parent',
                $mlf->getByID($id),
                TTi18n::gettext('Parent is invalid')
            )
        ) {
            $this->data['parent_id'] = $id;

            return true;
        }

        return false;
    }

    public function setObjectType($type)
    {
        $type = trim($type);

        if ($this->Validator->inArrayKey('object_type',
            $type,
            TTi18n::gettext('Object Type is invalid'),
            $this->getOptions('type'))
        ) {
            $this->data['object_type_id'] = $type;

            return true;
        }

        return false;
    }

    public function setObject($id)
    {
        $id = trim($id);

        if ($this->Validator->isResultSetWithRows('object',
            $this->getObjectHandler()->getByID($id),
            TTi18n::gettext('Object ID is invalid')
        )
        ) {
            $this->data['object_id'] = $id;

            return true;
        }

        return false;
    }

    public function getObjectHandler()
    {
        if (is_object($this->obj_handler)) {
            return $this->obj_handler;
        } else {
            switch ($this->getObjectType()) {
                case 5:
                case 100:
                    $this->obj_handler = TTnew('UserListFactory');
                    break;
                case 40:
                    $this->obj_handler = TTnew('AuthorizationListFactory');
                    break;
                case 50:
                    $this->obj_handler = TTnew('RequestListFactory');
                    break;
                case 90:
                    $this->obj_handler = TTnew('PayPeriodTimeSheetVerifyListFactory');
                    break;
            }

            return $this->obj_handler;
        }
    }

    public function getObjectType()
    {
        if (isset($this->data['object_type_id'])) {
            return (int)$this->data['object_type_id'];
        }

        return false;
    }

    public function getPriority()
    {
        if (isset($this->data['priority_id'])) {
            return (int)$this->data['priority_id'];
        }

        return false;
    }

    public function setPriority($priority = null)
    {
        $priority = trim($priority);

        if (empty($priority)) {
            $priority = 50;
        }

        if ($this->Validator->inArrayKey('priority',
            $priority,
            TTi18n::gettext('Invalid Priority'),
            $this->getOptions('priority'))
        ) {
            $this->data['priority_id'] = $priority;

            return true;
        }

        return false;
    }

    public function setStatus($status)
    {
        $status = trim($status);

        if ($this->Validator->inArrayKey('status',
            $status,
            TTi18n::gettext('Incorrect Status'),
            $this->getOptions('status'))
        ) {
            $this->setStatusDate();

            $this->data['status_id'] = $status;

            return true;
        }

        return false;
    }

    public function setStatusDate($epoch = null)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == null) {
            $epoch = TTDate::getTime();
        }

        if ($this->Validator->isDate('status_date',
            $epoch,
            TTi18n::gettext('Incorrect Date'))
        ) {
            $this->data['status_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function getStatusDate()
    {
        if (isset($this->data['status_date'])) {
            return $this->data['status_date'];
        }

        return false;
    }

    public function setSubject($text)
    {
        $text = trim($text);

        if (strlen($text) == 0
            or
            $this->Validator->isLength('subject',
                $text,
                TTi18n::gettext('Invalid Subject length'),
                2,
                100)
        ) {
            $this->data['subject'] = $text;

            return true;
        }

        return false;
    }

    public function getBody()
    {
        if (isset($this->data['body'])) {
            return $this->data['body'];
        }

        return false;
    }

    public function setBody($text)
    {
        $text = trim($text);

        if ($this->Validator->isLength('body',
            $text,
            TTi18n::gettext('Invalid Body length'),
            2, //Allow the word: "ok", or "done" to at least be a response.
            1024)
        ) {
            $this->data['body'] = $text;

            return true;
        }

        return false;
    }

    public function isAck()
    {
        if ($this->getRequireAck() == true and $this->getAckDate() == '') {
            return false;
        }

        return true;
    }

    public function getRequireAck()
    {
        return $this->fromBool($this->data['require_ack']);
    }

    public function getAckDate()
    {
        if (isset($this->data['ack_date'])) {
            return $this->data['ack_date'];
        }

        return false;
    }

    public function setRequireAck($bool)
    {
        $this->data['require_ack'] = $this->toBool($bool);

        return true;
    }

    public function setAck($bool)
    {
        $this->data['ack'] = $this->toBool($bool);

        if ($this->getAck() == true) {
            $this->setAckDate();
            $this->setAckBy();
        }

        return true;
    }

    public function getAck()
    {
        return $this->fromBool($this->data['ack']);
    }

    public function setAckDate($epoch = null)
    {
        $epoch = (!is_int($epoch)) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

        if ($epoch == null) {
            $epoch = TTDate::getTime();
        }

        if ($this->Validator->isDate('ack_date',
            $epoch,
            TTi18n::gettext('Invalid Acknowledge Date'))
        ) {
            $this->data['ack_date'] = $epoch;

            return true;
        }

        return false;
    }

    public function setAckBy($id = null)
    {
        $id = trim($id);

        if (empty($id)) {
            global $current_user;

            if (is_object($current_user)) {
                $id = $current_user->getID();
            } else {
                return false;
            }
        }

        $ulf = TTnew('UserListFactory');

        if ($this->Validator->isResultSetWithRows('ack_by',
            $ulf->getByID($id),
            TTi18n::gettext('Incorrect User')
        )
        ) {
            $this->data['ack_by'] = $id;

            return true;
        }

        return false;
    }

    public function getAckBy()
    {
        if (isset($this->data['ack_by'])) {
            return $this->data['ack_by'];
        }

        return false;
    }

    public function postSave()
    {
        //Only email message notifications when they are not deleted and UNREAD still. Other it may email when a message is marked as read as well.
        //Don't email messages when they are being deleted.
        if ($this->getDeleted() == false and $this->getStatus() == 10) {
            $this->emailMessage();
        }

        if ($this->getStatus() == 20) {
            global $current_user;

            $this->removeCache($current_user->getId());
        }

        return true;
    }

    public function getStatus()
    {
        if (isset($this->data['status_id'])) {
            return (int)$this->data['status_id'];
        }

        return false;
    }

    public function emailMessage()
    {
        Debug::Text('emailMessage: ', __FILE__, __LINE__, __METHOD__, 10);

        $email_to_arr = $this->getEmailMessageAddresses();
        if ($email_to_arr == false) {
            return false;
        }

        $from = $reply_to = '"' . APPLICATION_NAME . ' - ' . TTi18n::gettext('Message') . '" <' . Misc::getEmailLocalPart() . '@' . Misc::getEmailDomain() . '>';

        global $current_user;
        if (is_object($current_user) and $current_user->getWorkEmail() != '') {
            $reply_to = Misc::formatEmailAddress($current_user->getWorkEmail(), $current_user);
        }
        Debug::Text('From: ' . $from . ' Reply-To: ' . $reply_to, __FILE__, __LINE__, __METHOD__, 10);

        $to = array_shift($email_to_arr);
        Debug::Text('To: ' . $to, __FILE__, __LINE__, __METHOD__, 10);
        if (is_array($email_to_arr) and count($email_to_arr) > 0) {
            $bcc = implode(',', $email_to_arr);
        } else {
            $bcc = null;
        }

        $email_subject = TTi18n::gettext('New message waiting in') . ' ' . APPLICATION_NAME;
        $email_body = TTi18n::gettext('*DO NOT REPLY TO THIS EMAIL - PLEASE USE THE LINK BELOW INSTEAD*') . "\n\n";
        $email_body .= TTi18n::gettext('You have a new message waiting for you in') . ' ' . APPLICATION_NAME . "\n";
        if ($this->getSubject() != '') {
            $email_body .= TTi18n::gettext('Subject') . ': ' . $this->getSubject() . "\n";
        }

        $email_body .= TTi18n::gettext('Link') . ': <a href="' . Misc::getURLProtocol() . '://' . Misc::getHostName() . Environment::getDefaultInterfaceBaseURL() . '">' . APPLICATION_NAME . ' ' . TTi18n::getText('Login') . '</a>';

        //Define subject/body variables here.
        $search_arr = array(
            '#employee_first_name#',
            '#employee_last_name#',
        );

        $replace_arr = array(
            null,
            null,
        );

        $subject = str_replace($search_arr, $replace_arr, $email_subject);
        Debug::Text('Subject: ' . $subject, __FILE__, __LINE__, __METHOD__, 10);

        $headers = array(
            'From' => $from,
            'Subject' => $subject,
            'Bcc' => $bcc,
            //Reply-To/Return-Path are handled in TTMail.
        );

        $body = '<pre>' . str_replace($search_arr, $replace_arr, $email_body) . '</pre>';
        Debug::Text('Body: ' . $body, __FILE__, __LINE__, __METHOD__, 10);

        $mail = new TTMail();
        $mail->setTo($to);
        $mail->setHeaders($headers);

        @$mail->getMIMEObject()->setHTMLBody($body);

        $mail->setBody($mail->getMIMEObject()->get($mail->default_mime_config));
        $retval = $mail->Send();

        if ($retval == true) {
            TTLog::addEntry($this->getId(), 500, TTi18n::getText('Email Message to') . ': ' . $to . ' Bcc: ' . $headers['Bcc'], null, $this->getTable());
            return true;
        }

        return true; //Always return true
    }

    public function getEmailMessageAddresses()
    {
        $olf = $this->getObjectHandler();
        if (is_object($olf)) {
            $user_ids = array();
            $olf->getById($this->getObject());
            if ($olf->getRecordCount() > 0) {
                $obj = $olf->getCurrent();

                switch ($this->getObjectType()) {
                    case 5:
                    case 100:
                        Debug::Text('Email Object Type... Parent ID: ' . $this->getParent(), __FILE__, __LINE__, __METHOD__, 10);
                        if ($this->getParent() == 0) {
                            $user_ids[] = $obj->getId();
                        } else {
                            $mlf = TTnew('MessageListFactory');
                            $mlf->getById($this->getParent());
                            if ($mlf->getRecordCount() > 0) {
                                $m_obj = $mlf->getCurrent();

                                $user_ids[] = $m_obj->getCreatedBy();
                            }
                            Debug::Text('cEmail Object Type... Parent ID: ' . $this->getParent(), __FILE__, __LINE__, __METHOD__, 10);
                        }
                        break;
                    case 40:
                        $user_ids[] = $obj->getId();
                        break;
                    case 50: //Request
                        //Get all users who have contributed to the thread.
                        $mlf = TTnew('MessageListFactory');
                        $mlf->getMessagesInThreadById($this->getId());
                        Debug::Text(' Messages In Thread: ' . $mlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                        if ($mlf->getRecordCount() > 0) {
                            foreach ($mlf as $m_obj) {
                                $user_ids[] = $m_obj->getCreatedBy();
                            }
                        }
                        unset($mlf, $m_obj);
                        //Debug::Arr($user_ids, 'User IDs in Thread: ', __FILE__, __LINE__, __METHOD__, 10);

                        //Only alert direct supervisor to request at this point. Because we need to take into account
                        //if the request was authorized or not to determine if we should email the next higher level in the hierarchy.
                        if ($this->getParent() == 0) {
                            //Get direct parent in hierarchy.
                            $u_obj = $obj->getUserObject();

                            $hlf = TTnew('HierarchyListFactory');
                            $user_ids[] = $hlf->getHierarchyParentByCompanyIdAndUserIdAndObjectTypeID($u_obj->getCompany(), $u_obj->getId(), $this->getObjectType(), true, false);
                            unset($hlf);
                        }

                        global $current_user;
                        if (isset($current_user) and is_object($current_user) and isset($user_ids) and is_array($user_ids)) {
                            $user_ids = array_unique($user_ids);
                            $current_user_key = array_search($current_user->getId(), $user_ids);
                            Debug::Text(' Current User Key: ' . $current_user_key, __FILE__, __LINE__, __METHOD__, 10);
                            if ($current_user_key !== false) {
                                Debug::Text(' Removing Current User From Recipient List...' . $current_user->getId(), __FILE__, __LINE__, __METHOD__, 10);
                                unset($user_ids[$current_user_key]);
                            }
                        } else {
                            Debug::Text(' Current User Object not available...', __FILE__, __LINE__, __METHOD__, 10);
                        }
                        unset($current_user, $current_user_key);

                        break;
                    case 90:
                        $user_ids[] = $obj->getUser();
                        break;
                }
            }

            if (empty($user_ids) == false) {
                //Get user preferences and determine if they accept email notifications.
                Debug::Arr($user_ids, 'Recipient User Ids: ', __FILE__, __LINE__, __METHOD__, 10);

                $uplf = TTnew('UserPreferenceListFactory');
                $uplf->getByUserId($user_ids);
                if ($uplf->getRecordCount() > 0) {
                    $retarr = array();
                    foreach ($uplf as $up_obj) {
                        if ($up_obj->getEnableEmailNotificationMessage() == true and $up_obj->getUserObject()->getStatus() == 10) {
                            if ($up_obj->getUserObject()->getWorkEmail() != '') {
                                $retarr[] = Misc::formatEmailAddress($up_obj->getUserObject()->getWorkEmail(), $up_obj->getUserObject());
                            }

                            if ($up_obj->getEnableEmailNotificationHome() and $up_obj->getUserObject()->getHomeEmail() != '') {
                                $retarr[] = Misc::formatEmailAddress($up_obj->getUserObject()->getHomeEmail(), $up_obj->getUserObject());
                            }
                        }
                    }

                    if (empty($retarr) == false) {
                        Debug::Arr($retarr, 'Recipient Email Addresses: ', __FILE__, __LINE__, __METHOD__, 10);
                        return $retarr;
                    }
                }
            }
        }

        return false;
    }

    public function getObject()
    {
        if (isset($this->data['object_id'])) {
            return (int)$this->data['object_id'];
        }

        return false;
    }

    public function getParent()
    {
        if (isset($this->data['parent_id'])) {
            return (int)$this->data['parent_id'];
        }

        return false;
    }

    public function getSubject()
    {
        if (isset($this->data['subject'])) {
            return $this->data['subject'];
        }

        return false;
    }
}
